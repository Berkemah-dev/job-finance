<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['invoice_date' => 'date', 'due_date' => 'date', 'issued_at' => 'datetime', 'customer_snapshot' => 'array', 'exchange_rate' => 'decimal:2', 'subtotal' => 'decimal:2', 'tax' => 'decimal:2', 'total' => 'decimal:2', 'paid_amount' => 'decimal:2', 'balance' => 'decimal:2'];
    }

    public function inInvoiceCurrency(string $field): BigDecimal
    {
        $value = BigDecimal::of((string) $this->{$field});
        $rate = BigDecimal::of((string) $this->exchange_rate);
        if ($this->currency === 'IDR' || $rate->isZero()) {
            return $value->toScale(2, RoundingMode::HalfUp);
        }

        return $value->dividedBy($rate, 2, RoundingMode::HalfUp);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(JobClosingSnapshot::class, 'job_closing_snapshot_id');
    }
}
