<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Job;
use App\Models\JobCost;
use App\Models\Journal;
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
    private const POST_INVOICE_HPP_JOURNAL_TYPE = 'job_cost_post_invoice_hpp';

    public function __construct(private MasterDataService $master, private DocumentNumberService $numbers, private JournalService $journals, private PartyAccountService $partyAccounts) {}

    public function markPaid(Job $job, JobCost $cost, array $data, User $actor): void
    {
        DB::transaction(function () use ($job, $cost, $data, $actor) {
            Gate::forUser($actor)->authorize('costs.manage');
            $job = $this->lockJob($job, $data);
            $cost = $job->costs()->lockForUpdate()->findOrFail($cost->id);

            if (Money::decimal($cost->total_cost)->isZero()) {
                throw ValidationException::withMessages(['cost' => 'Biaya tagih tanpa modal tidak memiliki hutang vendor dan tidak perlu ditandai PAID.']);
            }

            $paidAmount = $this->paidAmount($cost);
            $totalAmount = Money::decimal($cost->total_cost);
            $outstanding = $totalAmount->minus($paidAmount);
            if (! $outstanding->isPositive()) {
                throw ValidationException::withMessages(['cost' => 'Biaya ini sudah lunas. Gunakan Unpaid terlebih dahulu bila pembayaran perlu dibatalkan.']);
            }

            $pph = Money::decimal($data['pph23_amount'] ?? 0);
            $vendor = $cost->vendor_id ? Vendor::find($cost->vendor_id) : null;
            $isCommission = $vendor?->type === 'commission' || $cost->cost_category === 'credit_note' || str_contains(mb_strtolower($cost->description), 'komisi');
            $taxKey = $isCommission ? 'pph21_payable' : 'pph23_payable';
            $taxLabel = $isCommission ? 'PPh 21 hutang ' : 'PPh 23 hutang ';
            $taxName = $isCommission ? 'PPh 21' : 'PPh 23';
            $payableKey = $vendor ? $this->partyAccounts->payableMappingKey($vendor) : 'vendor_payable';
            $payableAccount = $vendor
                ? $this->partyAccounts->vendorPayable($vendor)
                : $this->journals->mapped([$payableKey])[$payableKey];
            if ($pph->isGreaterThan($outstanding)) {
                throw ValidationException::withMessages(['pph23_amount' => $taxName.' tidak boleh melebihi kekurangan pembayaran Rp '.Money::format($outstanding).'.']);
            }

            // Pastikan pengakuan hutang selalu sama dengan modal terbaru sebelum
            // pembayaran. Bila modal naik setelah pembayaran lama, hanya selisih
            // modal yang ditambahkan ke jurnal hutang.
            $recognitionAmount = $this->recognitionAmount($cost);
            if (! $recognitionAmount->isEqualTo($totalAmount)) {
                $this->reverseRecognition($cost, $actor);
                $this->recognizePayable($cost, $actor, $recognitionAmount->isZero() ? 'job_cost_finalization' : 'job_cost_amendment', $totalAmount);
            }

            $account = ChartOfAccount::where('type', 'asset')->findOrFail($data['payment_account_id']);
            $maps = $this->journals->mapped([$taxKey]);
            $hasStandaloneHppReclassification = $this->activePostInvoiceHppJournals($cost)->isNotEmpty();
            $isSubsequentProvision = $cost->type === 'provision'
                && ($hasStandaloneHppReclassification || $this->isSubsequentCost($job, $cost));
            if ($isSubsequentProvision) {
                $flow = $this->invoiceFlowAccountKeys($job);
                $maps += $this->journals->mapped(['provision_wip', $flow['cogs_unclosed']]);
            }
            $previousPph = $paidAmount->isPositive() ? Money::decimal($cost->pph23_amount ?? 0) : Money::decimal(0);
            $totalPph = $previousPph->plus($pph);
            if ($totalPph->isGreaterThan($totalAmount)) {
                throw ValidationException::withMessages(['pph23_amount' => $taxName.' tidak boleh melebihi total biaya.']);
            }

            // Satu biaya memiliki satu jurnal pembayaran aktif. Untuk pelunasan
            // kekurangan, jurnal pembayaran lama dibalik lalu dibuat ulang dengan
            // total pembayaran terbaru. Saldo kas tetap hanya berkurang sebesar
            // selisihnya, sementara audit trail tetap utuh.
            foreach ($this->activePaymentJournals($cost) as $paymentJournal) {
                $this->reverseJournal($paymentJournal, $cost, $actor, 'Pembatalan pembayaran lama sebelum pelunasan kekurangan');
            }
            foreach ($this->activePostInvoiceHppJournals($cost) as $hppJournal) {
                $this->reverseJournal($hppJournal, $cost, $actor, 'Pembatalan reklasifikasi HPP sebelum pelunasan kekurangan');
            }

            $entries = [
                ['account_id' => $payableAccount->id, 'description' => 'Pelunasan '.mb_strtolower($vendor ? $this->partyAccounts->payableLabel($vendor) : 'Hutang vendor ').$cost->number, 'debit' => (string) $totalAmount, 'credit' => 0],
                ['account_id' => $account->id, 'description' => 'Pembayaran biaya job '.$cost->number, 'debit' => 0, 'credit' => (string) $totalAmount->minus($totalPph)],
            ];
            if ($totalPph->isPositive()) {
                $entries[] = ['account_id' => $maps[$taxKey]->id, 'description' => $taxLabel.$cost->number, 'debit' => 0, 'credit' => (string) $totalPph];
            }
            if ($isSubsequentProvision) {
                $entries[] = ['account_id' => $maps[$flow['cogs_unclosed']]->id, 'description' => 'HPP Unclose biaya susulan · '.$cost->number, 'debit' => (string) $totalAmount, 'credit' => 0];
                $entries[] = ['account_id' => $maps['provision_wip']->id, 'description' => 'Reklasifikasi Provisi biaya susulan · '.$cost->number, 'debit' => 0, 'credit' => (string) $totalAmount];
            }

            $number = $this->numbers->nextBankJournal($account->code, $account->name, false, Carbon::parse($data['paid_date']));
            $this->journals->post('job_cost_payment', JobCost::class, $cost->id, $data['paid_date'], 'Pembayaran '.$cost->number, $entries, $actor, $number);
            $cost->update([
                'paid_date' => $data['paid_date'],
                'paid_at' => now(),
                'payment_account_id' => $account->id,
                'pph23_amount' => (string) $totalPph,
                'withholding_document_number' => $data['withholding_document_number'] ?? null,
            ]);
            foreach ($this->activePostInvoiceHppJournals($cost) as $hppJournal) {
                $this->reverseJournal($hppJournal, $cost, $actor, 'Unpaid biaya job');
            }
            $this->touchJob($job, $actor);
        }, 3);
    }

    /** Nilai modal yang sudah dibayar berdasarkan jurnal pembayaran aktif. */
    public function paidAmount(JobCost $cost): BigDecimal
    {
        $amount = Money::decimal(0);
        foreach ($this->activePaymentJournals($cost) as $journal) {
            foreach ($journal->entries as $entry) {
                // Jurnal pembayaran dapat memuat reklasifikasi Provisi ke HPP.
                // Reklasifikasi bukan pembayaran kas dan tidak boleh dihitung
                // dua kali sebagai nominal yang sudah dibayar.
                if (str_starts_with((string) $entry->description, 'Reklasifikasi Provisi biaya susulan')) {
                    continue;
                }
                $amount = $amount->plus($entry->credit);
            }
        }

        // Kompatibilitas data lama yang sudah PAID sebelum jurnal pembayaran
        // dapat ditelusuri dari detail biaya.
        if ($amount->isPositive()) {
            return $amount;
        }
        if ($cost->paid_at) {
            $recognized = $this->recognitionAmount($cost);

            return $recognized->isPositive() ? $recognized : Money::decimal($cost->total_cost);
        }

        return $amount;
    }

    public function unpaid(Job $job, JobCost $cost, array $data, User $actor): void
    {
        DB::transaction(function () use ($job, $cost, $data, $actor) {
            Gate::forUser($actor)->authorize('costs.manage');
            $job = $this->lockJob($job, $data);
            $cost = $job->costs()->lockForUpdate()->findOrFail($cost->id);
            $cost->setRelation('job', $job);
            Gate::forUser($actor)->authorize('unpaid', $cost);
            $this->master->checkVersion($cost, $data);

            foreach ($this->activePaymentJournals($cost) as $paymentJournal) {
                $this->reverseJournal($paymentJournal, $cost, $actor, 'Unpaid biaya job oleh Finance');
            }

            $cost->update([
                'paid_date' => null,
                'paid_at' => null,
                'payment_account_id' => null,
                'pph23_amount' => '0.00',
                'withholding_document_number' => null,
                'updated_by' => $actor->id,
                'lock_version' => $cost->lock_version + 1,
            ]);
            $this->touchJob($job, $actor);
            $this->master->log($actor, 'job_cost.unpaid', 'Unpaid '.$cost->number.' · '.$job->number, ['module' => 'job_cost', 'record_id' => $cost->id]);
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
            $paidAmount = null;
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
                $paidAmount = $this->paidAmount($cost);
                $wasPaid = $paidAmount->isPositive() || (bool) $cost->paid_at;
                if ($wasPaid) {
                    foreach ($this->activePostInvoiceHppJournals($cost) as $hppJournal) {
                        $this->reverseJournal($hppJournal, $cost, $actor, 'Penyesuaian biaya paid');
                    }
                }
                // Setelah biaya final diedit, biaya kembali menjadi Draft agar
                // Finance Manager memeriksa dan melakukan Finalisasi Biaya lagi.
                // Jika belum dibayar, pengakuan lama dibalik sekarang. Jika
                // sudah dibayar, pengakuan lama dipertahankan sampai finalisasi
                // ulang agar jurnal pembayaran tetap seimbang.
                if ($cost->status === 'final') {
                    if (! $wasPaid) {
                        $this->reverseRecognition($cost, $actor);
                    }
                    $cost->status = 'draft';
                    $cost->finalized_by = null;
                    $cost->finalized_at = null;
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
            if ($paidAmount && Money::decimal($cost->total_cost)->isLessThan($paidAmount)) {
                throw ValidationException::withMessages([
                    'unit_cost' => 'Total modal tidak boleh lebih kecil dari modal yang sudah dibayar Rp '.Money::format((string) $paidAmount).'. Tambahkan modal atau lakukan Unpaid terlebih dahulu.',
                ]);
            }
            $cost->updated_by = $actor->id;
            $cost->lock_version = $new ? 0 : $cost->lock_version + 1;
            $invoiceChanged = $new
                ? $cost->type === 'temporary' || Money::decimal($cost->total_price)->isPositive()
                : $cost->isDirty(['description', 'type', 'currency', 'quantity', 'unit', 'price_exchange_rate', 'foreign_unit_price', 'unit_price', 'total_price']);
            $cost->save();
            if (Money::decimal($cost->pph23_amount ?? 0)->isGreaterThan(Money::decimal($cost->total_cost))) {
                throw ValidationException::withMessages(['pph23_amount' => 'PPh 23 tidak boleh melebihi total biaya.']);
            }
            $this->summary($job); // Reject aggregate overflow inside the same transaction.
            $this->refreshIssuedInvoice($job, $actor, $invoiceChanged);
            $this->touchJob($job, $actor);
            $after = $cost->only(['description', 'type', 'cost_category', 'currency', 'cost_exchange_rate', 'price_exchange_rate', 'quantity', 'unit', 'unit_cost', 'unit_price', 'total_cost', 'total_price', 'status']);
            $this->master->log($actor, $new ? 'job_cost.created' : 'job_cost.updated', $cost->number.' · '.$job->number, ['module' => 'job_cost', 'record_id' => $cost->id, 'before' => $new ? null : $before, 'after' => $after]);

            return $cost;
        }, 3);
    }

    /** Pengakuan modal dan hutang vendor/agent; dipanggil saat Finance menyimpan biaya langsung atau memfinalisasi biaya quotation. */
    private function recognizePayable(JobCost $cost, User $actor, string $journalType, ?BigDecimal $amount = null): void
    {
        $amount ??= Money::decimal($cost->total_cost);
        if (! $amount->isPositive()) {
            return;
        }
        $vendor = $cost->vendor_id ? Vendor::find($cost->vendor_id) : null;
        $payableKey = $vendor ? $this->partyAccounts->payableMappingKey($vendor) : 'vendor_payable';
        $debitKey = $cost->type === 'temporary' ? 'temporary' : 'provision_wip';
        $maps = $this->journals->mapped([$debitKey]);
        $payableAccount = $vendor
            ? $this->partyAccounts->vendorPayable($vendor)
            : $this->journals->mapped([$payableKey])[$payableKey];
        $payableLabel = $vendor ? $this->partyAccounts->payableLabel($vendor) : 'Hutang vendor ';
        $this->journals->post($journalType, JobCost::class, $cost->id, $cost->cost_date->format('Y-m-d'), 'Pencatatan '.$cost->number, [
            ['account_id' => $maps[$debitKey]->id, 'description' => ($cost->type === 'temporary' ? 'Temporary Payment ' : 'Provisional Payment ').$cost->number, 'debit' => (string) $amount, 'credit' => 0],
            ['account_id' => $payableAccount->id, 'description' => $payableLabel.$cost->number, 'debit' => 0, 'credit' => (string) $amount],
        ], $actor);
    }

    private function activeRecognitionJournals(JobCost $cost): \Illuminate\Support\Collection
    {
        return Journal::with('entries')
            ->where('source_type', JobCost::class)
            ->where('source_id', $cost->id)
            ->whereIn('type', self::RECOGNITION_JOURNAL_TYPES)
            ->whereNull('reversed_at')
            ->whereNull('reversal_of_id')
            ->lockForUpdate()
            ->get();
    }

    private function recognitionAmount(JobCost $cost): BigDecimal
    {
        $amount = Money::decimal(0);
        foreach ($this->activeRecognitionJournals($cost) as $journal) {
            foreach ($journal->entries as $entry) {
                $amount = $amount->plus($entry->debit);
            }
        }

        return $amount;
    }

    private function activePaymentJournals(JobCost $cost): \Illuminate\Support\Collection
    {
        return Journal::with('entries')
            ->where('source_type', JobCost::class)
            ->where('source_id', $cost->id)
            ->where('type', 'job_cost_payment')
            ->whereNull('reversed_at')
            ->whereNull('reversal_of_id')
            ->lockForUpdate()
            ->get();
    }

    private function activePostInvoiceHppJournals(JobCost $cost): \Illuminate\Support\Collection
    {
        return Journal::with('entries')
            ->where('type', self::POST_INVOICE_HPP_JOURNAL_TYPE)
            ->where('source_type', JobCost::class)
            ->where('source_id', $cost->id)
            ->whereNull('reversed_at')
            ->whereNull('reversal_of_id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * Biaya baru setelah invoice customer diterbitkan diperlakukan sebagai biaya
     * susulan. Modalnya tetap masuk Provisi saat finalisasi biaya, lalu dipindah
     * ke HPP Unclosed ketika biaya dibayar.
     */
    private function isSubsequentCost(Job $job, JobCost $cost): bool
    {
        $invoice = $job->invoice()->first();

        if (! $invoice) {
            return false;
        }

        $hasIssuedInvoice = $invoice->issued_at || Journal::where('source_type', Job::class)
            ->where('source_id', $job->id)->where('type', 'job_invoice')
            ->whereNull('reversal_of_id')->exists();
        if (! $hasIssuedInvoice) {
            return false;
        }

        if ($this->activePostInvoiceHppJournals($cost)->isNotEmpty()) {
            return false;
        }

        if ($this->hasActiveInvoiceHppEntry($job, $cost)) {
            return false;
        }

        return true;
    }

    /** Biaya susulan tetap di Provisi sampai Paid; reklasifikasi tidak boleh dicatat dua kali. */
    public function invoiceHppCosts(Job $job, \Illuminate\Support\Collection $rows): \Illuminate\Support\Collection
    {
        $firstInvoiceJournal = Journal::with('entries')
            ->where('source_type', Job::class)->where('source_id', $job->id)
            ->where('type', 'job_invoice')->whereNull('reversal_of_id')
            ->orderBy('id')->first();

        return $rows->filter(function (JobCost $cost) use ($firstInvoiceJournal, $job) {
            if ($cost->type !== 'provision' || $cost->status !== 'final') {
                return false;
            }
            if ($this->hasActivePaymentHppReclassification($cost) || $this->activePostInvoiceHppJournals($cost)->isNotEmpty()) {
                return false;
            }
            if (! $firstInvoiceJournal) {
                return true;
            }

            // Gunakan isi jurnal invoice pertama, bukan issued_at yang berubah
            // setiap finalisasi ulang atau timestamp biaya dengan detik yang sama.
            return $firstInvoiceJournal->entries->contains(function ($entry) use ($cost, $job) {
                $description = (string) $entry->description;
                return str_starts_with($description, 'HPP Unclose')
                    && (str_contains($description, '· '.$cost->number.' ·')
                        || $description === 'HPP Unclose · '.strtoupper($cost->description).' · '.$job->number);
            });
        });
    }

    private function hasActiveInvoiceHppEntry(Job $job, JobCost $cost): bool
    {
        $flow = $this->invoiceFlowAccountKeys($job);
        $hppAccount = $this->journals->mapped([$flow['cogs_unclosed']])[$flow['cogs_unclosed']];
        $costNumber = (string) $cost->number;
        $legacyDescription = strtoupper((string) $cost->description);

        return Journal::query()
            ->where('source_type', Job::class)
            ->where('source_id', $job->id)
            ->whereIn('type', ['job_invoice', 'job_invoice_amendment'])
            ->whereNull('reversed_at')
            ->whereNull('reversal_of_id')
            ->whereHas('entries', fn ($query) => $query
                ->where('chart_of_account_id', $hppAccount->id)
                ->where('description', 'like', '%HPP Unclose%')
                ->where(function ($description) use ($costNumber, $legacyDescription, $job) {
                    $description->where('description', 'like', '%'.$costNumber.'%')
                        ->orWhere(fn ($fallback) => $fallback
                            ->where('description', 'like', '%'.$legacyDescription.'%')
                            ->where('description', 'like', '%'.$job->number.'%'));
                }))
            ->exists();
    }

    private function postPaidSubsequentHppReclassification(Job $job, JobCost $cost, User $actor): void
    {
        if ($cost->type !== 'provision' || ! $cost->paid_at || $this->hasActivePaymentHppReclassification($cost) || ! $this->isSubsequentCost($job, $cost)) {
            return;
        }

        $flow = $this->invoiceFlowAccountKeys($job);
        $maps = $this->journals->mapped(['provision_wip', $flow['cogs_unclosed']]);
        $amount = Money::decimal($cost->total_cost);
        if (! $amount->isPositive()) {
            return;
        }

        $this->journals->post(self::POST_INVOICE_HPP_JOURNAL_TYPE, JobCost::class, $cost->id, ($cost->paid_date ?: today())->format('Y-m-d'), 'Reklasifikasi Provisi ke HPP Unclosed '.$cost->number, [
            ['account_id' => $maps[$flow['cogs_unclosed']]->id, 'description' => 'HPP Unclose biaya susulan · '.$cost->number, 'debit' => (string) $amount, 'credit' => 0],
            ['account_id' => $maps['provision_wip']->id, 'description' => 'Reklasifikasi Provisi biaya susulan · '.$cost->number, 'debit' => 0, 'credit' => (string) $amount],
        ], $actor);
    }

    private function hasActivePaymentHppReclassification(JobCost $cost): bool
    {
        foreach ($this->activePaymentJournals($cost) as $journal) {
            if ($journal->entries->contains(fn ($entry) => str_starts_with((string) $entry->description, 'HPP Unclose biaya susulan'))) {
                return true;
            }
        }

        return false;
    }

    private function reverseJournal(Journal $journal, JobCost $cost, User $actor, string $reason): void
    {
        $this->journals->reverse($journal, [
            'reversal_date' => today()->toDateString(),
            'reason' => $reason.' · '.$cost->number,
            'lock_version' => $journal->lock_version,
        ], $actor);
    }

    /** Membalik jurnal pengakuan saat biaya yang sudah Finance Confirm diperbarui. */
    private function reverseRecognition(JobCost $cost, User $actor): void
    {
        $journals = $this->activeRecognitionJournals($cost);

        foreach ($journals as $journal) {
            $this->journals->reverse($journal, [
                'reversal_date' => today()->toDateString(),
                'reason' => 'Penyesuaian biaya '.$cost->number,
                'lock_version' => $journal->lock_version,
            ], $actor);
        }
    }

    /**
     * Invoice pra-closing mengikuti perubahan Biaya Job sampai ada pembayaran.
     * Baris invoice serta jurnal invoice lama dibalik terlebih dahulu agar audit
     * trail tetap ada dan nilai piutang, pendapatan, HPP mengikuti nilai terbaru.
     */
    private function refreshIssuedInvoice(Job $job, User $actor, bool $requiresApproval = false): void
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
        $invoiceRows = $rows->filter(fn (JobCost $cost) => $cost->type === 'temporary' || Money::decimal($cost->total_price)->isPositive())->values();
        $tax = Money::decimal($invoice->tax);
        $stampDuty = Money::decimal($invoice->stamp_duty ?? 0);
        $total = Money::decimal($summary['subtotal'])->plus($tax)->plus($stampDuty);
        if ($total->isZero()) {
            throw ValidationException::withMessages(['cost' => 'Perubahan membuat total invoice menjadi nol.']);
        }

        if ($requiresApproval && $invoice->status !== 'draft') {
            $journals = Journal::where('source_type', Job::class)
                ->where('source_id', $job->id)
                ->whereIn('type', ['job_invoice', 'job_invoice_amendment'])
                ->whereNull('reversed_at')->whereNull('reversal_of_id')
                ->lockForUpdate()->get();
            foreach ($journals as $journal) {
                $this->journals->reverse($journal, [
                    'reversal_date' => today()->toDateString(),
                    'reason' => 'Invoice menunggu finalisasi ulang setelah perubahan jual '.$invoice->number,
                    'lock_version' => $journal->lock_version,
                ], $actor);
            }
            $invoice->update(['status' => 'draft', 'issued_at' => null]);
            $this->master->log($actor, 'invoice.refinalization_required', 'Perubahan jual pada '.$invoice->number.' memerlukan finalisasi ulang Finance Manager.', [
                'module' => 'invoice', 'record_id' => $invoice->id,
            ]);
        }

        if ($invoice->status === 'draft') {
            [$currency, $rate] = $this->invoiceCurrencyAndRate($invoiceRows);
            $invoice->update([
                'currency' => $currency,
                'exchange_rate' => (string) $rate->toScale(2, RoundingMode::HalfUp),
                'subtotal' => $summary['subtotal'],
                'total' => Money::checked($total),
                'balance' => Money::checked($total),
                'lock_version' => $invoice->lock_version + 1,
            ]);
            $invoice->items()->delete();
            foreach ($invoiceRows as $position => $cost) {
                $invoice->items()->create($this->invoiceItemData($cost, $position + 1));
            }

            return;
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
            $this->journals->reverse($journal, [
                'reversal_date' => today()->toDateString(),
                'reason' => 'Penyesuaian invoice '.$invoice->number,
                'lock_version' => $journal->lock_version,
            ], $actor);
        }

        [$currency, $rate] = $this->invoiceCurrencyAndRate($invoiceRows);
        $invoice->update([
            'currency' => $currency,
            'exchange_rate' => (string) $rate->toScale(2, RoundingMode::HalfUp),
            'subtotal' => $summary['subtotal'],
            'total' => Money::checked($total),
            'balance' => Money::checked($total),
        ]);
        $invoice->items()->delete();
        foreach ($invoiceRows as $position => $cost) {
            $invoice->items()->create($this->invoiceItemData($cost, $position + 1));
        }

        $customer = $job->customer()->withTrashed()->firstOrFail();
        $customerAccounts = $this->partyAccounts->customerAccounts($customer);
        $flow = $this->invoiceFlowAccountKeys($job);
        $maps = $this->journals->mapped(array_merge(['temporary', 'provision_wip', 'temporary_receivable', 'receivable', 'tax_payable', 'stamp_duty_expense'], array_values($flow)));
        $temporary = Money::decimal($summary['temporary']);
        $provisionSell = Money::decimal($summary['provision_sell']);
        $invoiceRevenue = $provisionSell;
        // HPP Unclosed hanya dibentuk dari biaya yang sudah Final.
        $hppCosts = $this->invoiceHppCosts($job, $rows);
        $provisionCost = $hppCosts->reduce(
            fn ($total, $cost) => $total->plus(Money::decimal($cost->total_cost)),
            Money::decimal(0)
        );
        $entries = [];
        if ($invoiceRevenue->isPositive() || $stampDuty->isPositive() || $tax->isPositive()) {
            $entries[] = ['account_id' => $customerAccounts['receivable']->id, 'description' => 'Piutang '.$invoice->number, 'debit' => (string) $invoiceRevenue->plus($stampDuty)->plus($tax), 'credit' => 0];
        }
        if ($temporary->isPositive()) {
            $entries[] = ['account_id' => $customerAccounts['temporary']->id, 'description' => 'Piutang Temporary '.$invoice->number, 'debit' => (string) $temporary, 'credit' => 0];
            $entries[] = ['account_id' => $maps['temporary']->id, 'description' => 'Reklasifikasi temporary '.$invoice->number, 'debit' => 0, 'credit' => (string) $temporary];
        }
        foreach ($rows->where('type', 'provision') as $cost) {
            $sellingValue = Money::decimal($cost->total_price);
            if ($sellingValue->isPositive()) $entries[] = ['account_id' => $maps[$flow['revenue_unclosed']]->id, 'description' => 'Pendapatan Unclose · '.strtoupper($cost->description).' · '.$job->number, 'debit' => 0, 'credit' => (string) $sellingValue];
            if (! $hppCosts->contains('id', $cost->id)) continue;
            $costValue = Money::decimal($cost->total_cost);
            if ($costValue->isPositive()) $entries[] = ['account_id' => $maps[$flow['cogs_unclosed']]->id, 'description' => 'HPP Unclose · '.$cost->number.' · '.strtoupper($cost->description).' · '.$job->number, 'debit' => (string) $costValue, 'credit' => 0];
        }
        if ($stampDuty->isPositive()) $entries[] = ['account_id' => $maps['stamp_duty_expense']->id, 'description' => 'Materai ditagihkan · '.$job->number, 'debit' => 0, 'credit' => (string) $stampDuty];
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
            $recognitionAmount = $this->recognitionAmount($cost);
            $totalAmount = Money::decimal($cost->total_cost);
            if (! $recognitionAmount->isEqualTo($totalAmount)) {
                $this->reverseRecognition($cost, $actor);
                $this->recognizePayable($cost, $actor, $recognitionAmount->isZero() ? 'job_cost_finalization' : 'job_cost_amendment', $totalAmount);
            }
            // Bila biaya sudah terlanjur dibayar sebelum Finance Manager
            // memfinalisasikannya, tetap lakukan reklasifikasi Provisi ke HPP
            // sekarang agar saldo tidak tertahan di Provisi.
            $this->postPaidSubsequentHppReclassification($job, $cost, $actor);
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
