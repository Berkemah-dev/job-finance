<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Job;
use App\Models\JobCost;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class JobCostService
{
    private const RECOGNITION_JOURNAL_TYPES = ['job_cost_draft', 'job_cost_direct', 'job_cost_finalization', 'job_cost_amendment'];

    public function __construct(private MasterDataService $master, private DocumentNumberService $numbers, private JournalService $journals, private PartyAccountService $partyAccounts) {}

    public function markPaid(Job $job, JobCost $cost, array $data, User $actor): void
    {
        DB::transaction(function () use ($job, $cost, $data, $actor) {
            Gate::forUser($actor)->authorize('costs.manage');
            $job = $this->lockJob($job, $data);
            $cost = $job->costs()->lockForUpdate()->findOrFail($cost->id);
            if ($cost->paid_at) {
                throw ValidationException::withMessages(['cost' => 'Biaya ini sudah dibayar.']);
            }

            if (Money::decimal($cost->total_cost)->isZero()) {
                throw ValidationException::withMessages(['cost' => 'Biaya tagih tanpa modal tidak memiliki hutang vendor dan tidak perlu ditandai PAID.']);
            }

            $hasDraftPayable = Journal::where('source_type', JobCost::class)->where('source_id', $cost->id)
                ->whereIn('type', self::RECOGNITION_JOURNAL_TYPES)
                ->whereNull('reversed_at')->whereNull('reversal_of_id')->exists();
            $pph = Money::decimal($data['pph23_amount'] ?? $cost->pph23_amount ?? 0);
            $vendor = $cost->vendor_id ? Vendor::find($cost->vendor_id) : null;
            $isCommission = $vendor?->type === 'commission' || $cost->cost_category === 'credit_note' || str_contains(mb_strtolower($cost->description), 'komisi');
            $taxKey = $isCommission ? 'pph21_payable' : 'pph23_payable';
            $taxLabel = $isCommission ? 'PPh 21 hutang ' : 'PPh 23 hutang ';
            $taxName = $isCommission ? 'PPh 21' : 'PPh 23';
            // PPh baru menjadi Hutang Pajak ketika PAID. Nilai ini hanya untuk
            // transaksi lama yang telah mencatat PPh sebelum alur finalisasi.
            $draftPph = Money::decimal((string) JournalEntry::query()
                ->where('chart_of_account_id', $this->journals->mapped([$taxKey])[$taxKey]->id)
                ->where('credit', '>', 0)
                ->whereHas('journal', fn ($query) => $query->where('source_type', JobCost::class)->where('source_id', $cost->id))
                ->sum('credit'));
            $amount = Money::decimal($cost->total_cost);
            $payableKey = $vendor ? $this->partyAccounts->payableMappingKey($vendor) : 'vendor_payable';
            $payableAccount = $vendor
                ? $this->partyAccounts->vendorPayable($vendor)
                : $this->journals->mapped([$payableKey])[$payableKey];
            if ($pph->isGreaterThan($amount)) {
                throw ValidationException::withMessages(['pph23_amount' => $taxName.' tidak boleh melebihi biaya.']);
            }
            if ($hasDraftPayable && $pph->isLessThan($draftPph)) {
                throw ValidationException::withMessages(['pph23_amount' => $taxName.' tidak dapat dikurangi karena jurnal Draft sudah tercatat.']);
            }

            // Data lama yang sudah final tetapi belum punya jurnal tetap dapat
            // diselesaikan tanpa melewati proses Finance lagi.
            if (! $hasDraftPayable) {
                $mapsDraft = $this->journals->mapped([$cost->type === 'temporary' ? 'temporary' : 'provision_wip']);
                $debitKey = $cost->type === 'temporary' ? 'temporary' : 'provision_wip';
                $draftEntries = [
                    ['account_id' => $mapsDraft[$debitKey]->id, 'description' => ($cost->type === 'temporary' ? 'Temporary Payment ' : 'Provisional Payment ').$cost->number, 'debit' => (string) $amount, 'credit' => 0],
                    ['account_id' => $payableAccount->id, 'description' => ($vendor ? $this->partyAccounts->payableLabel($vendor) : 'Hutang vendor ').$cost->number, 'debit' => 0, 'credit' => (string) $amount],
                ];
                $costDate = $cost->cost_date ? $cost->cost_date->format('Y-m-d') : $data['paid_date'];
                $this->journals->post('job_cost_finalization', JobCost::class, $cost->id, $costDate, 'Finalisasi '.$cost->number, $draftEntries, $actor);
                $hasDraftPayable = true;
                $draftPph = Money::decimal(0);
            }

            $account = ChartOfAccount::where('type', 'asset')->findOrFail($data['payment_account_id']);
            $maps = $this->journals->mapped([$taxKey]);
            // Hutang vendor yang telah terbentuk pada Draft harus dilunasi penuh.
            // PPh yang baru diinput ketika PAID dipindahkan ke Hutang Pajak.
            $vendorSettlement = $amount->minus($draftPph);
            $bankPayment = $amount->minus($pph);
            $additionalPph = $pph->minus($draftPph);

            $entries = [
                ['account_id' => $payableAccount->id, 'description' => 'Pelunasan '.mb_strtolower($vendor ? $this->partyAccounts->payableLabel($vendor) : 'Hutang vendor ').$cost->number, 'debit' => (string) $vendorSettlement, 'credit' => 0],
                ['account_id' => $account->id, 'description' => 'Pembayaran biaya job '.$cost->number, 'debit' => 0, 'credit' => (string) $bankPayment],
            ];
            if ($additionalPph->isPositive()) {
                $entries[] = ['account_id' => $maps[$taxKey]->id, 'description' => $taxLabel.$cost->number, 'debit' => 0, 'credit' => (string) $additionalPph];
            }

            $number = $this->numbers->nextBankJournal($account->code, $account->name, false, Carbon::parse($data['paid_date']));
            $this->journals->post('job_cost_payment', JobCost::class, $cost->id, $data['paid_date'], 'Pembayaran '.$cost->number, $entries, $actor, $number);
            $cost->update([
                'paid_date' => $data['paid_date'],
                'paid_at' => now(),
                'payment_account_id' => $account->id,
                'pph23_amount' => (string) $pph,
                'withholding_document_number' => $data['withholding_document_number'] ?? null,
            ]);
            $this->touchJob($job, $actor);
        }, 3);
    }

    /**
     * Nomor bukti potong (Bupot) sering baru keluar setelah vendor dibayar,
     * sehingga harus tetap dapat diisi kapan saja tanpa mengubah jurnal.
     */
public function saveWithholdingDocument(Job $job, JobCost $cost, ?string $number, User $actor): void
    {
        DB::transaction(function () use ($job, $cost, $number, $actor) {
            Gate::forUser($actor)->authorize('costs.manage');

            $cost = JobCost::query()->whereKey($cost->id)->lockForUpdate()->firstOrFail();
            $before = $cost->only(['withholding_document_number']);
            $cost->withholding_document_number = $number;
            $cost->updated_by = $actor->id;
            $cost->save();

            $this->master->log($actor, 'job_cost.withholding_document_updated', 'Nomor bukti potong '.$cost->number, [
                'module' => 'job_cost',
                'record_id' => $cost->id,
                'before' => $before,
                'after' => $cost->only(['withholding_document_number']),
            ]);
        }, 3);
    }

private function lockJob(Job $job, array $data): Job
    {
        $job = Job::lockForUpdate()->findOrFail($job->id);
        $this->master->checkVersion($job, ['lock_version' => $data['job_version'] ?? -1]);

        return $job;
    }

    private function touchJob(Job $job, User $actor): void
    {
        $job->lock_version++;
        $job->updated_by = $actor->id;
        $job->save();
    }

    public function save(Job $job, ?JobCost $cost, array $data, User $actor): JobCost
    {
        return DB::transaction(function () use ($job, $cost, $data, $actor) {
            Gate::forUser($actor)->authorize('costs.manage');
            $job = $this->lockJob($job, $data);
            $new = $cost === null;
            $paidCostTotal = null;
            $costCategory = $data['cost_category'] ?? ($cost?->cost_category ?? (($data['type'] ?? 'provision') === 'temporary' ? 'reimbursement' : 'payment_request'));
            if ($new) {
                Gate::forUser($actor)->authorize('create', [JobCost::class, $job]);
                $cost = new JobCost;
                $cost->job_id = $job->id;
                $cost->number = $this->numbers->next(match ($costCategory) {
                    'payment_request' => 'pr',
                    'reimbursement' => 'rmb',
                    'debit_note' => 'dn',
                    'credit_note' => 'cn',
                });
                $cost->created_by = $actor->id;
                $cost->status = 'draft';
            } else {
                $cost = $job->costs()->lockForUpdate()->findOrFail($cost->id);
                $cost->setRelation('job', $job);
                Gate::forUser($actor)->authorize('update', $cost);
                $this->master->checkVersion($cost, $data);
                $before = $cost->only(['description', 'type', 'currency', 'cost_exchange_rate', 'price_exchange_rate', 'quantity', 'unit', 'unit_cost', 'unit_price', 'status']);
                $paidCostTotal = $cost->paid_at ? Money::decimal($cost->total_cost) : null;
                // Biaya yang sudah Finance Confirm boleh disesuaikan sebelum
                // Finalisasi Invoice. Jurnal pengakuan sebelumnya dibalik
                // agar nilai hutang dan modal mengikuti nilai terbaru.
                if ($cost->status === 'final') {
                    $this->reverseRecognition($cost, $actor);
                }
            }
            $date = Carbon::parse($data['cost_date']);
            if ($date->isBefore($job->job_date) || $date->isAfter(today())) {
                throw ValidationException::withMessages(['cost_date' => 'Tanggal biaya harus antara tanggal job dan hari ini.']);
            }
            $currency = strtoupper((string) ($data['currency'] ?? 'IDR'));
            $costExchangeRate = $currency === 'IDR'
                ? BigDecimal::one()
                : BigDecimal::of($data['cost_exchange_rate'])->toScale(4, RoundingMode::HalfUp);
            $priceExchangeRate = $currency === 'IDR'
                ? BigDecimal::one()
                : BigDecimal::of($data['price_exchange_rate'])->toScale(4, RoundingMode::HalfUp);
            $foreignUnitCost = Money::decimal($data['unit_cost']);
            $foreignUnitPrice = Money::decimal($data['unit_price']);
            $unitCost = $foreignUnitCost->multipliedBy($costExchangeRate)->toScale(2, RoundingMode::HalfUp);
            $unitPrice = $foreignUnitPrice->multipliedBy($priceExchangeRate)->toScale(2, RoundingMode::HalfUp);
            $quantity = Money::decimal($data['quantity']);
            $data['cost_category'] = $costCategory;
            $data['type'] = $costCategory === 'reimbursement' ? 'temporary' : 'provision';
            if ($data['type'] === 'temporary' && (! $unitCost->isEqualTo($unitPrice) || ! $costExchangeRate->isEqualTo($priceExchangeRate))) {
                throw ValidationException::withMessages(['unit_price' => 'Nilai jual Temporary harus sama dengan modal karena ditagihkan kembali tanpa profit.']);
            }
            if (! empty($data['vendor_id']) && empty($data['payee'])) {
                $vendor = Vendor::find($data['vendor_id']);
                if ($vendor) {
                    $data['payee'] = $vendor->name;
                }
            }
            $cost->fill(Arr::only($data, ['description', 'type', 'cost_category', 'cost_date', 'quantity', 'unit', 'pph23_amount', 'payee', 'vendor_id', 'reference', 'notes']));
            $cost->currency = $currency;
            // exchange_rate dipertahankan sebagai kurs tagih untuk kompatibilitas
            // data lama dan baris invoice yang menggunakan kolom tersebut.
            $cost->exchange_rate = (string) $priceExchangeRate;
            $cost->cost_exchange_rate = (string) $costExchangeRate;
            $cost->price_exchange_rate = (string) $priceExchangeRate;
            $cost->foreign_unit_cost = Money::checked($foreignUnitCost);
            $cost->foreign_unit_price = Money::checked($foreignUnitPrice);
            $cost->unit_cost = Money::checked($unitCost);
            $cost->unit_price = Money::checked($unitPrice);
            $cost->total_cost = Money::checked($quantity->multipliedBy($unitCost)->toScale(2, RoundingMode::HalfUp));
            $cost->total_price = Money::checked($quantity->multipliedBy($unitPrice)->toScale(2, RoundingMode::HalfUp));
            $cost->foreign_total_cost = Money::checked($quantity->multipliedBy($foreignUnitCost)->toScale(2, RoundingMode::HalfUp));
            $cost->foreign_total_price = Money::checked($quantity->multipliedBy($foreignUnitPrice)->toScale(2, RoundingMode::HalfUp));
            if ($paidCostTotal && ! Money::decimal($cost->total_cost)->isEqualTo($paidCostTotal)) {
                throw ValidationException::withMessages([
                    'unit_cost' => 'Total modal biaya yang sudah PAID tidak dapat diubah karena jurnal kas/bank sudah tercatat. Ubah nilai jual atau Kurs Tagih untuk revisi invoice.',
                ]);
            }
            $cost->updated_by = $actor->id;
            $cost->lock_version = $new ? 0 : $cost->lock_version + 1;
            $cost->save();
            if (Money::decimal($cost->pph23_amount ?? 0)->isGreaterThan(Money::decimal($cost->total_cost))) {
                throw ValidationException::withMessages(['pph23_amount' => 'PPh 23 tidak boleh melebihi total biaya.']);
            }
            // Charge yang berasal dari quotation wajib ditinjau Finance satu per satu.
            // Input yang dibuat langsung Finance tidak perlu antre Finalisasi Biaya.
            if ($new && $cost->quotation_id === null && Money::decimal($cost->total_cost)->isPositive()) {
                $cost->status = 'final';
                $cost->finalized_by = $actor->id;
                $cost->finalized_at = now();
                $cost->save();
                $this->recognizePayable($cost, $actor, 'job_cost_direct');
            }
            if (! $new && $cost->status === 'final' && Money::decimal($cost->total_cost)->isPositive()) {
                $this->recognizePayable($cost, $actor, 'job_cost_amendment');
            }
            $this->summary($job); // Reject aggregate overflow inside the same transaction.
            $this->refreshIssuedInvoice($job, $actor);
            $this->touchJob($job, $actor);
            $after = $cost->only(['description', 'type', 'cost_category', 'currency', 'cost_exchange_rate', 'price_exchange_rate', 'quantity', 'unit', 'unit_cost', 'unit_price', 'total_cost', 'total_price', 'status']);
            $this->master->log($actor, $new ? 'job_cost.created' : 'job_cost.updated', $cost->number.' · '.$job->number, ['module' => 'job_cost', 'record_id' => $cost->id, 'before' => $new ? null : $before, 'after' => $after]);

            return $cost;
        }, 3);
    }

    /** Pengakuan modal dan hutang vendor/agent; dipanggil saat Finance menyimpan biaya langsung atau memfinalisasi biaya quotation. */
    private function recognizePayable(JobCost $cost, User $actor, string $journalType): void
    {
        $vendor = $cost->vendor_id ? Vendor::find($cost->vendor_id) : null;
        $payableKey = $vendor ? $this->partyAccounts->payableMappingKey($vendor) : 'vendor_payable';
        $debitKey = $cost->type === 'temporary' ? 'temporary' : 'provision_wip';
        $maps = $this->journals->mapped([$debitKey]);
        $payableAccount = $vendor
            ? $this->partyAccounts->vendorPayable($vendor)
            : $this->journals->mapped([$payableKey])[$payableKey];
        $payableLabel = $vendor ? $this->partyAccounts->payableLabel($vendor) : 'Hutang vendor ';
        $this->journals->post($journalType, JobCost::class, $cost->id, $cost->cost_date->format('Y-m-d'), 'Pencatatan '.$cost->number, [
            ['account_id' => $maps[$debitKey]->id, 'description' => ($cost->type === 'temporary' ? 'Temporary Payment ' : 'Provisional Payment ').$cost->number, 'debit' => $cost->total_cost, 'credit' => 0],
            ['account_id' => $payableAccount->id, 'description' => $payableLabel.$cost->number, 'debit' => 0, 'credit' => $cost->total_cost],
        ], $actor);
    }

    /** Membalik jurnal pengakuan saat biaya yang sudah Finance Confirm diperbarui. */
    private function reverseRecognition(JobCost $cost, User $actor): void
    {
        $journals = Journal::with('entries')
            ->where('source_type', JobCost::class)
            ->where('source_id', $cost->id)
            ->whereIn('type', self::RECOGNITION_JOURNAL_TYPES)
            ->whereNull('reversed_at')
            ->whereNull('reversal_of_id')
            ->lockForUpdate()
            ->get();

        foreach ($journals as $journal) {
            $entries = $journal->entries->map(fn (JournalEntry $entry) => [
                'account_id' => $entry->chart_of_account_id,
                'description' => 'Penyesuaian '.$cost->number.': '.$entry->description,
                'debit' => $entry->credit,
                'credit' => $entry->debit,
            ])->all();
            if ($entries === []) {
                continue;
            }

            $reversal = $this->journals->post(
                'job_cost_amendment_reversal',
                JobCost::class,
                $cost->id,
                $cost->cost_date->format('Y-m-d'),
                'Penyesuaian biaya '.$cost->number,
                $entries,
                $actor
            );
            $reversal->reversal_of_id = $journal->id;
            $reversal->save();
            $journal->reversed_by = $actor->id;
            $journal->reversed_at = now();
            $journal->lock_version++;
            $journal->save();
        }
    }

    /**
     * Invoice pra-closing mengikuti perubahan Biaya Job sampai ada pembayaran.
     * Baris invoice serta jurnal invoice lama dibalik terlebih dahulu agar audit
     * trail tetap ada dan nilai piutang, pendapatan, HPP mengikuti nilai terbaru.
     */
    private function refreshIssuedInvoice(Job $job, User $actor): void
    {
        $invoice = $job->invoice()->lockForUpdate()->first();
        if (! $invoice) {
            return;
        }
        if ($invoice->payments()->exists() || Money::decimal($invoice->paid_amount)->isPositive()) {
            throw ValidationException::withMessages(['cost' => 'Biaya tidak dapat diubah karena invoice sudah memiliki pembayaran.']);
        }

        $rows = $job->costs()->orderBy('id')->get();
        $summary = $this->summary($job)['all'];
        $tax = Money::decimal($invoice->tax);
        $stampDuty = Money::decimal($invoice->stamp_duty ?? 0);
        $total = Money::decimal($summary['subtotal'])->plus($tax)->plus($stampDuty);
        if ($total->isZero()) {
            throw ValidationException::withMessages(['cost' => 'Perubahan membuat total invoice menjadi nol.']);
        }

        $activeJournals = Journal::with('entries')
            ->where('source_type', Job::class)
            ->where('source_id', $job->id)
            ->whereIn('type', ['job_invoice', 'job_invoice_amendment'])
            ->whereNull('reversed_at')
            ->whereNull('reversal_of_id')
            ->lockForUpdate()
            ->get();
        foreach ($activeJournals as $journal) {
            $entries = $journal->entries->map(fn (JournalEntry $entry) => [
                'account_id' => $entry->chart_of_account_id,
                'description' => 'Penyesuaian '.$invoice->number.': '.$entry->description,
                'debit' => $entry->credit,
                'credit' => $entry->debit,
            ])->all();
            $reversal = $this->journals->post('job_invoice_amendment_reversal', Job::class, $job->id, $invoice->invoice_date->format('Y-m-d'), 'Penyesuaian invoice '.$invoice->number, $entries, $actor);
            $reversal->reversal_of_id = $journal->id;
            $reversal->save();
            $journal->reversed_by = $actor->id;
            $journal->reversed_at = now();
            $journal->lock_version++;
            $journal->save();
        }

        [$currency, $rate] = $this->invoiceCurrencyAndRate($rows);
        $invoice->update([
            'currency' => $currency,
            'exchange_rate' => (string) $rate->toScale(2, RoundingMode::HalfUp),
            'subtotal' => $summary['subtotal'],
            'total' => Money::checked($total),
            'balance' => Money::checked($total),
        ]);
        $invoice->items()->delete();
        foreach ($rows as $position => $cost) {
            $invoice->items()->create($this->invoiceItemData($cost, $position + 1));
        }

        $customer = $job->customer()->withTrashed()->firstOrFail();
        $customerAccounts = $this->partyAccounts->customerAccounts($customer);
        $flow = $this->invoiceFlowAccountKeys($job);
        $maps = $this->journals->mapped(array_merge(['temporary', 'provision_wip', 'temporary_receivable', 'receivable', 'tax_payable'], array_values($flow)));
        $temporary = Money::decimal($summary['temporary']);
        $provisionSell = Money::decimal($summary['provision_sell']);
        $invoiceRevenue = $provisionSell->plus($stampDuty);
        $provisionCost = Money::decimal($summary['provision_cost']);
        $entries = [];
        if ($invoiceRevenue->isPositive() || $tax->isPositive()) {
            $entries[] = ['account_id' => $customerAccounts['receivable']->id, 'description' => 'Piutang '.$invoice->number, 'debit' => (string) $invoiceRevenue->plus($tax), 'credit' => 0];
        }
        if ($temporary->isPositive()) {
            $entries[] = ['account_id' => $customerAccounts['temporary']->id, 'description' => 'Piutang Temporary '.$invoice->number, 'debit' => (string) $temporary, 'credit' => 0];
            $entries[] = ['account_id' => $maps['temporary']->id, 'description' => 'Reklasifikasi temporary '.$invoice->number, 'debit' => 0, 'credit' => (string) $temporary];
        }
        foreach ($rows->where('type', 'provision') as $cost) {
            $sellingValue = Money::decimal($cost->total_price);
            if ($sellingValue->isPositive()) $entries[] = ['account_id' => $maps[$flow['revenue_unclosed']]->id, 'description' => 'Pendapatan Unclose · '.strtoupper($cost->description).' · '.$job->number, 'debit' => 0, 'credit' => (string) $sellingValue];
            $costValue = Money::decimal($cost->total_cost);
            if ($costValue->isPositive()) $entries[] = ['account_id' => $maps[$flow['cogs_unclosed']]->id, 'description' => 'HPP Unclose · '.strtoupper($cost->description).' · '.$job->number, 'debit' => (string) $costValue, 'credit' => 0];
        }
        if ($stampDuty->isPositive()) $entries[] = ['account_id' => $maps[$flow['revenue_unclosed']]->id, 'description' => 'Tagihan Materai · '.$job->number, 'debit' => 0, 'credit' => (string) $stampDuty];
        if ($provisionCost->isPositive()) $entries[] = ['account_id' => $maps['provision_wip']->id, 'description' => 'Reklasifikasi Provisional Payment '.$invoice->number, 'debit' => 0, 'credit' => (string) $provisionCost];
        if ($tax->isPositive()) $entries[] = ['account_id' => $maps['tax_payable']->id, 'description' => 'Pajak keluaran '.$invoice->number, 'debit' => 0, 'credit' => (string) $tax];
        $this->journals->post('job_invoice_amendment', Job::class, $job->id, $invoice->invoice_date->format('Y-m-d'), 'Penyesuaian invoice '.$invoice->number.' untuk '.$job->number, $entries, $actor);
    }

    private function invoiceFlowAccountKeys(Job $job): array
    {
        $group = match (true) {
            in_array((string) $job->service_type, ['imp_sea', 'imp_air'], true) => 'import',
            in_array((string) $job->service_type, ['exp_sea', 'exp_air', 'sea', 'air'], true) => 'export',
            default => 'domestic',
        };
        return ['revenue_unclosed' => 'revenue_'.$group.'_unclosed', 'cogs_unclosed' => 'cogs_'.$group.'_unclosed'];
    }

    private function invoiceCurrencyAndRate(\Illuminate\Support\Collection $rows): array
    {
        $foreignRows = $rows->filter(fn ($cost) => strtoupper((string) ($cost->currency ?: 'IDR')) !== 'IDR');
        if ($foreignRows->isEmpty() || $foreignRows->count() !== $rows->count()) return ['IDR', Money::decimal('1')];
        $currencies = $foreignRows->map(fn ($cost) => strtoupper((string) $cost->currency))->unique();
        $rates = $foreignRows->map(fn ($cost) => (string) ($cost->price_exchange_rate ?: $cost->exchange_rate ?: 1))->unique();
        if ($currencies->count() !== 1 || $rates->count() !== 1) return ['IDR', Money::decimal('1')];
        return [$currencies->first(), Money::decimal($rates->first())];
    }

    private function invoiceItemData(JobCost $cost, int $position): array
    {
        $isTemporary = $cost->type === 'temporary';
        $foreignUnitPrice = $isTemporary ? $cost->foreign_unit_cost : $cost->foreign_unit_price;
        if ($foreignUnitPrice === null) $foreignUnitPrice = $isTemporary ? $cost->unit_cost : $cost->unit_price;
        return ['position' => $position, 'description' => $cost->description, 'type' => $cost->type, 'quantity' => $cost->quantity, 'unit' => $cost->unit, 'currency' => strtoupper((string) ($cost->currency ?: 'IDR')), 'exchange_rate' => $cost->price_exchange_rate ?: $cost->exchange_rate ?: 1, 'foreign_unit_price' => $foreignUnitPrice, 'unit_price' => $isTemporary ? $cost->unit_cost : $cost->unit_price, 'amount' => $isTemporary ? $cost->total_cost : $cost->total_price];
    }

    public function finalize(Job $job, JobCost $cost, array $data, User $actor): void
    {
        DB::transaction(function () use ($job, $cost, $data, $actor) {
            Gate::forUser($actor)->authorize('costs.manage');
            $job = $this->lockJob($job, $data);
            $cost = $job->costs()->lockForUpdate()->findOrFail($cost->id);
            $cost->setRelation('job', $job);
            Gate::forUser($actor)->authorize('finalize', $cost);
            $this->master->checkVersion($cost, $data);
            $cost->status = 'final';
            $cost->finalized_by = $actor->id;
            $cost->finalized_at = now();
            $cost->updated_by = $actor->id;
            $cost->lock_version++;
            $cost->save();
            $hasRecognition = Journal::where('source_type', JobCost::class)->where('source_id', $cost->id)
                ->whereIn('type', self::RECOGNITION_JOURNAL_TYPES)
                ->whereNull('reversed_at')->whereNull('reversal_of_id')->exists();
            if (! $hasRecognition && Money::decimal($cost->total_cost)->isPositive()) {
                $this->recognizePayable($cost, $actor, 'job_cost_finalization');
            }
            $this->touchJob($job, $actor);
            $this->master->log($actor, 'job_cost.finalized', 'Finalisasi '.$cost->number.' · '.$job->number, ['module' => 'job_cost', 'record_id' => $cost->id, 'after' => $cost->only(['type', 'total_cost', 'total_price', 'status'])]);
        }, 3);
    }

    public function approve(Job $job, JobCost $cost, array $data, User $actor): void
    {
        DB::transaction(function () use ($job, $cost, $data, $actor) {
            $job = $this->lockJob($job, $data);
            $cost = $job->costs()->lockForUpdate()->findOrFail($cost->id);
            Gate::forUser($actor)->authorize('approve', $cost);
            $this->master->checkVersion($cost, $data);
            $cost->update(['status' => 'approved', 'approved_by' => $actor->id, 'approved_at' => now(), 'updated_by' => $actor->id, 'lock_version' => $cost->lock_version + 1]);
            $this->touchJob($job, $actor);
        }, 3);
    }

    public function delete(Job $job, JobCost $cost, array $data, User $actor): void
    {
        DB::transaction(function () use ($job, $cost, $data, $actor) {
            Gate::forUser($actor)->authorize('costs.manage');
            $job = $this->lockJob($job, $data);
            $cost = $job->costs()->lockForUpdate()->findOrFail($cost->id);
            $cost->setRelation('job', $job);
            Gate::forUser($actor)->authorize('delete', $cost);
            $this->master->checkVersion($cost, $data);
            $cost->deleted_by = $actor->id;
            $cost->updated_by = $actor->id;
            $cost->lock_version++;
            $cost->save();
            $cost->delete();
            $this->touchJob($job, $actor);
            $this->master->log($actor, 'job_cost.deleted', 'Menghapus Draft '.$cost->number.' · '.$job->number, ['module' => 'job_cost', 'record_id' => $cost->id, 'after' => ['deleted' => true]]);
        }, 3);
    }

    public function summary(Job $job): array
    {
        $totals = [];
        foreach (['draft', 'approved', 'final', 'all'] as $status) {
            $totals[$status] = ['temporary' => Money::decimal(0), 'provision_cost' => Money::decimal(0), 'provision_sell' => Money::decimal(0), 'count' => 0];
        }
        foreach ($job->costs()->select(['id', 'type', 'status', 'total_cost', 'total_price'])->cursor() as $cost) {
            foreach ([$cost->status, 'all'] as $group) {
                $totals[$group]['count']++;
                if ($cost->type === 'temporary') {
                    $totals[$group]['temporary'] = $totals[$group]['temporary']->plus($cost->total_cost);
                } else {
                    $totals[$group]['provision_cost'] = $totals[$group]['provision_cost']->plus($cost->total_cost);
                    $totals[$group]['provision_sell'] = $totals[$group]['provision_sell']->plus($cost->total_price);
                }
            }
        }
        foreach ($totals as &$group) {
            $group['profit'] = $group['provision_sell']->minus($group['provision_cost']);
            $group['subtotal'] = $group['temporary']->plus($group['provision_sell']);
            $group['margin'] = $group['provision_sell']->isZero() ? Money::decimal(0) : $group['profit']->multipliedBy(100)->dividedBy($group['provision_sell'], 2, RoundingMode::HalfUp);
            foreach ($group as $key => $value) {
                if ($key !== 'count') {
                    $group[$key] = Money::checked($value);
                }
            }
        }

        return $totals;
    }
}
