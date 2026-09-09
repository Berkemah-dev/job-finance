<?php

namespace Tests\Feature;

use App\Enums\QuotationStatus;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\TruckingPrice;
use App\Models\User;
use App\Models\WeeklyPricing;
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

    private User $approver;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actor = User::where('email', 'sales@jobfinance.test')->firstOrFail();
        $this->approver = User::where('email', 'sales-manager@jobfinance.test')->firstOrFail();
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
        $this->actingAs($this->approver);
        $this->post('/quotations/'.$q->id.'/approve', ['lock_version' => 1])->assertSessionHasNoErrors();
        $this->actingAs($this->actor);

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
        $this->actingAs($this->approver);
        $this->get('/quotations/'.$q->id)->assertSee('Konversi ke Job Order')->assertDontSee('Edit draft');
        $this->post('/quotations/'.$q->id.'/convert', ['lock_version' => 2])->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($this->actor);
        $job = Job::firstOrFail();
        $this->assertSame('draft', $job->status);
        $this->assertSame($q->id, $job->quotation_id);
        $this->assertSame('9500000.00', $job->quotation_snapshot['totals']['subtotal']);
        $this->assertCount(2, $job->quotation_snapshot['items']);
        $this->assertSame(QuotationStatus::Converted, $q->fresh()->status);
        $this->actingAs($this->approver);
        $this->post('/quotations/'.$q->id.'/convert', ['lock_version' => 2])->assertForbidden();
        $this->actingAs($this->actor);
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
        $this->actingAs($this->approver);
        $this->post('/quotations/'.$q->id.'/reject', ['lock_version' => 1])->assertSessionHasErrors('reason');
        $this->post('/quotations/'.$q->id.'/reject', ['lock_version' => 1, 'reason' => 'Harga perlu disesuaikan'])->assertSessionHasNoErrors();
        $this->assertSame(QuotationStatus::Rejected, $q->fresh()->status);
        $this->get('/quotations/'.$q->id)->assertSee('Harga perlu disesuaikan');
        $this->actingAs($this->actor);
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
        $this->assertSame(QuotationStatus::Submitted, $q->fresh()->status);
        $this->actingAs($this->actor->fresh());
        $this->post('/quotations/'.$q->id.'/approve', ['lock_version' => 1])->assertForbidden();
        $this->assertSame(QuotationStatus::Submitted, $q->fresh()->status);
        $this->approver->role->permissions()->detach(Permission::where('name', 'quotations.approve')->value('id'));
        $this->actingAs($this->approver->fresh());
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
        $this->actingAs($this->approver);
        $this->post('/quotations/'.$approved->id.'/convert', ['lock_version' => 2])->assertSessionHasErrors('customer_id');
        $this->actingAs($this->actor);
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
            app(QuotationService::class)->convert($q, ['lock_version' => 2], $this->approver);
            $this->fail('Failure should be propagated');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated failure', $e->getMessage());
        } finally {
            ActivityLog::flushEventListeners();
        }
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame(QuotationStatus::Approved, $q->fresh()->status);
        $this->assertDatabaseMissing('document_sequences', ['type' => 'job']);
        $job = app(QuotationService::class)->convert($q->fresh(), ['lock_version' => 2], $this->approver);
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

    public function test_trucking_pricing_item_fills_modal_from_master_and_converts_currency(): void
    {
        WeeklyPricing::factory()->create(['currency' => 'USD', 'exchange_rate' => '15850.00', 'effective_date' => '2026-09-01', 'is_active' => true]);
        $trucking = TruckingPrice::factory()->create(['port_origin' => 'Jakarta', 'destination' => 'Surabaya', 'container_type' => '40ft', 'overweight' => false, 'currency' => 'USD', 'price' => '1800.00', 'effective_date' => '2026-09-01', 'is_active' => true]);
        $data = $this->data();
        $data['quotation_date'] = '2026-09-08';
        $data['items'] = [['description' => 'Trucking FCL', 'type' => 'provision', 'unit' => 'Container', 'quantity' => '1', 'unit_cost' => '0', 'unit_price' => '32000000.00', 'pricing_source' => 'trucking', 'pricing_id' => (string) $trucking->id]];
        $this->post('/quotations', $data)->assertSessionHasNoErrors()->assertRedirect();

        $item = Quotation::firstOrFail()->items()->first();
        $this->assertSame('28530000.00', $item->unit_cost);
        $this->assertSame('28530000.00', $item->total_cost);
        $this->assertSame('USD', $item->currency);
        $this->assertSame('15850.00', $item->exchange_rate);
        $this->assertSame('40ft', $item->container_type);
        $this->assertFalse($item->overweight);
        $this->assertSame('trucking', $item->pricing_source);
        $this->assertSame($trucking->id, $item->pricing_id);
        $this->assertSame('Surabaya', $item->pricing_snapshot['destination']);
        $this->assertSame('1800.00', $item->pricing_snapshot['price']);
        $this->assertSame('USD', $item->pricing_snapshot['currency']);
        $this->assertSame('Jakarta', $item->pricing_snapshot['port_origin']);
    }

    public function test_trucking_pricing_recomputes_modal_from_master_when_tampered(): void
    {
        WeeklyPricing::factory()->create(['currency' => 'USD', 'exchange_rate' => '15850.00', 'effective_date' => '2026-09-01', 'is_active' => true]);
        $trucking = TruckingPrice::factory()->create(['currency' => 'USD', 'price' => '1800.00', 'effective_date' => '2026-09-01', 'is_active' => true]);
        $data = $this->data();
        $data['quotation_date'] = '2026-09-08';
        $data['items'] = [['description' => 'Trucking', 'type' => 'provision', 'unit' => 'Container', 'quantity' => '1', 'unit_cost' => '1.00', 'unit_price' => '30000000.00', 'pricing_source' => 'trucking', 'pricing_id' => (string) $trucking->id]];
        $this->post('/quotations', $data)->assertSessionHasNoErrors()->assertRedirect();

        $item = Quotation::firstOrFail()->items()->first();
        $this->assertSame('28530000.00', $item->unit_cost);
    }

    public function test_trucking_pricing_rejects_inactive_or_missing_master(): void
    {
        $trucking = TruckingPrice::factory()->create(['is_active' => false]);
        $data = $this->data();
        $data['items'] = [['description' => 'Trucking', 'type' => 'provision', 'unit' => 'Container', 'quantity' => '1', 'unit_cost' => '0', 'unit_price' => '1', 'pricing_source' => 'trucking', 'pricing_id' => (string) $trucking->id]];
        $this->post('/quotations', $data)->assertSessionHasErrors('items.0.pricing');
        $this->assertDatabaseCount('quotations', 0);
    }

    public function test_foreign_currency_item_requires_active_weekly_rate(): void
    {
        $data = $this->data();
        $data['items'] = [['description' => 'Agen luar negeri', 'type' => 'provision', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '1500', 'unit_price' => '1600', 'currency' => 'USD']];
        $this->post('/quotations', $data)->assertSessionHasErrors('items.*.currency');
        $this->assertDatabaseCount('quotations', 0);
    }

    public function test_manual_foreign_currency_item_uses_active_weekly_rate_and_rejects_invalid_currency(): void
    {
        WeeklyPricing::factory()->create(['currency' => 'USD', 'exchange_rate' => '15850.00', 'effective_date' => '2026-09-01', 'is_active' => true]);
        $data = $this->data();
        $data['quotation_date'] = '2026-09-08';
        $data['items'] = [['description' => 'Agen luar negeri', 'type' => 'provision', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '1500', 'unit_price' => '1600', 'currency' => 'USD']];
        $this->post('/quotations', $data)->assertSessionHasNoErrors()->assertRedirect();
        $item = Quotation::firstOrFail()->items()->first();
        $this->assertSame('1500.00', $item->unit_cost);
        $this->assertSame('USD', $item->currency);
        $this->assertSame('15850.00', $item->exchange_rate);
        $this->assertFalse($item->overweight);
        $data['items'][0]['currency'] = 'EUR';
        $this->post('/quotations', $data)->assertSessionHasErrors('items.0.currency');
    }

    public function test_trucking_suggestion_endpoint_quotes_and_respects_ability(): void
    {
        WeeklyPricing::factory()->create(['currency' => 'USD', 'exchange_rate' => '15850.00', 'effective_date' => '2026-09-01', 'is_active' => true]);
        $trucking = TruckingPrice::factory()->create(['port_origin' => 'Jakarta', 'destination' => 'Surabaya', 'container_type' => '40ft', 'currency' => 'USD', 'price' => '1800.00', 'effective_date' => '2026-09-01', 'is_active' => true]);
        $this->actingAs(User::where('email', 'finance@jobfinance.test')->firstOrFail());
        $this->getJson('/api/pricing/suggest-trucking?port_origin=Jakarta&destination=Surabaya&container_type=40ft')->assertForbidden();
        $this->actingAs($this->actor);

        $json = $this->getJson('/api/pricing/suggest-trucking?port_origin=Jakarta&destination=Surabaya&container_type=40ft&date=2026-09-08')
            ->assertOk()->assertJsonPath('found', true)->assertJsonPath('pricing_id', $trucking->id)->json();
        $this->assertSame('28530000.00', $json['unit_cost']);
        $this->assertSame('USD', $json['currency']);
        $this->assertSame('15850.00', $json['exchange_rate']);
        $this->assertSame('40ft', $json['container_type']);
        $this->assertSame('1800.00', $json['snapshot']['price']);
        $this->assertSame('Surabaya', $json['snapshot']['destination']);

        $this->getJson('/api/pricing/suggest-trucking?port_origin=Jakarta&destination=Medan&container_type=40ft&date=2026-09-08')
            ->assertOk()->assertJsonPath('found', false);
        $this->getJson('/api/pricing/suggest-trucking?port_origin=Jakarta&destination=Surabaya&container_type=99ft&date=2026-09-08')
            ->assertStatus(422);
    }
}
