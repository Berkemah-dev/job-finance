<?php

namespace Tests\Feature;

use App\Models\Quotation;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuotationPdfHeaderTest extends TestCase
{
    public function test_quotation_preview_and_download_share_invoice_style_header_and_courier_font(): void
    {
        $previous = DB::getDefaultConnection();
        config(['database.connections.quotation_pdf_header_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('quotation_pdf_header_test');
        try {
            Artisan::call('migrate', ['--database' => 'quotation_pdf_header_test', '--force' => true]);
            foreach ([RolePermissionSeeder::class, ChartOfAccountSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class] as $seeder) {
                app($seeder)->run();
            }
            $actor = User::where('email', 'sales@jobfinance.test')->firstOrFail();
            $this->actingAs($actor);
            $quotation = Quotation::factory()->create([
                'number' => 'QUO-TEST-00001', 'created_by' => $actor->id, 'updated_by' => $actor->id, 'sales_id' => $actor->id,
                'service_type' => 'imp_sea', 'origin' => 'SHANGHAI', 'destination' => 'JAKARTA', 'cargo_qty' => '1 X 20GP',
                'customer_snapshot' => ['name' => 'PT CUSTOMER CONTOH', 'address' => 'Jakarta, Indonesia'],
            ]);
            foreach (['OCEAN FREIGHT', 'TRUCKING', 'CUSTOMS CLEARANCE'] as $index => $description) {
                $quotation->items()->create(['position' => $index + 1, 'description' => $description,
                    'type' => 'provision', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '1000000',
                    'unit_price' => '1500000', 'total_cost' => '1000000', 'total_price' => '1500000', 'currency' => 'IDR']);
            }
            $quotation->load(['items', 'customer', 'creator', 'sales']);
            $html = view('documents.pdf.quotation', compact('quotation'))->render();
            $this->assertStringContainsString("font-family: 'Courier New', Courier, monospace", $html);
            $this->assertStringNotContainsString('Times New Roman', $html);
            $this->assertStringNotContainsString('margin-bottom: 76px', $html);
            $this->assertStringContainsString('width: 190px', $html);
            $this->assertMatchesRegularExpression('/\.header-logo\s*\{[^}]*vertical-align:\s*middle/s', $html);
            $this->assertMatchesRegularExpression('/\.header-company\s*\{[^}]*vertical-align:\s*middle/s', $html);
            $this->assertStringContainsString('PT.RADIX INTERNATIONAL LOGISTICS', $html);
            $this->assertStringContainsString('Jl.Teh No 3C Tamansari Pinangsia', $html);
            $this->assertStringContainsString('class="rdx-letterhead"', $html);
            $this->assertStringContainsString('data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/rdx-logo-clean.png'))), $html);
            $this->assertSame($html, view('quotations.pdf', compact('quotation'))->render());
            $this->get(route('quotations.preview', $quotation))->assertOk();
            $this->get(route('quotations.pdf', $quotation))->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->get(route('quotations.print', $quotation))->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $pdf = app('dompdf.wrapper')->loadView('documents.pdf.quotation', compact('quotation'))->setPaper('a4');
            $this->assertStringStartsWith('%PDF-', $pdf->output());
            $this->assertSame(1, $pdf->getDomPDF()->getCanvas()->get_page_count());
            if (getenv('QUOTATION_HEADER_PREVIEW')) {
                \Illuminate\Support\Facades\File::ensureDirectoryExists(base_path('tmp/pdfs'));
                $pdf->save(base_path('tmp/pdfs/quotation-header-preview.pdf'));
            }
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('quotation_pdf_header_test');
        }
    }
}
