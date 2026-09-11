<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'operational@jobfinance.test')->firstOrFail());
    }

    public function test_contact_create_works_with_optimistic_locking_defaults(): void
    {
        $customer = Customer::factory()->create();
        $this->post('/customer-contacts', [
            'customer_id' => $customer->id,
            'type' => 'shipper',
            'name' => 'PT Pengirim Utama',
            'company' => 'PT Pengirim Utama',
            'email' => 'pengirim@example.test',
            'phone' => '021-123456',
            'country' => 'Indonesia',
            'is_active' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect(route('customer-contacts.index'));

        $contact = CustomerContact::firstOrFail();
        $this->assertSame(0, (int) $contact->lock_version);
        $this->assertSame('PT Pengirim Utama', $contact->name);
        $this->assertTrue($contact->is_active);
    }

    public function test_contact_update_increments_lock_version_and_logs(): void
    {
        $customer = Customer::factory()->create();
        $contact = CustomerContact::create(['customer_id' => $customer->id, 'type' => 'consignee', 'name' => 'PT Penerima Lama']);
        $this->assertSame(0, (int) $contact->lock_version);

        $this->put('/customer-contacts/'.$contact->id, [
            'customer_id' => $customer->id,
            'type' => 'consignee',
            'name' => 'PT Penerima Baru',
            'lock_version' => 0,
        ])->assertSessionHasNoErrors()->assertRedirect(route('customer-contacts.show', $contact));

        $fresh = $contact->fresh();
        $this->assertSame('PT Penerima Baru', $fresh->name);
        $this->assertSame(1, (int) $fresh->lock_version);
        $this->assertDatabaseHas('activity_logs', ['action' => 'customer.contact_updated']);
    }

    public function test_contact_update_rejects_stale_lock_version_without_500(): void
    {
        $customer = Customer::factory()->create();
        $contact = CustomerContact::create(['customer_id' => $customer->id, 'type' => 'shipper', 'name' => 'PT Lama']);

        $this->put('/customer-contacts/'.$contact->id, [
            'customer_id' => $customer->id,
            'type' => 'shipper',
            'name' => 'PT Stale',
            'lock_version' => 5,
        ])->assertSessionHasErrors('lock_version');

        $this->assertSame('PT Lama', $contact->fresh()->name);
        $this->assertSame(0, (int) $contact->fresh()->lock_version);
    }

    public function test_contact_destroy_requires_matching_version(): void
    {
        $customer = Customer::factory()->create();
        $contact = CustomerContact::create(['customer_id' => $customer->id, 'type' => 'consignee', 'name' => 'PT Hapus']);
        $this->delete('/customer-contacts/'.$contact->id, ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('customer_contacts', ['id' => $contact->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'customer.contact_deleted']);
    }

    public function test_contact_updates_are_audited_and_visible_on_activity_page(): void
    {
        $customer = Customer::factory()->create();
        $contact = CustomerContact::create(['customer_id' => $customer->id, 'type' => 'shipper', 'name' => 'PT Audit']);
        $this->put('/customer-contacts/'.$contact->id, ['customer_id' => $customer->id, 'type' => 'shipper', 'name' => 'PT Audit Baru', 'lock_version' => 0])->assertSessionHasNoErrors();

        $this->actingAs(User::where('email', 'admin@jobfinance.test')->firstOrFail());
        $this->get('/activity')->assertOk()->assertSee('PT Audit Baru');
        $this->assertSame(1, ActivityLog::where('action', 'customer.contact_updated')->where('record_id', $customer->id)->count());
    }
}