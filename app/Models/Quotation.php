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

    protected $fillable = ['customer_id', 'subject', 'quotation_date', 'valid_until', 'notes'];

    protected function casts(): array
    {
        return ['status' => QuotationStatus::class, 'customer_snapshot' => 'array', 'quotation_date' => 'date', 'valid_until' => 'date',
            'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'rejected_at' => 'datetime', 'converted_at' => 'datetime',
            'total_temporary' => 'decimal:2', 'total_provision_cost' => 'decimal:2', 'total_provision_sell' => 'decimal:2', 'subtotal' => 'decimal:2', 'profit' => 'decimal:2', 'margin' => 'decimal:2'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
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
}
