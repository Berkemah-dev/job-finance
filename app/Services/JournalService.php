<?php

namespace App\Services;

use App\Models\AccountMapping;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalAdjustment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class JournalService
{
    public function __construct(private DocumentNumberService $numbers, private MasterDataService $master) {}

    public function adjustment(array $data, User $actor): Journal
    {
        return DB::transaction(function () use ($data, $actor) {
            Gate::forUser($actor)->authorize('journals.manage');
            $ids = collect($data['entries'])->pluck('account_id');
            $accounts = ChartOfAccount::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
            $entries = collect($data['entries'])->map(function ($entry) use ($accounts) {
                if (! $accounts->has((int) $entry['account_id'])) {
                    throw ValidationException::withMessages(['entries' => 'Seluruh akun jurnal harus aktif.']);
                }
                $debit = Money::decimal($entry['debit'] ?? 0);
                $credit = Money::decimal($entry['credit'] ?? 0);
                if (($debit->isZero() && $credit->isZero()) || (! $debit->isZero() && ! $credit->isZero())) {
                    throw ValidationException::withMessages(['entries' => 'Setiap baris harus memiliki tepat satu nilai debit atau kredit.']);
                }

                return ['account_id' => (int) $entry['account_id'], 'description' => $entry['description'], 'debit' => (string) $debit, 'credit' => (string) $credit];
            })->all();
            $source = JournalAdjustment::create(['description' => $data['description'], 'created_by' => $actor->id]);
            $journal = $this->post('adjustment', JournalAdjustment::class, $source->id, $data['journal_date'], $data['description'], $entries, $actor);
            $this->master->log($actor, 'journal.adjustment_created', $journal->number.' · '.$journal->description);

            return $journal;
        }, 3);
    }

    public function reverse(Journal $journal, array $data, User $actor): Journal
    {
        return DB::transaction(function () use ($journal, $data, $actor) {
            Gate::forUser($actor)->authorize('journals.manage');
            $journal = Journal::with('entries')->lockForUpdate()->findOrFail($journal->id);
            $this->master->checkVersion($journal, $data);
            if ($journal->reversal()->exists() || $journal->reversal_of_id) {
                throw ValidationException::withMessages(['journal' => 'Jurnal reversal atau jurnal yang sudah dibalik tidak dapat dibalik lagi.']);
            }
            if ($data['reversal_date'] < $journal->journal_date->format('Y-m-d')) {
                throw ValidationException::withMessages(['reversal_date' => 'Tanggal reversal tidak boleh sebelum tanggal jurnal.']);
            }
            $entries = $journal->entries->map(fn ($entry) => ['account_id' => $entry->chart_of_account_id, 'description' => 'Reversal: '.$entry->description, 'debit' => $entry->credit, 'credit' => $entry->debit])->all();
            $reversal = $this->post('journal_reversal', Journal::class, $journal->id, $data['reversal_date'], 'Reversal '.$journal->number.': '.$data['reason'], $entries, $actor);
            $reversal->reversal_of_id = $journal->id;
            $reversal->save();
            $journal->reversed_by = $actor->id;
            $journal->reversed_at = now();
            $journal->lock_version++;
            $journal->save();
            $this->master->log($actor, 'journal.reversed', $journal->number.' → '.$reversal->number);

            return $reversal;
        }, 3);
    }

    public function mapped(array $keys): array
    {
        $rows = AccountMapping::with('account')->whereIn('key', $keys)->lockForUpdate()->get()->keyBy('key');
        $result = [];
        foreach ($keys as $key) {
            $mapping = $rows->get($key);
            if (! $mapping || ! $mapping->account || $mapping->account->trashed()) {
                throw ValidationException::withMessages(['mapping' => 'Mapping akun '.$key.' belum lengkap atau akunnya diarsipkan.']);
            }$result[$key] = $mapping->account;
        }

        return $result;
    }

    public function post(string $type, string $sourceType, int $sourceId, string $date, string $description, array $entries, User $actor): Journal
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Journal posting requires a transaction.');
        }
        $debit = Money::decimal(0);
        $credit = Money::decimal(0);
        foreach ($entries as $entry) {
            $debit = $debit->plus($entry['debit'] ?? 0);
            $credit = $credit->plus($entry['credit'] ?? 0);
        }
        if (! $debit->isEqualTo($credit) || $debit->isZero()) {
            throw ValidationException::withMessages(['journal' => 'Jurnal harus seimbang dan tidak boleh bernilai nol.']);
        }
        $journal = Journal::create(['number' => $this->numbers->next('jrn'), 'journal_date' => $date, 'type' => $type, 'source_type' => $sourceType, 'source_id' => $sourceId, 'description' => $description, 'status' => 'posted', 'posted_by' => $actor->id, 'posted_at' => now()]);
        foreach ($entries as $entry) {
            if (Money::decimal($entry['debit'] ?? 0)->isZero() && Money::decimal($entry['credit'] ?? 0)->isZero()) {
                continue;
            }$journal->entries()->create(['chart_of_account_id' => $entry['account_id'], 'description' => $entry['description'], 'debit' => Money::checked(Money::decimal($entry['debit'] ?? 0)), 'credit' => Money::checked(Money::decimal($entry['credit'] ?? 0))]);
        }

        return $journal;
    }
}
