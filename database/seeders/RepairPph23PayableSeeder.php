<?php

namespace Database\Seeders;

use App\Models\AccountMapping;
use App\Models\JobCost;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\JournalService;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RepairPph23PayableSeeder extends Seeder
{
    /**
     * Memperbaiki pembayaran biaya yang telah diposting sebelum PPh 23
     * dicatat ke COA Utang Pajak. Aman dijalankan ulang.
     */
    public function run(): void
    {
        $actor = User::whereHas('role', fn ($query) => $query->where('name', 'super-admin'))->firstOrFail();
        $taxPayableId = AccountMapping::where('key', 'tax_payable')->value('chart_of_account_id');

        if (! $taxPayableId) {
            $this->command?->error('Mapping COA tax_payable belum tersedia.');

            return;
        }

        $repaired = 0;
        JobCost::query()
            ->whereNotNull('paid_at')
            ->where('pph23_amount', '>', 0)
            ->orderBy('id')
            ->each(function (JobCost $cost) use ($actor, $taxPayableId, &$repaired) {
                DB::transaction(function () use ($cost, $actor, $taxPayableId, &$repaired) {
                    $cost = JobCost::lockForUpdate()->findOrFail($cost->id);
                    $pph = Money::decimal($cost->pph23_amount);
                    $alreadyRecorded = Money::decimal((string) JournalEntry::query()
                        ->where('chart_of_account_id', $taxPayableId)
                        ->where('credit', '>', 0)
                        ->whereHas('journal', fn ($query) => $query
                            ->where('source_type', JobCost::class)
                            ->where('source_id', $cost->id))
                        ->sum('credit'));
                    $missing = $pph->minus($alreadyRecorded);

                    if (! $missing->isPositive()) {
                        return;
                    }

                    $maps = app(JournalService::class)->mapped(['vendor_payable', 'tax_payable']);
                    app(JournalService::class)->post(
                        'pph23_correction',
                        JobCost::class,
                        $cost->id,
                        ($cost->paid_date ?? now())->format('Y-m-d'),
                        'Koreksi PPh 23 '.$cost->number,
                        [
                            ['account_id' => $maps['vendor_payable']->id, 'description' => 'Koreksi hutang vendor '.$cost->number, 'debit' => (string) $missing, 'credit' => 0],
                            ['account_id' => $maps['tax_payable']->id, 'description' => 'PPh 23 hutang '.$cost->number, 'debit' => 0, 'credit' => (string) $missing],
                        ],
                        $actor,
                    );
                    $repaired++;
                }, 3);
            });

        $this->command?->info("Koreksi PPh 23 selesai: {$repaired} pembayaran diperbaiki.");
    }
}
