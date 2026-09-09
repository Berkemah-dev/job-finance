<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeeklyPricingFactory extends Factory
{
    public function definition(): array
    {
        $week = fake()->dateTimeBetween('-3 months', 'now');

        return [
            'week' => $week->format('W.Y'),
            'effective_date' => $week->format('Y-m-d'),
            'currency' => 'USD',
            'exchange_rate' => '15850.00',
            'service' => null,
            'notes' => null,
            'is_active' => true,
            'lock_version' => 0,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
