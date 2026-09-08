<?php

namespace Database\Factories;

use App\Models\JobClosingSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return ['number' => 'INV-FIXTURE-'.fake()->unique()->numerify('########'), 'job_closing_snapshot_id' => JobClosingSnapshot::factory(), 'job_id' => fn (array $attributes) => JobClosingSnapshot::findOrFail($attributes['job_closing_snapshot_id'])->job_id, 'customer_id' => fn (array $attributes) => JobClosingSnapshot::findOrFail($attributes['job_closing_snapshot_id'])->job->customer_id, 'customer_snapshot' => ['code' => 'CUS-FIXTURE', 'name' => 'Customer Fixture'], 'invoice_date' => today(), 'due_date' => today()->addDays(30), 'status' => 'issued', 'subtotal' => '9500000.00', 'tax' => '0.00', 'total' => '9500000.00', 'paid_amount' => '0.00', 'balance' => '9500000.00', 'created_by' => User::factory(), 'issued_at' => now()];
    }
}
