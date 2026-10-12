<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Job;
use App\Models\JobCost;
use App\Models\Journal;
use App\Models\Reimbursement;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalJurnalSimulationTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;

    private User $operator;

    private ChartOfAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
        $this->operator = User::where('email', 'operational@jobfinance.test')->firstOrFail();
        $this->actingAs($this->finance);

        $this->bankAccount = ChartOfAccount::where('type', 'asset')
            ->where('code', 'like', '1%')
            ->firstOrFail();
    }

    private function assertJournalBalanced(Journal $journal): void
    {
        $debit = (float) $journal->entries->sum(fn ($e) => (float) $e->debit);
        $credit = (float) $journal->entries->sum(fn ($e) => (float) $e->credit);
        $this->assertEquals($debit, $credit, "Jurnal {$journal->id} ({$journal->type}) tidak seimbang: Debit {$debit} vs Credit {$credit}");
    }

    public function test_direct_finance_cost_is_automatically_final_and_journalized_immediately(): void
    {
        $job = Job::factory()->open()->create();
        $vendor = Vendor::factory()->create(['type' => 'trucking']);

        // Biaya langsung diinput oleh Finance (tanpa quotation_id)
        $response = $this->post("/jobs/{$job->id}/costs", [
            'job_version' => $job->lock_version,
            'description' => 'Biaya Langsung Lift On / Lift Off',
            'type' => 'provision',
            'cost_category' => 'payment_request',
            'cost_date' => today()->toDateString(),
            'vendor_id' => $vendor->id,
            'quantity' => '1',
            'unit' => 'Container',
            'unit_cost' => '500000',
            'unit_price' => '650000',
            'payee' => $vendor->name,
            'reference' => 'LIFT-001',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $cost = $job->costs()->latest('id')->firstOrFail();

        // Status harus otomatis 'final'
        $this->assertSame('final', $cost->status);
        $this->assertNull($cost->quotation_id);

        // Jurnal job_cost_direct (Hutang Vendor vs Provision WIP) harus langsung terbentuk
        $journal = Journal::where('source_type', JobCost::class)
            ->where('source_id', $cost->id)
            ->where('type', 'job_cost_direct')
            ->first();

        $this->assertNotNull($journal, 'Jurnal pengakuan hutang langsung harus tercipta.');
        $this->assertJournalBalanced($journal);
        $this->assertEquals(500000.0, (float) $journal->entries->sum('debit'));
        $this->assertEquals(500000.0, (float) $journal->entries->sum('credit'));
    }

    public function test_trucking_cost_with_pph23_withholding_payment_simulation(): void
    {
        $job = Job::factory()->open()->create();
        $vendor = Vendor::factory()->create(['type' => 'trucking']);

        // Biaya trucking IDR 700.000
        $this->post("/jobs/{$job->id}/costs", [
            'job_version' => $job->lock_version,
            'description' => 'Jasa Trucking Pelabuhan',
            'type' => 'provision',
            'cost_date' => today()->toDateString(),
            'vendor_id' => $vendor->id,
            'quantity' => '1',
            'unit' => 'Trip',
            'unit_cost' => '700000',
            'unit_price' => '900000',
            'payee' => $vendor->name,
            'reference' => 'TRK-700',
        ])->assertSessionHasNoErrors();

        $cost = $job->costs()->latest('id')->firstOrFail();
        $this->assertSame('final', $cost->status);

        // Finance melakukan pembayaran biaya dengan potongan PPh 23 sebesar 2% (IDR 14.000)
        // Hutang Vendor = 700.000 (Dr), Kas/Bank = 686.000 (Cr), PPh 23 Hutang Pajak = 14.000 (Cr)
        $payResponse = $this->post("/jobs/{$job->id}/costs/{$cost->id}/pay", [
            'job_version' => $job->fresh()->lock_version,
            'paid_date' => today()->toDateString(),
            'payment_account_id' => $this->bankAccount->id,
            'pph23_amount' => '14000',
            'withholding_document_number' => 'BUPOT-PPH23-001',
        ]);

        $payResponse->assertSessionHasNoErrors();
        $cost->refresh();

        $this->assertNotNull($cost->paid_at);
        $this->assertSame('14000.00', $cost->pph23_amount);
        $this->assertSame('BUPOT-PPH23-001', $cost->withholding_document_number);

        // Cek jurnal pembayaran
        $paymentJournal = Journal::where('source_type', JobCost::class)
            ->where('source_id', $cost->id)
            ->where('type', 'job_cost_payment')
            ->firstOrFail();

        $this->assertJournalBalanced($paymentJournal);
        $entries = $paymentJournal->entries;

        // Debit Hutang Vendor: 700.000
        $debitTotal = (float) $entries->sum('debit');
        $this->assertEquals(700000.0, $debitTotal);

        // Credit Bank: 686.000
        $bankEntry = $entries->firstWhere('chart_of_account_id', $this->bankAccount->id);
        $this->assertNotNull($bankEntry);
        $this->assertEquals(686000.0, (float) $bankEntry->credit);

        // Credit PPh 23: 14.000
        $pph23Account = ChartOfAccount::where('code', config('accounting.mappings.pph23_payable.code'))->first();
        $this->assertNotNull($pph23Account);
        $pphEntry = $entries->firstWhere('chart_of_account_id', $pph23Account->id);
        $this->assertNotNull($pphEntry);
        $this->assertEquals(14000.0, (float) $pphEntry->credit);
    }

    public function test_agent_commission_with_pph21_withholding_payment_simulation(): void
    {
        $job = Job::factory()->open()->create();
        $agent = Vendor::factory()->create([
            'type' => 'international_agent',
            'name' => 'Agent Global Shipping',
        ]);

        // Biaya komisi agen IDR 300.000 (credit_note)
        $this->post("/jobs/{$job->id}/costs", [
            'job_version' => $job->lock_version,
            'description' => 'Komisi Agen Luar Negeri',
            'type' => 'provision',
            'cost_category' => 'credit_note',
            'cost_date' => today()->toDateString(),
            'vendor_id' => $agent->id,
            'quantity' => '1',
            'unit' => 'Shipment',
            'unit_cost' => '300000',
            'unit_price' => '300000',
            'payee' => $agent->name,
            'reference' => 'COMM-001',
        ])->assertSessionHasNoErrors();

        $cost = $job->costs()->latest('id')->firstOrFail();
        $this->assertSame('final', $cost->status);

        // Pembayaran komisi dengan pemotongan PPh 21 sebesar IDR 15.000
        $payResponse = $this->post("/jobs/{$job->id}/costs/{$cost->id}/pay", [
            'job_version' => $job->fresh()->lock_version,
            'paid_date' => today()->toDateString(),
            'payment_account_id' => $this->bankAccount->id,
            'pph23_amount' => '15000',
            'withholding_document_number' => 'BUPOT-PPH21-001',
        ]);

        $payResponse->assertSessionHasNoErrors();
        $cost->refresh();

        $this->assertNotNull($cost->paid_at);

        // Cek jurnal pembayaran: harus masuk ke akun PPh 21 Payable (bukan PPh 23)
        $paymentJournal = Journal::where('source_type', JobCost::class)
            ->where('source_id', $cost->id)
            ->where('type', 'job_cost_payment')
            ->firstOrFail();

        $this->assertJournalBalanced($paymentJournal);
        $entries = $paymentJournal->entries;

        // Debit Hutang Agent: 300.000
        $this->assertEquals(300000.0, (float) $entries->sum('debit'));

        // Credit Kas/Bank: 285.000
        $bankEntry = $entries->firstWhere('chart_of_account_id', $this->bankAccount->id);
        $this->assertNotNull($bankEntry);
        $this->assertEquals(285000.0, (float) $bankEntry->credit);

        // Credit PPh 21: 15.000
        $pph21Account = ChartOfAccount::where('code', config('accounting.mappings.pph21_payable.code'))->first();
        $this->assertNotNull($pph21Account);
        $pphEntry = $entries->firstWhere('chart_of_account_id', $pph21Account->id);
        $this->assertNotNull($pphEntry);
        $this->assertEquals(15000.0, (float) $pphEntry->credit);
    }

    public function test_complete_job_closing_and_radix_account_reclassification(): void
    {
        // Job Ekspor Udara (exp_air)
        $job = Job::factory()->open()->create([
            'service_type' => 'exp_air',
        ]);

        // 1. Biaya Temporary (misal Dokumen Clearance) IDR 200.000
        JobCost::factory()->create([
            'job_id' => $job->id,
            'status' => 'final',
            'type' => 'temporary',
            'description' => 'Biaya Dokumen Ekspor',
            'quantity' => '1.00',
            'unit_cost' => '200000.00',
            'unit_price' => '200000.00',
            'total_cost' => '200000.00',
            'total_price' => '200000.00',
            'finalized_by' => $this->finance->id,
            'finalized_at' => now(),
        ]);

        // 2. Biaya Provision (Air Freight) Cost 1.000.000, Sell 1.500.000
        JobCost::factory()->create([
            'job_id' => $job->id,
            'status' => 'final',
            'type' => 'provision',
            'description' => 'Air Freight Jakarta-Singapore',
            'quantity' => '1.00',
            'unit_cost' => '1000000.00',
            'unit_price' => '1500000.00',
            'total_cost' => '1000000.00',
            'total_price' => '1500000.00',
            'finalized_by' => $this->finance->id,
            'finalized_at' => now(),
        ]);

        // Eksekusi Closing Job
        $response = $this->post("/closing/{$job->id}", [
            'lock_version' => $job->lock_version,
            'closing_date' => today()->toDateString(),
            'due_date' => today()->addDays(14)->toDateString(),
            'funding_account' => 'bank',
            'tax' => '165000', // PPN 11% dari 1.500.000
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $job->refresh();
        $this->assertSame('closed', $job->status);

        // Verifikasi semua jurnal balance
        $journals = Journal::where('source_type', Job::class)
            ->where('source_id', $job->id)
            ->with('entries')
            ->get();

        $this->assertNotEmpty($journals);
        foreach ($journals as $j) {
            $this->assertJournalBalanced($j);
        }

        // Cek closing journal entries: HPP Unclose & Close, Pendapatan Unclose & Close
        $closingJournal = $journals->firstWhere('type', 'job_closing');
        $this->assertNotNull($closingJournal);

        // Akun-akun yang terlibat harus sesuai prefix exp_air (Ekspor)
        $accounts = $closingJournal->entries->load('account')->pluck('account.name');
        $this->assertTrue($accounts->contains(fn ($n) => str_contains($n, 'Ekspor') || str_contains($n, 'HPP') || str_contains($n, 'Pendapatan')));
    }

    public function test_operational_reimbursement_clearance_sppb_flow_simulation(): void
    {
        // 1. Pengajuan talangan clearance SPPB sebesar IDR 150.000 untuk operational employee
        $this->actingAs($this->finance);
        $createRes = $this->post('/reimbursements', [
            'employee_id' => $this->operator->id,
            'category' => 'other',
            'reimbursement_date' => today()->toDateString(),
            'description' => 'Talangan SPPB Bea Cukai',
            'amount' => '150000.00',
            'notes' => 'Clearance pelabuhan Tanjung Priok',
        ]);
        $createRes->assertSessionHasNoErrors()->assertRedirect();

        $reimbursement = Reimbursement::latest('id')->firstOrFail();
        $this->assertSame('pending', $reimbursement->status);
        $this->assertSame('150000.00', $reimbursement->amount);

        // 2. Finance menyetujui (Approve)
        $this->actingAs($this->finance);
        $this->post("/reimbursements/{$reimbursement->id}/approve", [
            'lock_version' => $reimbursement->lock_version,
            'notes' => 'Disetujui untuk dicairkan',
        ])->assertSessionHasNoErrors();
        $this->assertSame('approved', $reimbursement->fresh()->status);

        // 3. Finance mencairkan via Kas/Bank (Pay)
        $this->post("/reimbursements/{$reimbursement->id}/pay", [
            'lock_version' => $reimbursement->fresh()->lock_version,
            'paid_date' => today()->toDateString(),
            'funding_account' => 'bank',
            'reference' => 'KAS-SPPB-001',
        ])->assertSessionHasNoErrors();

        $reimbursement->refresh();
        $this->assertSame('paid', $reimbursement->status);

        // 4. Periksa Jurnal Reimbursement
        $journal = Journal::where('source_type', Reimbursement::class)
            ->where('source_id', $reimbursement->id)
            ->where('type', 'reimbursement')
            ->firstOrFail();

        $this->assertJournalBalanced($journal);
        $this->assertEquals(150000.0, (float) $journal->entries->sum('debit'));
        $this->assertEquals(150000.0, (float) $journal->entries->sum('credit'));
    }

    public function test_foreign_currency_cost_usd_dual_rate_exchange_simulation(): void
    {
        $job = Job::factory()->open()->create();
        $agent = Vendor::factory()->create([
            'type' => 'international_agent',
            'name' => 'Overseas Freight Forwarder',
        ]);

        // Biaya Air Freight USD 1.000 dengan kurs awal Rp 18.000 = IDR 18.000.000
        $response = $this->post("/jobs/{$job->id}/costs", [
            'job_version' => $job->lock_version,
            'description' => 'Air Freight USD Route',
            'type' => 'provision',
            'cost_category' => 'payment_request',
            'cost_date' => today()->toDateString(),
            'vendor_id' => $agent->id,
            'currency' => 'USD',
            'exchange_rate' => '18000',
            'quantity' => '1',
            'unit' => 'Shipment',
            'unit_cost' => '1000',      // Foreign unit cost USD 1,000
            'unit_price' => '1200',     // Foreign unit price USD 1,200
            'payee' => $agent->name,
            'reference' => 'AWB-USD-001',
        ]);

        $response->assertSessionHasNoErrors();
        $cost = $job->costs()->latest('id')->firstOrFail();

        // Validasi Foreign vs IDR Fields
        $this->assertSame('USD', $cost->currency);
        $this->assertSame('18000.0000', $cost->exchange_rate);
        $this->assertSame('1000.00', $cost->foreign_unit_cost);
        $this->assertSame('18000000.00', $cost->unit_cost);
        $this->assertSame('18000000.00', $cost->total_cost);
        $this->assertSame('1200.00', $cost->foreign_unit_price);
        $this->assertSame('21600000.00', $cost->unit_price);
        $this->assertSame('21600000.00', $cost->total_price);

        // Jurnal pengakuan hutang langsung harus mencatat IDR 18.000.000
        $journal = Journal::where('source_type', JobCost::class)
            ->where('source_id', $cost->id)
            ->where('type', 'job_cost_direct')
            ->firstOrFail();

        $this->assertJournalBalanced($journal);
        $this->assertEquals(18000000.0, (float) $journal->entries->sum('debit'));
        $this->assertEquals(18000000.0, (float) $journal->entries->sum('credit'));
    }
}
