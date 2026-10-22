<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobCost;
use App\Models\Journal;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class LegacyJournalRepairService
{
    public const TYPE = 'job_legacy_hpp_repair';

    public function __construct(private JournalService $journals, private MasterDataService $master) {}

    public function inspect(Job $job): array
    {
        $costs = $job->costs()->get();
        $sourceJournals = Journal::with('entries')->whereNull('reversed_at')->whereNull('reversal_of_id')
            ->where(function ($q) use ($job, $costs) {
                $q->where(fn ($s) => $s->where('source_type', Job::class)->where('source_id', $job->id))
                    ->orWhere(fn ($s) => $s->where('source_type', JobCost::class)->whereIn('source_id', $costs->pluck('id')));
            })->get();
        $stale = $sourceJournals->filter(function ($journal) use ($costs, $sourceJournals) {
            if ($journal->source_type !== JobCost::class || $journal->type !== 'job_cost_post_invoice_hpp') {
                return false;
            }
            $cost = $costs->firstWhere('id', $journal->source_id);
            return $cost && ! $cost->paid_at && ! $sourceJournals->contains(fn ($j) => $j->source_type === JobCost::class && $j->source_id === $cost->id && $j->type === 'job_cost_payment');
        });

        $group = match (true) {
            in_array($job->service_type, ['imp_sea', 'imp_air'], true) => 'import',
            in_array($job->service_type, ['exp_sea', 'exp_air', 'sea', 'air'], true) => 'export',
            default => 'domestic',
        };
        $maps = $this->journals->mapped(['provision_wip', 'cogs_'.$group.'_unclosed', 'cogs_'.$group.'_closed']);
        $provisionId = $maps['provision_wip']->id;
        $unclosedId = $maps['cogs_'.$group.'_unclosed']->id;
        $closedId = $maps['cogs_'.$group.'_closed']->id;
        $balance = function ($accountId, $journals) {
            $value = Money::decimal(0);
            foreach ($journals as $journal) {
                foreach ($journal->entries as $entry) {
                    if ($entry->chart_of_account_id === $accountId) {
                        $value = $value->plus($entry->debit)->minus($entry->credit);
                    }
                }
            }
            return $value;
        };
        $remaining = $sourceJournals->whereNotIn('id', $stale->pluck('id'));
        $provision = $balance($provisionId, $remaining);
        $unclosed = $balance($unclosedId, $remaining);
        $closed = $balance($closedId, $remaining);
        $entries = [];
        $warnings = [];
        if ($job->status !== 'closed' && ($provision->isNegative() || $unclosed->isNegative())) {
            $warnings[] = 'Job belum Closed tetapi saldo Provisi/HPP negatif; perlu pemeriksaan manual di luar dua pola koreksi ini.';
        }
        if ($job->status === 'closed' && (! $provision->isZero() || ! $unclosed->isZero())) {
            $total = $costs->where('type', 'provision')->reduce(fn ($sum, $cost) => $sum->plus($cost->total_cost), Money::decimal(0));
            if ($costs->contains(fn ($cost) => $cost->status !== 'final') || ! $closed->isEqualTo($total) || ! $provision->plus($unclosed)->isZero()) {
                $warnings[] = 'Saldo tidak cocok dengan pola kesalahan closing yang telah diuji; perlu pemeriksaan manual.';
            } elseif ($sourceJournals->contains('type', self::TYPE)) {
                $warnings[] = 'Job sudah memiliki jurnal koreksi aktif; perlu pemeriksaan manual.';
            } else {
                $amount = (string) $provision->abs();
                $entries = [
                    ['account_id' => $unclosedId, 'description' => 'Koreksi HPP Unclosed lama · '.$job->number, 'debit' => $provision->isPositive() ? $amount : '0', 'credit' => $provision->isNegative() ? $amount : '0'],
                    ['account_id' => $provisionId, 'description' => 'Koreksi Provisi lama · '.$job->number, 'debit' => $provision->isNegative() ? $amount : '0', 'credit' => $provision->isPositive() ? $amount : '0'],
                ];
            }
        }

        return [
            'job' => $job->number,
            'reverse_ids' => $stale->pluck('id')->all(),
            'reverse_numbers' => $stale->pluck('number')->all(),
            'entries' => $entries,
            'warnings' => $warnings,
            'provision_before' => (string) $balance($provisionId, $sourceJournals),
            'hpp_unclosed_before' => (string) $balance($unclosedId, $sourceJournals),
        ];
    }

    public function repair(Job $job, User $actor, string $date): array
    {
        Gate::forUser($actor)->authorize('journals.manage');
        if (! $actor->hasRole(['finance-manager', 'super-admin', 'admin'])) {
            throw ValidationException::withMessages(['actor' => 'Koreksi jurnal lama harus dilakukan oleh Finance Manager atau Admin.']);
        }

        return DB::transaction(function () use ($job, $actor, $date) {
            $job = Job::lockForUpdate()->findOrFail($job->id);
            $job->costs()->lockForUpdate()->get();
            $plan = $this->inspect($job);
            if ($plan['warnings']) {
                throw ValidationException::withMessages(['journal' => implode(' ', $plan['warnings'])]);
            }
            $created = [];
            foreach ($plan['reverse_ids'] as $id) {
                $journal = Journal::findOrFail($id);
                $created[] = $this->journals->reverse($journal, [
                    'lock_version' => $journal->lock_version, 'reversal_date' => $date,
                    'reason' => 'Koreksi reklasifikasi HPP tertinggal setelah Unpaid · '.$job->number,
                ], $actor)->number;
            }
            if ($plan['entries']) {
                $latest = Journal::where('source_type', Job::class)->where('source_id', $job->id)->max('journal_date');
                if ($latest && $date < substr((string) $latest, 0, 10)) {
                    throw ValidationException::withMessages(['date' => 'Tanggal koreksi tidak boleh sebelum jurnal job terakhir.']);
                }
                $created[] = $this->journals->post(self::TYPE, Job::class, $job->id, $date, 'Koreksi jurnal lama · '.$job->number, $plan['entries'], $actor)->number;
            }
            if ($created) {
                $this->master->log($actor, 'job.legacy_journals_repaired', 'Koreksi jurnal lama '.$job->number, [
                    'module' => 'job', 'record_id' => $job->id, 'before' => $plan, 'journals' => $created,
                ]);
            }

            return ['plan' => $plan, 'created_journals' => $created];
        }, 3);
    }
}
