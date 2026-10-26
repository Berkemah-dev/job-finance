<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Job;
use App\Models\JobCost;
use App\Models\Journal;
use App\Models\User;
use App\Services\JobCostService;
use App\Services\JobProfitReportService;
use App\Services\JournalService;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JobProfitPdfTest extends TestCase
{
    public function test_pdf_pairs_sell_and_buy_separates_reimbursement_and_lists_only_outstanding_cn(): void
    {
        $previous = DB::getDefaultConnection();
        config(['database.connections.job_profit_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('job_profit_test');
        try {
            Artisan::call('migrate', ['--database' => 'job_profit_test', '--force' => true]);
            foreach ([RolePermissionSeeder::class, ChartOfAccountSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class] as $seeder) {
                app($seeder)->run();
            }
            $finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
            $this->actingAs($finance);
            $job = Job::factory()->open()->create(['number' => 'JOB-PDF-TEST', 'service_type' => 'imp_sea', 'pol' => 'SHANGHAI', 'pod' => 'JAKARTA']);
            $make = fn ($description, $buy, $sell, $extra = []) => JobCost::factory()->create(array_replace([
                'job_id' => $job->id, 'description' => $description, 'status' => 'final',
                'unit_cost' => $buy, 'unit_price' => $sell, 'total_cost' => $buy, 'total_price' => $sell,
            ], $extra));
            $make('AIR FREIGHT', '8000000.25', '10000000.50');
            $make('TRUCKING', '700000', '1000000');
            $cn = $make('COMMISSION', '500000', '0', ['cost_category' => 'credit_note']);
            $paidCn = $make('PAID CN', '100000', '0', ['cost_category' => 'credit_note', 'paid_at' => now()]);
            $make('WAREHOUSE', '2000000', '2000000', ['type' => 'temporary', 'cost_category' => 'reimbursement']);
            $make('DRAFT EXCLUDED', '9000000', '99000000', ['status' => 'draft']);
            $deleted = $make('DELETED EXCLUDED', '9000000', '99000000');
            $deleted->delete();
            // A real active payment journal makes this CN partially paid even without paid_at.
            $payment = DB::transaction(fn () => app(JournalService::class)->post(
                'job_cost_payment', JobCost::class, $cn->id, today()->toDateString(), 'Partial CN payment', [
                    ['account_id' => ChartOfAccount::where('code', '1105')->value('id'), 'description' => 'Settlement', 'debit' => '200000', 'credit' => '0'],
                    ['account_id' => ChartOfAccount::where('code', '11121')->value('id'), 'description' => 'Bank payment', 'debit' => '0', 'credit' => '200000'],
                ], $finance
            ));
            $before = Journal::count();
            $service = app(JobProfitReportService::class);
            $report = $service->report($job, app(JobCostService::class));
            $this->assertSame('11000000.50', $report['sell']);
            $this->assertSame('9300000.25', $report['buy']);
            $this->assertSame('1700000.25', $report['profit']);
            $this->assertSame('2000000.00', $report['reimbursementBuy']);
            $this->assertCount(4, $report['charges']);
            $this->assertCount(1, $report['reimbursements']);
            $this->assertCount(1, $report['unpaidCreditNotes']);
            $this->assertSame($cn->id, $report['unpaidCreditNotes'][0]['cost']->id);
            $this->assertSame('300000.00', $report['unpaidCreditNotes'][0]['outstanding']);
            $this->assertSame(1, $report['draftCount']);
            $html = view('reports.pdf.job-profit', $report)->render();
            $this->assertStringContainsString('alt="RDX LOGISTICS"', $html);
            $this->assertStringContainsString('data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/rdx-logo-clean.png'))), $html);
            $this->assertStringContainsString("font-family: 'Courier New', Courier, monospace", $html);
            $this->assertStringContainsString('background: #dfdfdf', $html);
            $this->assertStringNotContainsString('#182337', $html);
            $this->assertStringNotContainsString('#eaf0f7', $html);
            $this->assertStringContainsString('JUAL (IDR)', $html);
            $this->assertStringContainsString('BELI (IDR)', $html);
            $this->assertStringContainsString('CREDIT NOTE BELUM LUNAS', $html);
            $this->assertStringNotContainsString('DRAFT EXCLUDED', $html);
            $this->assertStringNotContainsString('DELETED EXCLUDED', $html);
            $pdf = app('dompdf.wrapper')->loadView('reports.pdf.job-profit', $report)->setPaper('a4');
            $bytes = $pdf->output();
            $this->assertStringStartsWith('%PDF-', $bytes);
            if (getenv('JO_PROFIT_PREVIEW')) {
                \Illuminate\Support\Facades\File::ensureDirectoryExists(base_path('tmp/pdfs'));
                $pdf->save(base_path('tmp/pdfs/jo-profit-preview.pdf'));
            }
            $url = route('reports.job-profit.pdf', $job);
            $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->get($url.'?mode=download')->assertOk()->assertHeader('Content-Disposition', 'attachment; filename=JO_Profit_JOB-PDF-TEST.pdf');
            $this->assertSame($before, Journal::count());
            // Reversed payments must no longer reduce the CN balance.
            $payment->refresh();
            app(JournalService::class)->reverse($payment, ['lock_version' => $payment->lock_version,
                'reversal_date' => today()->toDateString(), 'reason' => 'Test unpaid'], $finance);
            $report = $service->report($job, app(JobCostService::class));
            $this->assertSame('500000.00', $report['unpaidCreditNotes'][0]['outstanding']);
            $this->actingAs(User::where('email', 'sales@jobfinance.test')->firstOrFail());
            $this->get($url)->assertForbidden();
            $job->update(['status' => 'closed']);
            $this->assertSame('1700000.25', $service->report($job, app(JobCostService::class))['profit']);
            foreach (range(1, 35) as $index) {
                $make('ADDITIONAL CHARGE '.$index.' - LOCAL HANDLING AND DOCUMENTATION', '10000.25', '15000.50');
            }
            $largePdf = app('dompdf.wrapper')->loadView('reports.pdf.job-profit', $service->report($job, app(JobCostService::class)))->setPaper('a4');
            $this->assertStringStartsWith('%PDF-', $largePdf->output());
            $this->assertGreaterThan(1, $largePdf->getDomPDF()->getCanvas()->get_page_count());
            if (getenv('JO_PROFIT_PREVIEW')) {
                $largePdf->save(base_path('tmp/pdfs/jo-profit-multipage.pdf'));
            }
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('job_profit_test');
        }
    }
}
