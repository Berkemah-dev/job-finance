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

        return self::query()
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();
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
