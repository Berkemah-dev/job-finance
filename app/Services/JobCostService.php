<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobCost;
use App\Models\Journal;
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
            Gate::forUser($actor)->authorize('costs.manage'); $job = $this->lockJob($job, $data);
            $cost = $job->costs()->lockForUpdate()->findOrFail($cost->id);
            if ($cost->paid_at) throw ValidationException::withMessages(['cost' => 'Biaya ini sudah dibayar.']);
            $hasDraftPayable = Journal::where('source_type', JobCost::class)->where('source_id', $cost->id)->where('type', 'job_cost_draft')->exists();
            if ($job->status !== 'closed' && ! $hasDraftPayable) {
                throw ValidationException::withMessages(['cost' => 'Biaya biasa dapat dibayar setelah Closing Job. Payment Request dan Reimbursement dapat dibayar sejak Draft karena Hutang Vendor sudah terbentuk.']);
            }
            $account = \App\Models\ChartOfAccount::where('type', 'asset')->findOrFail($data['payment_account_id']);
            $amount = Money::decimal($cost->total_cost); $pph = Money::decimal($data['pph23_amount'] ?? 0);
            if ($pph->isGreaterThan($amount)) throw ValidationException::withMessages(['pph23_amount' => 'PPh 23 tidak boleh melebihi biaya.']);
            if ($hasDraftPayable && ! $pph->isEqualTo(Money::decimal($cost->pph23_amount ?? 0))) {
                throw ValidationException::withMessages(['pph23_amount' => 'PPh 23 ditetapkan saat Draft Payment Request dan tidak dapat diubah saat pembayaran.']);
            }
            $maps = $this->journals->mapped(['vendor_payable','tax_payable']);
            $vendorSettlement = $hasDraftPayable ? $amount->minus($pph) : $amount;
            $entries = [['account_id'=>$maps['vendor_payable']->id,'description'=>'Pelunasan hutang vendor '.$cost->number,'debit'=>(string)$vendorSettlement,'credit'=>0], ['account_id'=>$account->id,'description'=>'Pembayaran biaya job '.$cost->number,'debit'=>0,'credit'=>(string)$vendorSettlement]];
            if (! $hasDraftPayable && $pph->isPositive()) $entries[]=['account_id'=>$maps['tax_payable']->id,'description'=>'PPh 23 hutang '.$cost->number,'debit'=>0,'credit'=>(string)$pph];
            $number = $this->numbers->nextBankJournal($account->code,$account->name,false,\Carbon\Carbon::parse($data['paid_date']));
            $this->journals->post('job_cost_payment', JobCost::class,$cost->id,$data['paid_date'],'Pembayaran '.$cost->number,$entries,$actor,$number);
            $cost->update(['paid_date'=>$data['paid_date'],'paid_at'=>now(),'payment_account_id'=>$account->id,'pph23_amount'=>(string)$pph]); $this->touchJob($job,$actor);
        },3);
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
            $costCategory = $data['cost_category'];
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
                $before = $cost->only(['description', 'type', 'quantity', 'unit', 'unit_cost', 'unit_price', 'status']);
                if (Journal::where('source_type', JobCost::class)->where('source_id', $cost->id)->where('type', 'job_cost_draft')->exists()) {
                    throw ValidationException::withMessages(['cost' => 'Transaksi Draft yang sudah memiliki jurnal tidak dapat diedit. Buat transaksi koreksi baru.']);
                }
            }
            $date = Carbon::parse($data['cost_date']);
            if ($date->isBefore($job->job_date) || $date->isAfter(today())) {
                throw ValidationException::withMessages(['cost_date' => 'Tanggal biaya harus antara tanggal job dan hari ini.']);
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
            $cost->fill(Arr::only($data, ['description', 'type', 'cost_category', 'cost_date', 'quantity', 'unit', 'unit_cost', 'unit_price', 'pph23_amount', 'payee', 'vendor_id', 'reference', 'notes']));
            $cost->total_cost = Money::checked($quantity->multipliedBy($unitCost)->toScale(2, RoundingMode::HalfUp));
            $cost->total_price = Money::checked($quantity->multipliedBy($unitPrice)->toScale(2, RoundingMode::HalfUp));
            $cost->updated_by = $actor->id;
            $cost->lock_version = $new ? 0 : $cost->lock_version + 1;
            $cost->save();
            if ($new && in_array($cost->cost_category, ['payment_request', 'reimbursement'], true)) {
                $pph = Money::decimal($cost->pph23_amount ?? 0);
                if ($pph->isGreaterThan(Money::decimal($cost->total_cost))) throw ValidationException::withMessages(['pph23_amount' => 'PPh 23 tidak boleh melebihi total biaya.']);
                $maps = $this->journals->mapped([$cost->cost_category === 'payment_request' ? 'provision_wip' : 'temporary', 'vendor_payable', 'tax_payable']);
                $debitKey = $cost->cost_category === 'payment_request' ? 'provision_wip' : 'temporary';
                $entries = [['account_id'=>$maps[$debitKey]->id,'description'=>($cost->cost_category === 'payment_request' ? 'Provisional Payment ' : 'Temporary Payment ').$cost->number,'debit'=>$cost->total_cost,'credit'=>0],['account_id'=>$maps['vendor_payable']->id,'description'=>'Hutang vendor '.$cost->number,'debit'=>0,'credit'=>(string) Money::decimal($cost->total_cost)->minus($pph)]];
                if ($pph->isPositive()) $entries[]=['account_id'=>$maps['tax_payable']->id,'description'=>'PPh 23 hutang '.$cost->number,'debit'=>0,'credit'=>(string)$pph];
                $this->journals->post('job_cost_draft', JobCost::class, $cost->id, $cost->cost_date->format('Y-m-d'), 'Draft '.$cost->number, $entries, $actor);
            }
            $this->summary($job); // Reject aggregate overflow inside the same transaction.
            $this->touchJob($job, $actor);
            $after = $cost->only(['description', 'type', 'cost_category', 'quantity', 'unit', 'unit_cost', 'unit_price', 'total_cost', 'total_price', 'status']);
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
