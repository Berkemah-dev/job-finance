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
