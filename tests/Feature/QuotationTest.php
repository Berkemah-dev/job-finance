<?php

namespace Tests\Feature;

use App\Enums\QuotationStatus;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\User;
use App\Services\QuotationService;
use App\Support\Money;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actor = User::where('email', 'operational@jobfinance.test')->firstOrFail();
        $this->customer = Customer::factory()->create(['created_by' => $this->actor->id, 'updated_by' => $this->actor->id]);
        $this->actingAs($this->actor);
    }

    private function data(): array
    {
        return ['customer_id' => $this->customer->id, 'subject' => 'Pengiriman Jakarta Surabaya', 'quotation_date' => '2026-09-08', 'valid_until' => '2026-10-08',
            'items' => [
                ['description' => 'Dokumen', 'type' => 'temporary', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '5000000', 'unit_price' => '5000000'],
                ['description' => 'Pengiriman', 'type' => 'provision', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '3000000', 'unit_price' => '4500000'],
            ]];
    }

    private function draft(): Quotation
    {
        $this->post('/quotations', $this->data())->assertSessionHasNoErrors()->assertRedirect();

        return Quotation::latest('id')->firstOrFail();
    }

    private function approved(): Quotation
    {
        $q = $this->draft();
        $this->post('/quotations/'.$q->id.'/submit', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->post('/quotations/'.$q->id.'/approve', ['lock_version' => 1])->assertSessionHasNoErrors();

        return $q->fresh();
    }

    public function test_brief_totals_and_all_quotation_pages(): void
    {
        $this->get('/quotations/create')->assertOk();
        $q = $this->draft();
        $this->assertSame('5000000.00', $q->total_temporary);
        $this->assertSame('3000000.00', $q->total_provision_cost);
        $this->assertSame('4500000.00', $q->total_provision_sell);
        $this->assertSame('1500000.00', $q->profit);
        $this->assertSame('9500000.00', $q->subtotal);
        $this->assertSame('33.33', $q->margin);
        $this->assertSame(QuotationStatus::Draft, $q->status);
        $this->get('/quotations?search='.$q->number)->assertOk()->assertSee($q->number);
        $this->get('/quotations/'.$q->id)->assertOk()->assertSee('9.500.000,00')->assertSee('Ajukan quotation');
        $this->get('/quotations/'.$q->id.'/edit')->assertOk()->assertSee('Pengiriman');
        $this->assertDatabaseHas('activity_logs', ['action' => 'quotation.created', 'user_id' => $this->actor->id]);
    }

    public function test_draft_update_replaces_items_recalculates_and_rejects_stale_data(): void
    {
        $q = $this->draft();
        $data = $this->data();
        $data['lock_version'] = 0;
        $data['items'] = [$data['items'][1]];
        $data['items'][0]['quantity'] = '2';
        $this->put('/quotations/'.$q->id, $data)->assertSessionHasNoErrors();
        $this->assertSame(1, $q->items()->count());
        $this->assertSame('9000000.00', $q->fresh()->subtotal);
        $this->put('/quotations/'.$q->id, $data)->assertSessionHasErrors('lock_version');
        $this->post('/quotations/'.$q->id.'/submit', ['lock_version' => 0])->assertSessionHasErrors('lock_version');
        $this->assertSame(QuotationStatus::Draft, $q->fresh()->status);
    }

    public function test_full_workflow_converts_once_and_preserves_snapshot(): void
    {
        $q = $this->approved();
        $this->get('/quotations/'.$q->id)->assertSee('Konversi ke Job Order')->assertDontSee('Edit draft');
        $this->post('/quotations/'.$q->id.'/convert', ['lock_version' => 2])->assertSessionHasNoErrors()->assertRedirect();
        $job = Job::firstOrFail();
        $this->assertSame('draft', $job->status);
        $this->assertSame($q->id, $job->quotation_id);
        $this->assertSame('9500000.00', $job->quotation_snapshot['totals']['subtotal']);
        $this->assertCount(2, $job->quotation_snapshot['items']);
        $this->assertSame(QuotationStatus::Converted, $q->fresh()->status);
        $this->post('/quotations/'.$q->id.'/convert', ['lock_version' => 2])->assertForbidden();
        $this->assertDatabaseCount('jobs', 1);
        $original = $job->quotation_snapshot['customer']['name'];
        $this->customer->update(['name' => 'Changed master']);
        $this->assertSame($original, $job->fresh()->quotation_snapshot['customer']['name']);
        $this->customer->delete();
        $this->get('/quotations/'.$q->id)->assertOk()->assertSee($original)->assertSee('Diarsipkan');
        $this->get('/jobs/'.$job->id)->assertOk()->assertSee('9.500.000,00');
        $this->get('/jobs')->assertOk()->assertSee($job->number);
    }

    public function test_invalid_transitions_and_locked_content_are_rejected(): void
    {
        $q = $this->draft();
        foreach (['approve', 'reject', 'convert'] as $action) {
            $this->post('/quotations/'.$q->id.'/'.$action, ['lock_version' => 0, 'reason' => 'No'])->assertForbidden();
        }
        $this->post('/quotations/'.$q->id.'/submit', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->put('/quotations/'.$q->id, $this->data() + ['lock_version' => 1])->assertForbidden();
        $this->get('/quotations/'.$q->id.'/edit')->assertForbidden();
        $this->post('/quotations/'.$q->id.'/submit', ['lock_version' => 1])->assertForbidden();
        $this->post('/quotations/'.$q->id.'/reject', ['lock_version' => 1])->assertSessionHasErrors('reason');
        $this->post('/quotations/'.$q->id.'/reject', ['lock_version' => 1, 'reason' => 'Harga perlu disesuaikan'])->assertSessionHasNoErrors();
        $this->assertSame(QuotationStatus::Rejected, $q->fresh()->status);
        $this->get('/quotations/'.$q->id)->assertSee('Harga perlu disesuaikan');
        $this->post('/quotations/'.$q->id.'/approve', ['lock_version' => 2])->assertForbidden();
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_role_permissions_protect_read_write_and_approval(): void
    {
        $q = $this->draft();
        foreach (['finance', 'management'] as $role) {
            $this->actingAs(User::where('email', $role.'@jobfinance.test')->firstOrFail());
            $this->get('/quotations')->assertForbidden();
            $this->get('/quotations/'.$q->id)->assertForbidden();
            $this->post('/quotations', $this->data())->assertForbidden();
            $this->put('/quotations/'.$q->id, $this->data() + ['lock_version' => 0])->assertForbidden();
            foreach (['submit', 'approve', 'reject', 'convert'] as $action) {
                $this->post('/quotations/'.$q->id.'/'.$action, ['lock_version' => 0])->assertForbidden();
            }
            $this->get('/jobs')->assertStatus($role === 'finance' ? 200 : 403);
        }
        $this->actingAs($this->actor);
        $this->post('/quotations/'.$q->id.'/submit', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->actor->role->permissions()->detach(Permission::where('name', 'quotations.approve')->value('id'));
        $this->actingAs($this->actor->fresh());
        $this->post('/quotations/'.$q->id.'/approve', ['lock_version' => 1])->assertForbidden();
        $this->assertSame(QuotationStatus::Submitted, $q->fresh()->status);
    }

    public function test_validation_rejects_empty_invalid_and_tampered_items(): void
    {
        $data = $this->data();
        $data['items'] = [];
        $this->post('/quotations', $data)->assertSessionHasErrors('items');
        $data = $this->data();
        $data['valid_until'] = '2026-09-01';
        $this->post('/quotations', $data)->assertSessionHasErrors('valid_until');
        foreach (['-1', '0.001', '1e6', '1000000000'] as $amount) {
            $data = $this->data();
            $data['items'][0]['unit_cost'] = $amount;
            $this->post('/quotations', $data)->assertSessionHasErrors('items.0.unit_cost');
        }
        $data = $this->data();
        $data['items'][0]['quantity'] = '0';
        $this->post('/quotations', $data)->assertSessionHasErrors('items.0.quantity');
        $data = $this->data();
        $data['items'][0]['type'] = 'invalid';
        $this->post('/quotations', $data)->assertSessionHasErrors('items.0.type');
        $data = $this->data();
        $data['items'][0]['unit_price'] = '6000000';
        $this->post('/quotations', $data)->assertSessionHasErrors('items.0.unit_price');
        $this->assertDatabaseCount('quotations', 0);
        $data = $this->data();
        $data['subtotal'] = '1';
        $data['status'] = 'approved';
        $this->post('/quotations', $data)->assertSessionHasNoErrors();
        $q = Quotation::firstOrFail();
        $this->assertSame('9500000.00', $q->subtotal);
        $this->assertSame(QuotationStatus::Draft, $q->status);
    }

    public function test_archived_customer_cannot_be_used_at_create_submit_or_convert(): void
    {
        $draft = $this->draft();
        $approved = $this->approved();
        $this->customer->delete();
        $this->post('/quotations', $this->data())->assertSessionHasErrors('customer_id');
        $this->post('/quotations/'.$draft->id.'/submit', ['lock_version' => 0])->assertSessionHasErrors('customer_id');
        $this->post('/quotations/'.$approved->id.'/convert', ['lock_version' => 2])->assertSessionHasErrors('customer_id');
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_decimal_rounding_loss_zero_sales_and_large_exact_formatting(): void
    {
        $data = $this->data();
        $data['items'] = [['description' => 'Fraction', 'type' => 'provision', 'unit' => 'Unit', 'quantity' => '0.15', 'unit_cost' => '0.10', 'unit_price' => '0.30']];
        $this->post('/quotations', $data)->assertSessionHasNoErrors();
        $q = Quotation::firstOrFail();
        $this->assertSame('0.02', $q->total_provision_cost);
        $this->assertSame('0.05', $q->total_provision_sell);
        $this->assertSame('0.03', $q->profit);
        $data['items'][0]['unit_price'] = '0';
        $data['lock_version'] = 0;
        $this->put('/quotations/'.$q->id, $data)->assertSessionHasNoErrors();
        $this->assertSame('-0.02', $q->fresh()->profit);
        $this->assertSame('0.00', $q->fresh()->margin);
        $this->assertSame('9.999.999.999.999.999,99', Money::format('9999999999999999.99'));
    }

    public function test_oversized_totals_rollback_without_consuming_number(): void
    {
        $data = $this->data();
        $data['items'] = array_fill(0, 100, ['description' => 'Large', 'type' => 'provision', 'unit' => 'Unit', 'quantity' => '999999.99', 'unit_cost' => '999999999.99', 'unit_price' => '999999999.99']);
        $this->post('/quotations', $data)->assertSessionHasErrors('items');
        $this->assertDatabaseCount('quotations', 0);
        $this->assertDatabaseCount('document_sequences', 0);
        $q = $this->draft();
        $this->assertStringEndsWith('-00001', $q->number);
    }

    public function test_conversion_rolls_back_job_status_and_sequence_if_audit_fails(): void
    {
        $q = $this->approved();
        ActivityLog::creating(function ($log) {
            if ($log->action === 'quotation.converted') {
                throw new \RuntimeException('Simulated failure');
            }
        });
        try {
            app(QuotationService::class)->convert($q, ['lock_version' => 2], $this->actor);
            $this->fail('Failure should be propagated');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated failure', $e->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame(QuotationStatus::Approved, $q->fresh()->status);
        $this->assertDatabaseMissing('document_sequences', ['type' => 'job']);
        $job = app(QuotationService::class)->convert($q->fresh(), ['lock_version' => 2], $this->actor);
        $this->assertStringEndsWith('-00001', $job->number);
    }

    public function test_numbering_is_unique_and_queue_is_separate_from_business_jobs(): void
    {
        $one = $this->draft();
        $two = $this->draft();
        $this->assertNotSame($one->number, $two->number);
        $this->assertStringEndsWith('-00002', $two->number);
        Queue::connection('database')->pushRaw(json_encode(['displayName' => 'Test', 'job' => 'Test', 'data' => []]));
        $this->assertSame('queue_jobs', config('queue.connections.database.table'));
        $this->assertDatabaseCount('queue_jobs', 1);
        $this->assertDatabaseCount('jobs', 0);
    }
}
