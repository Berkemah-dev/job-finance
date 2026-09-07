<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return ['code' => strtoupper(fake()->unique()->bothify('CUS-??????')), 'name' => fake()->company(), 'email' => fake()->companyEmail(), 'address' => fake()->address(), 'created_by' => User::factory(), 'updated_by' => User::factory()];
    }
}
