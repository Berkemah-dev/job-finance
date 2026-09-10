<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CalculationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'admin@jobfinance.test')->firstOrFail());
    }

    public function test_calculator_pages_and_quotation_inline_panel_render(): void
    {
        $this->get('/kalkulator')->assertOk()->assertSee('Estimasi Biaya LCL');
        $this->get('/kalkulator/volume-weight')->assertOk();
        $this->get('/kalkulator/lcl')->assertOk();
        $this->get('/kalkulator/pajak')->assertOk();
        $this->get('/quotations/create')->assertOk()->assertSee('Estimator biaya LCL')->assertSee('data-lcl-panel');
    }

    public function test_packages_api_computes_volume_weight_cbm_and_chargeable(): void
    {
        $json = $this->getJson('/api/calculators/packages?packages[0][qty]=2&packages[0][length]=100&packages[0][width]=80&packages[0][height]=60&packages[0][gross_weight]=40')
            ->assertOk()->json();

        $this->assertSame(2, $json['package_count']);
        $this->assertSame(160.0, (float) $json['total_volume_weight']);
        $this->assertSame(0.96, (float) $json['total_cbm']);
        $this->assertSame(160.0, (float) $json['total_chargeable_weight']);

        $this->getJson('/api/calculators/packages?packages[0][qty]=1&packages[0][length]=100&packages[0][width]=80&packages[0][height]=60&packages[0][gross_weight]=500')
            ->assertOk()->assertJsonPath('total_chargeable_weight', 500);
    }

    public function test_lcl_basis_is_max_of_cbm_and_tonnage(): void
    {
        $calc = app(CalculationService::class);

        $cbmWins = $calc->lcl([['qty' => 1, 'length' => 100, 'width' => 100, 'height' => 100, 'gross_weight' => 950]], '1000000');
        $this->assertSame(1.0, $cbmWins['chargeable_basis']);
        $this->assertSame('m³', $cbmWins['basis_note']);
        $this->assertSame('1000000.00', $cbmWins['total_cost']);

        $weightWins = $calc->lcl([['qty' => 1, 'length' => 100, 'width' => 100, 'height' => 100, 'gross_weight' => 1200]], '1000000');
        $this->assertSame(1.2, $weightWins['chargeable_basis']);
        $this->assertSame('tonase', $weightWins['basis_note']);
        $this->assertSame('1200000.00', $weightWins['total_cost']);

        $this->getJson('/api/calculators/lcl?rate_per_cbm=1000000&packages[0][qty]=1&packages[0][length]=100&packages[0][width]=100&packages[0][height]=100&packages[0][gross_weight]=1200')
            ->assertOk()->assertJsonPath('total_cost', '1200000.00');
    }

    public function test_tax_api_and_validation(): void
    {
        $this->getJson('/api/calculators/tax?base_amount=10000000&rate=11')
            ->assertOk()
            ->assertJsonPath('base', '10000000.00')
            ->assertJsonPath('tax', '1100000.00')
            ->assertJsonPath('total', '11100000.00');

        $this->getJson('/api/calculators/tax?base_amount=10000000&rate=101')->assertStatus(422);
        $this->getJson('/api/calculators/packages?packages[0][qty]=0&packages[0][length]=1&packages[0][width]=1&packages[0][height]=1&packages[0][gross_weight]=1')->assertStatus(422);
    }
}
