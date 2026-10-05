<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobCost;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\User;
use App\Support\Money;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class JobCostService
{
    public function __construct(private MasterDataService $master, private DocumentNumberService $numbers, private JournalService $journals) {}

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

            $hasDraftPayable = Journal::where('source_type', JobCost::class)->where('source_id', $cost->id)
                ->whereIn('type', ['job_cost_draft', 'job_cost_finalization'])->exists();
            $pph = Money::decimal($data['pph23_amount'] ?? $cost->pph23_amount ?? 0);
            // PPh baru menjadi Hutang Pajak ketika PAID. Nilai ini hanya untuk
            // transaksi lama yang telah mencatat PPh sebelum alur finalisasi.
            $draftPph = Money::decimal((string) JournalEntry::query()
                ->where('chart_of_account_id', $this->journals->mapped(['pph23_payable'])['pph23_payable']->id)
                ->where('credit', '>', 0)
                ->whereHas('journal', fn ($query) => $query->where('source_type', JobCost::class)->where('source_id', $cost->id))
                ->sum('credit'));
            $amount = Money::decimal($cost->total_cost);
            if ($pph->isGreaterThan($amount)) {
                throw ValidationException::withMessages(['pph23_amount' => 'PPh 23 tidak boleh melebihi biaya.']);
            }
            if ($hasDraftPayable && $pph->isLessThan($draftPph)) {
                throw ValidationException::withMessages(['pph23_amount' => 'PPh 23 tidak dapat dikurangi karena jurnal Draft sudah tercatat.']);
            }

            // Data lama yang sudah final tetapi belum punya jurnal tetap dapat
            // diselesaikan tanpa melewati proses Finance lagi.
            if (! $hasDraftPayable) {
                $mapsDraft = $this->journals->mapped([$cost->type === 'temporary' ? 'temporary' : 'provision_wip', 'vendor_payable']);
                $debitKey = $cost->type === 'temporary' ? 'temporary' : 'provision_wip';
                $draftEntries = [
                    ['account_id' => $mapsDraft[$debitKey]->id, 'description' => ($cost->type === 'temporary' ? 'Temporary Payment ' : 'Provisional Payment ').$cost->number, 'debit' => (string) $amount, 'credit' => 0],
                    ['account_id' => $mapsDraft['vendor_payable']->id, 'description' => 'Hutang vendor '.$cost->number, 'debit' => 0, 'credit' => (string) $amount],
                ];
                $costDate = $cost->cost_date ? $cost->cost_date->format('Y-m-d') : $data['paid_date'];
                $this->journals->post('job_cost_finalization', JobCost::class, $cost->id, $costDate, 'Finalisasi '.$cost->number, $draftEntries, $actor);
                $hasDraftPayable = true;
                $draftPph = Money::decimal(0);
            }

            $account = \App\Models\ChartOfAccount::where('type', 'asset')->findOrFail($data['payment_account_id']);
            $maps = $this->journals->mapped(['vendor_payable', 'pph23_payable']);
            // Hutang vendor yang telah terbentuk pada Draft harus dilunasi penuh.
            // PPh yang baru diinput ketika PAID dipindahkan ke Hutang Pajak.
            $vendorSettlement = $amount->minus($draftPph);
            $bankPayment = $amount->minus($pph);
            $additionalPph = $pph->minus($draftPph);

            $entries = [
                ['account_id' => $maps['vendor_payable']->id, 'description' => 'Pelunasan hutang vendor '.$cost->number, 'debit' => (string) $vendorSettlement, 'credit' => 0],
                ['account_id' => $account->id, 'description' => 'Pembayaran biaya job '.$cost->number, 'debit' => 0, 'credit' => (string) $bankPayment],
            ];
            if ($additionalPph->isPositive()) {
                $entries[] = ['account_id' => $maps['pph23_payable']->id, 'description' => 'PPh 23 hutang '.$cost->number, 'debit' => 0, 'credit' => (string) $additionalPph];
            }

            $number = $this->numbers->nextBankJournal($account->code, $account->name, false, \Carbon\Carbon::parse($data['paid_date']));
            $this->journals->post('job_cost_payment', JobCost::class, $cost->id, $data['paid_date'], 'Pembayaran '.$cost->number, $entries, $actor, $number);
            $cost->update([
                'paid_date' => $data['paid_date'],
                'paid_at' => now(),
                'payment_account_id' => $account->id,
                'pph23_amount' => (string) $pph,
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
            $currency = strtoupper((string) ($data['currency'] ?? $cost->currency ?? 'IDR'));
            $exchangeRate = $currency === 'IDR' ? Money::decimal('1') : Money::decimal($data['exchange_rate'] ?? $cost->exchange_rate ?? '1');
            if ($exchangeRate->isZero()) {
                throw ValidationException::withMessages(['exchange_rate' => 'Kurs tidak boleh 0.']);
            }
            $unitCost = Money::decimal($data['unit_cost']);
            $unitPrice = Money::decimal($data['unit_price']);
            $quantity = Money::decimal($data['quantity']);
            $data['cost_category'] = $costCategory;
            $data['type'] = $costCategory === 'reimbursement' ? 'temporary' : 'provision';
            if ($data['type'] === 'temporary' && ! $unitCost->isEqualTo($unitPrice)) {
                throw ValidationException::withMessages(['unit_price' => 'Nilai jual Temporary harus sama dengan modal karena ditagihkan kembali tanpa profit.']);
            }
            if (! empty($data['vendor_id']) && empty($data['payee'])) {
                $vendor = \App\Models\Vendor::find($data['vendor_id']);
                if ($vendor) {
                    $data['payee'] = $vendor->name;
                }
            }
            if (array_key_exists('pph23_amount', $data)) {
                $data['pph23_amount'] = ($data['pph23_amount'] !== null && $data['pph23_amount'] !== '')
                    ? (string) Money::decimal($data['pph23_amount'])
                    : '0.00';
            } else {
                $data['pph23_amount'] = $cost->pph23_amount ? (string) Money::decimal($cost->pph23_amount) : '0.00';
            }
            $cost->fill(Arr::only($data, ['description', 'type', 'cost_category', 'cost_date', 'quantity', 'unit', 'unit_cost', 'unit_price', 'pph23_amount', 'payee', 'vendor_id', 'reference', 'notes']));
            $cost->currency = $currency;
            $cost->exchange_rate = $exchangeRate->toScale(4, RoundingMode::HalfUp);
            $cost->pph23_amount = $data['pph23_amount'];
            $cost->total_cost = Money::checked($quantity->multipliedBy($unitCost)->multipliedBy($exchangeRate)->toScale(2, RoundingMode::HalfUp));
            $cost->total_price = Money::checked($quantity->multipliedBy($unitPrice)->multipliedBy($exchangeRate)->toScale(2, RoundingMode::HalfUp));
            $cost->updated_by = $actor->id;
            $cost->lock_version = $new ? 0 : $cost->lock_version + 1;
            $cost->save();
            $draftJournal = Journal::where('source_type', JobCost::class)->where('source_id', $cost->id)->where('type', 'job_cost_draft')->first();
            // Biaya yang berasal dari quotation hanya disimpan sebagai Draft.
            // Pengakuan COA dilakukan saat Finance menekan Finalisasi Biaya.
            if ($new && ! $cost->quotation_id && in_array($cost->cost_category, ['payment_request', 'reimbursement'], true)) {
                $pph = Money::decimal($cost->pph23_amount ?? 0);
                if ($pph->isGreaterThan(Money::decimal($cost->total_cost))) throw ValidationException::withMessages(['pph23_amount' => 'PPh 23 tidak boleh melebihi total biaya.']);
                $maps = $this->journals->mapped([$cost->cost_category === 'payment_request' ? 'provision_wip' : 'temporary', 'vendor_payable', 'pph23_payable']);
                $debitKey = $cost->cost_category === 'payment_request' ? 'provision_wip' : 'temporary';
                $entries = [['account_id'=>$maps[$debitKey]->id,'description'=>($cost->cost_category === 'payment_request' ? 'Provisional Payment ' : 'Temporary Payment ').$cost->number,'debit'=>$cost->total_cost,'credit'=>0],['account_id'=>$maps['vendor_payable']->id,'description'=>'Hutang vendor '.$cost->number,'debit'=>0,'credit'=>(string) Money::decimal($cost->total_cost)->minus($pph)]];
                if ($pph->isPositive()) $entries[]=['account_id'=>$maps['pph23_payable']->id,'description'=>'PPh 23 hutang '.$cost->number,'debit'=>0,'credit'=>(string)$pph];
                $this->journals->post('job_cost_draft', JobCost::class, $cost->id, $cost->cost_date->format('Y-m-d'), 'Draft '.$cost->number, $entries, $actor);
            } elseif (! $new && ! $cost->quotation_id && $draftJournal) {
                $pph = Money::decimal($cost->pph23_amount ?? 0);
                $maps = $this->journals->mapped([$cost->cost_category === 'payment_request' ? 'provision_wip' : 'temporary', 'vendor_payable', 'pph23_payable']);
                $debitKey = $cost->cost_category === 'payment_request' ? 'provision_wip' : 'temporary';
                $entries = [['account_id'=>$maps[$debitKey]->id,'description'=>($cost->cost_category === 'payment_request' ? 'Provisional Payment ' : 'Temporary Payment ').$cost->number,'debit'=>$cost->total_cost,'credit'=>0],['account_id'=>$maps['vendor_payable']->id,'description'=>'Hutang vendor '.$cost->number,'debit'=>0,'credit'=>(string) Money::decimal($cost->total_cost)->minus($pph)]];
                if ($pph->isPositive()) $entries[]=['account_id'=>$maps['pph23_payable']->id,'description'=>'PPh 23 hutang '.$cost->number,'debit'=>0,'credit'=>(string)$pph];
                $draftJournal->entries()->delete();
                foreach ($entries as $i => $entry) {
                    $draftJournal->entries()->create([
                        'position' => $i + 1,
                        'chart_of_account_id' => $entry['account_id'],
                        'description' => $entry['description'],
                        'debit' => $entry['debit'],
                        'credit' => $entry['credit'],
                    ]);
                }
            }
            $this->summary($job); // Reject aggregate overflow inside the same transaction.
            $this->touchJob($job, $actor);
            $after = $cost->only(['description', 'type', 'cost_category', 'currency', 'exchange_rate', 'quantity', 'unit', 'unit_cost', 'unit_price', 'total_cost', 'total_price', 'status']);
            $this->master->log($actor, $new ? 'job_cost.created' : 'job_cost.updated', $cost->number.' · '.$job->number, ['module' => 'job_cost', 'record_id' => $cost->id, 'before' => $new ? null : $before, 'after' => $after]);

            return $cost;
        }, 3);
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
                ->whereIn('type', ['job_cost_draft', 'job_cost_finalization'])->exists();
            if (! $hasRecognition) {
                $maps = $this->journals->mapped([$cost->type === 'temporary' ? 'temporary' : 'provision_wip', 'vendor_payable']);
                $debitKey = $cost->type === 'temporary' ? 'temporary' : 'provision_wip';
                $this->journals->post('job_cost_finalization', JobCost::class, $cost->id, $cost->cost_date->format('Y-m-d'), 'Finalisasi '.$cost->number, [
                    ['account_id' => $maps[$debitKey]->id, 'description' => ($cost->type === 'temporary' ? 'Temporary Payment ' : 'Provisional Payment ').$cost->number, 'debit' => $cost->total_cost, 'credit' => 0],
                    ['account_id' => $maps['vendor_payable']->id, 'description' => 'Hutang vendor '.$cost->number, 'debit' => 0, 'credit' => $cost->total_cost],
                ], $actor);
            }
            $this->touchJob($job, $actor);
            $this->master->log($actor, 'job_cost.finalized', 'Finalisasi '.$cost->number.' · '.$job->number, ['module' => 'job_cost', 'record_id' => $cost->id, 'after' => $cost->only(['type', 'total_cost', 'total_price', 'status'])]);
        }, 3);
    }

    public function approve(Job $job, JobCost $cost, array $data, User $actor): void
    {
        DB::transaction(function () use ($job, $cost, $data, $actor) {
            $job = $this->lockJob($job, $data); $cost = $job->costs()->lockForUpdate()->findOrFail($cost->id);
            Gate::forUser($actor)->authorize('approve', $cost); $this->master->checkVersion($cost, $data);
            $cost->update(['status'=>'approved','approved_by'=>$actor->id,'approved_at'=>now(),'updated_by'=>$actor->id,'lock_version'=>$cost->lock_version + 1]);
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
