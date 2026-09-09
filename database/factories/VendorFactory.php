<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('VND-??????')),
            'name' => fake()->company(),
            'type' => fake()->randomElement(['trucking', 'shipping_line', 'international_agent', 'national_agent']),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'tax_number' => null,
            'pic' => fake()->name(),
            'is_active' => true,
            'lock_version' => 0,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
