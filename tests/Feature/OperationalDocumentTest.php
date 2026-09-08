<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Job;
use App\Models\Quotation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalDocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actor = User::where('email', 'operational@jobfinance.test')->firstOrFail();
        $this->customer = Customer::factory()->create(['created_by' => $this->actor->id, 'updated_by' => $this->actor->id]);
        $this->actingAs($this->actor);
    }

    private function quotationData(): array
    {
        return [
            'customer_id' => $this->customer->id,
            'subject' => 'Pengiriman Jakarta Surabaya',
            'quotation_date' => '2026-09-08',
            'valid_until' => '2026-10-08',
            'items' => [
                ['description' => 'Dokumen', 'type' => 'temporary', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '5000000', 'unit_price' => '5000000'],
                ['description' => 'Pengiriman', 'type' => 'provision', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '3000000', 'unit_price' => '4500000'],
            ],
        ];
    }

    private function approvedQuotation(): Quotation
    {
        $this->post('/quotations', $this->quotationData())->assertSessionHasNoErrors();
        $quotation = Quotation::firstOrFail();
        $this->post('/quotations/'.$quotation->id.'/submit', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->post('/quotations/'.$quotation->id.'/approve', ['lock_version' => 1])->assertSessionHasNoErrors();

        return $quotation->fresh();
    }

    public function test_document_job_index_detail_and_pdf_use_real_workflow_data(): void
    {
        $quotation = $this->approvedQuotation();

        $this->get('/dokumen-job?search='.$quotation->number)
            ->assertOk()
            ->assertSee('Dokumen Job')
            ->assertSee($quotation->number)
            ->assertSee('Create JO');

        $this->get('/dokumen-job/'.$quotation->id)
            ->assertOk()
            ->assertSee('Overview')
            ->assertSee('Quotation');

        $this->get('/dokumen-job/'.$quotation->id.'?tab=job')
            ->assertOk()
            ->assertSee('Job Order belum dibuat');

        $this->post('/quotations/'.$quotation->id.'/convert', ['lock_version' => 2])->assertSessionHasNoErrors();
        $job = Job::firstOrFail();

        $this->get('/dokumen-job/'.$quotation->id.'?tab=job')
            ->assertOk()
            ->assertSee($job->number)
            ->assertSee('Pengiriman');

        $this->get('/api/dokumen-job/'.$quotation->id.'/quotation/pdf?mode=inline')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get('/api/dokumen-job/'.$quotation->id.'/job-order/pdf?mode=download')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
