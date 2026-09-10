<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeeklyPricing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['week', 'effective_date', 'effective_until', 'currency', 'exchange_rate', 'service', 'notes', 'is_active'];

    protected function casts(): array
    {
        return ['effective_date' => 'date', 'effective_until' => 'date', 'exchange_rate' => 'decimal:2', 'is_active' => 'boolean'];
    }
}
