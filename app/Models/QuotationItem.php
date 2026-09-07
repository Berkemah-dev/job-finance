<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $fillable = ['position', 'description', 'type', 'unit', 'quantity', 'unit_cost', 'unit_price', 'total_cost', 'total_price'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'unit_cost' => 'decimal:2', 'unit_price' => 'decimal:2', 'total_cost' => 'decimal:2', 'total_price' => 'decimal:2'];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}
