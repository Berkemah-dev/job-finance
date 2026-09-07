<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartOfAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['code', 'name', 'type'];

    public function mappings(): HasMany
    {
        return $this->hasMany(AccountMapping::class);
    }
}
