<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\JobCost;
use App\Models\User;

class JobCostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('costs.manage');
    }

    public function view(User $user, JobCost $cost): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user, Job $job): bool
    {
        return $this->viewAny($user)
            && $job->status === 'open'
            && ! $this->hasPaidInvoice($job);
    }

    public function update(User $user, JobCost $cost): bool
    {
        // Finance Confirm dan Finalisasi Invoice tetap fleksibel sebelum
        // ada pembayaran invoice. Setelah ada pembayaran, nominal dikunci.
        return $this->create($user, $cost->job)
            && in_array($cost->status, ['draft', 'final'], true)
            && ! $cost->paid_at
            && ! $cost->trashed();
    }

    public function delete(User $user, JobCost $cost): bool
    {
        return $this->update($user, $cost)
            && $cost->status === 'draft'
            && ! $cost->job->invoice()->exists();
    }

    public function finalize(User $user, JobCost $cost): bool
    {
        // Konfirmasi Finance tetap dapat dilakukan setelah vendor dibayar.
        // Yang dilarang setelah PAID hanya perubahan nominal biaya.
        return $this->viewAny($user)
            && $cost->job->status === 'open'
            && $cost->status === 'draft'
            && ! $this->hasPaidInvoice($cost->job)
            && ! $cost->trashed();
    }

    public function approve(User $user, JobCost $cost): bool
    {
        return $user->hasPermission('jobs.close') && $cost->status === 'draft' && ! $cost->trashed();
    }

    private function hasPaidInvoice(Job $job): bool
    {
        return $job->invoice()
            ->where(fn ($invoice) => $invoice->where('paid_amount', '>', 0)->orWhereHas('payments'))
            ->exists();
    }
}
