<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ChartOfAccountFactory extends Factory
{
    public function definition(): array
    {
        return ['code' => fake()->unique()->numerify('99######'), 'name' => 'Akun '.fake()->word(), 'type' => 'asset'];
    }
}
