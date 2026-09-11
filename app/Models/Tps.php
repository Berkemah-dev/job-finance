<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tps extends Model
{
    protected $table = 'tps';

    protected $fillable = ['city', 'name', 'code', 'mode', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMode($query, $mode)
    {
        return $mode ? $query->where('mode', $mode) : $query;
    }

    public function getModeLabelAttribute(): string
    {
        return $this->mode === 'air' ? 'Air (Udara)' : 'Sea (Laut)';
    }
}
