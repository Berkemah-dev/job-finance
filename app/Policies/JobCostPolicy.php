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
        // Biaya tetap dapat direvisi selama invoice customer belum dibayar.
        // Bila biaya vendor sudah PAID, service menjaga batas agar modal baru
        // tidak lebih kecil dari nilai yang sudah dibayarkan.
        return $this->create($user, $cost->job)
            && in_array($cost->status, ['draft', 'approved', 'final'], true)
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
        // Penyesuaian modal dan pembentukan ulang jurnal ditangani service.
        return $this->viewAny($user)
            && $cost->job->status === 'open'
            && $cost->status === 'draft'
            && ! $this->hasPaidInvoice($cost->job)
            && ! $cost->trashed();
    }

    public function unpaid(User $user, JobCost $cost): bool
    {
        return $this->viewAny($user)
            && $cost->job->status === 'open'
            && (bool) $cost->paid_at
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
