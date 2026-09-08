<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\JobCost;
use App\Models\Journal;
use App\Models\User;
use App\Services\FinancialReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingReportTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
        $this->actingAs($this->finance);
    }

    private function closeAndPay(): Invoice
    {
        $job = Job::factory()->open()->create();
        JobCost::factory()->create(['job_id' => $job->id, 'status' => 'final', 'type' => 'temporary', 'description' => 'Dokumen', 'unit_cost' => '5000000.00', 'unit_price' => '5000000.00', 'total_cost' => '5000000.00', 'total_price' => '5000000.00']);
        JobCost::factory()->create(['job_id' => $job->id, 'status' => 'final', 'type' => 'provision', 'description' => 'Trucking', 'unit_cost' => '3000000.00', 'unit_price' => '4500000.00', 'total_cost' => '3000000.00', 'total_price' => '4500000.00']);
        $this->post('/closing/'.$job->id, ['lock_version' => 0, 'closing_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString(), 'funding_account' => 'bank', 'tax' => '950000'])->assertSessionHasNoErrors();
        $invoice = Invoice::firstOrFail();
        $this->post('/invoices/'.$invoice->id.'/payments', ['lock_version' => 0, 'payment_date' => today()->toDateString(), 'amount' => '4000000', 'deposit_account' => 'bank', 'method' => 'transfer'])->assertSessionHasNoErrors();

        return $invoice;
    }

    private function adjustmentData(array $overrides = []): array
    {
        $expense = ChartOfAccount::where('code', '6101')->firstOrFail();
        $bank = ChartOfAccount::where('code', '1102')->firstOrFail();

        return array_replace_recursive(['journal_date' => today()->toDateString(), 'description' => 'Koreksi biaya kantor', 'entries' => [
            ['account_id' => $expense->id, 'description' => 'Beban kantor', 'debit' => '100000', 'credit' => '0'],
            ['account_id' => $bank->id, 'description' => 'Bank', 'debit' => '0', 'credit' => '100000'],
        ]], $overrides);
    }

    public function test_journal_pages_general_ledger_and_trial_balance_use_posted_entries(): void
    {
        $this->closeAndPay();
        $bank = ChartOfAccount::where('code', '1102')->firstOrFail();
        $report = app(FinancialReportService::class);
        $ledger = $report->ledger($bank->id, today()->startOfYear()->toDateString(), today()->toDateString());
        $this->assertSame('-4000000.00', $ledger['closing']);
        $this->assertCount(2, $ledger['entries']);
        $trial = $report->trialBalance(today()->toDateString());
        $this->assertEquals($trial->sum(fn ($row) => (float) $row->closing_debit), $trial->sum(fn ($row) => (float) $row->closing_credit));
        $this->get('/journals')->assertOk()->assertSee('Kapitalisasi biaya');
        $this->get('/reports/ledger?account_id='.$bank->id)->assertOk()->assertSee('4.000.000,00');
        $this->get('/reports/trial-balance')->assertOk()->assertSee('Neraca Saldo');
    }

    public function test_financial_statements_cash_flow_and_profit_per_job_are_correct(): void
    {
        $invoice = $this->closeAndPay();
        $service = app(FinancialReportService::class);
        $from = today()->startOfYear()->toDateString();
        $to = today()->toDateString();
        $income = $service->incomeStatement($from, $to);
        $this->assertSame('4500000.00', $income['revenue']);
        $this->assertSame('3000000.00', $income['cogs']);
        $this->assertSame('1500000.00', $income['net']);
        $balance = $service->balanceSheet($to);
        $this->assertSame('2450000.00', $balance['assets']);
        $this->assertSame('950000.00', $balance['liabilities']);
        $this->assertSame('2450000.00', $balance['liabilities_equity']);
        $cash = $service->cashFlow($from, $to);
        $this->assertSame('4000000.00', $cash['customer_payment']);
        $this->assertSame('-8000000.00', $cash['job_cost_capitalization']);
        $this->assertSame('-4000000.00', $cash['net']);
        $this->assertCount(1, $service->profitPerJob($from, $to));
        foreach (['balance-sheet', 'income-statement', 'cash-flow', 'profit-per-job'] as $route) {
            $this->get('/reports/'.$route)->assertOk();
        }
        $this->get('/reports/profit-per-job')->assertSee($invoice->job->number)->assertSee('1.500.000,00');
        $this->get('/dashboard')->assertOk()->assertSee('Enam bulan terakhir');
    }

    public function test_adjustment_must_balance_and_reversal_is_immutable_correction(): void
    {
        $this->get('/journals/create')->assertOk();
        $bad = $this->adjustmentData();
        $bad['entries'][1]['credit'] = '90000';
        $this->post('/journals', $bad)->assertSessionHasErrors('journal');
        $this->assertDatabaseCount('journals', 0);

        $this->post('/journals', $this->adjustmentData())->assertSessionHasNoErrors();
        $journal = Journal::firstOrFail();
        $this->assertSame('adjustment', $journal->type);
        $this->assertDatabaseHas('activity_logs', ['action' => 'journal.adjustment_created']);
        $this->post('/journals/'.$journal->id.'/reverse', ['lock_version' => 0, 'reversal_date' => today()->toDateString(), 'reason' => 'Salah klasifikasi'])->assertSessionHasNoErrors();
        $journal->refresh();
        $this->assertNotNull($journal->reversed_at);
        $this->assertDatabaseHas('journals', ['type' => 'journal_reversal', 'reversal_of_id' => $journal->id]);
        $this->post('/journals/'.$journal->id.'/reverse', ['lock_version' => 1, 'reversal_date' => today()->toDateString(), 'reason' => 'Ulang'])->assertSessionHasErrors('journal');
        $this->assertDatabaseCount('journals', 2);
        $income = app(FinancialReportService::class)->incomeStatement(today()->startOfYear()->toDateString(), today()->toDateString());
        $this->assertSame('0.00', $income['expense']);
    }

    public function test_adjustment_rejects_invalid_lines_and_archived_accounts(): void
    {
        $both = $this->adjustmentData();
        $both['entries'][0]['credit'] = '1';
        $this->post('/journals', $both)->assertSessionHasErrors('entries');
        $account = ChartOfAccount::where('code', '6101')->firstOrFail();
        $archived = $this->adjustmentData();
        $account->delete();
        $this->post('/journals', $archived)->assertSessionHasErrors('entries');
        $this->assertDatabaseCount('journals', 0);
    }

    public function test_management_can_read_reports_but_cannot_manage_journals(): void
    {
        $this->actingAs(User::where('email', 'management@jobfinance.test')->firstOrFail());
        foreach (['ledger', 'trial-balance', 'balance-sheet', 'income-statement', 'cash-flow', 'profit-per-job'] as $report) {
            $this->get('/reports/'.$report)->assertOk();
        }
        $this->get('/journals')->assertForbidden();
        $this->post('/journals', $this->adjustmentData())->assertForbidden();

        $this->actingAs(User::where('email', 'operational@jobfinance.test')->firstOrFail());
        $this->get('/reports/income-statement')->assertForbidden();
        $this->get('/journals')->assertForbidden();
    }
}
