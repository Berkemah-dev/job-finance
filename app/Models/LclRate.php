<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LclRate extends Model
{
    protected $fillable = [
        'country', 'fob_port', 'subject', 'customer', 'lead_time_days',
        'ocean_freight_rate', 'gri_rate', 'cfs_rate', 'cfs_min_wm',
        'others_per_set', 'mechanic_rate', 'mechanic_min_wm', 'administration', 'is_active',
    ];

    protected $casts = [
        'lead_time_days' => 'decimal:2', 'ocean_freight_rate' => 'decimal:2', 'gri_rate' => 'decimal:2',
        'cfs_rate' => 'decimal:2', 'cfs_min_wm' => 'decimal:2', 'others_per_set' => 'decimal:2',
        'mechanic_rate' => 'decimal:2', 'mechanic_min_wm' => 'decimal:2', 'administration' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
