<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tps extends Model
{
<<<<<<< HEAD
    protected $guarded = ['id'];
=======
    protected $table = 'tps';

    protected $fillable = ['city', 'name', 'code', 'mode', 'is_active'];
>>>>>>> 367a9ee93734e3a97628530a24dddb5e9c488978

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

<<<<<<< HEAD
    public function scopeMode($query, $mode)
    {
        return $mode ? $query->where('mode', $mode) : $query;
    }

    public function getModeLabelAttribute(): string
    {
        return $this->mode === 'air' ? 'Air (Udara)' : 'Sea (Laut)';
    }
}
=======
    public function scopeAir($query)
    {
        return $query->where('mode', 'air');
    }

    public function scopeSea($query)
    {
        return $query->where('mode', 'sea');
    }
}
>>>>>>> 367a9ee93734e3a97628530a24dddb5e9c488978
