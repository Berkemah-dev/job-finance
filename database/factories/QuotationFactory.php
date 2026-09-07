<?php

namespace Database\Factories;

use App\Enums\QuotationStatus;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuotationFactory extends Factory
{
    // For fixture headers; use QuotationService for business-valid totals/items.
    public function definition(): array
    {
        return ['number' => 'FIXTURE-'.fake()->unique()->numerify('########'), 'customer_id' => Customer::factory(), 'customer_snapshot' => ['code' => 'FIXTURE', 'name' => 'Customer Fixture'],
            'subject' => 'Pengiriman '.fake()->city(), 'quotation_date' => now()->toDateString(), 'valid_until' => now()->addDays(30)->toDateString(), 'status' => QuotationStatus::Draft, 'created_by' => User::factory(), 'updated_by' => User::factory()];
    }
}
