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
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClosingPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;

    private Job $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
        $this->actingAs($this->finance);
        $this->job = Job::factory()->open()->create();
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
        $this->assertDatabaseCount('journals', 2);

        foreach (Journal::with('entries')->get() as $journal) {
            $debit = $journal->entries->sum(fn ($entry) => (float) $entry->debit);
            $credit = $journal->entries->sum(fn ($entry) => (float) $entry->credit);
            $this->assertSame($debit, $credit);
            $this->assertSame('posted', $journal->status);
        }

        $this->assertEquals(8000000, Journal::where('type', 'job_cost_capitalization')->firstOrFail()->entries()->sum('debit'));
        $this->assertEquals(13450000, Journal::where('type', 'job_closing')->firstOrFail()->entries()->sum('debit'));
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
        $this->assertDatabaseCount('journals', 4);
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

    public function test_closing_requires_final_costs_open_job_and_current_version(): void
    {
        JobCost::factory()->create(['job_id' => $this->job->id, 'status' => 'draft']);
        $this->post('/closing/'.$this->job->id, $this->closingData())->assertSessionHasErrors('costs');
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
}
