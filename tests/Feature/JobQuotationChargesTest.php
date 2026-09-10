<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Quotation;
use App\Models\User;
use App\Models\WeeklyPricing;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobQuotationChargesTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;

    private User $manager;

    private User $operator;

    private User $finance;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->sales = User::where('email', 'sales@jobfinance.test')->firstOrFail();
        $this->manager = User::where('email', 'sales-manager@jobfinance.test')->firstOrFail();
        $this->operator = User::where('email', 'operational@jobfinance.test')->firstOrFail();
        $this->finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
        $this->customer = Customer::factory()->create(['created_by' => $this->sales->id, 'updated_by' => $this->sales->id]);
        $this->actingAs($this->sales);
    }

    private function quotationData(array $items): array
    {
        return ['customer_id' => $this->customer->id, 'subject' => 'Pengiriman Jakarta Surabaya', 'quotation_date' => '2026-09-08', 'valid_until' => '2026-10-08',
            'service_type' => 'sea', 'origin' => 'Jakarta', 'destination' => 'Surabaya', 'currency' => 'IDR', 'exchange_rate' => '1', 'payment_terms' => 'net_30',
            'shipper_name' => 'PT Sumber Makmur', 'shipper_address' => 'Jl. Raya Cakung 10, Jakarta', 'consignee_name' => 'PT Tujuan Jaya', 'consignee_address' => 'Jl. Tanjung Perak 20, Surabaya',
            'items' => $items];
    }

    private function convertedJob(array $items): Job
    {
        $this->post('/quotations', $this->quotationData($items))->assertSessionHasNoErrors()->assertRedirect();
        $q = Quotation::latest('id')->firstOrFail();
        $this->post('/quotations/'.$q->id.'/submit', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->actingAs($this->manager);
        $this->post('/quotations/'.$q->id.'/approve', ['lock_version' => 1])->assertSessionHasNoErrors();
        $this->post('/quotations/'.$q->id.'/convert', ['lock_version' => 2])->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($this->operator);

        return Job::firstOrFail();
    }

    public function test_open_converted_job_seeds_idr_charges_from_quotation(): void
    {
        $job = $this->convertedJob([
            ['description' => 'Dokumen', 'type' => 'temporary', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '5000000', 'unit_price' => '5000000'],
            ['description' => 'Ongkir laut', 'type' => 'provision', 'unit' => 'Layanan', 'quantity' => '2', 'unit_cost' => '3000000', 'unit_price' => '4500000'],
        ]);
        $this->post('/jobs/'.$job->id.'/open', ['lock_version' => 0])->assertSessionHasNoErrors();

        $rows = $job->costs()->get();
        $this->assertCount(2, $rows);
        $doc = $rows->firstWhere('type', 'temporary');
        $this->assertSame('draft', $doc->status);
        $this->assertSame('Dokumen', $doc->description);
        $this->assertSame('1.00', $doc->quantity);
        $this->assertSame('5000000.00', $doc->unit_cost);
        $this->assertSame('5000000.00', $doc->unit_price);
        $this->assertSame('5000000.00', $doc->total_cost);
        $this->assertSame($job->job_date->toDateString(), $doc->cost_date->toDateString());
        $this->assertStringContainsString($job->quotation_snapshot['number'], (string) $doc->notes);
        $sea = $rows->firstWhere('type', 'provision');
        $this->assertSame('2.00', $sea->quantity);
        $this->assertSame('3000000.00', $sea->unit_cost);
        $this->assertSame('4500000.00', $sea->unit_price);
        $this->assertSame('6000000.00', $sea->total_cost);
        $this->assertSame('9000000.00', $sea->total_price);
        foreach ($rows as $row) {
            $this->assertStringStartsWith('CST-', $row->number);
        }
        $this->assertDatabaseHas('activity_logs', ['action' => 'job.costs.seeded', 'user_id' => $this->operator->id]);
        $this->assertCount(2, ActivityLog::where('action', 'job_cost.created')->get());

        $this->actingAs($this->finance);
        $this->get('/jobs/'.$job->id)->assertOk()->assertSee('Dokumen')->assertSee('Ongkir laut');
    }

    public function test_open_converted_job_seeds_foreign_charges_at_quote_rate(): void
    {
        WeeklyPricing::factory()->create(['currency' => 'USD', 'exchange_rate' => '15850.00', 'effective_date' => '2026-09-01', 'is_active' => true]);
        $job = $this->convertedJob([
            ['description' => 'Agen luar negeri', 'type' => 'provision', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '1500', 'unit_price' => '1600', 'currency' => 'USD'],
        ]);
        $this->post('/jobs/'.$job->id.'/open', ['lock_version' => 0])->assertSessionHasNoErrors();

        $row = $job->costs()->sole();
        $this->assertSame('Agen luar negeri', $row->description);
        $this->assertSame('23775000.00', $row->unit_cost);
        $this->assertSame('25360000.00', $row->unit_price);
        $this->assertSame('23775000.00', $row->total_cost);
        $this->assertSame('25360000.00', $row->total_price);
    }

    public function test_open_job_without_quotation_snapshot_seeds_nothing(): void
    {
        $job = Job::factory()->create(['quotation_snapshot' => ['number' => 'QT-NONE', 'items' => [], 'totals' => ['subtotal' => '0.00']]]);
        $this->post('/jobs/'.$job->id.'/open', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->assertCount(0, $job->costs()->get());
        $this->assertDatabaseMissing('activity_logs', ['action' => 'job.costs.seeded']);
    }
}
