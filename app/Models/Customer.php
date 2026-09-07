<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['code', 'name', 'contact_name', 'email', 'phone', 'address', 'tax_number'];

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }
}
