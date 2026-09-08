<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalAdjustmentFactory extends Factory
{
    public function definition(): array
    {
        return ['description' => 'Penyesuaian fixture', 'created_by' => User::factory()];
    }
}
