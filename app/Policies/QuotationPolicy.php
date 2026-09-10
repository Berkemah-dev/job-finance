<?php

namespace App\Policies;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('quotations.manage');
    }

    public function view(User $user, Quotation $quotation): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Quotation $quotation): bool
    {
        return $this->viewAny($user) && in_array($quotation->status, [QuotationStatus::Draft, QuotationStatus::Revision], true);
    }

    public function submit(User $user, Quotation $quotation): bool
    {
        return $this->update($user, $quotation);
    }

    public function approve(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.approve') && $quotation->status === QuotationStatus::Submitted;
    }

    public function reject(User $user, Quotation $quotation): bool
    {
        return $this->approve($user, $quotation);
    }

    public function revise(User $user, Quotation $quotation): bool
    {
        return $this->approve($user, $quotation);
    }

    public function convert(User $user, Quotation $quotation): bool
    {
        return $this->viewAny($user) && $user->hasPermission('jobs.manage') && $quotation->status === QuotationStatus::Approved;
    }
}
