<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Job;
use App\Models\Quotation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;

    private User $manager;

    private User $operator;

    private User $cs;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->sales = User::where('email', 'sales@jobfinance.test')->firstOrFail();
        $this->manager = User::where('email', 'sales-manager@jobfinance.test')->firstOrFail();
        $this->operator = User::where('email', 'operational@jobfinance.test')->firstOrFail();
        $this->cs = User::where('email', 'customer-service@jobfinance.test')->firstOrFail();
        $this->customer = Customer::factory()->create(['created_by' => $this->sales->id, 'updated_by' => $this->sales->id]);
        $this->actingAs($this->sales);
    }

    private function quotationData(): array
    {
        return ['customer_id' => $this->customer->id, 'subject' => 'Pengiriman Jakarta Surabaya via laut', 'quotation_date' => '2026-09-08', 'valid_until' => '2026-10-08',
            'service_type' => 'sea', 'origin' => 'Jakarta', 'destination' => 'Surabaya', 'currency' => 'IDR', 'exchange_rate' => '1', 'payment_terms' => 'net_30',
            'shipper_name' => 'PT Sumber Makmur', 'shipper_address' => 'Jl. Raya Cakung 10, Jakarta', 'consignee_name' => 'PT Tujuan Jaya', 'consignee_address' => 'Jl. Tanjung Perak 20, Surabaya',
            'items' => [
                ['description' => 'Dokumen', 'type' => 'temporary', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '5000000', 'unit_price' => '5000000'],
                ['description' => 'Ongkir laut', 'type' => 'provision', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '3000000', 'unit_price' => '4500000'],
            ]];
    }

    private function convertedJob(): Job
    {
        $this->post('/quotations', $this->quotationData())->assertSessionHasNoErrors()->assertRedirect();
        $q = Quotation::latest('id')->firstOrFail();
        $this->post('/quotations/'.$q->id.'/submit', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->actingAs($this->manager);
        $this->post('/quotations/'.$q->id.'/approve', ['lock_version' => 1])->assertSessionHasNoErrors();
        $this->post('/quotations/'.$q->id.'/convert', ['lock_version' => 2])->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($this->sales);

        return Job::firstOrFail();
    }

    private function updateData(Job $job): array
    {
        return ['lock_version' => $job->lock_version, 'subject' => $job->subject, 'job_date' => $job->job_date->format('Y-m-d'),
            'service_type' => 'sea', 'pol' => 'Tanjung Priok', 'pod' => 'Tanjung Perak', 'etd' => '2026-09-15', 'eta' => '2026-09-16',
            'vessel_voyage' => 'KM SINAR 123 / V.88', 'flight_number' => 'GA-152', 'bl_number' => 'BL-001', 'hbl_number' => 'HBL-001',
            'awb_number' => 'AWB-001', 'hawb_number' => 'HAWB-001', 'booking_reference' => 'BK-001', 'shipment_reference' => 'SJ-001',
            'shipper_name' => 'Shipper A', 'shipper_address' => 'Alamat shipper A', 'consignee_name' => 'Consignee B', 'consignee_address' => 'Alamat consignee B',
            'package_count' => 5, 'gross_weight' => '1250.50', 'volume' => '15.75', 'container_type' => '40ft',
            'sales_id' => $this->sales->id, 'cs_id' => $this->cs->id, 'cargo_description' => 'Mesin & sparepart', 'operational_notes' => 'Prioritas tinggi'];
    }

    public function test_convert_seeds_shipper_consignee_routing_and_draft_history(): void
    {
        $job = $this->convertedJob();
        $this->assertSame('sea', $job->service_type);
        $this->assertSame('Jakarta', $job->origin);
        $this->assertSame('Surabaya', $job->destination);
        $this->assertSame('PT Sumber Makmur', $job->shipper_name);
        $this->assertSame('Jl. Raya Cakung 10, Jakarta', $job->shipper_address);
        $this->assertSame('PT Tujuan Jaya', $job->consignee_name);
        $this->assertDatabaseHas('job_status_history', ['job_id' => $job->id, 'from_status' => null, 'to_status' => 'draft']);
        $this->get('/jobs/'.$job->id)->assertOk()->assertSee('PT Sumber Makmur')->assertSee('PT Tujuan Jaya')->assertSee('Jakarta');
        $this->actingAs($this->operator);
        $this->get('/jobs/'.$job->id.'/edit')->assertOk()->assertSee('Pelabuhan muat (POL)')->assertSee('Nomor BL');
        $this->assertDatabaseHas('activity_logs', ['action' => 'quotation.converted', 'user_id' => $this->manager->id]);
    }

    public function test_update_edits_routing_references_cargo_and_assignees(): void
    {
        $job = Job::factory()->create();
        $this->actingAs($this->operator);
        $this->put('/jobs/'.$job->id, $this->updateData($job))->assertSessionHasNoErrors()->assertRedirect();
        $job->refresh();
        $this->assertSame('Tanjung Priok', $job->pol);
        $this->assertSame('Tanjung Perak', $job->pod);
        $this->assertSame('2026-09-15', $job->etd->toDateString());
        $this->assertSame('KM SINAR 123 / V.88', $job->vessel_voyage);
        $this->assertSame('BL-001', $job->bl_number);
        $this->assertSame('HAWB-001', $job->hawb_number);
        $this->assertSame(5, $job->package_count);
        $this->assertSame('1250.50', $job->gross_weight);
        $this->assertSame('40ft', $job->container_type);
        $this->assertSame($this->sales->id, $job->sales_id);
        $this->assertSame($this->cs->id, $job->cs_id);
        $this->get('/jobs/'.$job->id)->assertSee('Tanjung Priok → Tanjung Perak')->assertSee('Consignee B');
        $this->get('/jobs?sales_id='.$this->sales->id)->assertSee($job->number);
    }

    public function test_validation_rejects_invalid_routing_cargo_and_assignee(): void
    {
        $job = Job::factory()->create();
        $this->actingAs($this->operator);
        $base = $this->updateData($job);
        $this->put('/jobs/'.$job->id, array_replace($base, ['eta' => '2026-09-14']))->assertSessionHasErrors('eta');
        $this->put('/jobs/'.$job->id, array_replace($base, ['container_type' => '30ft']))->assertSessionHasErrors('container_type');
        $this->put('/jobs/'.$job->id, array_replace($base, ['sales_id' => 999999]))->assertSessionHasErrors('sales_id');
        $this->put('/jobs/'.$job->id, array_replace($base, ['gross_weight' => 'abc']))->assertSessionHasErrors('gross_weight');
        $this->assertNull($job->fresh()->pol);
    }

    public function test_transitions_record_status_timeline(): void
    {
        $job = Job::factory()->create();
        $this->actingAs($this->operator);
        $this->post('/jobs/'.$job->id.'/open', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('job_status_history', ['job_id' => $job->id, 'from_status' => 'draft', 'to_status' => 'open']);
        $this->get('/jobs/'.$job->id)->assertSee('Riwayat status')->assertSee('Draft → Open');
        $this->post('/jobs/'.$job->id.'/cancel', ['lock_version' => 1, 'reason' => 'Permintaan customer'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('job_status_history', ['job_id' => $job->id, 'from_status' => 'open', 'to_status' => 'cancelled', 'note' => 'Permintaan customer']);
        $this->get('/jobs/'.$job->id)->assertSee('Open → Dibatalkan')->assertSee('Permintaan customer');
    }

    public function test_index_filters_service_routing_and_assignee(): void
    {
        $jobA = $this->convertedJob();
        $jobB = Job::factory()->create(['service_type' => 'land', 'sales_id' => $this->manager->id, 'cs_id' => $this->cs->id]);
        $this->get('/jobs?service_type=sea')->assertSee($jobA->number)->assertDontSee($jobB->number);
        $this->get('/jobs?sales_id='.$this->manager->id)->assertSee($jobB->number)->assertDontSee($jobA->number);
        $this->get('/jobs?cs_id='.$this->cs->id)->assertSee($jobB->number)->assertDontSee($jobA->number);
        $this->get('/jobs?date_from='.today()->toDateString())->assertSee($jobB->number);
        $this->get('/jobs')->assertSee($jobA->number)->assertSee($jobB->number);
    }

    public function test_financial_summary_is_visible_to_finance_only(): void
    {
        $job = $this->convertedJob();
        $this->actingAs(User::where('email', 'finance@jobfinance.test')->firstOrFail());
        $this->get('/jobs/'.$job->id)->assertOk()->assertSee('Ringkasan keuangan')->assertSee('Estimasi modal');
        $this->actingAs($this->operator);
        $this->get('/jobs/'.$job->id)->assertOk()->assertDontSee('Ringkasan keuangan')->assertDontSee('Estimasi modal')->assertSee('Total sebelum pajak');
    }

    public function test_finance_is_read_only_and_management_cannot_open_job_pages(): void
    {
        $job = $this->convertedJob();
        $this->actingAs(User::where('email', 'finance@jobfinance.test')->firstOrFail());
        $this->get('/jobs/'.$job->id.'/edit')->assertForbidden();
        $this->actingAs(User::where('email', 'management@jobfinance.test')->firstOrFail());
        $this->get('/jobs')->assertForbidden();
    }
}
