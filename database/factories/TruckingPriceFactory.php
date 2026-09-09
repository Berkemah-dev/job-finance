<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class TruckingPriceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'port_origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'overweight' => false,
            'container_type' => '20ft',
            'vendor_id' => Vendor::factory(),
            'price' => '4500000.00',
            'currency' => 'IDR',
            'effective_date' => today()->toDateString(),
            'is_active' => true,
            'lock_version' => 0,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
