<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobCost;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Backed-up cleanup of canceled invoice revisions in trial data, not routine accounting. */
class TrialJournalHistoryService
{
    public function __construct(private JournalService $journals, private MasterDataService $master) {}

    public function compact(Job $job, User $actor, string $confirmation, bool $apply = false): array
    {
        Gate::forUser($actor)->authorize('journals.manage');
        $this->require($actor->hasRole(['super-admin', 'admin', 'finance-manager']), 'Akun harus Finance Manager/Admin.');
        $this->require($confirmation === 'TRIAL '.$job->number, 'Konfirmasi harus TRIAL diikuti nomor Job persis.');

        return DB::transaction(function () use ($job, $actor, $apply) {
            $job = Job::lockForUpdate()->findOrFail($job->id);
            $this->require($job->status === 'open', 'Hanya Job Open pada data trial yang boleh dibersihkan.');
            $invoice = $job->invoice()->lockForUpdate()->firstOrFail();
            $this->require($invoice->status === 'issued' && ! $invoice->payments()->exists()
                && Money::decimal($invoice->paid_amount)->isZero(), 'Invoice harus issued tanpa pembayaran customer.');
            $costs = $job->costs()->withTrashed()->lockForUpdate()->get();
            $this->require(! $costs->contains(fn ($cost) => $cost->status !== 'final'), 'Semua biaya terkait harus Final.');
            $roots = Journal::with('entries')->whereNull('reversal_of_id')
                ->where(function ($q) use ($job, $costs) {
                    $q->where(fn ($s) => $s->where('source_type', Job::class)->where('source_id', $job->id))
                        ->orWhere(fn ($s) => $s->where('source_type', JobCost::class)->whereIn('source_id', $costs->pluck('id')));
                })->orderBy('id')->lockForUpdate()->get();
            $reversals = Journal::with('entries')->whereIn('reversal_of_id', $roots->pluck('id'))->lockForUpdate()->get()->keyBy('reversal_of_id');
            // The first invoice is an accounting anchor used to identify initial
            // versus subsequent costs. Keep it AND its reversal, even if canceled.
            $anchor = $roots->first(fn ($j) => $j->source_type === Job::class && $j->type === 'job_invoice');
            $this->require($anchor !== null, 'Jurnal invoice pertama tidak ditemukan; perlu rekonstruksi manual.');
            $archive = collect();
            foreach ($roots as $original) {
                $reversal = $reversals->get($original->id);
                $this->require((bool) $original->reversed_at === (bool) $reversal, 'Pasangan pembalik tidak lengkap: '.$original->number);
                // Preserve cost recognition, payments, and their full history.
                // Only canceled invoice revisions are candidates for archival.
                if (! $reversal || $original->id === $anchor->id
                    || $original->source_type !== Job::class
                    || ! in_array($original->type, ['job_invoice', 'job_invoice_amendment'], true)) {
                    continue;
                }
                $this->require($original->journal_date->isSameDay($reversal->journal_date)
                    && $original->journal_date->toDateString() <= today()->toDateString(),
                    'Pasangan lintas tanggal tidak boleh dihapus: '.$original->number);
                $pair = collect([$original, $reversal]);
                foreach ($this->balances($pair) as $net) {
                    $this->require(Money::decimal($net)->isZero(), 'Pembalik tidak menetralkan seluruh akun: '.$original->number);
                }
                $this->require(! Journal::where('source_type', Journal::class)->whereIn('source_id', [$original->id, $reversal->id])
                    ->whereKeyNot($reversal->id)->exists(), 'Ada jurnal lain yang merujuk pasangan '.$original->number);
                $archive = $archive->concat($pair);
            }

            $all = $roots->concat($reversals->values());
            $before = $this->balances($all);
            $plan = [
                'job' => $job->number, 'invoice' => $invoice->number,
                'mode' => $apply ? 'APPLY' : 'DRY RUN',
                'jumlah_pasangan' => intdiv($archive->count(), 2),
                'jurnal_dipindahkan_ke_backup' => $archive->pluck('number')->all(),
                'invoice_pertama_dipertahankan' => $anchor->number,
                'saldo_per_akun_sebelum' => $before,
                'backup' => null,
            ];
            if (! $apply || $archive->isEmpty()) {
                return $plan;
            }

            $payload = [
                'version' => 1, 'created_at' => now()->toIso8601String(),
                'actor_id' => $actor->id, 'job_id' => $job->id, 'job_number' => $job->number,
                'job' => $job->getAttributes(), 'invoice' => $invoice->getAttributes(),
                'costs' => $costs->map->getAttributes()->all(),
                'archived_journals' => $archive->map(fn ($j) => [
                    'journal' => $j->getAttributes(), 'entries' => $j->entries->map->getAttributes()->all(),
                ])->values()->all(),
                'balances_before' => $before,
            ];
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            $path = 'accounting-backups/trial-history/'.Str::uuid().'.json';
            $disk = Storage::disk('private');
            $this->require($disk->put($path, $json), 'Backup gagal; tidak ada jurnal yang dihapus.');
            $checksum = hash('sha256', $json);
            $this->require(hash('sha256', $disk->get($path)) === $checksum, 'Verifikasi backup gagal.');

            DB::table('journal_entries')->whereIn('journal_id', $archive->pluck('id'))->delete();
            // The self-referencing foreign key requires deleting children first.
            Journal::whereIn('id', $archive->pluck('id'))->whereNotNull('reversal_of_id')->delete();
            Journal::whereIn('id', $archive->pluck('id'))->delete();
            $remaining = Journal::with('entries')->whereIn('id', $all->pluck('id'))->get();
            $this->require($this->balances($remaining) === $before, 'Saldo berubah; seluruh perubahan dibatalkan.');
            $maps = $this->journals->mapped(['temporary', 'provision_wip']);
            foreach ($maps as $account) {
                $balance = Money::decimal(0);
                $entries = JournalEntry::where('chart_of_account_id', $account->id)
                    ->whereHas('journal', fn ($q) => $q->whereDate('journal_date', '<=', today()->toDateString()))
                    ->orderBy(Journal::select('journal_date')->whereColumn('journals.id', 'journal_entries.journal_id')->limit(1))
                    ->orderBy('id')->get();
                foreach ($entries as $entry) {
                    $balance = $balance->plus($entry->debit)->minus($entry->credit);
                    $this->require(! $balance->isNegative(), 'Masih ada saldo berjalan minus di '.$account->code.'; seluruh perubahan dibatalkan. Backup: '.$path);
                }
            }
            $this->master->log($actor, 'job.trial_journal_history_compacted', 'Arsip riwayat trial '.$job->number, [
                'module' => 'job', 'record_id' => $job->id, 'backup' => $path,
                'sha256' => $checksum, 'journal_numbers' => $archive->pluck('number')->all(),
                'balances_before' => $before, 'balances_after' => $this->balances($remaining),
            ]);

            return array_replace($plan, [
                'backup' => $disk->path($path), 'sha256' => $checksum,
                'saldo_per_akun_sesudah' => $this->balances($remaining),
            ]);
        });
    }

    private function balances(Collection $journals): array
    {
        $balances = [];
        foreach ($journals as $journal) {
            $debit = Money::decimal(0);
            $credit = Money::decimal(0);
            $this->require($journal->entries->isNotEmpty(), 'Jurnal kosong: '.$journal->number);
            foreach ($journal->entries as $entry) {
                $key = $entry->chart_of_account_id;
                $balances[$key] = (string) Money::decimal($balances[$key] ?? 0)->plus($entry->debit)->minus($entry->credit);
                $debit = $debit->plus($entry->debit);
                $credit = $credit->plus($entry->credit);
            }
            $this->require($debit->isEqualTo($credit), 'Jurnal tidak balance: '.$journal->number);
        }
        ksort($balances);

        // Zero-only accounts may disappear with a fully canceled pair.
        return array_filter($balances, fn ($value) => ! Money::decimal($value)->isZero());
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['trial_history' => $message]);
        }
    }
}
