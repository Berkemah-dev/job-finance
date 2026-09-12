<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class ContainerUnit extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function options(bool $activeOnly = true): array
    {
        if (! Schema::hasTable('container_units')) {
            return config('operations.container_types', []);
        }

        $options = self::query()
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();

        return ! empty($options) ? $options : config('operations.container_types', []);
    }

    public static function allowedKeys(): array
    {
        return array_unique(array_merge(
            array_keys(self::options(false)),
            array_values(self::options(false)),
            array_keys(config('operations.container_types', [])),
            array_keys(config('operations.trucking_container_types', [])),
            array_values(config('operations.container_types', [])),
            array_values(config('operations.trucking_container_types', []))
        ));
    }

    public static function label(?string $name): string
    {
        if (! $name) {
            return '—';
        }

        return self::options(false)[$name]
            ?? config('operations.container_types.'.$name)
            ?? strtoupper($name);
    }
}
