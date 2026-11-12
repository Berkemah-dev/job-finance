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
        $this->assertStringNotContainsString('Ongkir laut', $xml);
        $this->assertStringContainsString('<Name>Jasa Angkutan Barang</Name>', $xml);
        $this->assertStringContainsString('<BuyerAddress>'.$invoice->customer_snapshot['address'].'</BuyerAddress>', $xml);
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

    public function test_bulk_xml_export_with_selected_invoices(): void
    {
        $inv1 = $this->closedInvoice('1100000');
        $inv2 = $this->closedInvoice('550000');

        $this->actingAs($this->finance);
        $response = $this->post('/invoices/coretax/bulk-xml', [
            'invoice_ids' => [$inv1->id, $inv2->id],
        ]);

        $response->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('faktur-bulk-', $response->headers->get('Content-Disposition'));

        $xml = $response->getContent();
        $this->assertStringContainsString('<TaxInvoiceBulk', $xml);
        $this->assertStringContainsString('<RefDesc>'.$inv1->number.'</RefDesc>', $xml);
        $this->assertStringContainsString('<RefDesc>'.$inv2->number.'</RefDesc>', $xml);
        $this->assertStringContainsString('<TIN>'.config('accounting.coretax.seller_npwp').'</TIN>', $xml);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'invoice.coretax.bulk_exported',
            'user_id' => $this->finance->id,
        ]);
    }

    public function test_bulk_excel_export_with_selected_invoices(): void
    {
        $inv1 = $this->closedInvoice('1100000');
        $inv2 = $this->closedInvoice('550000');

        $this->actingAs($this->finance);
        $response = $this->post('/invoices/coretax/bulk-excel', [
            'invoice_ids' => [$inv1->id, $inv2->id],
        ]);

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('coretax-faktur-', $response->headers->get('Content-Disposition'));

        $file = tempnam(sys_get_temp_dir(), 'test_coretax_excel');
        file_put_contents($file, $response->streamedContent());
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($file);
        unlink($file);

        $fakturSheet = $spreadsheet->getSheetByName('Faktur');
        $this->assertNotNull($fakturSheet);
        $this->assertEquals('NPWP Penjual', $fakturSheet->getCell('A1')->getValue());
        $this->assertEquals('Baris', $fakturSheet->getCell('A3')->getValue());
        $this->assertEquals('Tanggal Faktur', $fakturSheet->getCell('B3')->getValue());
        $this->assertEquals('Alamat Pembeli', $fakturSheet->getCell('P3')->getValue());
        $this->assertEquals('1', $fakturSheet->getCell('A4')->getValue());
        $this->assertEquals($inv1->number, $fakturSheet->getCell('H4')->getValue());
        $this->assertEquals($inv1->customer_snapshot['address'], $fakturSheet->getCell('P4')->getValue());
        $this->assertEquals('2', $fakturSheet->getCell('A5')->getValue());
        $this->assertEquals($inv2->number, $fakturSheet->getCell('H5')->getValue());
        $this->assertEquals('END', $fakturSheet->getCell('A6')->getValue());

        $detailSheet = $spreadsheet->getSheetByName('DetailFaktur');
        $this->assertNotNull($detailSheet);
        $this->assertEquals('Baris', $detailSheet->getCell('A1')->getValue());
        $this->assertEquals('Nama Barang / Jasa', $detailSheet->getCell('D1')->getValue());
        $this->assertEquals('DPP Nilai Lain', $detailSheet->getCell('J1')->getValue());
        $this->assertEquals('1', $detailSheet->getCell('A2')->getValue());
        $this->assertEquals('Jasa Angkutan Barang', $detailSheet->getCell('D2')->getValue());
        $this->assertEquals('2', $detailSheet->getCell('A3')->getValue());
        $this->assertEquals('Jasa Angkutan Barang', $detailSheet->getCell('D3')->getValue());
        $this->assertEquals('END', $detailSheet->getCell('A4')->getValue());

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'invoice.coretax.excel_exported',
            'user_id' => $this->finance->id,
        ]);
    }

    public function test_bulk_export_requires_invoice_selection(): void
    {
        $this->actingAs($this->finance);
        $response = $this->post('/invoices/coretax/bulk-xml', [
            'invoice_ids' => [],
        ]);
        $response->assertSessionHasErrors('invoice_ids');

        $responseExcel = $this->post('/invoices/coretax/bulk-excel', [
            'invoice_ids' => [],
        ]);
        $responseExcel->assertSessionHasErrors('invoice_ids');
    }

    public function test_non_finance_roles_cannot_bulk_export_coretax(): void
    {
        $inv = $this->closedInvoice('1100000');
        $this->actingAs($this->management);

        $this->post('/invoices/coretax/bulk-xml', [
            'invoice_ids' => [$inv->id],
        ])->assertForbidden();

        $this->post('/invoices/coretax/bulk-excel', [
            'invoice_ids' => [$inv->id],
        ])->assertForbidden();
    }
}
