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
}
