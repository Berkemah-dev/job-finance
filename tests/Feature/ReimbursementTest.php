<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\Journal;
use App\Models\Reimbursement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WeeklyPricing;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReimbursementTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
        $this->employee = User::where('email', 'operational@jobfinance.test')->firstOrFail();
        $this->actingAs($this->finance);
    }

    public function test_create_approve_pay_flow_creates_balanced_reimbursement_journal(): void
    {
        $this->post('/reimbursements', ['employee_id' => $this->employee->id, 'category' => 'transport', 'reimbursement_date' => today()->toDateString(), 'description' => 'Bensin kunjungan vendor', 'amount' => '150000.00', 'notes' => 'Mobil kebun'])
            ->assertSessionHasNoErrors()->assertRedirect();

        $reimbursement = Reimbursement::firstOrFail();
        $this->assertSame('pending', $reimbursement->status);
        $this->assertSame('150000.00', $reimbursement->amount);
        $this->assertStringStartsWith('REM-', $reimbursement->number);
        $this->get('/reimbursements')->assertOk()->assertSee($reimbursement->number)->assertSee('transport');
        $this->get('/reimbursements/'.$reimbursement->id)->assertOk()->assertSee('Menunggu')->assertSee('150.000,00');

        $this->post('/reimbursements/'.$reimbursement->id.'/approve', ['lock_version' => 0, 'notes' => 'Disetujui'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $reimbursement->fresh()->status);

        $this->post('/reimbursements/'.$reimbursement->id.'/pay', ['lock_version' => 1, 'paid_date' => today()->toDateString(), 'funding_account' => 'bank', 'reference' => 'KMK-001'])->assertSessionHasNoErrors();
        $reimbursement->refresh();
        $this->assertSame('paid', $reimbursement->status);
        $this->assertSame(today()->toDateString(), $reimbursement->paid_date->toDateString());
        $this->assertSame('KMK-001', $reimbursement->payment_reference);

        $journal = Journal::where('type', 'reimbursement')->firstOrFail();
        $this->assertSame(Reimbursement::class, $journal->source_type);
        $this->assertSame($reimbursement->id, $journal->source_id);
        $this->assertSame('posted', $journal->status);
        $debits = $journal->entries->sum(fn ($e) => (float) $e->debit);
        $credits = $journal->entries->sum(fn ($e) => (float) $e->credit);
        $this->assertSame($debits, $credits);
        $this->assertEquals(150000, $debits);
        $this->assertDatabaseCount('journal_entries', 2);
        $this->assertSame('expense', (string) $journal->entries->first()->account->type);

        foreach (['reimbursement.created', 'reimbursement.approved', 'reimbursement.paid'] as $action) {
            $this->assertDatabaseHas('activity_logs', ['action' => $action, 'user_id' => $this->finance->id]);
        }
        $this->get('/reimbursements/'.$reimbursement->id)->assertOk()->assertSee($journal->number)->assertSee('Dibayar');
    }

    public function test_foreign_reimbursement_locks_currency_rate_and_books_idr_journal(): void
    {
        Storage::fake('local');
        $job = Job::factory()->create();
        $vendor = Vendor::factory()->create();
        $attachment = UploadedFile::fake()->image('bukti.jpg', 200, 200);

        $this->post('/reimbursements', [...$this->base(), 'job_id' => $job->id, 'vendor_id' => $vendor->id, 'currency' => 'USD', 'exchange_rate' => '16000', 'amount' => '100.00', 'attachment' => $attachment])
            ->assertSessionHasNoErrors()->assertRedirect();

        $reimbursement = Reimbursement::firstOrFail();
        $this->assertSame('USD', $reimbursement->currency);
        $this->assertSame('16000.00', $reimbursement->exchange_rate);
        $this->assertSame($job->id, $reimbursement->job_id);
        $this->assertSame($vendor->id, $reimbursement->vendor_id);
        $this->assertSame('bukti.jpg', $reimbursement->attachment_name);
        Storage::disk('local')->assertExists($reimbursement->attachment_path);
        $this->get('/reimbursements/'.$reimbursement->id)->assertOk()->assertSee($job->number)->assertSee($vendor->name)->assertSee('USD')->assertSee('Unduh bukti.jpg');
        $this->get('/reimbursements/'.$reimbursement->id.'/attachment')->assertOk()->assertHeader('content-disposition', 'attachment; filename=bukti.jpg');

        $this->post('/reimbursements/'.$reimbursement->id.'/approve', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->post('/reimbursements/'.$reimbursement->id.'/pay', ['lock_version' => 1, 'paid_date' => today()->toDateString(), 'funding_account' => 'bank'])->assertSessionHasNoErrors();

        $journal = Journal::where('type', 'reimbursement')->firstOrFail();
        $this->assertEquals(1600000, $journal->entries->sum(fn ($e) => (float) $e->debit));
        $this->assertEquals(1600000, $journal->entries->sum(fn ($e) => (float) $e->credit));
    }

    public function test_foreign_reimbursement_defaults_rate_from_active_weekly_pricing(): void
    {
        WeeklyPricing::factory()->create(['week' => now()->format('o-W'), 'currency' => 'USD', 'exchange_rate' => 15250, 'is_active' => true]);

        $this->post('/reimbursements', [...$this->base(), 'currency' => 'USD', 'exchange_rate' => '', 'amount' => '100.00'])
            ->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('15250.00', Reimbursement::firstOrFail()->exchange_rate);
    }

    public function test_foreign_reimbursement_without_rate_and_no_weekly_is_rejected(): void
    {
        $this->post('/reimbursements', [...$this->base(), 'currency' => 'USD', 'exchange_rate' => '', 'amount' => '100.00'])
            ->assertSessionHasErrors('exchange_rate');
        $this->assertDatabaseCount('reimbursements', 0);
    }

    public function test_idr_reimbursement_force_rate_one_even_if_override_submitted(): void
    {
        $this->post('/reimbursements', [...$this->base(), 'currency' => 'IDR', 'exchange_rate' => '16000', 'amount' => '50000'])
            ->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('1.00', Reimbursement::firstOrFail()->exchange_rate);
    }

    public function test_validation_rejects_invalid_requests(): void
    {
        $base = $this->base();
        $this->post('/reimbursements', [...$base, 'amount' => '0'])->assertSessionHasErrors('amount');
        $this->post('/reimbursements', [...$base, 'amount' => 'abc'])->assertSessionHasErrors('amount');
        $this->post('/reimbursements', [...$base, 'category' => 'box'])->assertSessionHasErrors('category');
        $this->post('/reimbursements', [...$base, 'reimbursement_date' => today()->addDays(2)->toDateString()])->assertSessionHasErrors('reimbursement_date');
        $this->post('/reimbursements', [...$base, 'employee_id' => 999999])->assertSessionHasErrors('employee_id');
        $this->post('/reimbursements', [...$base, 'description' => 'x'])->assertSessionHasErrors('description');
        $this->post('/reimbursements', [...$base, 'job_id' => 999999])->assertSessionHasErrors('job_id');
        $this->post('/reimbursements', [...$base, 'vendor_id' => 999999])->assertSessionHasErrors('vendor_id');
        $this->post('/reimbursements', [...$base, 'currency' => 'EUR'])->assertSessionHasErrors('currency');
        $this->post('/reimbursements', [...$base, 'exchange_rate' => '0'])->assertSessionHasErrors('exchange_rate');
        $this->post('/reimbursements', [...$base, 'attachment' => UploadedFile::fake()->create('barang.txt', 10)])->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('reimbursements', 0);
    }

    private function base(): array
    {
        return ['employee_id' => $this->employee->id, 'category' => 'transport', 'reimbursement_date' => today()->toDateString(), 'description' => 'Bensin mobil', 'amount' => '150000'];
    }

    public function test_transitions_guard_status_version_and_roles(): void
    {
        $this->post('/reimbursements', ['employee_id' => $this->employee->id, 'category' => 'meals', 'reimbursement_date' => today()->toDateString(), 'description' => 'Makan klien', 'amount' => '75000']);
        $reimbursement = Reimbursement::firstOrFail();

        $this->post('/reimbursements/'.$reimbursement->id.'/pay', ['lock_version' => 0, 'paid_date' => today()->toDateString(), 'funding_account' => 'bank'])->assertSessionHasErrors('reimbursement');
        $this->post('/reimbursements/'.$reimbursement->id.'/approve', ['lock_version' => 5])->assertSessionHasErrors('lock_version');
        $this->assertSame('pending', $reimbursement->fresh()->status);

        $this->post('/reimbursements/'.$reimbursement->id.'/approve', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->post('/reimbursements/'.$reimbursement->id.'/approve', ['lock_version' => 1])->assertSessionHasErrors('reimbursement');
        $this->post('/reimbursements/'.$reimbursement->id.'/pay', ['lock_version' => 1, 'paid_date' => today()->subDay()->toDateString(), 'funding_account' => 'bank'])->assertSessionHasErrors('paid_date');
        $this->post('/reimbursements/'.$reimbursement->id.'/pay', ['lock_version' => 1, 'paid_date' => today()->toDateString(), 'funding_account' => 'bank'])->assertSessionHasNoErrors();
        $this->post('/reimbursements/'.$reimbursement->id.'/pay', ['lock_version' => 2, 'paid_date' => today()->toDateString(), 'funding_account' => 'bank'])->assertSessionHasErrors('reimbursement');
        $this->assertDatabaseCount('journals', 1);

        $this->actingAs(User::where('email', 'operational@jobfinance.test')->firstOrFail());
        $this->get('/reimbursements')->assertForbidden();
        $this->post('/reimbursements', ['employee_id' => $this->employee->id, 'category' => 'travel', 'reimbursement_date' => today()->toDateString(), 'description' => 'Hotel', 'amount' => '300000'])->assertForbidden();

        $this->actingAs(User::where('email', 'management@jobfinance.test')->firstOrFail());
        $this->get('/reimbursements')->assertForbidden();
        $this->get('/reimbursements/'.$reimbursement->id)->assertForbidden();
    }

    public function test_index_filters_and_counts(): void
    {
        $this->post('/reimbursements', ['employee_id' => $this->employee->id, 'category' => 'travel', 'reimbursement_date' => today()->toDateString(), 'description' => 'Tiket pesawat', 'amount' => '2500000']);
        $this->post('/reimbursements', ['employee_id' => $this->finance->id, 'category' => 'supplies', 'reimbursement_date' => today()->toDateString(), 'description' => 'ATK kantor', 'amount' => '120000']);
        [$first, $second] = Reimbursement::orderBy('id')->get();
        $this->post('/reimbursements/'.$first->id.'/approve', ['lock_version' => 0])->assertSessionHasNoErrors();

        $this->get('/reimbursements?status=approved')->assertOk()->assertSee($first->number)->assertDontSee($second->number);
        $this->get('/reimbursements?category=supplies')->assertOk()->assertSee($second->number)->assertDontSee($first->number);
        $this->get('/reimbursements?search='.$first->number)->assertOk()->assertSee($first->number)->assertDontSee($second->number);
        $this->get('/reimbursements')->assertOk()->assertSee('1')->assertSee('2');
    }
}
