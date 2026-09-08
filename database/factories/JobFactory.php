<?php

namespace Database\Factories;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'JOB-FIXTURE-'.fake()->unique()->numerify('########'),
            'quotation_id' => Quotation::factory()->state(['status' => QuotationStatus::Converted]),
            'customer_id' => fn (array $attributes) => Quotation::findOrFail($attributes['quotation_id'])->customer_id,
            'subject' => 'Pengiriman demo', 'status' => 'draft', 'lock_version' => 0, 'job_date' => today()->toDateString(),
            'quotation_snapshot' => fn (array $attributes) => [
                'number' => Quotation::findOrFail($attributes['quotation_id'])->number,
                'customer' => Quotation::findOrFail($attributes['quotation_id'])->customer_snapshot,
                'items' => [], 'totals' => ['subtotal' => '0.00', 'profit' => '0.00'],
            ],
            'created_by' => User::factory(), 'updated_by' => User::factory(),
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => ['status' => 'open', 'opened_at' => now()]);
    }
}
