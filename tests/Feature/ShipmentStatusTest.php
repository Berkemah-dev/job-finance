<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Quotation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentStatusTest extends TestCase
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

    private function jobData(): array
    {
        return ['customer_id' => $this->customer->id, 'subject' => 'Pengiriman Jakarta Surabaya via laut', 'quotation_date' => '2026-09-08', 'valid_until' => '2026-10-08',
            'service_type' => 'sea', 'origin' => 'Jakarta', 'destination' => 'Surabaya', 'currency' => 'IDR', 'exchange_rate' => '1', 'payment_terms' => 'net_30',
            'shipper_name' => 'PT Sumber Makmur', 'shipper_address' => 'Jl. Raya Cakung 10, Jakarta', 'consignee_name' => 'PT Tujuan Jaya', 'consignee_address' => 'Jl. Tanjung Perak 20, Surabaya',
            'items' => [
                ['description' => 'Dokumen', 'type' => 'temporary', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '5000000', 'unit_price' => '5000000'],
            ]];
    }

    private function openJob(): Job
    {
        $this->post('/quotations', $this->jobData())->assertSessionHasNoErrors()->assertRedirect();
        $q = Quotation::latest('id')->firstOrFail();
        $this->post('/quotations/'.$q->id.'/submit', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->actingAs($this->manager);
        $this->post('/quotations/'.$q->id.'/approve', ['lock_version' => 1])->assertSessionHasNoErrors();
        $this->post('/quotations/'.$q->id.'/convert', ['lock_version' => 2])->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($this->operator);
        $job = Job::firstOrFail();
        $this->post('/jobs/'.$job->id.'/open', ['lock_version' => $job->lock_version])->assertSessionHasNoErrors();
        $this->actingAs($this->sales);

        return $job->fresh();
    }

    public function test_operator_updates_shipment_status_records_history_and_audit(): void
    {
        $job = $this->openJob();
        $this->actingAs($this->operator);
        $this->post('/jobs/'.$job->id.'/shipment-status', ['lock_version' => $job->lock_version, 'shipment_status' => 'booked', 'reason' => 'Booking kapal KM SINAR'])
            ->assertSessionHasNoErrors()->assertRedirect(route('jobs.show', $job));
        $job->refresh();
        $this->assertSame('booked', $job->shipment_status);
        $this->assertNotNull($job->shipment_status_at);
        $this->assertSame($this->operator->id, $job->shipment_status_by);
        $this->assertSame(2, $job->lock_version);
        $history = $job->shipmentStatusHistory()->orderBy('id')->get()->firstWhere('to_status', 'booked');
        $this->assertNotNull($history);
        $this->assertNull($history->from_status);
        $this->assertSame('booked', $history->to_status);
        $this->assertSame('Booking kapal KM SINAR', $history->note);
        $this->assertTrue(ActivityLog::where('action', 'job.shipment_status')->where('record_id', $job->id)->exists());

        $this->post('/jobs/'.$job->id.'/shipment-status', ['lock_version' => $job->lock_version, 'shipment_status' => 'departed'])->assertSessionHasNoErrors();
        $this->assertSame('departed', $job->fresh()->shipment_status);
        $this->assertSame('booked', $job->fresh()->shipmentStatusHistory()->latest('id')->firstOrFail()->from_status);
    }

    public function test_shipment_status_rejected_for_draft_job(): void
    {
        $job = Job::factory()->create(['status' => 'draft', 'quotation_snapshot' => ['customer' => ['name' => 'X']], 'job_date' => today()]);
        $this->actingAs($this->operator);
        $this->post('/jobs/'.$job->id.'/shipment-status', ['lock_version' => 0, 'shipment_status' => 'booked'])
            ->assertSessionHasErrors('shipment_status');
        $this->assertNull($job->fresh()->shipment_status);
    }

    public function test_shipment_status_must_be_valid_value(): void
    {
        $job = $this->openJob();
        $this->actingAs($this->operator);
        $this->post('/jobs/'.$job->id.'/shipment-status', ['lock_version' => $job->lock_version, 'shipment_status' => 'teleport'])
            ->assertSessionHasErrors('shipment_status');
        $this->assertNull($job->fresh()->shipment_status);
    }

    public function test_finance_cannot_update_shipment_status(): void
    {
        $job = $this->openJob();
        $this->actingAs($this->finance);
        $this->post('/jobs/'.$job->id.'/shipment-status', ['lock_version' => $job->lock_version, 'shipment_status' => 'booked'])
            ->assertForbidden();
    }

    public function test_index_filters_by_shipment_status(): void
    {
        $job = $this->openJob();
        $this->actingAs($this->operator);
        $this->post('/jobs/'.$job->id.'/shipment-status', ['lock_version' => $job->lock_version, 'shipment_status' => 'booked'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->sales);
        $this->get('/jobs?shipment_status=booked')->assertOk()->assertSee($job->number);
        $this->get('/jobs?shipment_status=completed')->assertDontSee($job->number);
    }
}
