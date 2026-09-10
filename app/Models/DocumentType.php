<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active'   => 'boolean',
            'sort_order'  => 'integer',
        ];
    }

    public function jobDocuments(): HasMany
    {
        return $this->hasMany(JobDocument::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'shipment'   => 'Pengiriman',
            'finance'    => 'Keuangan',
            'compliance' => 'Kepatuhan',
            default      => 'Umum',
        };
    }
}
