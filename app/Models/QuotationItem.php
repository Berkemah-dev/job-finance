<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $fillable = ['position', 'description', 'note', 'type', 'unit', 'quantity', 'unit_cost', 'unit_price', 'total_cost', 'total_price', 'container_type', 'overweight', 'gross_weight', 'volume', 'currency', 'exchange_rate', 'pricing_source', 'pricing_id', 'pricing_snapshot'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'unit_cost' => 'decimal:2', 'unit_price' => 'decimal:2', 'total_cost' => 'decimal:2', 'total_price' => 'decimal:2', 'gross_weight' => 'decimal:2', 'volume' => 'decimal:4', 'exchange_rate' => 'decimal:2', 'overweight' => 'boolean', 'pricing_snapshot' => 'array'];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}
