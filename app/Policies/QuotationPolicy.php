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
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($user->hasRole('sales') && ! $user->hasRole(['sales-manager', 'super-admin', 'admin'])) {
            return $quotation->sales_id === $user->id || ($quotation->sales_id === null && $quotation->created_by === $user->id);
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Quotation $quotation): bool
    {
        if (! $this->view($user, $quotation)) {
            return false;
        }

        if (in_array($quotation->status, [QuotationStatus::Draft, QuotationStatus::Revision], true)) {
            return true;
        }

        // Sales Manager / Admin can re-edit an approved or converted quotation if the job is not yet created or has been cancelled
        if (in_array($quotation->status, [QuotationStatus::Approved, QuotationStatus::Converted], true) && ($user->hasRole(['sales-manager', 'super-admin', 'admin']) || $user->hasPermission('quotations.approve'))) {
            return ! $quotation->job || $quotation->job->status === 'cancelled';
        }

        return false;
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
        if ($user->hasRole(['sales', 'sales-manager']) && ! $user->hasRole(['super-admin', 'admin', 'customer-service', 'operational'])) {
            return false;
        }

        return $this->viewAny($user) 
            && $user->hasPermission('jobs.manage') 
            && in_array($quotation->status, [QuotationStatus::Approved, QuotationStatus::Converted], true)
            && (! $quotation->job || $quotation->job->status === 'cancelled');
    }
}
