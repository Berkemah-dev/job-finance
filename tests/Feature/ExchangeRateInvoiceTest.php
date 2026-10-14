<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
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

    private function convertedJob(string $currency, string $rate, ?array $items = null): Job
    {
        $items ??= [
            ['description' => 'Dokumen', 'type' => 'temporary', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '1000', 'unit_price' => '1000', 'currency' => $currency, 'exchange_rate' => $rate],
            ['description' => 'Ongkir laut', 'type' => 'provision', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '2000', 'unit_price' => '3000', 'currency' => $currency, 'exchange_rate' => $rate],
        ];

        $this->post('/quotations', ['customer_id' => $this->customer->id, 'subject' => 'Pengiriman internasional', 'quotation_date' => '2026-09-08', 'valid_until' => '2026-10-08',
            'service_type' => 'sea', 'origin' => 'Jakarta', 'destination' => 'Singapore', 'currency' => $currency, 'exchange_rate' => $rate, 'payment_terms' => 'net_30',
            'shipper_name' => 'PT Sumber Makmur', 'shipper_address' => 'Jl. Raya Cakung 10, Jakarta', 'consignee_name' => 'Global Trading Pte', 'consignee_address' => '1 Raffles Place, Singapore',
            'items' => $items])->assertSessionHasNoErrors()->assertRedirect();
        $q = Quotation::latest('id')->firstOrFail();
        $this->post('/quotations/'.$q->id.'/submit', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->actingAs($this->manager);
        $this->post('/quotations/'.$q->id.'/approve', ['lock_version' => 1])->assertSessionHasNoErrors();
        $this->actingAs($this->operator);
        $this->post('/quotations/'.$q->id.'/convert', ['lock_version' => 2])->assertSessionHasNoErrors()->assertRedirect();
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

    public function test_closing_carries_biaya_kurs_tagih_into_invoice(): void
    {
        $job = $this->convertedJob('USD', '15850');
        $this->close($job);
        $invoice = $job->fresh()->invoice;

        // Kurs header invoice mengikuti Kurs Tagih pada setiap Biaya Job, bukan input quotation.
        $this->assertSame('USD', $invoice->currency);
        $this->assertSame('15850.00', $invoice->exchange_rate);
        $this->assertSame('USD', $invoice->snapshot->currency);
        $this->assertSame('15850.00', $invoice->snapshot->exchange_rate);
        $this->assertSame('63400000.00', $invoice->subtotal);
        $this->assertSame('64500000.00', $invoice->total);
        $this->assertSame('4000.00', (string) $invoice->inInvoiceCurrency('subtotal'));
        $this->assertSame('69.40', (string) $invoice->inInvoiceCurrency('tax'));
        $this->assertSame('4069.40', (string) $invoice->inInvoiceCurrency('total'));

        $this->get('/invoices/'.$invoice->id)->assertOk()->assertSee('USD')->assertSee('Mata uang')->assertSee('Kurs terhadap Rupiah')->assertSee('15.850,00')->assertSee('Total setara')->assertSee('4.069,40');
        $this->get('/invoices')->assertOk()->assertSee('USD')->assertSee('64.500.000,00');

        $this->assertDatabaseHas('activity_logs', ['action' => 'job.closed', 'record_id' => $invoice->snapshot->id]);
        $journal = Journal::where('source_type', Job::class)->where('source_id', $invoice->job_id)->where('type', 'job_closing')->firstOrFail();
        $this->assertEquals(64500000, $journal->entries()->where('description', 'like', 'Piutang%'.$invoice->number)->sum('debit'));
    }

    public function test_idr_quotation_produces_idr_invoice_defaults(): void
    {
        $job = $this->convertedJob('IDR', '1');
        $this->close($job, '0');
        $invoice = $job->fresh()->invoice;

        $this->assertSame('IDR', $invoice->currency);
        $this->assertSame('1.00', $invoice->exchange_rate);
        $this->assertSame('IDR', $invoice->snapshot->currency);
        $this->assertSame('4000.00', $invoice->total);
        $this->get('/invoices/'.$invoice->id)->assertOk()->assertSee('IDR')->assertDontSee('setara')->assertDontSee('15.850,00');
    }

    public function test_cost_form_rejects_zero_kurs_tagih(): void
    {
        $job = $this->convertedJob('USD', '15850');

        // Kurs Modal maupun Kurs Tagih wajib bernilai di atas nol.
        $this->post('/jobs/'.$job->id.'/costs', [
            'job_version' => $job->lock_version, 'description' => 'Uji kurs', 'type' => 'provision',
            'cost_date' => today()->toDateString(), 'quantity' => '1', 'unit' => 'Layanan',
            'currency' => 'USD', 'cost_exchange_rate' => '15850', 'price_exchange_rate' => '0',
            'unit_cost' => '1', 'unit_price' => '1',
        ])->assertSessionHasErrors('price_exchange_rate');
    }

    public function test_invoice_rate_follows_biaya_kurs_tagih_not_the_modal_rate(): void
    {
        $job = $this->convertedJob('USD', '15850');

        // Kurs Modal dan Kurs Tagih dicatat terpisah pada biaya yang sama.
        foreach ($job->costs()->get() as $cost) {
            $cost->forceFill([
                'cost_exchange_rate' => '15850.0000',
                'price_exchange_rate' => '16000.0000',
                'unit_price' => bcmul((string) $cost->foreign_unit_price, '16000', 2),
                'total_price' => bcmul((string) $cost->foreign_unit_price, '16000', 2),
            ])->save();
        }

        $this->close($job, '1120000');
        $invoice = $job->fresh()->invoice;

        // Header invoice memakai Kurs Tagih (16000), bukan Kurs Modal (15850).
        // Nominal baris temporary tetap memakai Kurs Modal karena reimbursement dibayar sebesar biaya.
        $this->assertSame('USD', $invoice->currency);
        $this->assertSame('16000.00', $invoice->exchange_rate);
        $this->assertSame('16000.00', $invoice->snapshot->exchange_rate);
        $this->assertSame('63850000.00', $invoice->subtotal);
        $this->assertSame('64970000.00', $invoice->total);
        $this->assertSame('70.00', (string) $invoice->inInvoiceCurrency('tax'));

        // Baris invoice memakai Kurs Tagih masing-masing biaya.
        $this->assertSame(
            ['16000.00', '16000.00'],
            $invoice->items()->pluck('exchange_rate')->map(fn ($r) => number_format((float) $r, 2, '.', ''))->all()
        );

        $this->assertDatabaseHas('activity_logs', ['action' => 'job.closed', 'record_id' => $invoice->snapshot->id]);
        $log = ActivityLog::where('action', 'job.closed')->where('record_id', $invoice->snapshot->id)->firstOrFail();
        $this->assertStringContainsString('"exchange_rate":"16000.00"', json_encode((array) $log->after));
        $this->assertStringContainsString('"currency":"USD"', json_encode((array) $log->after));
    }

    public function test_closing_ignores_legacy_manual_exchange_rate_override(): void
    {
        // Input kurs manual sudah dihapus; field lama harus diabaikan, bukan diterima.
        $job = $this->convertedJob('USD', '15850');
        $this->post('/closing/'.$job->id, ['lock_version' => $job->lock_version, 'closing_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString(), 'funding_account' => 'bank', 'tax' => '1100000', 'exchange_rate_override' => '16000'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $invoice = $job->fresh()->invoice;

        $this->assertSame('15850.00', $invoice->exchange_rate);
        $this->assertSame('64500000.00', $invoice->total);
    }

    public function test_closing_ignores_override_on_idr_invoice(): void
    {
        $job = $this->convertedJob('IDR', '1');
        $this->post('/closing/'.$job->id, ['lock_version' => $job->lock_version, 'closing_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString(), 'funding_account' => 'bank', 'tax' => '0', 'exchange_rate_override' => '16000'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $invoice = $job->fresh()->invoice;

        $this->assertSame('IDR', $invoice->currency);
        $this->assertSame('1.00', $invoice->exchange_rate);
        $this->assertSame('4000.00', $invoice->total);
    }
}
