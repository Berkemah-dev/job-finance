<?php

namespace Database\Factories;

use App\Models\JournalAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalFactory extends Factory
{
    public function definition(): array
    {
        return ['number' => 'JRN-FIXTURE-'.fake()->unique()->numerify('########'), 'journal_date' => today(), 'type' => 'adjustment', 'source_type' => JournalAdjustment::class, 'source_id' => JournalAdjustment::factory(), 'description' => 'Jurnal fixture', 'status' => 'posted', 'posted_by' => User::factory(), 'posted_at' => now()];
    }
}
