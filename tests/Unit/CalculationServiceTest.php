<?php

namespace Tests\Unit;

use App\Services\CalculationService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CalculationServiceTest extends TestCase
{
    private CalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CalculationService;
    }

    public function test_volume_weight_uses_air_cargo_divisor_by_default(): void
    {
        $this->assertSame(166.67, $this->service->volumeWeight(100, 100, 100));
        $this->assertSame(1.67, $this->service->volumeWeight(100, 100, 100, 600000));
        $this->assertSame(200.0, $this->service->volumeWeight(100, 100, 100, 5000));
    }

    public function test_cbm_is_cubic_volume_in_cubic_meters(): void
    {
        $this->assertSame(1.0, $this->service->cbm(100, 100, 100));
        $this->assertSame(1.728, $this->service->cbm(120, 120, 120));
        $this->assertSame(0.125, $this->service->cbm(50, 50, 50));
    }

    public function test_chargeable_weight_takes_the_larger_of_gross_or_volume(): void
    {
        $this->assertSame(166.67, $this->service->chargeableWeight(100, 166.67));
        $this->assertSame(300.0, $this->service->chargeableWeight(300, 166.67));
        $this->assertSame(150.25, $this->service->chargeableWeight(150.251, 120));
    }

    public function test_package_projects_one_row_of_charges(): void
    {
        $row = $this->service->package(1, 100, 100, 100, 10.5);
        $this->assertSame(166.67, $row['volume_weight_per_unit']);
        $this->assertSame(1.0, $row['cbm_per_unit']);
        $this->assertSame(10.5, $row['gross_weight_per_unit']);
        $this->assertSame(166.67, $row['volume_weight']);
        $this->assertSame(10.5, $row['gross_weight']);
        $this->assertSame(166.67, $row['chargeable_weight']);

        $double = $this->service->package(2, 100, 100, 100, 10.5);
        $this->assertSame(333.34, $double['volume_weight']);
        $this->assertSame(2.0, $double['cbm']);
        $this->assertSame(21.0, $double['gross_weight']);
        $this->assertSame(333.34, $double['chargeable_weight']);
    }

    public function test_package_rejects_zero_or_negative_qty(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->package(0, 100, 100, 100, 10);
    }

    public function test_package_totals_aggregates_rows_and_skips_empty_lines(): void
    {
        $totals = $this->service->packageTotals([
            ['qty' => 2, 'length' => 100, 'width' => 100, 'height' => 100, 'gross_weight' => 10.5],
            ['qty' => 1, 'length' => 50, 'width' => 50, 'height' => 50, 'gross_weight' => 5],
            ['qty' => 0, 'length' => 100, 'width' => 100, 'height' => 100, 'gross_weight' => 50],
        ]);

        $this->assertCount(2, $totals['rows']);
        $this->assertSame(3, $totals['package_count']);
        $this->assertEqualsWithDelta(26.0, $totals['total_gross_weight'], 0.001);
        $this->assertEqualsWithDelta(354.17, $totals['total_volume_weight'], 0.001);
        $this->assertEqualsWithDelta(2.125, $totals['total_cbm'], 0.0001);
        $this->assertEqualsWithDelta(354.17, $totals['total_chargeable_weight'], 0.001);
    }

    public function test_tax_computes_base_rate_and_total(): void
    {
        $tax = $this->service->tax('10000', '11');
        $this->assertSame('10000.00', $tax['base']);
        $this->assertSame(11.0, $tax['rate']);
        $this->assertSame('1100.00', $tax['tax']);
        $this->assertSame('11100.00', $tax['total']);
    }

    public function test_tax_zero_rate_and_rounding(): void
    {
        $tax = $this->service->tax('33333.33', '10');
        $this->assertSame('3333.33', $tax['tax']);
        $this->assertSame('36666.66', $tax['total']);
        $zero = $this->service->tax('50000', '0');
        $this->assertSame('0.00', $zero['tax']);
        $this->assertSame('50000.00', $zero['total']);
    }

    public function test_tax_rejects_rates_outside_range(): void
    {
        foreach ([-1, 101] as $rate) {
            try {
                $this->service->tax('10000', $rate);
                $this->fail('Rate '.$rate.' should be rejected');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('rate', $e->errors());
            }
        }
    }
}
