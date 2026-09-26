<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobCost extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = ['description', 'type', 'cost_category', 'cost_date', 'quantity', 'unit', 'unit_cost', 'unit_price', 'pph23_amount', 'payee', 'vendor_id', 'reference', 'notes', 'quotation_id', 'quotation_item_id'];

    protected function casts(): array
    {
        return ['cost_date' => 'date', 'paid_date' => 'date', 'paid_at' => 'datetime', 'quantity' => 'decimal:2', 'unit_cost' => 'decimal:2', 'unit_price' => 'decimal:2', 'total_cost' => 'decimal:2', 'total_price' => 'decimal:2', 'pph23_amount' => 'decimal:2', 'finalized_at' => 'datetime'];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function quotationItem(): BelongsTo
    {
        return $this->belongsTo(QuotationItem::class);
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function paymentAccount(): BelongsTo { return $this->belongsTo(ChartOfAccount::class, 'payment_account_id'); }
}
