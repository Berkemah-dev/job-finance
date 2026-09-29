<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\JobCost;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceCreationTest extends TestCase
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

        // Create sample costs matching user scenario: 11jt temporary + 17jt provision (cost 10jt, sell 17jt)
        JobCost::create([
            'job_id' => $this->job->id,
            'number' => 'CST-0001',
            'description' => 'Lift On/Off',
            'type' => 'temporary',
            'cost_category' => 'reimbursement',
            'status' => 'draft',
            'cost_date' => today()->toDateString(),
            'quantity' => 1,
            'unit' => 'Container',
            'unit_cost' => '11000000.00',
            'unit_price' => '11000000.00',
            'total_cost' => '11000000.00',
            'total_price' => '11000000.00',
            'created_by' => $this->finance->id,
            'updated_by' => $this->finance->id,
        ]);

        JobCost::create([
            'job_id' => $this->job->id,
            'number' => 'CST-0002',
            'description' => 'Air Freight & Customs',
            'type' => 'provision',
            'cost_category' => 'payment_request',
            'status' => 'draft',
            'cost_date' => today()->toDateString(),
            'quantity' => 1,
            'unit' => 'Shipment',
            'unit_cost' => '10000000.00',
            'unit_price' => '17000000.00',
            'total_cost' => '10000000.00',
            'total_price' => '17000000.00',
            'created_by' => $this->finance->id,
            'updated_by' => $this->finance->id,
        ]);
    }

    public function test_invoice_can_be_created_while_job_is_open(): void
    {
        $response = $this->post('/invoices', [
            'job_id' => $this->job->id,
            'lock_version' => $this->job->lock_version,
            'invoice_date' => today()->toDateString(),
            'due_date' => today()->addDays(30)->toDateString(),
            'tax' => '0.00',
        ]);

        $response->assertSessionHasNoErrors();
        $invoice = Invoice::firstOrFail();
        $response->assertRedirect(route('invoices.show', $invoice));

        // Job must STILL be open!
        $this->job->refresh();
        $this->assertSame('open', $this->job->status);

        // Invoice total is 28.000.000 (11jt temp + 17jt provision)
        $this->assertEquals('28000000.00', $invoice->total);
        $this->assertEquals('issued', $invoice->status);

        // Verify journal debits: Piutang Customer 17jt, Piutang Temporary 11jt
        $journal = Journal::where('type', 'job_invoice')->where('source_id', $this->job->id)->firstOrFail();
        $receivableAcc = ChartOfAccount::where('code', '1103')->firstOrFail();
        $tempReceivableAcc = ChartOfAccount::where('name', 'Piutang Temporary')->firstOrFail();

        $custDebit = (float) $journal->entries()->where('chart_of_account_id', $receivableAcc->id)->value('debit');
        $tempDebit = (float) $journal->entries()->where('chart_of_account_id', $tempReceivableAcc->id)->value('debit');

        $this->assertEquals(17000000.0, $custDebit);
        $this->assertEquals(11000000.0, $tempDebit);
    }

    public function test_payment_settles_both_customer_and_temporary_receivables(): void
    {
        // Issue invoice
        $this->post('/invoices', [
            'job_id' => $this->job->id,
            'lock_version' => $this->job->lock_version,
            'invoice_date' => today()->toDateString(),
            'due_date' => today()->addDays(30)->toDateString(),
            'tax' => '0.00',
        ])->assertSessionHasNoErrors();

        $invoice = Invoice::firstOrFail();

        // Pay full amount (e.g. 27.440.000 transfer + 560.000 PPh 23)
        $bankAcc = ChartOfAccount::where('code', '11120')->firstOrFail();
        $payResponse = $this->post('/invoices/'.$invoice->id.'/payments', [
            'lock_version' => $invoice->lock_version,
            'payment_date' => today()->toDateString(),
            'amount' => '27440000.00',
            'pph23_amount' => '560000.00',
            'deposit_account' => (string) $bankAcc->id,
            'method' => 'transfer',
        ]);
        $payResponse->assertSessionHasNoErrors();

        $payment = Payment::firstOrFail();
        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertEquals('0.00', $invoice->balance);

        // Check payment journal credits
        $payJournal = Journal::where('type', 'customer_payment')->where('source_id', $payment->id)->firstOrFail();
        $receivableAcc = ChartOfAccount::where('code', '1103')->firstOrFail();
        $tempReceivableAcc = ChartOfAccount::where('name', 'Piutang Temporary')->firstOrFail();

        $custCredit = (float) $payJournal->entries()->where('chart_of_account_id', $receivableAcc->id)->value('credit');
        $tempCredit = (float) $payJournal->entries()->where('chart_of_account_id', $tempReceivableAcc->id)->value('credit');

        // Customer receivable credited 17jt, Temporary receivable credited 11jt
        $this->assertEquals(17000000.0, $custCredit);
        $this->assertEquals(11000000.0, $tempCredit);

        // Total balances in GL for both 1103 and 1104 are exactly 0!
        $allEntries1103 = \App\Models\JournalEntry::where('chart_of_account_id', $receivableAcc->id)->get();
        $balance1103 = $allEntries1103->sum(fn ($e) => (float) $e->debit) - $allEntries1103->sum(fn ($e) => (float) $e->credit);
        $this->assertEquals(0.0, $balance1103);

        $allEntries1104 = \App\Models\JournalEntry::where('chart_of_account_id', $tempReceivableAcc->id)->get();
        $balance1104 = $allEntries1104->sum(fn ($e) => (float) $e->debit) - $allEntries1104->sum(fn ($e) => (float) $e->credit);
        $this->assertEquals(0.0, $balance1104);
    }
}
