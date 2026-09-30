<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Dnp;
use App\Models\Job;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DnpFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Customer $customer;
    private Job $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actor = User::where('email', 'operational@jobfinance.test')->firstOrFail();
        $this->customer = Customer::factory()->create(['created_by' => $this->actor->id, 'updated_by' => $this->actor->id]);
        $this->job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'service_type' => 'imp_sea',
            'created_by' => $this->actor->id,
            'updated_by' => $this->actor->id,
        ]);
        $this->actingAs($this->actor);
    }

    public function test_dnp_create_page_renders_without_status_field(): void
    {
        $response = $this->get(route('dnps.create', ['job_id' => $this->job->id]));
        $response->assertOk();
        $response->assertDontSee('<select id="status"', false);
    }

    public function test_dnp_can_be_stored_without_status_and_defaults_to_draft(): void
    {
        $payload = [
            'number' => 'DNP/202609/00001',
            'dnp_date' => '2026-09-18',
            'job_id' => $this->job->id,
            'customer_id' => $this->customer->id,
            'consignee_name' => 'PT Test Consignee',
            'shipper_name' => 'Test Shipper Ltd',
            'importer_name' => 'PT Test Importer',
            'currency' => 'USD',
            'invoice_value' => '10000',
            'freight' => '1500',
            'insurance' => '500',
            'is_repeated_transaction' => '0',
            'supporting_documents' => ['1', '2'],
        ];

        $response = $this->post(route('dnps.store'), $payload);
        $response->assertSessionHasNoErrors();

        $dnp = Dnp::firstOrFail();
        $this->assertSame('draft', $dnp->status);
        $this->assertSame('DNP/202609/00001', $dnp->number);
        $this->assertEquals(12000.0, (float)$dnp->total_value);

        $response->assertRedirect(route('dnps.show', $dnp));
    }

    public function test_dnp_edit_page_renders_without_status_and_can_be_updated(): void
    {
        $dnp = Dnp::create([
            'number' => 'DNP/202609/00002',
            'dnp_date' => '2026-09-18',
            'job_id' => $this->job->id,
            'customer_id' => $this->customer->id,
            'currency' => 'USD',
            'status' => 'draft',
            'created_by' => $this->actor->id,
        ]);

        $editResponse = $this->get(route('dnps.edit', $dnp));
        $editResponse->assertOk();
        $editResponse->assertDontSee('<select id="status"', false);

        $updateResponse = $this->put(route('dnps.update', $dnp), [
            'number' => $dnp->number,
            'dnp_date' => '2026-09-19',
            'job_id' => $this->job->id,
            'customer_id' => $this->customer->id,
            'currency' => 'USD',
            'invoice_value' => '20000',
            'freight' => '2000',
            'insurance' => '1000',
            'is_repeated_transaction' => '1',
            'supporting_documents' => ['1', '2', '3'],
        ]);

        $updateResponse->assertSessionHasNoErrors();
        $dnp->refresh();
        $this->assertEquals('2026-09-19', $dnp->dnp_date->format('Y-m-d'));
        $this->assertSame('draft', $dnp->status);
        $this->assertEquals(23000.0, (float)$dnp->total_value);
    }

    public function test_dnp_index_and_job_show_do_not_render_status_column(): void
    {
        $dnp = Dnp::create([
            'number' => 'DNP/202609/00003',
            'dnp_date' => '2026-09-18',
            'job_id' => $this->job->id,
            'customer_id' => $this->customer->id,
            'currency' => 'USD',
            'status' => 'draft',
            'created_by' => $this->actor->id,
        ]);

        $indexResponse = $this->get(route('dnps.index'));
        $indexResponse->assertOk();
        $indexResponse->assertDontSee('name="status"', false);

        $jobShowResponse = $this->get(route('jobs.show', $this->job));
        $jobShowResponse->assertOk();
        $jobShowResponse->assertSee($dnp->number);
    }

    public function test_dnp_can_only_be_created_once_per_job(): void
    {
        $dnp = Dnp::create([
            'number' => 'DNP/202609/00004',
            'dnp_date' => '2026-09-18',
            'job_id' => $this->job->id,
            'customer_id' => $this->customer->id,
            'currency' => 'USD',
            'status' => 'draft',
            'created_by' => $this->actor->id,
        ]);

        // Attempting to open create form for the same job redirects to show
        $createResponse = $this->get(route('dnps.create', ['job_id' => $this->job->id]));
        $createResponse->assertRedirect(route('dnps.show', $dnp));
        $createResponse->assertSessionHas('warning');

        // Job page shows max 1x badge and hides create button
        $jobShowResponse = $this->get(route('jobs.show', $this->job));
        $jobShowResponse->assertOk();
        $jobShowResponse->assertSee('Dokumen Dibuat (Maks 1x)');
        $jobShowResponse->assertDontSee(route('dnps.create', ['job_id' => $this->job->id]));
    }

    public function test_dnp_supports_indonesian_thousand_separator_dots_and_hides_zero_decimals(): void
    {
        $newJob = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'service_type' => 'imp_sea',
            'created_by' => $this->actor->id,
            'updated_by' => $this->actor->id,
        ]);

        $payload = [
            'number' => 'DNP/202609/00099',
            'dnp_date' => '2026-09-20',
            'job_id' => $newJob->id,
            'customer_id' => $this->customer->id,
            'consignee_name' => 'PT Consignee Test',
            'currency' => 'IDR',
            'invoice_value' => '15.000.000',
            'freight' => '2.500.000',
            'insurance' => '500.000',
            'is_repeated_transaction' => '0',
            'supporting_documents' => ['1', '2'],
        ];

        $response = $this->post(route('dnps.store'), $payload);
        $response->assertSessionHasNoErrors();

        $dnp = Dnp::where('number', 'DNP/202609/00099')->firstOrFail();
        $this->assertEquals(15000000.0, (float)$dnp->invoice_value);
        $this->assertEquals(2500000.0, (float)$dnp->freight);
        $this->assertEquals(500000.0, (float)$dnp->insurance);
        $this->assertEquals(18000000.0, (float)$dnp->total_value);

        // Check show page does not render .00
        $showResponse = $this->get(route('dnps.show', $dnp));
        $showResponse->assertOk();
        $showResponse->assertSee('15.000.000');
        $showResponse->assertDontSee('15.000.000,00');
        $showResponse->assertDontSee('15,000,000.00');

        // Check edit page pre-fills clean without .00
        $editResponse = $this->get(route('dnps.edit', $dnp));
        $editResponse->assertOk();
        $editResponse->assertSee('value="15.000.000"', false);
    }
}
