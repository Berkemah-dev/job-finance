<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Job;
use App\Models\JobCost;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\JobCostService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobCostTest extends TestCase
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

    private function data(array $overrides = []): array
    {
        return array_replace(['job_version' => $this->job->fresh()->lock_version, 'description' => 'Jasa trucking', 'type' => 'provision', 'cost_date' => today()->toDateString(),
            'quantity' => '1', 'unit' => 'Layanan', 'unit_cost' => '3000000', 'unit_price' => '4500000', 'payee' => 'PT Angkutan', 'reference' => 'INV-001', 'notes' => 'Biaya aktual'], $overrides);
    }

    private function createCost(array $overrides = []): JobCost
    {
        $this->post('/jobs/'.$this->job->id.'/costs', $this->data($overrides))->assertSessionHasNoErrors()->assertRedirect();

        return $this->job->costs()->latest('id')->firstOrFail();
    }

    private function versions(JobCost $cost): array
    {
        return ['job_version' => $this->job->fresh()->lock_version, 'lock_version' => $cost->fresh()->lock_version];
    }

    public function test_aggregate_overflow_rolls_back_cost_and_job_version(): void
    {
        JobCost::factory()->create(['job_id' => $this->job->id, 'total_cost' => '9999999999999999.99', 'total_price' => '9999999999999999.99']);
        $version = $this->job->fresh()->lock_version;
        $this->post('/jobs/'.$this->job->id.'/costs', $this->data())->assertSessionHasErrors('items');
        $this->assertDatabaseCount('job_costs', 1);
        $this->assertSame($version, $this->job->fresh()->lock_version);
        $this->assertDatabaseMissing('document_sequences', ['type' => 'cst']);
    }

    public function test_dashboard_uses_final_open_job_costs_only(): void
    {
        $draft = $this->createCost();
        $temporary = $this->createCost(['type' => 'temporary', 'unit_cost' => '1250.25', 'unit_price' => '1250.25']);
        $this->post('/jobs/'.$this->job->id.'/costs/'.$temporary->id.'/finalize', $this->versions($temporary))->assertSessionHasNoErrors();
        JobCost::factory()->create(['job_id' => Job::factory()->create(['status' => 'closed'])->id, 'status' => 'final', 'total_cost' => '999.00', 'total_price' => '999.00']);
        $summary = app(DashboardService::class)->summary();
        $this->assertSame('1250.25', $summary['temporaryBalance']);
        $this->assertSame('0.00', $summary['provisionBalance']);
        $this->get('/dashboard')->assertOk()->assertSee('1.250,25');
        $this->post('/jobs/'.$this->job->id.'/costs/'.$draft->id.'/finalize', $this->versions($draft))->assertSessionHasNoErrors();
        $this->assertSame('3000000.00', app(DashboardService::class)->summary()['provisionBalance']);
    }

    public function test_finance_can_record_update_and_finalize_actual_costs_with_brief_totals(): void
    {
        $this->get('/costs')->assertOk();
        $this->get('/jobs/'.$this->job->id.'/costs/create')->assertOk();
        $temporary = $this->createCost(['type' => 'temporary', 'description' => 'Dokumen', 'unit_cost' => '5000000', 'unit_price' => '5000000']);
        $provision = $this->createCost();
        $url = '/jobs/'.$this->job->id.'/costs/'.$provision->id;
        $this->get($url.'/edit')->assertOk();
        $this->put($url, $this->data(['lock_version' => 0, 'notes' => 'Sudah diperiksa']))->assertSessionHasNoErrors();
        $summary = app(JobCostService::class)->summary($this->job);
        $this->assertSame('9500000.00', $summary['all']['subtotal']);
        $this->assertSame('1500000.00', $summary['all']['profit']);
        $this->assertSame('33.33', $summary['all']['margin']);
        $this->assertSame(2, $summary['draft']['count']);
        $this->assertSame(0, $summary['final']['count']);
        foreach ([$temporary, $provision] as $cost) {
            $this->post('/jobs/'.$this->job->id.'/costs/'.$cost->id.'/finalize', $this->versions($cost))->assertSessionHasNoErrors();
        }
        $summary = app(JobCostService::class)->summary($this->job);
        $this->assertSame(2, $summary['final']['count']);
        $this->assertSame(0, $summary['draft']['count']);
        $this->assertSame('9500000.00', $summary['final']['subtotal']);
        $this->assertSame($this->finance->id, $provision->fresh()->finalized_by);
        $this->assertNotNull($provision->fresh()->finalized_at);
        $this->get($url)->assertOk()->assertSee('terkunci')->assertDontSee('Edit biaya Draft')->assertDontSee('Finalisasi biaya</button>', false);
        $this->get('/jobs/'.$this->job->id.'/costs')->assertOk()->assertSee('9.500.000,00');
        $this->assertDatabaseHas('activity_logs', ['action' => 'job_cost.finalized', 'user_id' => $this->finance->id]);
    }

    public function test_final_costs_cannot_be_modified_deleted_or_finalized_twice(): void
    {
        $cost = $this->createCost();
        $url = '/jobs/'.$this->job->id.'/costs/'.$cost->id;
        $this->post($url.'/finalize', $this->versions($cost))->assertSessionHasNoErrors();
        $this->get($url.'/edit')->assertForbidden();
        $this->put($url, $this->data(['lock_version' => 1]))->assertForbidden();
        $this->delete($url, $this->versions($cost))->assertForbidden();
        $this->post($url.'/finalize', $this->versions($cost))->assertForbidden();
        $this->assertSame('4500000.00', $cost->fresh()->total_price);
        $this->assertSame(1, ActivityLog::where('action', 'job_cost.finalized')->count());
    }

    public function test_delete_draft_soft_deletes_and_removes_it_from_totals(): void
    {
        $cost = $this->createCost();
        $this->delete('/jobs/'.$this->job->id.'/costs/'.$cost->id, $this->versions($cost))->assertSessionHasNoErrors();
        $this->assertSoftDeleted($cost);
        $this->assertSame($this->finance->id, $cost->fresh()->deleted_by);
        $this->assertSame('0.00', app(JobCostService::class)->summary($this->job)['all']['subtotal']);
        $this->get('/jobs/'.$this->job->id.'/costs/'.$cost->id)->assertNotFound();
    }

    public function test_operational_management_and_guests_cannot_access_cost_endpoints(): void
    {
        $cost = $this->createCost();
        $url = '/jobs/'.$this->job->id.'/costs';
        foreach (['operational', 'management'] as $role) {
            $this->actingAs(User::where('email', $role.'@jobfinance.test')->firstOrFail());
            $this->get('/costs')->assertForbidden();
            $this->get($url)->assertForbidden();
            $this->get($url.'/create')->assertForbidden();
            $this->get($url.'/'.$cost->id)->assertForbidden();
            $this->post($url, $this->data())->assertForbidden();
            $this->put($url.'/'.$cost->id, $this->data(['lock_version' => 0]))->assertForbidden();
            $this->delete($url.'/'.$cost->id, $this->versions($cost))->assertForbidden();
            $this->post($url.'/'.$cost->id.'/finalize', $this->versions($cost))->assertForbidden();
        }
        $this->app['auth']->forgetGuards();
        $this->get('/costs')->assertRedirect('/login');
    }

    public function test_nested_routes_cannot_read_or_mutate_a_cost_from_another_job(): void
    {
        $cost = $this->createCost();
        $other = Job::factory()->open()->create();
        $url = '/jobs/'.$other->id.'/costs/'.$cost->id;
        $this->get($url)->assertNotFound();
        $this->get($url.'/edit')->assertNotFound();
        $this->put($url, $this->data(['lock_version' => 0, 'job_version' => 0]))->assertNotFound();
        $this->delete($url, ['lock_version' => 0, 'job_version' => 0])->assertNotFound();
        $this->post($url.'/finalize', ['lock_version' => 0, 'job_version' => 0])->assertNotFound();
        $this->assertSame('draft', $cost->fresh()->status);
    }

    public function test_cost_mutations_require_open_job_including_direct_urls(): void
    {
        $cost = $this->createCost();
        $url = '/jobs/'.$this->job->id.'/costs';
        foreach (['draft', 'cancelled', 'closed'] as $status) {
            $this->job->update(['status' => $status]);
            $this->get($url)->assertOk();
            $this->get($url.'/create')->assertForbidden();
            $this->post($url, $this->data())->assertForbidden();
            $this->put($url.'/'.$cost->id, $this->data(['lock_version' => 0]))->assertForbidden();
            $this->delete($url.'/'.$cost->id, $this->versions($cost))->assertForbidden();
            $this->post($url.'/'.$cost->id.'/finalize', $this->versions($cost))->assertForbidden();
        }
        $this->assertSame('draft', $cost->fresh()->status);
    }

    public function test_stale_pages_and_duplicate_create_do_not_overwrite_or_duplicate_costs(): void
    {
        $url = '/jobs/'.$this->job->id.'/costs';
        $original = $this->data();
        $this->post($url, $original)->assertSessionHasNoErrors();
        $cost = $this->job->costs()->firstOrFail();
        $this->post($url, $original)->assertSessionHasErrors('lock_version');
        $this->assertDatabaseCount('job_costs', 1);
        $this->put($url.'/'.$cost->id, $this->data(['lock_version' => 99]))->assertSessionHasErrors('lock_version');
        $versions = $this->versions($cost);
        $this->job->increment('lock_version');
        $this->post($url.'/'.$cost->id.'/finalize', $versions)->assertSessionHasErrors('lock_version');
        $this->delete($url.'/'.$cost->id, $versions)->assertSessionHasErrors('lock_version');
        $this->assertSame('draft', $cost->fresh()->status);
    }

    public function test_invalid_amounts_dates_types_and_temporary_markup_are_rejected(): void
    {
        $url = '/jobs/'.$this->job->id.'/costs';
        foreach (['-1', '0.001', '1e6', '1000000000'] as $amount) {
            $this->post($url, $this->data(['unit_cost' => $amount]))->assertSessionHasErrors('unit_cost');
        }
        $this->post($url, $this->data(['type' => 'temporary']))->assertSessionHasErrors('unit_price');
        $this->post($url, $this->data(['type' => 'other']))->assertSessionHasErrors('type');
        $this->post($url, $this->data(['quantity' => '0']))->assertSessionHasErrors('quantity');
        $this->post($url, $this->data(['cost_date' => today()->subDay()->toDateString()]))->assertSessionHasErrors('cost_date');
        $this->post($url, $this->data(['cost_date' => today()->addDay()->toDateString()]))->assertSessionHasErrors('cost_date');
        $this->post($url, $this->data(['description' => '']))->assertSessionHasErrors('description');
        $this->assertDatabaseCount('job_costs', 0);
    }

    public function test_decimal_rounding_loss_zero_margin_and_injected_fields(): void
    {
        $cost = $this->createCost(['quantity' => '0.15', 'unit_cost' => '0.10', 'unit_price' => '0.30', 'total_price' => '999999', 'status' => 'final', 'finalized_by' => 1, 'number' => 'HACK']);
        $this->assertSame('0.02', $cost->total_cost);
        $this->assertSame('0.05', $cost->total_price);
        $this->assertSame('draft', $cost->status);
        $this->assertNull($cost->finalized_by);
        $this->assertNotSame('HACK', $cost->number);
        $this->assertSame('0.03', app(JobCostService::class)->summary($this->job)['all']['profit']);
        $this->put('/jobs/'.$this->job->id.'/costs/'.$cost->id, $this->data(['lock_version' => 0, 'quantity' => '0.15', 'unit_cost' => '0.10', 'unit_price' => '0']))->assertSessionHasNoErrors();
        $summary = app(JobCostService::class)->summary($this->job)['all'];
        $this->assertSame('-0.02', $summary['profit']);
        $this->assertSame('0.00', $summary['margin']);
    }

    public function test_filters_and_summary_keep_other_jobs_and_deleted_costs_separate(): void
    {
        $draft = $this->createCost();
        $final = $this->createCost(['type' => 'temporary', 'unit_cost' => '100', 'unit_price' => '100']);
        $this->post('/jobs/'.$this->job->id.'/costs/'.$final->id.'/finalize', $this->versions($final))->assertSessionHasNoErrors();
        JobCost::factory()->create(['description' => 'Another job expense']);
        $this->get('/jobs/'.$this->job->id.'/costs?status=final&type=temporary')->assertSee($final->number)->assertDontSee($draft->number)->assertDontSee('Another job expense');
        $summary = app(JobCostService::class)->summary($this->job);
        $this->assertSame('100.00', $summary['final']['temporary']);
        $this->assertSame('4500000.00', $summary['draft']['provision_sell']);
    }

    public function test_create_and_finalize_rollback_with_job_version_and_number_on_audit_failure(): void
    {
        ActivityLog::creating(function ($log) {
            if ($log->action === 'job_cost.created') {
                throw new \RuntimeException('Audit failed');
            }
        });
        try {
            app(JobCostService::class)->save($this->job, null, $this->data(), $this->finance);
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Audit failed', $e->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }
        $this->assertDatabaseCount('job_costs', 0);
        $this->assertDatabaseMissing('document_sequences', ['type' => 'cst']);
        $this->assertSame(0, $this->job->fresh()->lock_version);
        $cost = $this->createCost();
        $this->assertStringEndsWith('-00001', $cost->number);
        $version = $this->job->fresh()->lock_version;
        ActivityLog::creating(function ($log) {
            if ($log->action === 'job_cost.finalized') {
                throw new \RuntimeException('Audit failed');
            }
        });
        try {
            app(JobCostService::class)->finalize($this->job, $cost, $this->versions($cost), $this->finance);
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Audit failed', $e->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }
        $this->assertSame('draft', $cost->fresh()->status);
        $this->assertNull($cost->fresh()->finalized_at);
        $this->assertSame($version, $this->job->fresh()->lock_version);
    }
}
