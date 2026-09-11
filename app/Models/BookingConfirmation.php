<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingConfirmation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'booking_date'        => 'date',
        'etd'                 => 'date',
        'eta'                 => 'date',
        'doc_cutoff_at'       => 'datetime',
        'cy_cutoff_at'        => 'datetime',
        'delivery_cutoff_at'  => 'datetime',
        'gross_weight'        => 'decimal:2',
        'volume'              => 'decimal:3',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateNumber(?string $prefix = null): string
    {
        $prefix = $prefix ?: 'BC-JOB/EXP/' . date('ym');
        $count = static::whereYear('booking_date', date('Y'))->count() + 1;
        return $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
