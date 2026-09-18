<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillOfLading extends Model
{
    use HasFactory;

    protected $table = 'bills_of_lading';
    protected $guarded = ['id'];

    protected $casts = [
        'bl_date'               => 'date',
        'date_of_issue'         => 'date',
        'shipped_on_board_date' => 'date',
        'etd'                   => 'date',
        'eta'                   => 'date',
        'gross_weight'          => 'decimal:2',
        'net_weight'            => 'decimal:2',
        'measurement'           => 'decimal:3',
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
        $prefix = $prefix ?: 'RDXL' . date('ym');
        $count = static::whereYear('bl_date', date('Y'))->count() + 1;
        return $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
