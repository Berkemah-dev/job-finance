<?php

namespace Tests\Feature;

use App\Models\BookingConfirmation;
use App\Models\Job;
use App\Models\ShippingInstruction;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PdfLogoConsistencyTest extends TestCase
{
    public function test_legacy_document_templates_use_the_same_rdx_logo_without_replacing_signatures(): void
    {
        $previous = DB::getDefaultConnection();
        config(['database.connections.pdf_logo_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('pdf_logo_test');
        try {
            Artisan::call('migrate', ['--database' => 'pdf_logo_test', '--force' => true]);
            foreach ([RolePermissionSeeder::class, ChartOfAccountSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class] as $seeder) {
                app($seeder)->run();
            }
            $job = Job::factory()->open()->create(['service_type' => 'imp_sea']);
            $quotation = $job->quotation;
            $customer = $job->customer;
            $bc = new BookingConfirmation(['number' => 'BC-LOGO-TEST', 'booking_date' => today()]);
            $bc->setRelation('job', $job)->setRelation('customer', $customer);
            $si = new ShippingInstruction(['number' => 'SI-LOGO-TEST', 'si_date' => today()]);
            $si->setRelation('job', $job)->setRelation('customer', $customer);
            $statement = ['rows' => collect(), 'opening' => '0', 'invoiced' => '0', 'paid' => '0', 'closing' => '0'];
            $from = today()->startOfMonth();
            $to = today();
            $data = compact('job', 'quotation', 'customer', 'bc', 'si', 'statement', 'from', 'to');
            $expectedLogo = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/rdx-logo-clean.png')));
            foreach (['booking-confirmation', 'shipping-instruction', 'surat-jalan', 'tanda-terima', 'soa'] as $name) {
                $html = view('documents.pdf.'.$name, $data)->render();
                $this->assertStringContainsString('class="rdx-letterhead"', $html);
                $this->assertStringContainsString('PT.RADIX INTERNATIONAL LOGISTICS', $html);
                $previousErrors = libxml_use_internal_errors(true);
                try {
                    $document = new \DOMDocument;
                    $document->loadHTML($html, LIBXML_NONET);
                    $image = $document->getElementsByTagName('img')->item(0);
                    $this->assertNotNull($image, $name);
                    $this->assertSame($expectedLogo, $image->getAttribute('src'), $name);
                } finally {
                    libxml_clear_errors();
                    libxml_use_internal_errors($previousErrors);
                }
                $pdf = app('dompdf.wrapper')->loadView('documents.pdf.'.$name, $data)->setPaper('a4');
                $this->assertStringStartsWith('%PDF-', $pdf->output(), $name);
                if (getenv('PDF_LOGO_PREVIEW')) {
                    \Illuminate\Support\Facades\File::ensureDirectoryExists(base_path('tmp/pdfs'));
                    $pdf->save(base_path('tmp/pdfs/logo-'.$name.'.pdf'));
                }
            }
            foreach (['sk-do', 'sk-pabean'] as $name) {
                $this->assertStringContainsString("public_path('images/signature-syanne.jpeg')", file_get_contents(resource_path('views/documents/pdf/'.$name.'.blade.php')));
            }
            $this->assertStringContainsString("public_path('images/signature-alliyah.jpeg')", file_get_contents(resource_path('views/documents/pdf/surat-jalan.blade.php')));
            $invoice = \App\Models\Invoice::factory()->create([
                'job_closing_snapshot_id' => null, 'job_id' => $job->id, 'customer_id' => $customer->id,
            ])->refresh();
            $ledger = app(\App\Services\FinancialReportService::class)->ledgerExport(
                \App\Models\ChartOfAccount::where('code', '11121')->value('id'), $from->toDateString(), $to->toDateString()
            );
            foreach ([
                ['documents.pdf.invoice', ['invoice' => $invoice], 'invoice'],
                ['documents.pdf.job-order', $data, 'job-order'],
                ['reports.pdf.ledger', $ledger + ['from' => $from->toDateString(), 'to' => $to->toDateString()], 'ledger'],
            ] as [$template, $viewData, $name]) {
                $html = view($template, $viewData)->render();
                $this->assertStringContainsString('class="rdx-letterhead"', $html);
                $this->assertStringContainsString($expectedLogo, $html);
                $pdf = app('dompdf.wrapper')->loadView($template, $viewData)->setPaper('a4');
                $this->assertStringStartsWith('%PDF-', $pdf->output());
                if (getenv('PDF_LOGO_PREVIEW')) {
                    $pdf->save(base_path('tmp/pdfs/logo-'.$name.'.pdf'));
                }
            }
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('pdf_logo_test');
        }
    }
}
