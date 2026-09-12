<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class DocumentType extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active'   => 'boolean',
            'sort_order'  => 'integer',
            'service_codes' => 'array',
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

    public function scopeForService($query, ?string $serviceCode)
    {
        if (! $serviceCode || ! Schema::hasColumn('document_types', 'service_codes')) {
            return $query;
        }

        return $query->where(function ($query) use ($serviceCode) {
            $query->whereNull('service_codes')
                ->orWhereJsonContains('service_codes', $serviceCode);
        });
    }

    public function getServiceLabelsAttribute(): string
    {
        $codes = $this->service_codes ?? [];

        if ($codes === []) {
            return 'Semua service';
        }

        $options = \App\Models\ServiceType::options(false);

        return collect($codes)
            ->map(fn ($code) => $options[$code] ?? strtoupper((string) $code))
            ->implode(', ');
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
