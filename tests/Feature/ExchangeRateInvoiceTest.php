<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Job;
use App\Models\Journal;
use App\Models\Quotation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRateInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;

    private User $manager;

    private User $operator;

    private User $finance;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->sales = User::where('email', 'sales@jobfinance.test')->firstOrFail();
        $this->manager = User::where('email', 'sales-manager@jobfinance.test')->firstOrFail();
        $this->operator = User::where('email', 'operational@jobfinance.test')->firstOrFail();
        $this->finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
        $this->customer = Customer::factory()->create(['created_by' => $this->sales->id, 'updated_by' => $this->sales->id]);
        $this->actingAs($this->sales);
    }

    private function convertedJob(string $currency, string $rate): Job
    {
        $this->post('/quotations', ['customer_id' => $this->customer->id, 'subject' => 'Pengiriman internasional', 'quotation_date' => '2026-09-08', 'valid_until' => '2026-10-08',
            'service_type' => 'sea', 'origin' => 'Jakarta', 'destination' => 'Singapore', 'currency' => $currency, 'exchange_rate' => $rate, 'payment_terms' => 'net_30',
            'shipper_name' => 'PT Sumber Makmur', 'shipper_address' => 'Jl. Raya Cakung 10, Jakarta', 'consignee_name' => 'Global Trading Pte', 'consignee_address' => '1 Raffles Place, Singapore',
            'items' => [
                ['description' => 'Dokumen', 'type' => 'temporary', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '5000000', 'unit_price' => '5000000'],
                ['description' => 'Ongkir laut', 'type' => 'provision', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '3000000', 'unit_price' => '4500000'],
            ]])->assertSessionHasNoErrors()->assertRedirect();
        $q = Quotation::latest('id')->firstOrFail();
        $this->post('/quotations/'.$q->id.'/submit', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->actingAs($this->manager);
        $this->post('/quotations/'.$q->id.'/approve', ['lock_version' => 1])->assertSessionHasNoErrors();
        $this->post('/quotations/'.$q->id.'/convert', ['lock_version' => 2])->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($this->operator);
        $job = Job::firstOrFail();
        $this->post('/jobs/'.$job->id.'/open', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->actingAs($this->finance);
        foreach ($job->costs()->get() as $cost) {
            $job->refresh();
            $this->post('/jobs/'.$job->id.'/costs/'.$cost->id.'/finalize', ['job_version' => $job->lock_version, 'lock_version' => $cost->lock_version])->assertSessionHasNoErrors();
        }

        return $job->fresh();
    }

    private function close(Job $job, string $tax = '1100000'): void
    {
        $this->post('/closing/'.$job->id, ['lock_version' => $job->lock_version, 'closing_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString(), 'funding_account' => 'bank', 'tax' => $tax])->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_closing_carries_quotation_currency_and_rate_into_invoice(): void
    {
        $job = $this->convertedJob('USD', '15850');
        $this->close($job);
        $invoice = $job->fresh()->invoice;

        $this->assertSame('USD', $invoice->currency);
        $this->assertSame('15850.00', $invoice->exchange_rate);
        $this->assertSame('USD', $invoice->snapshot->currency);
        $this->assertSame('15850.00', $invoice->snapshot->exchange_rate);
        $this->assertSame('10600000.00', $invoice->total);
        $this->assertSame('599.37', (string) $invoice->inInvoiceCurrency('subtotal'));
        $this->assertSame('69.40', (string) $invoice->inInvoiceCurrency('tax'));
        $this->assertSame('668.77', (string) $invoice->inInvoiceCurrency('total'));

        $this->get('/invoices/'.$invoice->id)->assertOk()->assertSee('USD')->assertSee('Mata uang')->assertSee('Kurs terhadap Rupiah')->assertSee('15.850,00')->assertSee('Total setara')->assertSee('668,77');
        $this->get('/invoices')->assertOk()->assertSee('USD')->assertSee('10.600.000,00');

        $this->assertDatabaseHas('activity_logs', ['action' => 'job.closed', 'record_id' => $invoice->snapshot->id]);
        $journal = Journal::where('source_type', Job::class)->where('source_id', $invoice->job_id)->where('type', 'job_closing')->firstOrFail();
        $this->assertSame('10600000.00', $journal->entries()->where('description', 'Piutang '.$invoice->number)->firstOrFail()->debit);
    }

    public function test_idr_quotation_produces_idr_invoice_defaults(): void
    {
        $job = $this->convertedJob('IDR', '1');
        $this->close($job, '0');
        $invoice = $job->fresh()->invoice;

        $this->assertSame('IDR', $invoice->currency);
        $this->assertSame('1.00', $invoice->exchange_rate);
        $this->assertSame('IDR', $invoice->snapshot->currency);
        $this->assertSame('9500000.00', $invoice->total);
        $this->get('/invoices/'.$invoice->id)->assertOk()->assertSee('IDR')->assertDontSee('setara')->assertDontSee('15.850,00');
    }

    public function test_closing_rejects_zero_rate_quotation(): void
    {
        $job = $this->convertedJob('USD', '15850');
        $snapshot = $job->quotation_snapshot;
        $snapshot['exchange_rate'] = '0';
        $job->quotation_snapshot = $snapshot;
        $job->save();
        $this->post('/closing/'.$job->id, ['lock_version' => $job->lock_version, 'closing_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString(), 'funding_account' => 'bank', 'tax' => '1100000'])->assertSessionHasErrors('job');
        $this->assertSame('open', $job->fresh()->status);
        $this->assertNull($job->fresh()->invoice);
    }
}
