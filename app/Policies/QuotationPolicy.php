<?php

namespace App\Policies;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('quotations.view')
            || $user->hasPermission('quotations.manage')
            || $user->hasRole(['super-admin', 'admin']);
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
        return $this->viewAny($user)
            && ! $user->hasRole(['customer-service', 'finance', 'finance-manager']);
    }

    public function update(User $user, Quotation $quotation): bool
    {
        if (! $this->view($user, $quotation)) {
            return false;
        }

        if ($user->hasRole('customer-service')) {
            return false;
        }

        if ($user->hasRole(['finance', 'finance-manager']) && ! $user->hasRole(['super-admin', 'admin'])) {
            return false;
        }

        if (in_array($quotation->status, [QuotationStatus::Draft, QuotationStatus::Revision], true)) {
            return true;
        }

        // Sales Manager/Admin may correct a quotation that is already submitted or accepted.
        // The Job Order keeps its own snapshot, so its existing data is not changed here.
        if (in_array($quotation->status, [QuotationStatus::Submitted, QuotationStatus::Approved], true) && ($user->hasRole(['sales-manager', 'super-admin', 'admin']) || $user->hasPermission('quotations.approve'))) {
            return true;
        }

        return false;
    }

    public function submit(User $user, Quotation $quotation): bool
    {
        return $this->view($user, $quotation)
            && $user->hasRole('sales')
            && ! $user->hasRole(['sales-manager', 'super-admin', 'admin'])
            && in_array($quotation->status, [QuotationStatus::Draft, QuotationStatus::Revision], true);
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
