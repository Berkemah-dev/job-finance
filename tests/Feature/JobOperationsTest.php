<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Job;
use App\Models\JobCost;
use App\Models\User;
use App\Services\JobService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->operator = User::where('email', 'operational@jobfinance.test')->firstOrFail();
        $this->actingAs($this->operator);
    }

    private function data(Job $job): array
    {
        return ['lock_version' => $job->lock_version, 'subject' => 'Pengiriman Jakarta ke Surabaya', 'job_date' => $job->job_date->format('Y-m-d'), 'service_type' => 'land', 'origin' => 'Jakarta', 'destination' => 'Surabaya', 'shipment_reference' => 'SJ-123', 'cargo_description' => '2 kontainer', 'operational_notes' => 'Hubungi penerima sebelum tiba', 'expected_completion_date' => today()->addDays(2)->toDateString()];
    }

    public function test_edit_open_job_and_preserve_source_snapshot(): void
    {
        $job = Job::factory()->create();
        $snapshot = $job->quotation_snapshot;
        $this->get('/jobs/'.$job->id.'/edit')->assertOk();
        $this->put('/jobs/'.$job->id, $this->data($job) + ['status' => 'closed', 'customer_id' => 999, 'number' => 'HACK'])->assertSessionHasNoErrors()->assertRedirect();
        $job->refresh();
        $this->assertSame('draft', $job->status);
        $this->assertSame($snapshot, $job->quotation_snapshot);
        $this->assertSame('Jakarta', $job->origin);
        $this->assertNotSame('HACK', $job->number);
        $this->post('/jobs/'.$job->id.'/open', ['lock_version' => 1])->assertSessionHasNoErrors();
        $job->refresh();
        $this->assertSame('open', $job->status);
        $this->assertSame($this->operator->id, $job->opened_by);
        $this->assertNotNull($job->opened_at);
        $this->get('/jobs?status=open')->assertSee($job->number);
        $this->get('/jobs/'.$job->id)->assertSee('Jakarta')->assertSee('Surabaya')->assertDontSee('Lihat biaya aktual');
        $this->put('/jobs/'.$job->id, $this->data($job))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('activity_logs', ['action' => 'job.open', 'user_id' => $this->operator->id]);
    }

    public function test_stale_operational_data_invalid_dates_and_state_transitions(): void
    {
        $job = Job::factory()->create();
        $this->put('/jobs/'.$job->id, array_replace($this->data($job), ['expected_completion_date' => today()->subDay()->toDateString()]))->assertSessionHasErrors('expected_completion_date');
        $this->post('/jobs/'.$job->id.'/cancel', ['lock_version' => 0, 'reason' => 'test'])->assertForbidden();
        $this->post('/jobs/'.$job->id.'/open', ['lock_version' => 9])->assertSessionHasErrors('lock_version');
        $this->post('/jobs/'.$job->id.'/open', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->post('/jobs/'.$job->id.'/open', ['lock_version' => 1])->assertForbidden();
        $this->put('/jobs/'.$job->id, $this->data($job))->assertSessionHasErrors('lock_version');
        $job->refresh();
        $this->put('/jobs/'.$job->id, array_replace($this->data($job), ['job_date' => today()->subDay()->toDateString()]))->assertSessionHasErrors('job_date');
        $this->post('/jobs/'.$job->id.'/cancel', ['lock_version' => 1])->assertSessionHasErrors('reason');
    }

    public function test_open_requires_active_customer_and_nonfuture_date(): void
    {
        $job = Job::factory()->create();
        $job->customer->delete();
        $this->post('/jobs/'.$job->id.'/open', ['lock_version' => 0])->assertSessionHasErrors('customer');
        $job = Job::factory()->create(['job_date' => today()->addDay()]);
        $this->post('/jobs/'.$job->id.'/open', ['lock_version' => 0])->assertSessionHasErrors('job_date');
    }

    public function test_cancel_requires_no_active_costs_and_locks_the_job(): void
    {
        $job = Job::factory()->open()->create();
        $cost = JobCost::factory()->create(['job_id' => $job->id, 'created_by' => $this->operator->id, 'updated_by' => $this->operator->id]);
        $this->post('/jobs/'.$job->id.'/cancel', ['lock_version' => 0, 'reason' => 'Permintaan customer'])->assertSessionHasErrors('costs');
        $cost->delete();
        $this->post('/jobs/'.$job->id.'/cancel', ['lock_version' => 0, 'reason' => 'Permintaan customer'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $job->fresh()->status);
        $this->get('/jobs/'.$job->id)->assertSee('Permintaan customer');
        $this->put('/jobs/'.$job->id, $this->data($job->fresh()))->assertForbidden();
        $this->post('/jobs/'.$job->id.'/open', ['lock_version' => 1])->assertForbidden();
        $closed = Job::factory()->create(['status' => 'closed']);
        $this->put('/jobs/'.$closed->id, $this->data($closed))->assertForbidden();
        $this->post('/jobs/'.$closed->id.'/cancel', ['lock_version' => 0, 'reason' => 'test'])->assertForbidden();
    }

    public function test_finance_is_read_only_on_operational_job_and_management_is_denied(): void
    {
        $job = Job::factory()->create();
        $this->actingAs(User::where('email', 'finance@jobfinance.test')->firstOrFail());
        $this->get('/jobs')->assertOk();
        $this->get('/jobs/'.$job->id)->assertOk()->assertDontSee('Edit operasional')->assertDontSee('Buka job');
        $this->get('/jobs/'.$job->id.'/edit')->assertForbidden();
        $this->put('/jobs/'.$job->id, $this->data($job))->assertForbidden();
        foreach (['open', 'cancel'] as $action) {
            $this->post('/jobs/'.$job->id.'/'.$action, ['lock_version' => 0, 'reason' => 'test'])->assertForbidden();
        }
        $this->actingAs(User::where('email', 'management@jobfinance.test')->firstOrFail());
        $this->get('/jobs')->assertForbidden();
        $this->get('/jobs/'.$job->id)->assertForbidden();
    }

    public function test_job_transition_rolls_back_if_audit_fails(): void
    {
        $job = Job::factory()->create();
        ActivityLog::creating(function ($log) {
            if ($log->action === 'job.open') {
                throw new \RuntimeException('Audit unavailable');
            }
        });
        try {
            app(JobService::class)->transition($job, 'open', ['lock_version' => 0], $this->operator);
            $this->fail('Expected exception');
        } catch (\RuntimeException $e) {
            $this->assertSame('Audit unavailable', $e->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }
        $this->assertSame('draft', $job->fresh()->status);
        $this->assertNull($job->fresh()->opened_at);
        $this->assertSame(0, $job->fresh()->lock_version);
    }
}
