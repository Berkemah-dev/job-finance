<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobClosingSnapshot extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['closing_date' => 'date', 'closed_at' => 'datetime', 'customer_snapshot' => 'array', 'costs_snapshot' => 'array', 'exchange_rate' => 'decimal:2', 'total_temporary' => 'decimal:2', 'total_provision_cost' => 'decimal:2', 'total_provision_sell' => 'decimal:2', 'subtotal' => 'decimal:2', 'tax' => 'decimal:2', 'total' => 'decimal:2', 'profit' => 'decimal:2', 'margin' => 'decimal:2'];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
