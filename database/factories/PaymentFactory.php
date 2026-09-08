<?php

namespace Database\Factories;

use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return ['number' => 'PAY-FIXTURE-'.fake()->unique()->numerify('########'), 'invoice_id' => Invoice::factory(), 'payment_date' => today(), 'amount' => '1000000.00', 'deposit_account_id' => ChartOfAccount::factory()->state(['type' => 'asset']), 'method' => 'transfer', 'reference' => fake()->numerify('REF-######'), 'created_by' => User::factory()];
    }
}
