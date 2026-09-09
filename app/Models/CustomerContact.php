<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerContact extends Model
{
    protected $fillable = ['customer_id', 'type', 'name', 'company', 'email', 'phone', 'address'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
