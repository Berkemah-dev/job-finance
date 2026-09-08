<?php

namespace App\Services;

use App\Models\AccountMapping;
use App\Models\Journal;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalService
{
    public function __construct(private DocumentNumberService $numbers) {}

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
