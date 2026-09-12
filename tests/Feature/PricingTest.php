<?php

namespace Tests\Feature;

use App\Models\TruckingPrice;
use App\Models\User;
use App\Models\WeeklyPricing;
use App\Services\PricingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'admin@jobfinance.test')->firstOrFail());
    }

    public function test_trucking_price_rejects_overlapping_active_period(): void
    {
        TruckingPrice::factory()->create([
            'port_origin' => 'Jakarta', 'destination' => 'Semarang', 'container_type' => '20ft',
            'overweight' => false, 'vendor_id' => null, 'currency' => 'IDR', 'price' => '2500000',
            'effective_date' => '2026-09-01', 'effective_until' => null, 'is_active' => true,
        ]);

        $payload = [
            'port_origin' => 'Jakarta', 'destination' => 'Semarang', 'container_type' => '20ft',
            'overweight' => 0, 'vendor_id' => '', 'currency' => 'IDR', 'price' => '2600000',
            'effective_date' => '2026-09-15', 'is_active' => 1,
        ];
        $this->post('/pricing/trucking', $payload)->assertSessionHasErrors('effective_date');

        // Dibatasi masa berlaku, tetap tumpang tindih dengan periode "tanpa batas" milik baris lama.
        $this->post('/pricing/trucking', [...$payload, 'effective_date' => '2026-10-01', 'effective_until' => '2026-10-15'])
            ->assertSessionHasErrors('effective_date');

        // Periode berakhir sebelum 2026-09-01 -> tidak tumpang tindih.
        $this->post('/pricing/trucking', [...$payload, 'effective_date' => '2026-08-01', 'effective_until' => '2026-08-31'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, TruckingPrice::count());
    }

    public function test_trucking_overlap_ignores_inactive_and_other_routes_and_effective_until_bounds_lookup(): void
    {
        TruckingPrice::factory()->create([
            'port_origin' => 'Jakarta', 'destination' => 'Semarang', 'container_type' => '20ft',
            'overweight' => false, 'vendor_id' => null, 'currency' => 'IDR', 'price' => '2500000',
            'effective_date' => '2026-09-01', 'effective_until' => '2026-09-10', 'is_active' => true,
        ]);

        // Nonaktif boleh tumpang tindih.
        $this->post('/pricing/trucking', [
            'port_origin' => 'Jakarta', 'destination' => 'Semarang', 'container_type' => '20ft',
            'overweight' => 0, 'vendor_id' => '', 'currency' => 'IDR', 'price' => '2600000',
            'effective_date' => '2026-09-05', 'is_active' => 0,
        ])->assertSessionHasNoErrors();

        // Rute berbeda tidak memicu penolakan.
        $this->post('/pricing/trucking', [
            'port_origin' => 'Jakarta', 'destination' => 'Bandung', 'container_type' => '20ft',
            'overweight' => 0, 'vendor_id' => '', 'currency' => 'IDR', 'price' => '2600000',
            'effective_date' => '2026-09-05', 'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $service = app(PricingService::class);
        $found = $service->findTruckingPrice('Jakarta', 'Surabaya', '20ft', false, null, '2026-09-05');
        $this->assertNull($found);

        $bounded = TruckingPrice::factory()->create([
            'port_origin' => 'Jakarta', 'destination' => 'Surabaya', 'container_type' => '20ft',
            'overweight' => false, 'vendor_id' => null, 'currency' => 'IDR', 'price' => '4500000',
            'effective_date' => '2026-09-01', 'effective_until' => '2026-09-10', 'is_active' => true,
        ]);
        $this->assertSame($bounded->id, $service->findTruckingPrice('Jakarta', 'Surabaya', '20ft', false, null, '2026-09-05')->id);
        $this->assertNull($service->findTruckingPrice('Jakarta', 'Surabaya', '20ft', false, null, '2026-09-11'));
    }

    public function test_weekly_pricing_accepts_overlap_and_effective_until_bounds_rate(): void
    {
        WeeklyPricing::factory()->create([
            'week' => 'W36-2026', 'currency' => 'USD', 'exchange_rate' => '15850.00',
            'effective_date' => '2026-09-01', 'effective_until' => '2026-09-10', 'is_active' => true,
        ]);
        WeeklyPricing::factory()->create([
            'week' => 'W37-2026', 'currency' => 'USD', 'exchange_rate' => '15900.00',
            'effective_date' => '2026-09-11', 'effective_until' => '2026-09-15', 'is_active' => true,
        ]);

        $service = app(PricingService::class);
        $this->assertSame('15850.00', (string) $service->activeWeeklyRate('USD', null, '2026-09-08')->exchange_rate);
        $this->assertSame('15900.00', (string) $service->activeWeeklyRate('USD', null, '2026-09-12')->exchange_rate);
        $this->assertNull($service->activeWeeklyRate('USD', null, '2026-09-16'));

        // Periode boleh tumpang tindih untuk weekly (lenient).
        $this->post('/pricing/weekly', [
            'week' => 'W37-2026', 'currency' => 'USD', 'exchange_rate' => '15900.00',
            'effective_date' => '2026-09-11', 'effective_until' => '2026-09-20', 'is_active' => 1,
        ])->assertSessionHasNoErrors();

        // effective_until tidak boleh mendahului effective_date.
        $this->post('/pricing/weekly', [
            'week' => 'W38-2026', 'currency' => 'USD', 'exchange_rate' => '16000.00',
            'effective_date' => '2026-09-18', 'effective_until' => '2026-09-10', 'is_active' => 1,
        ])->assertSessionHasErrors('effective_until');
    }

    public function test_trucking_price_show_renders_matrix_for_20gp_40ft_40hq(): void
    {
        $tp20 = TruckingPrice::factory()->create([
            'port_origin' => 'Jakarta', 'destination' => 'Semarang', 'container_type' => '20gp',
            'overweight' => false, 'vendor_id' => null, 'currency' => 'IDR', 'price' => '2500000',
            'selling_price' => '3000000', 'effective_date' => '2026-09-01', 'is_active' => true,
        ]);

        $tp20Ow = TruckingPrice::factory()->create([
            'port_origin' => 'Jakarta', 'destination' => 'Semarang', 'container_type' => '20gp',
            'overweight' => true, 'vendor_id' => null, 'currency' => 'IDR', 'price' => '3200000',
            'selling_price' => '3800000', 'effective_date' => '2026-09-01', 'is_active' => true,
        ]);

        $response = $this->get(route('pricing.trucking.show', $tp20));
        $response->assertOk();
        $response->assertSee('20 GP / 20 FT Trailer');
        $response->assertSee('40 FT Trailer');
        $response->assertSee('40 HQ / 40 HC Trailer');
        $response->assertSee('Muatan Normal');
        $response->assertSee('Muatan Overweight');
        $response->assertSee('3.000.000');
        $response->assertSee('3.800.000');
    }
}
