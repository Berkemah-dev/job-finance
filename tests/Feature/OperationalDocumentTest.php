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
        $sales = User::where('email', 'sales@jobfinance.test')->firstOrFail();
        $salesManager = User::where('email', 'sales-manager@jobfinance.test')->firstOrFail();
        $this->actingAs($sales)->post('/quotations', $this->quotationData())->assertSessionHasNoErrors();
        $quotation = Quotation::firstOrFail();
        $this->actingAs($sales)->post('/quotations/'.$quotation->id.'/submit', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->actingAs($salesManager)->post('/quotations/'.$quotation->id.'/approve', ['lock_version' => 1])->assertSessionHasNoErrors();
        $this->actingAs($this->actor);

        return $quotation->fresh();
    }

    public function test_document_job_index_detail_and_pdf_use_real_workflow_data(): void
    {
        $quotation = $this->approvedQuotation();

        $this->get('/dokumen-job?search='.$quotation->number)
            ->assertOk()
            ->assertSee('Dokumen Job')
            ->assertSee($quotation->number)
            ->assertDontSee('Create JO');

        $this->actingAs(User::where('email', 'sales-manager@jobfinance.test')->firstOrFail());
        $this->get('/dokumen-job?search='.$quotation->number)
            ->assertOk()
            ->assertSee('Create JO');
        $this->actingAs($this->actor);

        $this->get('/dokumen-job/'.$quotation->id)
            ->assertOk()
            ->assertSee('Overview')
            ->assertSee('Quotation');

        $this->get('/dokumen-job/'.$quotation->id.'?tab=job')
            ->assertOk()
            ->assertSee('Job Order belum dibuat');

        $this->actingAs(User::where('email', 'sales-manager@jobfinance.test')->firstOrFail());
        $this->post('/quotations/'.$quotation->id.'/convert', ['lock_version' => 2])->assertSessionHasNoErrors();
        $this->actingAs($this->actor);
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

    public function test_sk_pabean_manual_fields_can_be_saved_and_rendered_in_pdf(): void
    {
        $quotation = $this->approvedQuotation();
        $this->actingAs(User::where('email', 'sales-manager@jobfinance.test')->firstOrFail());
        $this->post('/quotations/'.$quotation->id.'/convert', ['lock_version' => 2])->assertSessionHasNoErrors();
        $this->actingAs($this->actor);
        $job = Job::firstOrFail();
        $job->update(['service_type' => 'imp_sea']);

        $response = $this->put(route('jobs.update', $job), [
            'lock_version' => $job->lock_version,
            'subject' => $job->subject,
            'job_date' => $job->job_date->format('Y-m-d'),
            'redirect_tab' => 'sk',
            'commercial_invoice_number' => 'INV-TEST-2026-001',
            'commercial_invoice_date' => '2026-09-15',
            'packing_list_number' => 'PL-TEST-2026-001',
            'packing_list_date' => '2026-09-15',
            'invoice_issuer' => 'SHANGHAI TRADING CO., LTD',
            'invoice_amount' => 'USD 45,000',
            'incoterm' => 'FOB',
        ]);

        $response->assertRedirect(route('jobs.show', $job).'#tab-sk');

        $job->refresh();
        $this->assertSame('INV-TEST-2026-001', $job->commercial_invoice_number);
        $this->assertSame('PL-TEST-2026-001', $job->packing_list_number);
        $this->assertSame('SHANGHAI TRADING CO., LTD', $job->invoice_issuer);
        $this->assertSame('USD 45,000', $job->invoice_amount);
        $this->assertSame('FOB', $job->incoterm);

        $pdfResponse = $this->get(route('jobs.sk-pabean.pdf', $job));
        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('content-type', 'application/pdf');
    }
}
