<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['code', 'name', 'contact_name', 'email', 'phone', 'address', 'tax_number', 'default_payment_terms', 'npwp_file', 'nib_file'];

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function shippers(): HasMany
    {
        return $this->hasMany(CustomerContact::class)->where('type', 'shipper');
    }

    public function consignees(): HasMany
    {
        return $this->hasMany(CustomerContact::class)->where('type', 'consignee');
    }
}
