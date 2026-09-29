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

    public static function badgeConfig(?string $code): array
    {
        $code = strtolower(trim((string) $code));

        return match ($code) {
            'exp_sea', 'sea' => [
                'code' => 'exp_sea',
                'label' => self::label($code),
                'icon' => 'ship',
                'bg' => '#ecfdf5',
                'color' => '#047857',
                'border' => '#a7f3d0',
            ],
            'imp_sea' => [
                'code' => 'imp_sea',
                'label' => self::label($code),
                'icon' => 'ship',
                'bg' => '#eff6ff',
                'color' => '#1d4ed8',
                'border' => '#bfdbfe',
            ],
            'exp_air', 'air' => [
                'code' => 'exp_air',
                'label' => self::label($code),
                'icon' => 'plane',
                'bg' => '#f5f3ff',
                'color' => '#6d28d9',
                'border' => '#ddd6fe',
            ],
            'imp_air' => [
                'code' => 'imp_air',
                'label' => self::label($code),
                'icon' => 'plane',
                'bg' => '#fff7ed',
                'color' => '#c2410c',
                'border' => '#fed7aa',
            ],
            'domestic', 'trucking', 'land', 'dom_sea', 'dom_air', 'other' => [
                'code' => 'domestic',
                'label' => self::label($code),
                'icon' => 'truck',
                'bg' => '#f1f5f9',
                'color' => '#334155',
                'border' => '#cbd5e1',
            ],
            default => [
                'code' => $code ?: 'default',
                'label' => self::label($code),
                'icon' => null,
                'bg' => '#f3f4f6',
                'color' => '#4b5563',
                'border' => '#e5e7eb',
            ],
        };
    }
}
