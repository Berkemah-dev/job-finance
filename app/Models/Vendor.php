<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'email',
        'phone',
        'address',
        'country',
        'tax_number',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'pic',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(VendorCategory::class);
    }

    public function categoryLabels(): array
    {
        $types = config('operations.vendor_types');
        $cats = $this->categories->pluck('category')->all();
        if (empty($cats) && $this->type) {
            $cats = [$this->type];
        }

        return array_map(fn ($c) => $types[$c] ?? $c, $cats);
    }

    public function truckingPrices(): HasMany
    {
        return $this->hasMany(TruckingPrice::class);
    }
}
