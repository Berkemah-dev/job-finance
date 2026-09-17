<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Awb extends Model
{
    use HasFactory;

    protected $table = 'awbs';
    protected $guarded = ['id'];

    protected $casts = [
        'awb_date'               => 'date',
        'flight_date'            => 'date',
        'connecting_flight_date' => 'date',
        'etd'                    => 'date',
        'eta'                    => 'date',
        'exchange_rate'          => 'decimal:4',
        'gross_weight'           => 'decimal:2',
        'chargeable_weight'      => 'decimal:2',
        'volume'                 => 'decimal:3',
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
        $prefix = $prefix ?: 'AWB/EXP/AIR/' . date('ym');
        $count = static::whereYear('awb_date', date('Y'))->count() + 1;
        return $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
