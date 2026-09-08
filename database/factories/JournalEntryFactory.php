<?php

namespace Database\Factories;

use App\Models\ChartOfAccount;
use App\Models\Journal;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalEntryFactory extends Factory
{
    public function definition(): array
    {
        return ['journal_id' => Journal::factory(), 'chart_of_account_id' => ChartOfAccount::factory(), 'description' => 'Baris jurnal fixture', 'debit' => '100000.00', 'credit' => '0.00'];
    }
}
