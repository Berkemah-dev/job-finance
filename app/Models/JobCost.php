<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobCost extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = ['description', 'type', 'cost_date', 'quantity', 'unit', 'unit_cost', 'unit_price', 'payee', 'reference', 'notes'];

    protected function casts(): array
    {
        return ['cost_date' => 'date', 'quantity' => 'decimal:2', 'unit_cost' => 'decimal:2', 'unit_price' => 'decimal:2', 'total_cost' => 'decimal:2', 'total_price' => 'decimal:2', 'finalized_at' => 'datetime'];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
