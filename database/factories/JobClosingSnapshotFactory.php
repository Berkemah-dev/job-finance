<?php

namespace Database\Factories;

use App\Models\ChartOfAccount;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobClosingSnapshotFactory extends Factory
{
    public function definition(): array
    {
        return ['job_id' => Job::factory()->state(['status' => 'closed']), 'closing_date' => today(), 'customer_snapshot' => ['code' => 'CUS-FIXTURE', 'name' => 'Customer Fixture'], 'costs_snapshot' => [], 'total_temporary' => '5000000.00', 'total_provision_cost' => '3000000.00', 'total_provision_sell' => '4500000.00', 'subtotal' => '9500000.00', 'tax' => '0.00', 'total' => '9500000.00', 'profit' => '1500000.00', 'margin' => '33.33', 'funding_account_id' => ChartOfAccount::factory()->state(['type' => 'asset']), 'closed_by' => User::factory(), 'closed_at' => now()];
    }
}
