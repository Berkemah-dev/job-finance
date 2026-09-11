<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Quotation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoConfirmationTest extends TestCase
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

    private function openJob(): Job
    {
        $this->post('/quotations', ['customer_id' => $this->customer->id, 'subject' => 'Pengiriman DO', 'quotation_date' => '2026-09-08', 'valid_until' => '2026-10-08',
            'service_type' => 'sea', 'origin' => 'Jakarta', 'destination' => 'Surabaya', 'currency' => 'IDR', 'exchange_rate' => '1', 'payment_terms' => 'net_30',
            'items' => [['description' => 'Dokumen', 'type' => 'temporary', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '5000000', 'unit_price' => '5000000']]])->assertSessionHasNoErrors();
        $q = Quotation::latest('id')->firstOrFail();
        $this->post('/quotations/'.$q->id.'/submit', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->actingAs($this->manager);
        $this->post('/quotations/'.$q->id.'/approve', ['lock_version' => 1])->assertSessionHasNoErrors();
        $this->post('/quotations/'.$q->id.'/convert', ['lock_version' => 2])->assertSessionHasNoErrors();
        $this->actingAs($this->operator);
        $job = Job::firstOrFail();
        $this->post('/jobs/'.$job->id.'/open', ['lock_version' => $job->lock_version])->assertSessionHasNoErrors();

        return $job->fresh();
    }

    public function test_customer_service_can_confirm_do_done_and_persists_timestamp_and_actor(): void
    {
        $job = $this->openJob();
        $this->actingAs(User::where('email', 'customer-service@jobfinance.test')->firstOrFail());

        $this->post('/jobs/'.$job->id.'/confirm-do')
            ->assertSessionHasNoErrors()->assertRedirect(route('jobs.show', $job));

        $fresh = $job->fresh();
        $this->assertNotNull($fresh->do_confirmed_at);
        $this->assertSame(User::where('email', 'customer-service@jobfinance.test')->value('id'), $fresh->do_confirmed_by);
        $this->assertDatabaseHas('activity_logs', ['action' => 'job.do_confirmed', 'record_id' => $job->id]);
        $this->get(route('jobs.show', $job))->assertOk()->assertSee('DO Selesai');
    }

    public function test_sales_manager_cannot_confirm_do_without_permission(): void
    {
        $job = $this->openJob();
        $this->actingAs($this->manager);
        $this->post('/jobs/'.$job->id.'/confirm-do')->assertForbidden();
        $this->assertNull($job->fresh()->do_confirmed_at);
    }

    public function test_draft_job_cannot_be_confirmed_do_before_opened(): void
    {
        $draft = Job::factory()->create(['status' => 'draft', 'quotation_snapshot' => ['customer' => ['name' => 'X']], 'job_date' => today()]);
        $this->actingAs($this->operator);
        $this->post('/jobs/'.$draft->id.'/confirm-do')->assertSessionHasErrors('do');
        $this->assertNull($draft->fresh()->do_confirmed_at);
    }

    public function test_do_not_confirmed_twice(): void
    {
        $job = $this->openJob();
        $this->actingAs($this->operator);
        $this->post('/jobs/'.$job->id.'/confirm-do')->assertSessionHasNoErrors();
        $this->post('/jobs/'.$job->id.'/confirm-do')->assertSessionHasErrors('do');
    }

    public function test_finance_cannot_confirm_do(): void
    {
        $job = $this->openJob();
        $this->actingAs($this->finance);
        $this->post('/jobs/'.$job->id.'/confirm-do')->assertForbidden();
    }
}