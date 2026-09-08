<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    public function definition(): array
    {
        return ['invoice_id' => Invoice::factory(), 'position' => 1, 'description' => 'Jasa fixture', 'type' => 'provision', 'quantity' => '1.00', 'unit' => 'Layanan', 'unit_price' => '4500000.00', 'amount' => '4500000.00'];
    }
}
