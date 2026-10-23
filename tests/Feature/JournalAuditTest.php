<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Job;
use App\Models\User;
use App\Services\JobClosingService;
use App\Services\JobCostService;
use App\Services\JournalService;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JournalAuditTest extends TestCase
{
    private function audit(callable $scenario): void
    {
        $previous = DB::getDefaultConnection();
        config(['database.connections.journal_audit' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        DB::setDefaultConnection('journal_audit');
        try {
            Artisan::call('migrate', ['--database' => 'journal_audit', '--force' => true]);
            foreach ([RolePermissionSeeder::class, ChartOfAccountSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class] as $seeder) app($seeder)->run();
            $finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
            $manager = User::where('email', 'finance-manager@jobfinance.test')->firstOrFail();
            $job = Job::factory()->open()->create(['service_type' => 'exp_sea']);
            $costs = app(JobCostService::class);
            $closing = app(JobClosingService::class);
            $save = fn ($description, $purchase, $sale) => $costs->save($job->fresh(), null, [
                'job_version' => $job->fresh()->lock_version, 'description' => $description, 'type' => 'provision', 'cost_category' => 'payment_request',
                'cost_date' => today()->toDateString(), 'currency' => 'IDR', 'quantity' => '1', 'unit' => 'Layanan', 'unit_cost' => $purchase, 'unit_price' => $sale,
            ], $finance);
            $finalize = fn ($cost) => $costs->finalize($job->fresh(), $cost->fresh(), ['job_version' => $job->fresh()->lock_version, 'lock_version' => $cost->fresh()->lock_version], $manager);
            $pay = fn ($cost) => $costs->markPaid($job->fresh(), $cost->fresh(), ['job_version' => $job->fresh()->lock_version, 'payment_account_id' => ChartOfAccount::where('code', '11120')->value('id'), 'paid_date' => today()->toDateString(), 'pph23_amount' => '0'], $finance);
            $balance = function ($key) {
                $account = app(JournalService::class)->mapped([$key])[$key];
                return (float) DB::table('journal_entries')->where('chart_of_account_id', $account->id)->selectRaw('COALESCE(SUM(debit - credit), 0) AS balance')->value('balance');
            };
            $initial = $save('Initial freight', '100000', '700000');
            $finalize($initial);
            $invoice = $closing->createInvoiceForJob($job->fresh(), ['lock_version' => $job->fresh()->lock_version, 'invoice_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString(), 'tax' => '0', 'stamp_duty' => '0'], $finance)->fresh();
            $issue = fn () => $closing->finalizeInvoice($invoice->fresh(), ['job_version' => $job->fresh()->lock_version, 'lock_version' => $invoice->fresh()->lock_version], $manager);
            $scenario($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue);
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('journal_audit');
        }
    }

    private function assertWipLedgerNeverNegative(): void
    {
        foreach (['temporary', 'provision_wip'] as $key) {
            $account = app(JournalService::class)->mapped([$key])[$key];
            $ledger = app(\App\Services\FinancialReportService::class)->ledgerExport($account->id, '1900-01-01', today()->toDateString());
            foreach ($ledger['entries'] as $entry) {
                $this->assertFalse(\App\Support\Money::decimal($entry->running_balance)->isNegative(),
                    $key.' became negative at '.$entry->journal->number.': '.$entry->running_balance);
            }
        }
    }

    public function test_editing_invoiced_unpaid_cost_releases_invoice_before_reversing_recognition(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            $issue();
            $initial = $job->costs()->firstOrFail();
            $costs->save($job->fresh(), $initial, [
                'job_version' => $job->fresh()->lock_version, 'lock_version' => $initial->lock_version,
                'description' => $initial->description, 'cost_category' => 'payment_request',
                'cost_date' => today()->toDateString(), 'currency' => 'IDR', 'quantity' => '1',
                'unit' => 'Layanan', 'unit_cost' => '120000', 'unit_price' => '700000',
            ], $finance);
            $this->assertWipLedgerNeverNegative();
            $this->assertEquals(0, $balance('provision_wip'));
        });
    }

    public function test_invoice_rejects_unrecognized_temporary_without_posting_any_journal(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            $costs->save($job->fresh(), null, [
                'job_version' => $job->fresh()->lock_version, 'description' => 'Temporary handling',
                'cost_category' => 'reimbursement', 'cost_date' => today()->toDateString(),
                'currency' => 'IDR', 'quantity' => '1', 'unit' => 'Layanan',
                'unit_cost' => '250000', 'unit_price' => '250000',
            ], $finance);
            $before = \App\Models\Journal::count();
            try {
                $issue();
                $this->fail('Invoice must not credit Temporary before cost recognition.');
            } catch (\Illuminate\Validation\ValidationException $error) {
                $this->assertArrayHasKey('invoice', $error->errors());
            }
            $this->assertSame('draft', $invoice->fresh()->status);
            $this->assertSame($before, \App\Models\Journal::count());
            $this->assertWipLedgerNeverNegative();
            $temporary = $job->costs()->where('type', 'temporary')->firstOrFail();
            $finalize($temporary);
            $issue();
            $this->assertSame('issued', $invoice->fresh()->status);
            $this->assertEquals(0, $balance('temporary'));
            $this->assertWipLedgerNeverNegative();
        });
    }

    public function test_editing_invoiced_temporary_preserves_nonnegative_ledger_history(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            $data = ['description' => 'Temporary handling', 'cost_category' => 'reimbursement',
                'cost_date' => today()->toDateString(), 'currency' => 'IDR', 'quantity' => '1',
                'unit' => 'Layanan', 'unit_cost' => '250000', 'unit_price' => '250000'];
            $temporary = $costs->save($job->fresh(), null, $data + ['job_version' => $job->fresh()->lock_version], $finance);
            $finalize($temporary);
            $issue();
            $data['unit_cost'] = $data['unit_price'] = '300000';
            $temporary = $costs->save($job->fresh(), $temporary->fresh(), $data + [
                'job_version' => $job->fresh()->lock_version, 'lock_version' => $temporary->fresh()->lock_version,
            ], $finance);
            $this->assertSame('draft', $invoice->fresh()->status);
            $this->assertWipLedgerNeverNegative();
            $finalize($temporary);
            $issue();
            $this->assertEquals(0, $balance('temporary'));
            $this->assertWipLedgerNeverNegative();
        });
    }

    public function test_paid_subsequent_cost_increase_never_makes_provision_ledger_negative(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            $issue();
            $late = $save('Paid handling', '500000', '0');
            $finalize($late);
            $pay($late);
            foreach (['800000', '900000'] as $amount) {
                $late = $costs->save($job->fresh(), $late->fresh(), [
                    'job_version' => $job->fresh()->lock_version, 'lock_version' => $late->fresh()->lock_version,
                    'description' => 'Paid handling', 'cost_category' => 'payment_request',
                    'cost_date' => today()->toDateString(), 'currency' => 'IDR', 'quantity' => '1',
                    'unit' => 'Layanan', 'unit_cost' => $amount, 'unit_price' => '0',
                ], $finance);
                $this->assertSame('draft', $late->fresh()->status);
                $finalize($late);
                $this->assertWipLedgerNeverNegative();
                $pay($late);
                $this->assertEquals((float) $amount, (float) (string) $costs->paidAmount($late->fresh()));
                $this->assertEquals(0, $balance('provision_wip'));
                $this->assertEquals(100000 + (float) $amount, $balance('cogs_export_unclosed'));
                $this->assertWipLedgerNeverNegative();
            }
        });
    }

    public function test_next_day_invoice_revisions_do_not_backdate_wip_reclassification(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            $issue();
            $originalInvoiceDate = $invoice->fresh()->invoice_date->format('Y-m-d');
            $previousClock = \Illuminate\Support\Carbon::getTestNow();
            \Illuminate\Support\Carbon::setTestNow(today()->addDay()->setTime(12, 0));
            try {
                $late = $save('Next-day handling', '500000', '0');
                $finalize($late);
                $this->assertWipLedgerNeverNegative();
                $sale = $save('Next-day sale', '100000', '200000');
                $finalize($sale);
                $issue();
                $this->assertSame($originalInvoiceDate, $invoice->fresh()->invoice_date->format('Y-m-d'));
                $this->assertWipLedgerNeverNegative();
            } finally {
                \Illuminate\Support\Carbon::setTestNow($previousClock);
            }
        });
    }

    private function trialLegacyPair($job, $manager): array
    {
        return DB::transaction(function () use ($job, $manager) {
            $service = app(JournalService::class);
            $maps = $service->mapped(['provision_wip', 'cogs_export_unclosed']);
            $old = $service->post('job_invoice_amendment', Job::class, $job->id, today()->toDateString(), 'Legacy trial reclassification', [
                ['account_id' => $maps['cogs_export_unclosed']->id, 'description' => 'Legacy HPP', 'debit' => '250000', 'credit' => '0'],
                ['account_id' => $maps['provision_wip']->id, 'description' => 'Legacy Provision', 'debit' => '0', 'credit' => '250000'],
            ], $manager);
            $reverse = $service->reverse($old->fresh(), ['lock_version' => $old->fresh()->lock_version, 'reversal_date' => today()->toDateString(), 'reason' => 'Trial fixture'], $manager);

            return [$old, $reverse];
        });
    }

    public function test_trial_history_cleanup_is_scoped_backed_up_and_idempotent(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            \Illuminate\Support\Facades\Storage::fake('private');
            $issue();
            [$old, $reverse] = $this->trialLegacyPair($job, $manager);
            $active = \App\Models\Journal::with('entries')->whereNull('reversed_at')->whereNull('reversal_of_id')->get()->toJson();
            $service = app(\App\Services\TrialJournalHistoryService::class);
            $confirmation = 'TRIAL '.$job->number;
            $count = \App\Models\Journal::count();
            $plan = $service->compact($job, $manager, $confirmation);
            $this->assertSame(1, $plan['jumlah_pasangan']);
            $this->assertSame($count, \App\Models\Journal::count());
            $this->assertSame([], \Illuminate\Support\Facades\Storage::disk('private')->allFiles());

            $result = $service->compact($job, $manager, $confirmation, true);
            $this->assertSame($count - 2, \App\Models\Journal::count());
            $this->assertFalse(\App\Models\Journal::whereKey($old->id)->exists());
            $this->assertFalse(\App\Models\Journal::whereKey($reverse->id)->exists());
            $this->assertSame($active, \App\Models\Journal::with('entries')->whereNull('reversed_at')->whereNull('reversal_of_id')->get()->toJson());
            $this->assertSame($result['saldo_per_akun_sebelum'], $result['saldo_per_akun_sesudah']);
            $files = \Illuminate\Support\Facades\Storage::disk('private')->allFiles();
            $this->assertCount(1, $files);
            $json = \Illuminate\Support\Facades\Storage::disk('private')->get($files[0]);
            $this->assertSame($result['sha256'], hash('sha256', $json));
            $backup = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame($job->number, $backup['job_number']);
            $this->assertCount(2, $backup['archived_journals']);
            $this->assertSame($old->id, $backup['archived_journals'][0]['journal']['id']);
            $this->assertWipLedgerNeverNegative();
            $this->assertSame(0, $service->compact($job, $manager, $confirmation, true)['jumlah_pasangan']);
            $this->assertCount(1, \Illuminate\Support\Facades\Storage::disk('private')->allFiles());
        });
    }

    public function test_trial_history_cleanup_rejects_unequal_reversals(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            $issue();
            [$old, $reverse] = $this->trialLegacyPair($job, $manager);
            $reverse->entries()->where('debit', '>', 0)->update(['debit' => '200000']);
            $reverse->entries()->where('credit', '>', 0)->update(['credit' => '200000']);
            try {
                app(\App\Services\TrialJournalHistoryService::class)->compact($job, $manager, 'TRIAL '.$job->number, true);
                $this->fail('Unequal reversal must not be deleted.');
            } catch (\Illuminate\Validation\ValidationException $error) {
                $this->assertArrayHasKey('trial_history', $error->errors());
            }
            $this->assertNotNull($old->fresh());
            $this->assertNotNull($reverse->fresh());
        });
    }

    public function test_trial_history_cleanup_rolls_back_if_other_history_is_still_negative(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            \Illuminate\Support\Facades\Storage::fake('private');
            $issue();
            [$old, $reverse] = $this->trialLegacyPair($job, $manager);
            $outside = Job::factory()->open()->create(['service_type' => 'exp_sea']);
            DB::transaction(function () use ($outside, $manager) {
                $service = app(JournalService::class);
                $maps = $service->mapped(['provision_wip', 'cogs_export_unclosed']);
                $service->post('outside_trial', Job::class, $outside->id, today()->toDateString(), 'Outside scope', [
                    ['account_id' => $maps['cogs_export_unclosed']->id, 'description' => 'Outside', 'debit' => '100000', 'credit' => '0'],
                    ['account_id' => $maps['provision_wip']->id, 'description' => 'Outside', 'debit' => '0', 'credit' => '100000'],
                ], $manager);
            });
            $before = \App\Models\Journal::with('entries')->orderBy('id')->get()->toJson();
            try {
                app(\App\Services\TrialJournalHistoryService::class)->compact($job, $manager, 'TRIAL '.$job->number, true);
                $this->fail('Remaining negative history must roll back all deletions.');
            } catch (\Illuminate\Validation\ValidationException $error) {
                $this->assertArrayHasKey('trial_history', $error->errors());
            }
            $this->assertSame($before, \App\Models\Journal::with('entries')->orderBy('id')->get()->toJson());
        });
    }

    public function test_trial_history_cleanup_requires_confirmation_and_unpaid_customer_invoice(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            $issue();
            $service = app(\App\Services\TrialJournalHistoryService::class);
            try {
                $service->compact($job, $manager, 'TRIAL WRONG-JOB', true);
                $this->fail('Exact scope confirmation is required.');
            } catch (\Illuminate\Validation\ValidationException $error) {
                $this->assertArrayHasKey('trial_history', $error->errors());
            }
            $invoice->update(['paid_amount' => '1.00']);
            try {
                $service->compact($job, $manager, 'TRIAL '.$job->number, true);
                $this->fail('An invoice with customer payments must not be compacted.');
            } catch (\Illuminate\Validation\ValidationException $error) {
                $this->assertArrayHasKey('trial_history', $error->errors());
            }
        });
    }

    private function hppDetailTotal($rows): string
    {
        return (string) $rows->reduce(fn ($sum, $row) => $sum->plus($row->total_cost), \App\Support\Money::decimal(0));
    }

    public function test_closed_job_charge_details_leave_unclosed_and_appear_only_in_closed(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            $issue();
            $late = $save('Late handling', '500000', '0');
            $finalize($late);
            $pay($late);
            $unpaid = $save('Unpaid handling', '250000', '0');
            $finalize($unpaid);
            $report = app(\App\Services\FinancialReportService::class);
            $from = today()->startOfMonth()->toDateString();
            $to = today()->toDateString();
            $open = $report->incomeStatement($from, $to);
            $this->assertSame('600000.00', $this->hppDetailTotal($open['hppUnclosedBreakdown']));
            $this->assertSame($open['totalHppUnclosed'], $this->hppDetailTotal($open['hppUnclosedBreakdown']));
            $this->assertCount(0, $open['hppBreakdown']);
            $closing->close($job->fresh(), ['lock_version' => $job->fresh()->lock_version,
                'closing_date' => $to, 'due_date' => today()->addDays(30)->toDateString()], $manager);
            $count = \App\Models\Journal::count();
            $closed = $report->incomeStatement($from, $to);
            $this->assertSame('0.00', $closed['totalHppUnclosed']);
            $this->assertCount(0, $closed['hppUnclosedBreakdown']);
            $this->assertSame('850000.00', $this->hppDetailTotal($closed['hppBreakdown']));
            $this->assertSame($closed['totalHppClosed'], $this->hppDetailTotal($closed['hppBreakdown']));
            $this->assertSame($count, \App\Models\Journal::count(), 'Rendering the report must never change journals.');
        });
    }

    public function test_mixed_open_and_closed_jobs_do_not_duplicate_hpp_details_and_reopen_is_supported(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            $issue();
            $other = Job::factory()->open()->create(['service_type' => 'exp_sea']);
            $cost = $costs->save($other, null, ['job_version' => $other->lock_version,
                'description' => 'Initial freight', 'cost_category' => 'payment_request', 'cost_date' => today()->toDateString(),
                'currency' => 'IDR', 'quantity' => '1', 'unit' => 'Layanan', 'unit_cost' => '250000', 'unit_price' => '400000'], $finance);
            $costs->finalize($other->fresh(), $cost->fresh(), ['job_version' => $other->fresh()->lock_version,
                'lock_version' => $cost->fresh()->lock_version], $manager);
            $makeInvoice = function ($target) use ($closing, $finance, $manager) {
                $draft = $closing->createInvoiceForJob($target->fresh(), ['lock_version' => $target->fresh()->lock_version,
                    'invoice_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString(), 'tax' => '0', 'stamp_duty' => '0'], $finance);
                $closing->finalizeInvoice($draft->fresh(), ['job_version' => $target->fresh()->lock_version,
                    'lock_version' => $draft->fresh()->lock_version], $manager);
            };
            $makeInvoice($other);
            $closing->close($job->fresh(), ['lock_version' => $job->fresh()->lock_version,
                'closing_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString()], $manager);
            $report = app(\App\Services\FinancialReportService::class);
            $read = fn () => $report->incomeStatement(today()->startOfMonth()->toDateString(), today()->toDateString());
            $r = $read();
            $this->assertSame('250000.00', $this->hppDetailTotal($r['hppUnclosedBreakdown']));
            $this->assertSame('100000.00', $this->hppDetailTotal($r['hppBreakdown']));
            foreach ($r['hppUnclosedBreakdown'] as $row) {
                foreach ($row->items as $item) {
                    $this->assertSame($other->id, $item['job_id']);
                }
            }
            $closing->reopen($job->fresh(), ['lock_version' => $job->fresh()->lock_version, 'reason' => 'Report regression'], $manager);
            $makeInvoice($job);
            $r = $read();
            $this->assertSame('0.00', $r['totalHppClosed']);
            $this->assertCount(0, $r['hppBreakdown']);
            $this->assertSame('350000.00', $this->hppDetailTotal($r['hppUnclosedBreakdown']));
            $this->assertSame($r['totalHppUnclosed'], $this->hppDetailTotal($r['hppUnclosedBreakdown']));
        });
    }

    public function test_hpp_details_respect_historical_periods_when_closing_in_next_month(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            $issue();
            $originalDay = today();
            $previousClock = \Illuminate\Support\Carbon::getTestNow();
            \Illuminate\Support\Carbon::setTestNow($originalDay->copy()->addMonthNoOverflow()->startOfMonth()->setTime(12, 0));
            try {
                $closing->close($job->fresh(), ['lock_version' => $job->fresh()->lock_version,
                    'closing_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString()], $manager);
                $report = app(\App\Services\FinancialReportService::class);
                $old = $report->incomeStatement($originalDay->copy()->startOfMonth()->toDateString(), $originalDay->copy()->endOfMonth()->toDateString());
                $this->assertSame('100000.00', $this->hppDetailTotal($old['hppUnclosedBreakdown']));
                $this->assertCount(0, $old['hppBreakdown']);
                $current = $report->incomeStatement(today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString());
                $this->assertSame('-100000.00', $this->hppDetailTotal($current['hppUnclosedBreakdown']));
                $this->assertSame($current['totalHppUnclosed'], $this->hppDetailTotal($current['hppUnclosedBreakdown']));
                $this->assertStringContainsString('Reklasifikasi', $current['hppUnclosedBreakdown']->first()->description);
                $this->assertSame('100000.00', $this->hppDetailTotal($current['hppBreakdown']));
                $all = $report->incomeStatement($originalDay->copy()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString());
                $this->assertCount(0, $all['hppUnclosedBreakdown']);
                $this->assertSame('100000.00', $this->hppDetailTotal($all['hppBreakdown']));
            } finally {
                \Illuminate\Support\Carbon::setTestNow($previousClock);
            }
        });
    }

    public function test_unpaid_restores_provision_after_standalone_hpp_reclassification(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            $late = $save('Paid before approval', '500000', '0');
            $pay($late);
            $issue();
            $finalize($late);
            $costs->unpaid($job->fresh(), $late->fresh(), ['job_version' => $job->fresh()->lock_version, 'lock_version' => $late->fresh()->lock_version], $finance);
            $this->assertEquals(500000, $balance('provision_wip'), 'Unpaid should restore 500,000 to Provision.');
            $this->assertEquals(100000, $balance('cogs_export_unclosed'));
            $this->assertSame(0, \App\Models\Journal::where('source_type', \App\Models\JobCost::class)->where('source_id', $late->id)->where('type', 'job_cost_post_invoice_hpp')->whereNull('reversed_at')->count());
            $pay($late);
            $this->assertEquals(0, $balance('provision_wip'));
            $this->assertEquals(600000, $balance('cogs_export_unclosed'));
        });
    }

    public function test_closing_does_not_leave_negative_hpp_unclosed_for_unpaid_subsequent_costs(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            $issue();
            $late = $save('Unpaid subsequent cost', '500000', '0');
            $finalize($late);
            $closing->close($job->fresh(), ['lock_version' => $job->fresh()->lock_version, 'closing_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString()], $manager);
            $this->assertEquals(0, $balance('cogs_export_unclosed'), 'Closed job should not leave negative HPP Unclosed.');
            $this->assertEquals(0, $balance('provision_wip'));
            $this->assertEquals(600000, $balance('cogs_export_closed'));
        });
    }

    public function test_closing_mixed_paid_and_unpaid_costs_reclassifies_only_remaining_provision(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            $issue();
            $paid = $save('Paid subsequent cost', '500000', '0');
            $finalize($paid);
            $pay($paid);
            $unpaid = $save('Unpaid subsequent cost', '250000', '0');
            $finalize($unpaid);
            $this->assertEquals(250000, $balance('provision_wip'));
            $this->assertEquals(600000, $balance('cogs_export_unclosed'));
            $closing->close($job->fresh(), ['lock_version' => $job->fresh()->lock_version, 'closing_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString()], $manager);
            $this->assertEquals(0, $balance('provision_wip'));
            $this->assertEquals(0, $balance('cogs_export_unclosed'));
            $this->assertEquals(850000, $balance('cogs_export_closed'));
            $this->assertEquals(-350000, $balance('vendor_payable'));
        });
    }

    public function test_legacy_unpaid_repair_is_audited_and_idempotent(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            $late = $save('Legacy unpaid cost', '500000', '0');
            $pay($late);
            $issue();
            $finalize($late);
            // Reproduce the old Unpaid implementation that left standalone HPP active.
            DB::transaction(function () use ($late, $manager) {
                foreach (\App\Models\Journal::where('source_type', \App\Models\JobCost::class)->where('source_id', $late->id)->where('type', 'job_cost_payment')->whereNull('reversed_at')->get() as $journal) {
                    app(JournalService::class)->reverse($journal, ['lock_version' => $journal->lock_version, 'reversal_date' => today()->toDateString(), 'reason' => 'Legacy unpaid fixture'], $manager);
                }
                $late->update(['paid_at' => null, 'paid_date' => null]);
            });
            $repair = app(\App\Services\LegacyJournalRepairService::class);
            $count = \App\Models\Journal::count();
            $this->assertCount(1, $repair->inspect($job->fresh())['reverse_ids']);
            $this->assertSame($count, \App\Models\Journal::count());
            $this->assertSame(0, Artisan::call('accounting:repair-legacy-journals', ['--job' => $job->number]));
            $this->assertSame($count, \App\Models\Journal::count());
            try {
                $repair->repair($job, $finance, today()->toDateString());
                $this->fail('Finance cannot apply historical repairs.');
            } catch (\Illuminate\Validation\ValidationException $error) {
                $this->assertArrayHasKey('actor', $error->errors());
            }
            $this->assertSame(0, Artisan::call('accounting:repair-legacy-journals', ['--document' => $late->number, '--apply' => true, '--actor' => $manager->email]));
            $this->assertEquals(500000, $balance('provision_wip'));
            $this->assertEquals(100000, $balance('cogs_export_unclosed'));
            $this->assertSame($count + 1, \App\Models\Journal::count());
            $this->assertSame([], $repair->repair($job, $manager, today()->toDateString())['created_journals']);
            $this->assertSame($count + 1, \App\Models\Journal::count());
        });
    }

    public function test_legacy_closing_repair_preserves_closed_hpp_and_reverses_on_reopen(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            $issue();
            $late = $save('Legacy closing cost', '500000', '0');
            $finalize($late);
            $closing->close($job->fresh(), ['lock_version' => $job->fresh()->lock_version, 'closing_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString()], $manager);
            // Restore the old error: Provisi +500,000 and HPP Unclosed -500,000.
            // In this isolated fixture only, omit the two entries the old code never created.
            $closingJournal = \App\Models\Journal::where('source_type', Job::class)->where('source_id', $job->id)->where('type', 'job_closing')->firstOrFail();
            $closingJournal->entries()->where(fn ($q) => $q->where('description', 'like', 'HPP Unclose · Sisa Provisi saat closing%')->orWhere('description', 'like', 'Reklasifikasi sisa Provisi saat closing%'))->delete();
            $repair = app(\App\Services\LegacyJournalRepairService::class);
            $plan = $repair->inspect($job->fresh());
            $this->assertCount(2, $plan['entries']);
            $this->assertSame([], $plan['warnings']);
            $result = $repair->repair($job->fresh(), $manager, today()->toDateString());
            $this->assertCount(1, $result['created_journals']);
            $this->assertEquals(0, $balance('provision_wip'));
            $this->assertEquals(0, $balance('cogs_export_unclosed'));
            $this->assertEquals(600000, $balance('cogs_export_closed'));
            $this->assertSame([], $repair->repair($job->fresh(), $manager, today()->toDateString())['created_journals']);
            $closing->reopen($job->fresh(), ['lock_version' => $job->fresh()->lock_version, 'reason' => 'Test reopen corrected job'], $manager);
            $correction = \App\Models\Journal::where('number', $result['created_journals'][0])->firstOrFail();
            $this->assertNotNull($correction->reversed_at);
        });
    }

    public function test_legacy_repair_refuses_unexplained_accounting_differences(): void
    {
        $this->audit(function ($job, $invoice, $finance, $manager, $costs, $closing, $save, $finalize, $pay, $balance, $issue) {
            $issue();
            $late = $save('Unexplained legacy cost', '500000', '0');
            $finalize($late);
            $closing->close($job->fresh(), ['lock_version' => $job->fresh()->lock_version, 'closing_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString()], $manager);
            $job->costs()->whereKey($late->id)->update(['total_cost' => '550000']);
            $closingJournal = \App\Models\Journal::where('source_type', Job::class)->where('source_id', $job->id)->where('type', 'job_closing')->firstOrFail();
            $closingJournal->entries()->where(fn ($q) => $q->where('description', 'like', 'HPP Unclose · Sisa Provisi saat closing%')->orWhere('description', 'like', 'Reklasifikasi sisa Provisi saat closing%'))->delete();
            $repair = app(\App\Services\LegacyJournalRepairService::class);
            $this->assertNotEmpty($repair->inspect($job->fresh())['warnings']);
            $count = \App\Models\Journal::count();
            try {
                $repair->repair($job->fresh(), $manager, today()->toDateString());
                $this->fail('Unexplained differences must not be repaired automatically.');
            } catch (\Illuminate\Validation\ValidationException $error) {
                $this->assertArrayHasKey('journal', $error->errors());
            }
            $this->assertSame($count, \App\Models\Journal::count());
        });
    }
}
