<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'authorizer_name',
        'authorizer_title',
        'tax_number',
        'default_payment_terms',
        'approval_status',
        'approved_by',
        'approved_at',
        'npwp_file',
        'nib_file',
    ];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }

    public function isApproved(): bool
    {
        return ($this->approval_status ?? 'approved') === 'approved';
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function shippers(): HasMany
    {
        return $this->hasMany(CustomerContact::class)->where('type', 'shipper');
    }

    public function consignees(): HasMany
    {
        return $this->hasMany(CustomerContact::class)->where('type', 'consignee');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function scopeForUser($query, ?User $user)
    {
        if (! $user) {
            return $query;
        }

        if ($user->hasRole('sales') && ! $user->hasRole(['sales-manager', 'super-admin', 'admin'])) {
            return $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhereHas('quotations', function ($quotation) use ($user) {
                        $quotation->where('sales_id', $user->id)
                            ->orWhere(function ($nested) use ($user) {
                                $nested->whereNull('sales_id')->where('created_by', $user->id);
                            });
                    });
            });
        }

        return $query;
    }

    public function isAccessibleBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (! $user->hasRole('sales') || $user->hasRole(['sales-manager', 'super-admin', 'admin'])) {
            return true;
        }

        return $this->created_by === $user->id
            || $this->quotations()
                ->where(function ($q) use ($user) {
                    $q->where('sales_id', $user->id)
                        ->orWhere(function ($nested) use ($user) {
                            $nested->whereNull('sales_id')->where('created_by', $user->id);
                        });
                })
                ->exists();
    }
}
