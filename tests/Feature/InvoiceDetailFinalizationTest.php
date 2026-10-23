<?php

namespace Tests\Feature;

use App\Http\Controllers\InvoiceController;
use App\Models\Job;
use App\Models\Journal;
use App\Models\User;
use App\Services\JobClosingService;
use App\Services\JobCostService;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvoiceDetailFinalizationTest extends TestCase
{
    private function scenario(callable $test): void
    {
        $previous = DB::getDefaultConnection();
        config(['database.connections.invoice_detail_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('invoice_detail_test');
        try {
            Artisan::call('migrate', ['--database' => 'invoice_detail_test', '--force' => true]);
            foreach ([RolePermissionSeeder::class, ChartOfAccountSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class] as $seeder) {
                app($seeder)->run();
            }
            $finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
            $manager = User::where('email', 'finance-manager@jobfinance.test')->firstOrFail();
            $job = Job::factory()->open()->create(['service_type' => 'exp_sea']);
            $costs = app(JobCostService::class);
            $cost = $costs->save($job, null, [
                'job_version' => $job->lock_version, 'description' => 'Ocean freight', 'cost_category' => 'payment_request',
                'cost_date' => today()->toDateString(), 'currency' => 'IDR', 'quantity' => '1', 'unit' => 'Layanan',
                'unit_cost' => '100000', 'unit_price' => '500000',
            ], $finance);
            $costs->finalize($job->fresh(), $cost->fresh(), [
                'job_version' => $job->fresh()->lock_version, 'lock_version' => $cost->fresh()->lock_version,
            ], $manager);
            $closing = app(JobClosingService::class);
            $invoice = $closing->createInvoiceForJob($job->fresh(), [
                'lock_version' => $job->fresh()->lock_version, 'invoice_date' => today()->toDateString(),
                'due_date' => today()->addDays(30)->toDateString(), 'tax' => '0', 'stamp_duty' => '0',
            ], $finance)->fresh();
            $test($job, $invoice, $finance, $manager, $closing);
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('invoice_detail_test');
        }
    }

    private function renderDetail($invoice, $actor): string
    {
        $this->actingAs($actor);

        return app(InvoiceController::class)->show($invoice->fresh())->with('errors', new ViewErrorBag)->render();
    }

    private function finalizeRequest($invoice, $job, $actor): Request
    {
        $request = Request::create(route('invoices.finalize', $invoice), 'POST', [
            'job_version' => $job->fresh()->lock_version, 'lock_version' => $invoice->fresh()->lock_version,
        ]);
        $request->setUserResolver(fn () => $actor);

        return $request;
    }

    private function stampRequest($invoice, $job, $actor, string $amount): Request
    {
        $request = Request::create(route('invoices.stamp-duty.update', $invoice), 'PATCH', [
            'job_version' => $job->fresh()->lock_version, 'lock_version' => $invoice->fresh()->lock_version,
            'stamp_duty' => $amount,
        ]);
        $request->setUserResolver(fn () => $actor);

        return $request;
    }

    public function test_finance_can_add_stamp_to_existing_draft_but_cannot_finalize_it(): void
    {
        $this->scenario(function ($job, $invoice, $finance, $manager, $closing) {
            $html = $this->renderDetail($invoice, $finance);
            $this->assertStringContainsString('id="invoice-stamp-duty-editor"', $html);
            $this->assertStringContainsString(route('invoices.stamp-duty.update', $invoice), $html);
            $response = app(InvoiceController::class)->updateStampDuty($this->stampRequest($invoice, $job, $finance, '10000'), $invoice, $closing);
            $this->assertSame(route('invoices.show', $invoice), $response->getTargetUrl());
            $this->assertSame('10000.00', $invoice->fresh()->stamp_duty);
            $this->assertSame('510000.00', $invoice->fresh()->total);
            $this->assertSame('510000.00', $invoice->fresh()->balance);
            $this->assertSame('draft', $invoice->fresh()->status);
            $this->assertSame(0, Journal::where('type', 'job_invoice')->count());
            try {
                app(InvoiceController::class)->finalize($this->finalizeRequest($invoice, $job, $finance), $invoice->fresh(), $closing);
                $this->fail('Adding stamp duty must not give Finance finalization rights.');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('invoice', $error->errors());
            }
        });
    }

    public function test_stamp_changes_on_issued_invoice_require_reapproval_and_never_duplicate_stamp_posting(): void
    {
        $this->scenario(function ($job, $invoice, $finance, $manager, $closing) {
            $controller = app(InvoiceController::class);
            $controller->finalize($this->finalizeRequest($invoice, $job, $manager), $invoice, $closing);
            foreach (['10000', '20000', '0'] as $amount) {
                $oldJournal = Journal::where('source_type', Job::class)->where('source_id', $job->id)
                    ->whereIn('type', ['job_invoice', 'job_invoice_amendment'])->whereNull('reversed_at')->firstOrFail();
                $controller->updateStampDuty($this->stampRequest($invoice, $job, $finance, $amount), $invoice->fresh(), $closing);
                $this->assertSame('draft', $invoice->fresh()->status);
                $this->assertNull($invoice->fresh()->issued_at);
                $this->assertNotNull($oldJournal->fresh()->reversed_at);
                $this->assertSame(0, Journal::where('source_type', Job::class)->where('source_id', $job->id)
                    ->whereIn('type', ['job_invoice', 'job_invoice_amendment'])->whereNull('reversed_at')->count());
                foreach ([
                    fn () => $controller->preview($invoice->fresh()),
                    fn () => $controller->pdf(Request::create('/'), $invoice->fresh(), app(\App\Services\MasterDataService::class)),
                ] as $print) {
                    try {
                        $print();
                        $this->fail('Printing must wait for stamp-duty reapproval.');
                    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $error) {
                        $this->assertSame(403, $error->getStatusCode());
                    }
                }
                $controller->finalize($this->finalizeRequest($invoice, $job, $manager), $invoice->fresh(), $closing);
                $this->assertSame('issued', $invoice->fresh()->status);
                $this->assertSame((string) \App\Support\Money::decimal('500000')->plus($amount), $invoice->fresh()->total);
                $this->assertSame(1, Journal::where('source_type', Job::class)->where('source_id', $job->id)
                    ->whereIn('type', ['job_invoice', 'job_invoice_amendment'])->whereNull('reversed_at')->count());
                $netStamp = \App\Models\JournalEntry::whereHas('account', fn ($q) => $q->where('code', '65600'))
                    ->get()->reduce(fn ($sum, $e) => $sum->plus($e->credit)->minus($e->debit), \App\Support\Money::decimal(0));
                $this->assertTrue($netStamp->isEqualTo(\App\Support\Money::decimal($amount)));
                $netRevenue = \App\Models\JournalEntry::whereHas('account', fn ($q) => $q->where('code', '42100'))
                    ->get()->reduce(fn ($sum, $e) => $sum->plus($e->credit)->minus($e->debit), \App\Support\Money::decimal(0));
                $this->assertSame('500000.00', (string) $netRevenue);
            }
        });
    }

    public function test_stamp_update_rejects_stale_forms_and_unchanged_value_does_not_invalidate_issued_invoice(): void
    {
        $this->scenario(function ($job, $invoice, $finance, $manager, $closing) {
            $controller = app(InvoiceController::class);
            $controller->finalize($this->finalizeRequest($invoice, $job, $manager), $invoice, $closing);
            $count = Journal::count();
            $version = $invoice->fresh()->lock_version;
            $controller->updateStampDuty($this->stampRequest($invoice, $job, $finance, '0'), $invoice->fresh(), $closing);
            $this->assertSame('issued', $invoice->fresh()->status);
            $this->assertSame($count, Journal::count());
            $this->assertSame($version, $invoice->fresh()->lock_version);
            $stale = $this->stampRequest($invoice, $job, $finance, '10000');
            $controller->updateStampDuty($stale, $invoice->fresh(), $closing);
            $count = Journal::count();
            try {
                $controller->updateStampDuty($stale, $invoice->fresh(), $closing);
                $this->fail('A stale form must not overwrite a newer invoice.');
            } catch (ValidationException $error) {
                $this->assertSame('stampDuty', $error->errorBag);
            }
            $this->assertSame($count, Journal::count());
            $this->assertSame('10000.00', $invoice->fresh()->stamp_duty);
        });
    }

    public function test_stamp_update_rejects_invalid_amounts_paid_invoices_and_closed_jobs(): void
    {
        $this->scenario(function ($job, $invoice, $finance, $manager, $closing) {
            $controller = app(InvoiceController::class);
            foreach (['-10000', '1000000000', '10000.001', 'not-money'] as $amount) {
                try {
                    $controller->updateStampDuty($this->stampRequest($invoice, $job, $finance, $amount), $invoice->fresh(), $closing);
                    $this->fail('Invalid stamp amount must be rejected.');
                } catch (ValidationException $error) {
                    $this->assertSame('stampDuty', $error->errorBag);
                }
            }
            $invoice->update(['status' => 'partially_paid', 'paid_amount' => '10000']);
            try {
                $controller->updateStampDuty($this->stampRequest($invoice, $job, $finance, '10000'), $invoice->fresh(), $closing);
                $this->fail('Paid invoice must not be edited through this route.');
            } catch (ValidationException $error) {
                $this->assertSame('stampDuty', $error->errorBag);
            }
            $this->assertStringNotContainsString('id="invoice-stamp-duty-editor"', $this->renderDetail($invoice, $finance));
            $invoice->update(['status' => 'draft', 'paid_amount' => '0']);
            $job->update(['status' => 'closed']);
            try {
                $controller->updateStampDuty($this->stampRequest($invoice, $job, $finance, '10000'), $invoice->fresh(), $closing);
                $this->fail('Closed Job must not be edited through this route.');
            } catch (ValidationException $error) {
                $this->assertSame('stampDuty', $error->errorBag);
            }
            $this->assertSame('0.00', $invoice->fresh()->stamp_duty);
        });
    }

    public function test_manager_can_finalize_after_review_with_correct_versions(): void
    {
        $this->scenario(function ($job, $invoice, $finance, $manager) {
            $html = $this->renderDetail($invoice, $manager);
            $previousErrors = libxml_use_internal_errors(true);
            try {
                $document = new \DOMDocument;
                $document->loadHTML($html, LIBXML_NONET);
                $xpath = new \DOMXPath($document);
                $this->assertSame(1, $xpath->query('//form[@id="invoice-finalize-top"]')->length);
                $this->assertSame(1, $xpath->query('//section[@id="invoice-finalization-review"]//form[@id="invoice-finalize-after-review"]')->length);
                $form = $xpath->query('//form[@id="invoice-finalize-after-review"]')->item(0);
                $this->assertSame('POST', $form->getAttribute('method'));
                $this->assertSame(route('invoices.finalize', $invoice), $form->getAttribute('action'));
                foreach (['job_version' => $job->fresh()->lock_version, 'lock_version' => $invoice->fresh()->lock_version] as $name => $value) {
                    $input = $xpath->query('.//input[@name="'.$name.'"]', $form)->item(0);
                    $this->assertSame((string) $value, $input->getAttribute('value'));
                }
                $this->assertSame(1, $xpath->query('.//input[@name="_token"]', $form)->length);
                $this->assertNotEmpty($form->getAttribute('data-confirm'));
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previousErrors);
            }
        });
    }

    public function test_finance_does_not_get_finalize_button_and_cannot_submit(): void
    {
        $this->scenario(function ($job, $invoice, $finance, $manager, $closing) {
            $html = $this->renderDetail($invoice, $finance);
            $this->assertStringNotContainsString('id="invoice-finalize-after-review"', $html);
            $this->assertStringNotContainsString('id="invoice-finalize-top"', $html);
            try {
                app(InvoiceController::class)->finalize($this->finalizeRequest($invoice, $job, $finance), $invoice, $closing);
                $this->fail('Finance may not finalize invoices.');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('invoice', $error->errors());
            }
            $this->assertSame('draft', $invoice->fresh()->status);
            $this->assertSame(0, Journal::where('type', 'job_invoice')->count());
        });
    }

    public function test_finalizing_from_detail_returns_to_same_invoice_without_duplicate_journals(): void
    {
        $this->scenario(function ($job, $invoice, $finance, $manager, $closing) {
            $request = $this->finalizeRequest($invoice, $job, $manager);
            $response = app(InvoiceController::class)->finalize($request, $invoice, $closing);
            $this->assertSame(route('invoices.show', $invoice), $response->getTargetUrl());
            $this->assertSame('issued', $invoice->fresh()->status);
            $this->assertSame(1, Journal::where('type', 'job_invoice')->count());
            $html = $this->renderDetail($invoice, $manager);
            $this->assertStringNotContainsString('id="invoice-finalize-after-review"', $html);
            $this->assertStringNotContainsString('id="invoice-finalize-top"', $html);
            try {
                app(InvoiceController::class)->finalize($request, $invoice->fresh(), $closing);
                $this->fail('Repeated submission must not post again.');
            } catch (ValidationException) {
                $this->assertSame(1, Journal::where('type', 'job_invoice')->count());
            }
        });
    }

    public function test_closed_job_does_not_offer_draft_invoice_finalization(): void
    {
        $this->scenario(function ($job, $invoice, $finance, $manager) {
            $job->update(['status' => 'closed']);
            $html = $this->renderDetail($invoice, $manager);
            $this->assertStringNotContainsString('id="invoice-finalize-after-review"', $html);
            $this->assertStringNotContainsString('id="invoice-finalize-top"', $html);
        });
    }
}
