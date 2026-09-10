<?php

namespace Tests\Feature;

use App\Enums\QuotationStatus;
use App\Mail\StatementOfAccountMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\JobCost;
use App\Models\Quotation;
use App\Models\User;
use App\Services\StatementOfAccountService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StatementOfAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
        $this->customer = Customer::factory()->create(['code' => 'CUS-SOA', 'name' => 'PT Statement Client', 'created_by' => $this->finance->id, 'updated_by' => $this->finance->id]);
        $this->actingAs($this->finance);
    }

    private function invoice(string $invoiceDate, string $dueDate): Invoice
    {
        $quotation = Quotation::factory()->create(['customer_id' => $this->customer->id, 'status' => QuotationStatus::Converted]);
        $job = Job::factory()->create(['quotation_id' => $quotation->id, 'customer_id' => $this->customer->id, 'status' => 'open', 'job_date' => $invoiceDate]);
        JobCost::factory()->create(['job_id' => $job->id, 'status' => 'final', 'type' => 'temporary', 'description' => 'Dokumen', 'quantity' => '1.00', 'unit_cost' => '5000000.00', 'unit_price' => '5000000.00', 'total_cost' => '5000000.00', 'total_price' => '5000000.00', 'finalized_by' => $this->finance->id, 'finalized_at' => now()]);
        JobCost::factory()->create(['job_id' => $job->id, 'status' => 'final', 'type' => 'provision', 'description' => 'Trucking', 'quantity' => '1.00', 'unit_cost' => '3000000.00', 'unit_price' => '4500000.00', 'total_cost' => '3000000.00', 'total_price' => '4500000.00', 'finalized_by' => $this->finance->id, 'finalized_at' => now()]);
        $this->post('/closing/'.$job->id, ['lock_version' => $job->fresh()->lock_version, 'closing_date' => $invoiceDate, 'due_date' => $dueDate, 'funding_account' => 'bank', 'tax' => '0'])->assertSessionHasNoErrors()->assertRedirect();

        return Invoice::firstWhere('job_id', $job->id);
    }

    private function pay(Invoice $invoice, string $date, string $amount): void
    {
        $this->post('/invoices/'.$invoice->id.'/payments', ['lock_version' => $invoice->lock_version, 'payment_date' => $date, 'amount' => $amount, 'deposit_account' => 'bank', 'method' => 'transfer', 'reference' => 'TRX-SOA'])->assertSessionHasNoErrors();
    }

    public function test_summary_aggregates_balance_and_aging_buckets(): void
    {
        $old = $this->invoice(today()->subDays(95)->toDateString(), today()->subDays(95)->toDateString());
        $recent = $this->invoice(today()->subDays(10)->toDateString(), today()->subDays(5)->toDateString());
        $this->pay($recent, today()->subDays(2)->toDateString(), '4000000');

        $this->get('/reports/statement-of-account')->assertOk()->assertSee('CUS-SOA')->assertSee('PT Statement Client')
            ->assertSee('15.000.000,00')->assertSee('19.000.000,00')
            ->assertSee('> 90 hari: Rp 9.500.000,00')->assertSee('1-30 hari: Rp 5.500.000,00');
    }

    public function test_statement_running_balance_and_period_math(): void
    {
        $old = $this->invoice(today()->subDays(95)->toDateString(), today()->subDays(95)->toDateString());
        $recent = $this->invoice(today()->subDays(3)->toDateString(), today()->subDays(1)->toDateString());
        $this->pay($recent, today()->subDays(2)->toDateString(), '4000000');

        $from = today()->startOfMonth()->toDateString();
        $this->get('/reports/statement-of-account/'.$this->customer->id.'?from='.$from.'&to='.today()->toDateString())
            ->assertOk()->assertSee($recent->number)
            ->assertSee('9.500.000,00')->assertSee('19.000.000,00')->assertSee('15.000.000,00');

        $totals = app(StatementOfAccountService::class)->statement($this->customer, today()->startOfMonth(), today());
        $this->assertSame('9500000.00', $totals['opening']);
        $this->assertSame('9500000.00', $totals['invoiced']);
        $this->assertSame('4000000.00', $totals['paid']);
        $this->assertSame('15000000.00', $totals['closing']);
        $this->assertCount(2, $totals['rows']);
        $this->assertSame('15000000.00', end($totals['rows'])['balance']);
    }

    public function test_fully_paid_customer_shows_no_aging(): void
    {
        $invoice = $this->invoice(today()->subDays(20)->toDateString(), today()->subDays(18)->toDateString());
        $this->pay($invoice, today()->subDays(15)->toDateString(), '9500000');
        $this->get('/reports/statement-of-account')->assertOk()->assertSee('CUS-SOA')->assertSee('0,00')->assertSee('Lunas');
    }

    public function test_unpaid_only_filter_hides_settled_customers(): void
    {
        $this->invoice(today()->subDays(4)->toDateString(), today()->addDays(26)->toDateString());

        $other = Customer::factory()->create(['code' => 'CUS-SOA2', 'name' => 'PT Pelunasan Cepat', 'created_by' => $this->finance->id, 'updated_by' => $this->finance->id]);
        $quotation = Quotation::factory()->create(['customer_id' => $other->id, 'status' => QuotationStatus::Converted]);
        $job = Job::factory()->create(['quotation_id' => $quotation->id, 'customer_id' => $other->id, 'status' => 'open', 'job_date' => today()->subDays(20)->toDateString()]);
        JobCost::factory()->create(['job_id' => $job->id, 'status' => 'final', 'type' => 'provision', 'description' => 'Trucking', 'quantity' => '1.00', 'unit_cost' => '1000000.00', 'unit_price' => '2000000.00', 'total_cost' => '1000000.00', 'total_price' => '2000000.00', 'finalized_by' => $this->finance->id, 'finalized_at' => now()]);
        $this->post('/closing/'.$job->id, ['lock_version' => $job->fresh()->lock_version, 'closing_date' => today()->subDays(20)->toDateString(), 'due_date' => today()->subDays(18)->toDateString(), 'funding_account' => 'bank', 'tax' => '0'])->assertSessionHasNoErrors()->assertRedirect();
        $settled = Invoice::firstWhere('job_id', $job->id);
        $this->post('/invoices/'.$settled->id.'/payments', ['lock_version' => $settled->lock_version, 'payment_date' => today()->subDays(15)->toDateString(), 'amount' => '2000000', 'deposit_account' => 'bank', 'method' => 'transfer', 'reference' => 'TRX-LUNAS'])->assertSessionHasNoErrors();

        $this->get('/reports/statement-of-account')->assertOk()->assertSee('PT Statement Client')->assertSee('PT Pelunasan Cepat')->assertSee('Lunas');
        $this->get('/reports/statement-of-account?unpaid=1')->assertOk()->assertSee('PT Statement Client')->assertDontSee('PT Pelunasan Cepat');

        $paid = $this->service()->summary(null, true);
        $this->assertCount(1, $paid['customers']);
        $this->assertSame($this->customer->id, $paid['customers'][0]['customer']->id);
    }

    public function test_email_sends_soa_to_selected_recipients_and_requires_permissions(): void
    {
        Mail::fake();
        $this->customer->contacts()->create(['type' => 'shipper', 'name' => 'Pengirim', 'company' => 'PT Kirim', 'email' => 'shipper@client.test']);
        $this->invoice(today()->subDays(3)->toDateString(), today()->addDays(27)->toDateString());

        $this->post('/reports/statement-of-account/'.$this->customer->id.'/email', ['emails' => 'ops@client.test, shipper@client.test'])
            ->assertSessionHasNoErrors()->assertRedirect();
        Mail::assertSent(StatementOfAccountMail::class, fn (StatementOfAccountMail $mail) => $mail->hasTo('ops@client.test') && $mail->hasTo('shipper@client.test'));

        $this->post('/reports/statement-of-account/'.$this->customer->id.'/email', ['emails' => ' '])->assertSessionHasErrors('emails');
        $this->post('/reports/statement-of-account/'.$this->customer->id.'/email', ['emails' => 'bukan-email'])->assertSessionHasErrors('emails');
        Mail::assertSent(StatementOfAccountMail::class, 1);

        $this->actingAs(User::where('email', 'operational@jobfinance.test')->firstOrFail());
        $this->post('/reports/statement-of-account/'.$this->customer->id.'/email', ['emails' => 'ops@client.test'])->assertForbidden();

        $this->actingAs(User::where('email', 'management@jobfinance.test')->firstOrFail());
        $this->post('/reports/statement-of-account/'.$this->customer->id.'/email', ['emails' => 'ops@client.test'])->assertForbidden();
    }

    private function service(): StatementOfAccountService
    {
        return app(StatementOfAccountService::class);
    }

    public function test_non_finance_roles_cannot_open_statement(): void
    {
        $invoice = $this->invoice(today()->toDateString(), today()->addDays(30)->toDateString());
        $this->actingAs(User::where('email', 'operational@jobfinance.test')->firstOrFail());
        $this->get('/reports/statement-of-account')->assertForbidden();
        $this->get('/reports/statement-of-account/'.$this->customer->id)->assertForbidden();
        $this->actingAs(User::where('email', 'management@jobfinance.test')->firstOrFail());
        $this->get('/reports/statement-of-account')->assertForbidden();
    }
}
