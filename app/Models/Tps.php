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

    public function scopeAir($query)
    {
        return $query->where('mode', 'air');
    }

    public function scopeSea($query)
    {
        return $query->where('mode', 'sea');
    }
}
