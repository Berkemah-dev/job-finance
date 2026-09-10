<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TruckingPrice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['port_origin', 'destination', 'overweight', 'container_type', 'vendor_id', 'price', 'currency', 'effective_date', 'effective_until', 'is_active'];

    protected function casts(): array
    {
        return ['overweight' => 'boolean', 'price' => 'decimal:2', 'effective_date' => 'date', 'effective_until' => 'date', 'is_active' => 'boolean'];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }
}
