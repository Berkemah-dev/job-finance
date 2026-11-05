<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Quotation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoretaxExportTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;

    private User $manager;

    private User $operator;

    private User $finance;

    private User $management;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->sales = User::where('email', 'sales@jobfinance.test')->firstOrFail();
        $this->manager = User::where('email', 'sales-manager@jobfinance.test')->firstOrFail();
        $this->operator = User::where('email', 'operational@jobfinance.test')->firstOrFail();
        $this->finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
        $this->management = User::where('email', 'management@jobfinance.test')->firstOrFail();
        $this->customer = Customer::factory()->create(['created_by' => $this->sales->id, 'updated_by' => $this->sales->id, 'tax_number' => '0200000000000000']);
        $this->actingAs($this->sales);
    }

    private function closedInvoice(string $tax): Invoice
    {
        $this->actingAs($this->sales);
        $this->post('/quotations', ['customer_id' => $this->customer->id, 'subject' => 'Pengiriman laut', 'quotation_date' => '2026-09-08', 'valid_until' => '2026-10-08',
            'service_type' => 'sea', 'origin' => 'Jakarta', 'destination' => 'Surabaya', 'currency' => 'IDR', 'exchange_rate' => '1', 'payment_terms' => 'net_30',
            'shipper_name' => 'PT Sumber Makmur', 'shipper_address' => 'Jl. Raya Cakung 10, Jakarta', 'consignee_name' => 'PT Tujuan Jaya', 'consignee_address' => 'Jl. Tanjung Perak 20, Surabaya',
            'items' => [
                ['description' => 'Dokumen', 'type' => 'temporary', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '5000000', 'unit_price' => '5000000'],
                ['description' => 'Ongkir laut', 'type' => 'provision', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '3000000', 'unit_price' => '4500000'],
            ]])->assertSessionHasNoErrors()->assertRedirect();
        $q = Quotation::latest('id')->firstOrFail();
        $this->post('/quotations/'.$q->id.'/submit', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->actingAs($this->manager);
        $this->post('/quotations/'.$q->id.'/approve', ['lock_version' => 1])->assertSessionHasNoErrors();
        $admin = User::where('email', 'admin@jobfinance.test')->firstOrFail();
        $this->actingAs($admin);
        $this->post('/quotations/'.$q->id.'/convert', ['lock_version' => 2])->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($this->operator);
        $job = Job::latest('id')->firstOrFail();
        $this->post('/jobs/'.$job->id.'/open', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->actingAs($admin);
        foreach ($job->costs()->get() as $cost) {
            $job->refresh();
            $this->post('/jobs/'.$job->id.'/costs/'.$cost->id.'/finalize', ['job_version' => $job->lock_version, 'lock_version' => $cost->lock_version])->assertSessionHasNoErrors();
        }
        $this->actingAs($this->finance);
        $this->post('/closing/'.$job->id, ['lock_version' => $job->fresh()->lock_version, 'closing_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString(), 'funding_account' => 'bank', 'tax' => $tax])->assertSessionHasNoErrors()->assertRedirect();

        return $job->fresh()->invoice;
    }

    public function test_ppn_invoice_exports_coretax_xml_with_seller_and_buyer_data(): void
    {
        $invoice = $this->closedInvoice('1100000');
        $this->get('/invoices/'.$invoice->id)->assertOk()->assertSee('Ekspor XML Coretax');

        $response = $this->get('/invoices/'.$invoice->id.'/coretax');
        $response->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('faktur-'.$invoice->number.'.xml', $response->headers->get('Content-Disposition'));
        $xml = $response->getContent();
        $this->assertStringContainsString('<TaxInvoiceBulk', $xml);
        $this->assertStringContainsString('<TIN>'.config('accounting.coretax.seller_npwp').'</TIN>', $xml);
        $this->assertStringContainsString('<RefDesc>'.$invoice->number.'</RefDesc>', $xml);
        $this->assertStringContainsString('<SellerIDTKU>'.config('accounting.coretax.seller_npwp').'000000</SellerIDTKU>', $xml);
        $this->assertStringContainsString('<BuyerTin>0200000000000000</BuyerTin>', $xml);
        $this->assertStringContainsString('<BuyerName>'.$invoice->customer_snapshot['name'].'</BuyerName>', $xml);
        $this->assertStringNotContainsString('Dokumen', $xml);
        $this->assertStringContainsString('Ongkir laut', $xml);
        $this->assertStringContainsString('<GoodService>', $xml);
        $this->assertStringContainsString('<Opt>B</Opt>', $xml);
        $this->assertStringContainsString('<Code>060000</Code>', $xml);
        $this->assertStringContainsString('<Price>4500000</Price>', $xml);
        $this->assertStringContainsString('<VAT>1100000</VAT>', $xml);
        $this->assertDatabaseHas('activity_logs', ['action' => 'invoice.coretax.exported', 'user_id' => $this->finance->id]);
    }

    public function test_tax_free_invoice_cannot_be_exported(): void
    {
        $invoice = $this->closedInvoice('0');
        $this->get('/invoices/'.$invoice->id)->assertOk()->assertDontSee('Ekspor XML Coretax');
        $this->get('/invoices/'.$invoice->id.'/coretax')->assertSessionHasErrors('invoice');
        $this->assertDatabaseMissing('activity_logs', ['action' => 'invoice.coretax.exported']);
    }

    public function test_select_preview_page_lists_eligible_invoices_and_preview_does_not_log(): void
    {
        $withTax = $this->closedInvoice('1100000');
        $this->closedInvoice('0');

        $this->get('/invoices/coretax')->assertOk()->assertSee($withTax->number)->assertSee('Pratinjau')->assertSee('Unduh XML');
        $this->get('/invoices/coretax?search='.$withTax->number)->assertOk()->assertSee($withTax->number);
        $this->get('/invoices/coretax?status=paid')->assertOk()->assertDontSee($withTax->number);

        $preview = $this->get('/invoices/'.$withTax->id.'/coretax/preview');
        $preview->assertOk();
        $this->assertStringContainsString('text/plain', $preview->headers->get('Content-Type'));
        $this->assertStringContainsString('<TaxInvoiceBulk', $preview->getContent());
        $this->assertDatabaseMissing('activity_logs', ['action' => 'invoice.coretax.exported']);
    }

    public function test_select_preview_routes_respect_finance_only_permission(): void
    {
        $invoice = $this->closedInvoice('1100000');
        $this->actingAs($this->management);
        $this->get('/invoices/coretax')->assertForbidden();
        $this->get('/invoices/'.$invoice->id.'/coretax/preview')->assertForbidden();
    }

    public function test_non_finance_roles_cannot_export_coretax_xml(): void
    {
        $invoice = $this->closedInvoice('1100000');
        $this->actingAs($this->management);
        $this->get('/invoices/'.$invoice->id.'/coretax')->assertForbidden();
    }
}
