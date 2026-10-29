<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\JobCost;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\JobClosingService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ClosingPaymentTest extends TestCase
{
    private string $previousConnection;

    private User $finance;

    private Job $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance('env', 'testing');
        $this->previousConnection = DB::getDefaultConnection();
        config(['database.connections.closing_payment_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('closing_payment_test');
        Artisan::call('migrate', ['--database' => 'closing_payment_test', '--force' => true]);
        foreach ([RolePermissionSeeder::class, ChartOfAccountSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class] as $seeder) app($seeder)->run();
        $this->finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
        $this->actingAs($this->finance);
        $this->job = Job::factory()->open()->create();
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->previousConnection);
        DB::purge('closing_payment_test');
        parent::tearDown();
    }

    private function finalCosts(): void
    {
        JobCost::factory()->create(['job_id' => $this->job->id, 'status' => 'final', 'type' => 'temporary', 'description' => 'Dokumen', 'quantity' => '1.00', 'unit_cost' => '5000000.00', 'unit_price' => '5000000.00', 'total_cost' => '5000000.00', 'total_price' => '5000000.00', 'finalized_by' => $this->finance->id, 'finalized_at' => now()]);
        JobCost::factory()->create(['job_id' => $this->job->id, 'status' => 'final', 'type' => 'provision', 'description' => 'Trucking', 'quantity' => '1.00', 'unit_cost' => '3000000.00', 'unit_price' => '4500000.00', 'total_cost' => '3000000.00', 'total_price' => '4500000.00', 'finalized_by' => $this->finance->id, 'finalized_at' => now()]);
    }

    private function closingData(array $overrides = []): array
    {
        return array_replace(['lock_version' => $this->job->fresh()->lock_version, 'closing_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString(), 'funding_account' => 'bank', 'tax' => '950000'], $overrides);
    }

    private function close(): Invoice
    {
        $this->finalCosts();
        $this->post('/closing/'.$this->job->id, $this->closingData())->assertSessionHasNoErrors()->assertRedirect();

        return Invoice::firstOrFail();
    }

    public function test_closing_creates_locked_snapshot_invoice_and_balanced_journals(): void
    {
        $invoice = $this->close();
        $snapshot = $this->job->fresh()->closingSnapshot;

        $this->assertSame('closed', $this->job->fresh()->status);
        $this->assertSame('9500000.00', $snapshot->subtotal);
        $this->assertSame('10450000.00', $snapshot->total);
        $this->assertSame('1500000.00', $snapshot->profit);
        $this->assertSame('issued', $invoice->status);
        $this->assertSame('10450000.00', $invoice->balance);
        $this->assertCount(2, $invoice->items);
        $this->assertDatabaseCount('journals', 3);

        foreach (Journal::with('entries')->get() as $journal) {
            $debit = $journal->entries->sum(fn ($entry) => (float) $entry->debit);
            $credit = $journal->entries->sum(fn ($entry) => (float) $entry->credit);
            $this->assertSame($debit, $credit);
            $this->assertSame('posted', $journal->status);
        }

        $this->assertEquals(8000000, Journal::where('type', 'job_cost_capitalization')->firstOrFail()->entries()->sum('debit'));
        $this->assertEquals(13450000, Journal::where('type', 'job_invoice')->firstOrFail()->entries()->sum('debit'));
        $this->assertEquals(7500000, Journal::where('type', JobClosingService::CLOSING_TRANSFER)->firstOrFail()->entries()->sum('debit'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'job.closed', 'user_id' => $this->finance->id]);
        $this->get('/invoices/'.$invoice->id)->assertOk()->assertSee('10.450.000,00');
        $this->get('/payments')->assertOk()->assertSee($invoice->number);

        JobCost::where('job_id', $this->job->id)->first()->update(['description' => 'Diubah di luar alur']);
        $this->assertSame('Dokumen', $snapshot->fresh()->costs_snapshot[0]['description']);
    }

    public function test_partial_and_full_payments_update_receivable_and_post_journals(): void
    {
        $invoice = $this->close();
        $this->post('/invoices/'.$invoice->id.'/payments', ['lock_version' => 0, 'payment_date' => today()->toDateString(), 'amount' => '4000000', 'deposit_account' => 'bank', 'method' => 'transfer', 'reference' => 'TRX-1'])->assertSessionHasNoErrors();
        $invoice->refresh();
        $this->assertSame('partially_paid', $invoice->status);
        $this->assertSame('6450000.00', $invoice->balance);
        $this->assertSame('4000000.00', $invoice->paid_amount);
        $this->assertDatabaseHas('journals', ['type' => 'customer_payment', 'source_type' => Payment::class]);

        $this->post('/invoices/'.$invoice->id.'/payments', ['lock_version' => 1, 'payment_date' => today()->toDateString(), 'amount' => '6450000', 'deposit_account' => 'cash', 'method' => 'cash'])->assertSessionHasNoErrors();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('0.00', $invoice->fresh()->balance);
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseCount('journals', 5);
        $this->get('/payments')->assertOk()->assertDontSee($invoice->number);
    }

    public function test_overpayment_stale_submission_and_paid_invoice_are_rejected(): void
    {
        $invoice = $this->close();
        $base = ['payment_date' => today()->toDateString(), 'deposit_account' => 'bank', 'method' => 'transfer'];
        $this->post('/invoices/'.$invoice->id.'/payments', $base + ['lock_version' => 0, 'amount' => '11000000'])->assertSessionHasErrors('amount');
        $this->post('/invoices/'.$invoice->id.'/payments', $base + ['lock_version' => 0, 'amount' => '1000000'])->assertSessionHasNoErrors();
        $this->post('/invoices/'.$invoice->id.'/payments', $base + ['lock_version' => 0, 'amount' => '1000000'])->assertSessionHasErrors('lock_version');
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_unpaid_reverses_all_receipts_and_allows_payment_again(): void
    {
        $invoice = $this->close();
        $recognitionIds = Journal::pluck('id')->all();
        $base = ['payment_date' => today()->toDateString(), 'deposit_account' => 'bank', 'method' => 'transfer'];
        $this->post(route('payments.store', $invoice), $base + ['lock_version' => 0, 'amount' => '3900000', 'pph23_amount' => '100000'])->assertSessionHasNoErrors();
        $this->post(route('payments.store', $invoice), $base + ['lock_version' => 1, 'amount' => '6450000'])->assertSessionHasNoErrors();
        $this->assertSame('paid', $invoice->fresh()->status);
        $paymentIds = $invoice->payments()->pluck('id');
        $paymentJournals = Journal::where('source_type', Payment::class)->whereIn('source_id', $paymentIds)->get();
        $this->get(route('invoices.show', $invoice))->assertOk()->assertSee('Unpaid Invoice');
        $this->post(route('invoices.unpaid', $invoice), ['lock_version' => 2])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('issued', $invoice->fresh()->status);
        $this->assertSame('0.00', $invoice->fresh()->paid_amount);
        $this->assertSame($invoice->total, $invoice->fresh()->balance);
        $this->assertSame('closed', $this->job->fresh()->status);
        $this->assertSame(0, $invoice->payments()->count());
        $this->assertSame(2, $invoice->paymentHistory()->whereNotNull('reversed_at')->count());
        $soa = app(\App\Services\StatementOfAccountService::class)->statement(
            $invoice->job->customer, today()->startOfDay(), today()->endOfDay(), false
        );
        $this->assertSame($invoice->total, $soa['closing']);
        $this->assertSame('0.00', $soa['paid']);
        $this->assertCount(2, array_filter($soa['rows'], fn ($row) => $row['type'] === 'payment_reversal'));
        $this->assertSame(0, Journal::whereIn('id', $recognitionIds)->whereNotNull('reversed_at')->count());
        foreach ($paymentJournals as $journal) {
            $this->assertNotNull($journal->fresh()->reversed_at);
            $reverse = Journal::with('entries')->where('reversal_of_id', $journal->id)->firstOrFail();
            foreach ($journal->entries as $entry) {
                $opposite = $reverse->entries->firstWhere('chart_of_account_id', $entry->chart_of_account_id);
                $this->assertSame($entry->debit, $opposite->credit);
                $this->assertSame($entry->credit, $opposite->debit);
            }
        }
        $count = Journal::count();
        $this->post(route('invoices.unpaid', $invoice), ['lock_version' => 2])->assertSessionHasErrors('lock_version');
        $this->post(route('invoices.unpaid', $invoice), ['lock_version' => 3])->assertSessionHasErrors('payment');
        $this->assertSame($count, Journal::count());
        $this->get(route('invoices.show', $invoice))->assertOk()->assertSee('Dibatalkan (Unpaid)');
        $this->post(route('payments.store', $invoice), $base + ['lock_version' => 3, 'amount' => '10450000'])->assertSessionHasNoErrors();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('0.00', $invoice->fresh()->balance);
        $receipt = Journal::with('entries.account')->where('source_type', Payment::class)->whereNull('reversed_at')->firstOrFail();
        $temp = $receipt->entries->first(fn ($entry) => str_contains($entry->account->name, 'Piutang Temporary'));
        $this->assertSame('5000000.00', $temp->credit);
    }

    public function test_unpaid_is_atomic_when_a_payment_journal_is_missing_and_requires_permission(): void
    {
        $invoice = $this->close();
        $base = ['payment_date' => today()->toDateString(), 'deposit_account' => 'bank', 'method' => 'transfer'];
        $this->post(route('payments.store', $invoice), $base + ['lock_version' => 0, 'amount' => '4000000'])->assertSessionHasNoErrors();
        $this->post(route('payments.store', $invoice), $base + ['lock_version' => 1, 'amount' => '6450000'])->assertSessionHasNoErrors();
        $oldest = Payment::oldest('id')->firstOrFail();
        Journal::where('source_type', Payment::class)->where('source_id', $oldest->id)->update(['reversed_at' => now()]);
        $count = Journal::count();
        $this->post(route('invoices.unpaid', $invoice), ['lock_version' => 2])->assertSessionHasErrors('payment');
        $this->assertSame($count, Journal::count());
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(2, $invoice->payments()->count());
        $this->actingAs(User::where('email', 'operational@jobfinance.test')->firstOrFail());
        $this->post(route('invoices.unpaid', $invoice), ['lock_version' => 2])->assertForbidden();
    }

    public function test_partially_paid_invoice_can_be_unpaid(): void
    {
        $invoice = $this->close();
        $this->post(route('payments.store', $invoice), [
            'lock_version' => 0, 'payment_date' => today()->toDateString(), 'deposit_account' => 'bank', 'amount' => '1000000',
        ])->assertSessionHasNoErrors();
        $this->assertSame('partially_paid', $invoice->fresh()->status);
        $paymentDay = today();
        $this->travel(1)->days();
        $this->post(route('invoices.unpaid', $invoice), ['lock_version' => 1])->assertSessionHasNoErrors();
        $this->assertSame('issued', $invoice->fresh()->status);
        $this->assertSame($invoice->total, $invoice->fresh()->balance);
        $service = app(\App\Services\StatementOfAccountService::class);
        $before = $service->statement($invoice->job->customer, $paymentDay->copy()->startOfDay(), $paymentDay->copy()->endOfDay(), false);
        $this->assertSame('9450000.00', $before['closing']);
        $after = $service->statement($invoice->job->customer, today()->startOfDay(), today()->endOfDay(), false);
        $this->assertSame('9450000.00', $after['opening']);
        $this->assertSame($invoice->total, $after['closing']);
        $this->assertSame('1000000.00', $after['total_credit']);
    }

    public function test_closing_requires_final_costs_open_job_and_current_version(): void
    {
        // Job without any costs should fail validation
        $emptyJob = Job::factory()->open()->create(['quotation_snapshot' => ['customer' => ['name' => 'Demo Customer'], 'items' => []]]);
        $this->post('/closing/'.$emptyJob->id, array_replace($this->closingData(), ['lock_version' => $emptyJob->lock_version]))->assertSessionHasErrors('costs');

        // Job with draft costs auto-finalizes and succeeds directly
        $draftCost = JobCost::factory()->create(['job_id' => $this->job->id, 'status' => 'draft', 'type' => 'temporary', 'total_cost' => '1000000', 'total_price' => '1000000', 'unit_cost' => '1000000', 'unit_price' => '1000000']);
        $this->job->increment('lock_version');
        $this->post('/closing/'.$this->job->id, $this->closingData(['lock_version' => 0]))->assertSessionHasErrors('lock_version');
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('journals', 0);
        $this->assertSame('open', $this->job->fresh()->status);
    }

    public function test_duplicate_closing_is_rejected_and_numbers_are_not_duplicated(): void
    {
        $invoice = $this->close();
        $this->post('/closing/'.$this->job->id, $this->closingData(['lock_version' => 1]))->assertSessionHasErrors('job');
        $this->assertDatabaseCount('invoices', 1);
        $this->assertSame($invoice->number, Invoice::firstOrFail()->number);
    }

    public function test_closing_rolls_back_snapshot_invoice_journals_job_and_sequences_when_audit_fails(): void
    {
        $this->finalCosts();
        ActivityLog::creating(function ($log) {
            if ($log->action === 'job.closed') {
                throw new \RuntimeException('Audit failed');
            }
        });
        try {
            app(JobClosingService::class)->close($this->job, $this->closingData(), $this->finance);
            $this->fail('Expected failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Audit failed', $exception->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }
        $this->assertDatabaseCount('job_closing_snapshots', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('journals', 0);
        $this->assertDatabaseMissing('document_sequences', ['type' => 'inv']);
        $this->assertDatabaseMissing('document_sequences', ['type' => 'jrn']);
        $this->assertSame('open', $this->job->fresh()->status);
    }

    public function test_dashboard_reflects_closing_and_payments(): void
    {
        $invoice = $this->close();
        $summary = app(DashboardService::class)->summary();
        $this->assertSame('10450000.00', $summary['receivableBalance']);
        $this->assertSame('4500000.00', $summary['revenueBalance']);
        $this->assertSame('3000000.00', $summary['cogsBalance']);
        $this->assertSame('1500000.00', $summary['profitBalance']);
        $this->get('/dashboard')->assertOk()->assertSee('10.450.000,00')->assertSee($invoice->number);
    }

    public function test_non_finance_roles_cannot_access_closing_invoice_or_payment_routes(): void
    {
        $this->finalCosts();
        foreach (['operational', 'management'] as $role) {
            $this->actingAs(User::where('email', $role.'@jobfinance.test')->firstOrFail());
            $this->get('/closing')->assertForbidden();
            $this->get('/closing/'.$this->job->id)->assertForbidden();
            $this->post('/closing/'.$this->job->id, $this->closingData())->assertForbidden();
            $this->get('/invoices')->assertForbidden();
            $this->get('/payments')->assertForbidden();
        }
    }

    public function test_payment_with_pph23_records_journal_to_pph23_dimuka(): void
    {
        $invoice = $this->close();
        $this->post('/invoices/'.$invoice->id.'/payments', [
            'lock_version' => 0,
            'payment_date' => today()->toDateString(),
            'amount' => '10000000',
            'pph23_amount' => '200000',
            'deposit_account' => 'bank',
            'method' => 'transfer',
            'reference' => 'TRX-PPH23',
        ])->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertSame('10200000.00', $invoice->paid_amount);
        $this->assertSame('250000.00', $invoice->balance);
        $this->assertSame('partially_paid', $invoice->status);

        $payment = Payment::firstOrFail();
        $this->assertSame('200000.00', $payment->pph23_amount);

        $journal = Journal::where('type', 'customer_payment')->where('source_id', $payment->id)->firstOrFail();
        $entries = $journal->entries()->with('account')->get();

        $bankEntry = $entries->first(fn ($e) => (float) $e->debit == 10000000);
        $pphEntry = $entries->first(fn ($e) => (float) $e->debit == 200000);
        $totalCredit = $entries->sum(fn ($e) => (float) $e->credit);

        $this->assertNotNull($bankEntry);
        $this->assertNotNull($pphEntry);
        $this->assertEquals(10200000, $totalCredit);
        $this->assertSame('11192', $pphEntry->account->code);
    }

    public function test_reopen_job_reverses_only_close_unclose_transfer_and_preserves_invoice(): void
    {
        $invoice = $this->close();
        $this->assertSame('closed', $this->job->fresh()->status);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('job_closing_snapshots', 1);

        $closingJournals = Journal::where('source_type', Job::class)->where('source_id', $this->job->id)->get();
        $this->assertCount(3, $closingJournals);

        $this->post('/jobs/'.$this->job->id.'/reopen', [
            'lock_version' => $this->job->fresh()->lock_version,
            'reason' => 'Salah alokasi biaya',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('open', $this->job->fresh()->status);
        $this->assertNull($this->job->fresh()->closed_at);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertSame('issued', $invoice->fresh()->status);
        $this->assertNull($invoice->fresh()->job_closing_snapshot_id);
        $this->assertDatabaseCount('job_closing_snapshots', 0);

        foreach ($closingJournals as $cj) {
            $cj->refresh();
            $this->assertSame($cj->type === JobClosingService::CLOSING_TRANSFER, $cj->reversed_at !== null);
            $this->assertSame($cj->type === JobClosingService::CLOSING_TRANSFER, $cj->reversal()->exists());
        }

        $this->assertDatabaseCount('journals', 4);
        $reversals = Journal::where('type', 'journal_reversal')->get();
        $this->assertCount(1, $reversals);
    }

    public function test_job_can_be_closed_again_after_reopen_without_double_posting(): void
    {
        $this->close();

        // Simpan nominal Piutang dari penutupan pertama untuk dibandingkan.
        $firstClosing = Journal::where('source_type', Job::class)->where('source_id', $this->job->id)
            ->where('type', 'job_invoice')->whereNull('reversed_at')->firstOrFail();
        $firstClosingReceivable = (float) $firstClosing->entries()->where('description', 'like', 'Piutang%')->sum('debit');

        $this->post('/jobs/'.$this->job->id.'/reopen', [
            'lock_version' => $this->job->fresh()->lock_version,
            'reason' => 'Koreksi alokasi biaya',
        ])->assertSessionHasNoErrors();

        $reversed = Journal::where('source_type', Job::class)->where('source_id', $this->job->id)->pluck('id');
        $this->assertSame('open', $this->job->fresh()->status);

        // Menutup kembali harus berhasil dan memakai revisi jurnal berikutnya.
        $this->post('/closing/'.$this->job->id, $this->closingData())->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('closed', $this->job->fresh()->status);
        $this->assertDatabaseCount('invoices', 1);

        // Hanya transfer closing pertama yang dibalik; pengakuan tetap aktif.
        foreach (Journal::whereIn('id', $reversed)->get() as $journal) {
            $this->assertSame($journal->type === JobClosingService::CLOSING_TRANSFER, $journal->reversed_at !== null);
        }

        $active = Journal::where('source_type', Job::class)->where('source_id', $this->job->id)
            ->whereNull('reversed_at')->whereNull('reversal_of_id')->get();
        $this->assertCount(3, $active);
        $this->assertSame(1, $active->firstWhere('type', JobClosingService::CLOSING_TRANSFER)->revision);

        // Buku besar harus seimbang dan nominalnya sama dengan penutupan pertama (tanpa penggandakan).
        $entries = \App\Models\JournalEntry::whereIn('journal_id', $active->pluck('id'))->get();
        $totalDebit = (float) $entries->sum('debit');
        $totalCredit = (float) $entries->sum('credit');
        $this->assertEqualsWithDelta($totalCredit, $totalDebit, 0.01);

        $repostedClosing = $active->firstWhere('type', 'job_invoice');
        $this->assertEqualsWithDelta(
            (float) $firstClosingReceivable,
            (float) $repostedClosing->entries()->where('description', 'like', 'Piutang%')->sum('debit'),
            0.01
        );
    }

    public function test_reopen_job_preserves_partial_payment_invoice_journals_and_ledger_balances(): void
    {
        $invoice = $this->close();
        $this->post('/invoices/'.$invoice->id.'/payments', [
            'lock_version' => 0,
            'payment_date' => today()->toDateString(),
            'amount' => '1000000',
            'deposit_account' => 'bank',
            'method' => 'transfer',
        ])->assertSessionHasNoErrors();

        $invoiceBefore = $invoice->fresh()->only(['id', 'number', 'total', 'status', 'balance', 'paid_amount']);
        $paymentsBefore = $invoice->payments()->get()->toArray();
        $protected = Journal::where('type', '!=', JobClosingService::CLOSING_TRANSFER)->get()->pluck('id');
        $bankId = $invoice->payments()->firstOrFail()->deposit_account_id;
        $customerAccounts = app(\App\Services\PartyAccountService::class)->customerAccounts($this->job->customer);
        $accounts = [$bankId, $customerAccounts['receivable']->id, $customerAccounts['temporary']->id];
        $read = fn () => DB::table('journal_entries')->whereIn('chart_of_account_id', $accounts)
            ->selectRaw('chart_of_account_id, SUM(debit) AS debit, SUM(credit) AS credit')
            ->groupBy('chart_of_account_id')->orderBy('chart_of_account_id')->get()->toJson();
        $ledgerBefore = $read();
        $this->get(route('jobs.show', $this->job))->assertOk()->assertSee('Undo / Buka Kembali Job');

        $this->post('/jobs/'.$this->job->id.'/reopen', [
            'lock_version' => $this->job->fresh()->lock_version,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('open', $this->job->fresh()->status);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertSame($invoiceBefore, $invoice->fresh()->only(array_keys($invoiceBefore)));
        $this->assertSame($paymentsBefore, $invoice->payments()->get()->toArray());
        $this->assertSame($ledgerBefore, $read());
        $this->assertSame(0, Journal::whereIn('id', $protected)->whereNotNull('reversed_at')->count());
        foreach ($accounts as $accountId) {
            $ledger = app(\App\Services\FinancialReportService::class)->ledgerExport($accountId, '1900-01-01', today()->toDateString());
            $this->assertNotEmpty($ledger['entries'], 'Invoice/customer payment ledger entries must remain visible after reopen.');
        }
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'partially_paid', 'paid_amount' => '1000000.00', 'balance' => '9450000.00']);
        $unclosed = app(\App\Services\JournalService::class)->mapped(['revenue_domestic_unclosed'])['revenue_domestic_unclosed'];
        $this->assertEquals(-4500000, DB::table('journal_entries')->where('chart_of_account_id', $unclosed->id)->sum(DB::raw('debit - credit')));
    }

    public function test_fully_paid_invoice_can_reopen_and_reclose_without_losing_receivable_or_bank_history(): void
    {
        $invoice = $this->close();
        $this->post('/invoices/'.$invoice->id.'/payments', [
            'lock_version' => $invoice->lock_version, 'payment_date' => today()->toDateString(),
            'amount' => '10450000', 'deposit_account' => 'bank', 'method' => 'transfer',
        ])->assertSessionHasNoErrors();
        $invoiceBefore = $invoice->fresh()->only(['id', 'number', 'status', 'paid_amount', 'balance', 'total']);
        $payment = $invoice->payments()->firstOrFail();
        $journal = Journal::where('source_type', Payment::class)->where('source_id', $payment->id)->firstOrFail();
        $read = fn () => \App\Models\JournalEntry::where('journal_id', $journal->id)->get()->toArray();
        $paymentEntries = $read();
        $invoiceJournal = Journal::where('source_type', Job::class)->where('source_id', $this->job->id)->where('type', 'job_invoice')->firstOrFail();
        foreach (range(1, 2) as $cycle) {
            $this->post('/jobs/'.$this->job->id.'/reopen', [
                'lock_version' => $this->job->fresh()->lock_version,
            ])->assertSessionHasNoErrors();
            $this->assertSame('open', $this->job->fresh()->status);
            $this->assertSame($invoiceBefore, $invoice->fresh()->only(array_keys($invoiceBefore)));
            $this->assertSame($paymentEntries, $read());
            $this->assertNull($journal->fresh()->reversed_at);
            $this->assertNull($invoiceJournal->fresh()->reversed_at);
            $this->assertSame(1, $invoice->payments()->count());
            $this->post('/closing/'.$this->job->id, $this->closingData())->assertSessionHasNoErrors();
            $this->assertSame($invoiceBefore, $invoice->fresh()->only(array_keys($invoiceBefore)));
        }
    }

    public function test_reopen_refuses_missing_invoice_recognition_without_creating_replacement_journals(): void
    {
        $invoice = $this->close();
        $journal = Journal::where('source_type', Job::class)->where('source_id', $this->job->id)->where('type', 'job_invoice')->firstOrFail();
        app(\App\Services\JournalService::class)->reverse($journal, [
            'lock_version' => $journal->lock_version, 'reversal_date' => today()->toDateString(), 'reason' => 'Missing legacy recognition fixture',
        ], $this->finance);
        $count = Journal::count();
        $this->post('/jobs/'.$this->job->id.'/reopen', [
            'lock_version' => $this->job->fresh()->lock_version,
        ])->assertSessionHasErrors('journal');
        $this->assertSame('closed', $this->job->fresh()->status);
        $this->assertSame($count, Journal::count());
        $this->assertNotNull($invoice->fresh()->job_closing_snapshot_id);
    }

    public function test_reopen_refuses_missing_payment_journal_without_mutating_invoice_or_cash(): void
    {
        $invoice = $this->close();
        $this->post('/invoices/'.$invoice->id.'/payments', [
            'lock_version' => $invoice->lock_version, 'payment_date' => today()->toDateString(),
            'amount' => '1000000', 'deposit_account' => 'bank', 'method' => 'transfer',
        ])->assertSessionHasNoErrors();
        $payment = $invoice->payments()->firstOrFail();
        $journal = Journal::where('source_type', Payment::class)->where('source_id', $payment->id)->firstOrFail();
        app(\App\Services\JournalService::class)->reverse($journal, [
            'lock_version' => $journal->lock_version, 'reversal_date' => today()->toDateString(), 'reason' => 'Legacy payment journal fixture',
        ], $this->finance);
        $before = $invoice->fresh()->only(['status', 'balance', 'paid_amount']);
        $count = Journal::count();
        $this->post('/jobs/'.$this->job->id.'/reopen', [
            'lock_version' => $this->job->fresh()->lock_version,
        ])->assertSessionHasErrors('journal');
        $this->assertSame($before, $invoice->fresh()->only(array_keys($before)));
        $this->assertSame('closed', $this->job->fresh()->status);
        $this->assertSame($count, Journal::count());
    }
}
