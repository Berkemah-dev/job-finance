<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Job extends Model
{
    use HasFactory;

    public function costs(): HasMany
    {
        return $this->hasMany(JobCost::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(JobStatusHistory::class);
    }

    /**
     * Alias untuk statusHistory() — riwayat status pengiriman.
     * Status shipment ditulis ke job_status_history (satu tabel gabungan).
     * Tabel job_shipment_statuses sudah dihapus karena dead code.
     */
    public function shipmentStatusHistory(): HasMany
    {
        return $this->hasMany(JobStatusHistory::class);
    }

    public function doConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'do_confirmed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(JobDocument::class);
    }

    public function closingSnapshot(): HasOne
    {
        return $this->hasOne(JobClosingSnapshot::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function bookingConfirmations(): HasMany
    {
        return $this->hasMany(BookingConfirmation::class);
    }

    public function shippingInstructions(): HasMany
    {
        return $this->hasMany(ShippingInstruction::class);
    }

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quotation_snapshot' => 'array', 'job_date' => 'date', 'expected_completion_date' => 'date', 'etd' => 'date', 'eta' => 'date', 'peb_date' => 'date', 'gross_weight' => 'decimal:2', 'volume' => 'decimal:2', 'opened_at' => 'datetime', 'cancelled_at' => 'datetime', 'closed_at' => 'datetime', 'shipment_status_at' => 'datetime', 'do_confirmed_at' => 'datetime'];
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function cs(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cs_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function etaApproaching(): bool
    {
        if (! $this->eta) {
            return false;
        }

        return $this->eta->gte(today()) && $this->eta->lte(today()->addDays(3));
    }
}
