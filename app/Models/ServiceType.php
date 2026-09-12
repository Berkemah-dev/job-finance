<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class ServiceType extends Model
{
    use SoftDeletes;

    protected $fillable = ['code', 'name', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public static function options(bool $activeOnly = true): array
    {
        if (! Schema::hasTable('service_types')) {
            return config('operations.canonical_service_types', config('operations.service_types', []));
        }

        $options = self::query()
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'code')
            ->all();

        return ! empty($options) ? $options : config('operations.canonical_service_types', config('operations.service_types', []));
    }

    public static function allowedKeys(): array
    {
        return array_unique(array_merge(
            array_keys(self::options(false)),
            array_keys(config('operations.service_types', []))
        ));
    }

    public static function label(?string $code): string
    {
        if (! $code) {
            return '—';
        }

        return self::options(false)[$code]
            ?? config('operations.service_types.'.$code)
            ?? strtoupper($code);
    }
}
