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

            if ($cost->status !== 'final') {
                throw ValidationException::withMessages(['cost' => 'Biaya harus difinalisasi Finance sebelum dapat dibayar.']);
            }

            if (Money::decimal($cost->total_cost)->isZero()) {
                throw ValidationException::withMessages(['cost' => 'Biaya tagih tanpa modal tidak memiliki hutang vendor dan tidak perlu ditandai PAID.']);
            }

            $hasDraftPayable = Journal::where('source_type', JobCost::class)->where('source_id', $cost->id)
                ->whereIn('type', ['job_cost_draft', 'job_cost_direct', 'job_cost_finalization'])->exists();
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
                $before = $cost->only(['description', 'type', 'currency', 'exchange_rate', 'quantity', 'unit', 'unit_cost', 'unit_price', 'status']);
                if ($cost->paid_at) {
                    throw ValidationException::withMessages(['cost' => 'Biaya yang sudah dibayar tidak dapat diedit.']);
                }
            }
            $date = Carbon::parse($data['cost_date']);
            if ($date->isBefore($job->job_date) || $date->isAfter(today())) {
                throw ValidationException::withMessages(['cost_date' => 'Tanggal biaya harus antara tanggal job dan hari ini.']);
            }
            $currency = strtoupper((string) ($data['currency'] ?? 'IDR'));
            $exchangeRate = $currency === 'IDR'
                ? BigDecimal::one()
                : BigDecimal::of($data['exchange_rate'])->toScale(4, RoundingMode::HalfUp);
            $foreignUnitCost = Money::decimal($data['unit_cost']);
            $foreignUnitPrice = Money::decimal($data['unit_price']);
            $unitCost = $foreignUnitCost->multipliedBy($exchangeRate)->toScale(2, RoundingMode::HalfUp);
            $unitPrice = $foreignUnitPrice->multipliedBy($exchangeRate)->toScale(2, RoundingMode::HalfUp);
            $quantity = Money::decimal($data['quantity']);
            $data['cost_category'] = $costCategory;
            $data['type'] = $costCategory === 'reimbursement' ? 'temporary' : 'provision';
            if ($data['type'] === 'temporary' && ! $unitCost->isEqualTo($unitPrice)) {
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
            $cost->exchange_rate = (string) $exchangeRate;
            $cost->foreign_unit_cost = Money::checked($foreignUnitCost);
            $cost->foreign_unit_price = Money::checked($foreignUnitPrice);
            $cost->unit_cost = Money::checked($unitCost);
            $cost->unit_price = Money::checked($unitPrice);
            $cost->total_cost = Money::checked($quantity->multipliedBy($unitCost)->toScale(2, RoundingMode::HalfUp));
            $cost->total_price = Money::checked($quantity->multipliedBy($unitPrice)->toScale(2, RoundingMode::HalfUp));
            $cost->foreign_total_cost = Money::checked($quantity->multipliedBy($foreignUnitCost)->toScale(2, RoundingMode::HalfUp));
            $cost->foreign_total_price = Money::checked($quantity->multipliedBy($foreignUnitPrice)->toScale(2, RoundingMode::HalfUp));
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
            $this->summary($job); // Reject aggregate overflow inside the same transaction.
            $this->touchJob($job, $actor);
            $after = $cost->only(['description', 'type', 'cost_category', 'currency', 'exchange_rate', 'quantity', 'unit', 'unit_cost', 'unit_price', 'total_cost', 'total_price', 'status']);
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
                ->whereIn('type', ['job_cost_draft', 'job_cost_direct', 'job_cost_finalization'])->exists();
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
