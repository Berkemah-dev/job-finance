<?php

namespace App\Models;

use App\Enums\QuotationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'sales_id', 'subject', 'quotation_date', 'valid_until', 'notes',
        'shipper_name', 'shipper_address', 'consignee_name', 'consignee_address',
        'service_type', 'origin', 'destination', 'currency', 'exchange_rate', 'payment_terms',
        'terms_of_delivery', 'cargo_qty', 'weight_meas', 'commodity',
        'discount', 'tax_rate',
    ];

    protected function casts(): array
    {
        return ['status' => QuotationStatus::class, 'customer_snapshot' => 'array', 'quotation_date' => 'date', 'valid_until' => 'date',
            'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'rejected_at' => 'datetime', 'converted_at' => 'datetime', 'revised_at' => 'datetime',
            'total_temporary' => 'decimal:2', 'total_provision_cost' => 'decimal:2', 'total_provision_sell' => 'decimal:2', 'subtotal' => 'decimal:2', 'profit' => 'decimal:2', 'margin' => 'decimal:2',
            'discount' => 'decimal:2', 'tax_rate' => 'decimal:2', 'tax_amount' => 'decimal:2', 'grand_total' => 'decimal:2', 'exchange_rate' => 'decimal:2'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('position');
    }

    public function job(): HasOne
    {
        return $this->hasOne(Job::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function revisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revised_by');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(QuotationStatusHistory::class)->orderBy('id');
    }
}
