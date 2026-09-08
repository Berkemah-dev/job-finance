<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobCostFactory extends Factory
{
    public function definition(): array
    {
        return ['job_id' => Job::factory()->open(), 'number' => 'CST-FIXTURE-'.fake()->unique()->numerify('########'),
            'description' => 'Biaya pengiriman', 'type' => 'provision', 'status' => 'draft', 'cost_date' => today()->toDateString(),
            'quantity' => '1.00', 'unit' => 'Layanan', 'unit_cost' => '3000000.00', 'unit_price' => '4500000.00', 'total_cost' => '3000000.00', 'total_price' => '4500000.00',
            'created_by' => User::factory(), 'updated_by' => User::factory()];
    }
}
