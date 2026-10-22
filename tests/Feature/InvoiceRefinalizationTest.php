<?php

namespace Tests\Feature;

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OperationalDocumentController;
use App\Models\Job;
use App\Models\Journal;
use App\Models\User;
use App\Services\JobClosingService;
use App\Services\JobCostService;
use App\Services\MasterDataService;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InvoiceRefinalizationTest extends TestCase
{
    public function test_subsequent_costs_do_not_make_provision_negative_when_invoice_is_rebuilt(): void
    {
        $previous = DB::getDefaultConnection();
        config(['database.connections.invoice_hpp_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        DB::setDefaultConnection('invoice_hpp_test');
        try {
            Artisan::call('migrate', ['--database' => 'invoice_hpp_test', '--force' => true]);
            foreach ([RolePermissionSeeder::class, ChartOfAccountSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class] as $seeder) {
                app($seeder)->run();
            }
            $finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
            $manager = User::where('email', 'finance-manager@jobfinance.test')->firstOrFail();
            $job = Job::factory()->open()->create(['service_type' => 'imp_sea']);
            $costs = app(JobCostService::class);
            $closing = app(JobClosingService::class);
            $save = fn ($description, $purchase, $sale) => $costs->save($job->fresh(), null, [
                'job_version' => $job->fresh()->lock_version, 'description' => $description,
                'type' => 'provision', 'cost_category' => 'payment_request',
                'cost_date' => today()->toDateString(), 'currency' => 'IDR',
                'quantity' => '1', 'unit' => 'Layanan', 'unit_cost' => $purchase, 'unit_price' => $sale,
            ], $finance);
            $finalize = function ($cost) use ($costs, $job, $manager) {
                $costs->finalize($job->fresh(), $cost->fresh(), ['job_version' => $job->fresh()->lock_version, 'lock_version' => $cost->fresh()->lock_version], $manager);
            };
            $pay = function ($cost) use ($costs, $job, $finance) {
                $costs->markPaid($job->fresh(), $cost->fresh(), [
                    'job_version' => $job->fresh()->lock_version,
                    'payment_account_id' => \App\Models\ChartOfAccount::where('code', '11120')->value('id'),
                    'paid_date' => today()->toDateString(), 'pph23_amount' => '0',
                ], $finance);
            };
            $provision = function () {
                $account = app(\App\Services\JournalService::class)->mapped(['provision_wip'])['provision_wip'];
                return (float) DB::table('journal_entries')->where('chart_of_account_id', $account->id)->selectRaw('COALESCE(SUM(debit - credit), 0) AS balance')->value('balance');
            };
            $initial = $save('Initial freight', '100000', '700000');
            $finalize($initial);
            $invoice = $closing->createInvoiceForJob($job->fresh(), ['lock_version' => $job->fresh()->lock_version, 'invoice_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString(), 'tax' => '0', 'stamp_duty' => '0'], $finance)->fresh();
            $invoice = $closing->finalizeInvoice($invoice, ['job_version' => $job->fresh()->lock_version, 'lock_version' => $invoice->lock_version], $manager);
            $this->assertEquals(0, $provision());

            $latePaid = $save('Late cost paid', '500000', '0');
            $finalize($latePaid);
            $this->assertEquals(500000, $provision());
            $pay($latePaid);
            $this->assertEquals(0, $provision());

            // Previously rebuilding this invoice credited Provisi again by 500,000.
            $lateUnpaid = $save('Late cost unpaid', '250000', '0');
            $this->assertEquals(0, $provision());
            $finalize($lateUnpaid);
            $this->assertEquals(250000, $provision());

            $lateSale = $save('Additional sale', '100000', '200000');
            $finalize($lateSale);
            $invoice->refresh();
            $this->assertSame('draft', $invoice->status);
            $invoice = $closing->finalizeInvoice($invoice, ['job_version' => $job->fresh()->lock_version, 'lock_version' => $invoice->lock_version], $manager);
            $this->assertEquals(350000, $provision());
            $pay($lateUnpaid);
            $this->assertEquals(100000, $provision());
            $pay($lateSale);
            $this->assertEquals(0, $provision());
            $hppAccount = app(\App\Services\JournalService::class)->mapped(['cogs_import_unclosed'])['cogs_import_unclosed'];
            $hpp = DB::table('journal_entries')->where('chart_of_account_id', $hppAccount->id)->selectRaw('COALESCE(SUM(debit - credit), 0) AS balance')->value('balance');
            $this->assertEquals(950000, $hpp);
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('invoice_hpp_test');
        }
    }

    public function test_selling_changes_require_manager_approval_before_any_invoice_pdf(): void
    {
        $previous = DB::getDefaultConnection();
        config(['database.connections.invoice_approval_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        DB::setDefaultConnection('invoice_approval_test');
        try {
            Artisan::call('migrate', ['--database' => 'invoice_approval_test', '--force' => true]);
            foreach ([RolePermissionSeeder::class, ChartOfAccountSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class] as $seeder) {
                app($seeder)->run();
            }
            $finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
            $manager = User::where('email', 'finance-manager@jobfinance.test')->firstOrFail();
            $job = Job::factory()->open()->create(['service_type' => 'imp_sea']);
            $costs = app(JobCostService::class);
            $closing = app(JobClosingService::class);
            $data = ['job_version' => $job->lock_version, 'description' => 'Ocean Freight', 'type' => 'provision', 'cost_category' => 'payment_request', 'cost_date' => today()->toDateString(), 'currency' => 'IDR', 'quantity' => '1', 'unit' => 'Layanan', 'unit_cost' => '100000', 'unit_price' => '500000'];
            $cost = $costs->save($job, null, $data, $finance);
            $invoice = $closing->createInvoiceForJob($job->fresh(), ['lock_version' => $job->fresh()->lock_version, 'invoice_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString(), 'tax' => '0', 'stamp_duty' => '0'], $finance);
            $invoice->refresh();
            $invoice = $closing->finalizeInvoice($invoice, ['job_version' => $job->fresh()->lock_version, 'lock_version' => $invoice->lock_version], $manager);
            $this->assertSame('issued', $invoice->status);
            $oldJournal = Journal::where('type', 'job_invoice')->where('source_id', $job->id)->firstOrFail();

            $data['unit_price'] = '700000';
            $data['job_version'] = $job->fresh()->lock_version;
            $data['lock_version'] = $cost->lock_version;
            $cost = $costs->save($job->fresh(), $cost, $data, $finance);
            $invoice->refresh();
            $this->assertSame('draft', $invoice->status);
            $this->assertNull($invoice->issued_at);
            $this->assertEquals('700000.00', $invoice->total);
            $this->assertNotNull($oldJournal->fresh()->reversed_at);
            $this->assertSame(0, Journal::where('source_type', Job::class)->where('source_id', $job->id)->whereIn('type', ['job_invoice', 'job_invoice_amendment'])->whereNull('reversed_at')->whereNull('reversal_of_id')->count());

            $controller = app(InvoiceController::class);
            foreach ([
                fn () => $controller->preview($invoice),
                fn () => $controller->pdf(Request::create('/'), $invoice, app(MasterDataService::class)),
                fn () => app(OperationalDocumentController::class)->invoicePdf(Request::create('/'), $invoice, app(MasterDataService::class)),
            ] as $print) {
                try {
                    $print();
                    $this->fail('Draft invoice PDF must be blocked.');
                } catch (HttpException $error) {
                    $this->assertSame(403, $error->getStatusCode());
                }
            }
            try {
                $closing->finalizeInvoice($invoice, ['job_version' => $job->fresh()->lock_version, 'lock_version' => $invoice->lock_version], $finance);
                $this->fail('Finance cannot finalize an invoice.');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('invoice', $error->errors());
            }
            $invoice = $closing->finalizeInvoice($invoice, ['job_version' => $job->fresh()->lock_version, 'lock_version' => $invoice->lock_version], $manager);
            $this->assertSame('issued', $invoice->status);
            $this->assertEquals('700000.00', $invoice->total);
            $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $controller->preview($invoice));

            // Editing only the purchase cost does not require invoice approval again.
            $data['unit_cost'] = '120000';
            $data['job_version'] = $job->fresh()->lock_version;
            $data['lock_version'] = $cost->lock_version;
            $costs->save($job->fresh(), $cost, $data, $finance);
            $this->assertSame('issued', $invoice->fresh()->status);
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('invoice_approval_test');
        }
    }
}
