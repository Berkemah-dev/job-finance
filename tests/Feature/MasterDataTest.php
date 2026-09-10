<?php

namespace Tests\Feature;

use App\Models\AccountMapping;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function login(string $role = 'admin'): void
    {
        $this->actingAs(User::where('email', $role.'@jobfinance.test')->firstOrFail());
    }

    public function test_customer_crud_search_archive_and_unique_code(): void
    {
        $this->login('operational');
        $this->post('/customers', ['code' => 'cus-001', 'name' => 'PT Pelanggan', 'email' => 'contact@example.test'])->assertSessionHasNoErrors()->assertRedirect('/customers');
        $customer = Customer::firstOrFail();
        $this->get('/customers?search=Pelangg')->assertOk()->assertSee('PT Pelanggan');
        $this->get('/customers/'.$customer->id.'/edit')->assertOk();
        $this->put('/customers/'.$customer->id, ['code' => 'CUS-001', 'name' => 'Nama Baru', 'lock_version' => 0])->assertSessionHasNoErrors();
        $this->put('/customers/'.$customer->id, ['code' => 'CUS-001', 'name' => 'Stale', 'lock_version' => 0])->assertSessionHasErrors('lock_version');
        $this->assertSame('Nama Baru', $customer->fresh()->name);
        $this->delete('/customers/'.$customer->id, ['lock_version' => 1])->assertSessionHasNoErrors();
        $this->assertSoftDeleted($customer);
        $this->get('/customers')->assertDontSee('Nama Baru');
        $this->get('/customers?archived=1')->assertSee('Nama Baru');
        $this->post('/customers', ['code' => 'CUS-001', 'name' => 'Duplicate'])->assertSessionHasErrors('code');
        $this->assertDatabaseHas('activity_logs', ['action' => 'customer.archived']);
    }

    public function test_customer_validation_and_direct_access_authorization(): void
    {
        $customer = Customer::factory()->create();
        foreach (['finance', 'management'] as $role) {
            $this->login($role);
            $this->get('/customers')->assertForbidden();
            $this->post('/customers', ['code' => 'NEW', 'name' => 'Blocked'])->assertForbidden();
            $this->put('/customers/'.$customer->id, ['code' => 'BAD', 'name' => 'Blocked', 'lock_version' => 0])->assertForbidden();
            $this->delete('/customers/'.$customer->id, ['lock_version' => 0])->assertForbidden();
        }
        $this->login('operational');
        $this->post('/customers', ['code' => 'invalid code', 'name' => '', 'email' => 'wrong'])->assertSessionHasErrors(['code', 'name', 'email']);
    }

    public function test_coa_mapping_type_and_archive_protection(): void
    {
        $this->login();
        $this->get('/accounts')->assertOk()->assertSee('Piutang Customer');
        $this->get('/accounts/mappings')->assertOk();
        $cash = ChartOfAccount::where('code', '1101')->firstOrFail();
        $this->put('/accounts/'.$cash->id, ['code' => '1101', 'name' => 'Kas', 'type' => 'revenue', 'lock_version' => 0])->assertSessionHasErrors('type');
        $this->delete('/accounts/'.$cash->id, ['lock_version' => 0])->assertSessionHasErrors('account');
        $this->post('/accounts', ['code' => '1199', 'name' => 'Kas Cabang', 'type' => 'asset'])->assertSessionHasNoErrors();
        $replacement = ChartOfAccount::where('code', '1199')->firstOrFail();
        $mappings = AccountMapping::pluck('chart_of_account_id', 'key')->all();
        $bad = $mappings;
        $bad['cash'] = ChartOfAccount::where('type', 'revenue')->value('id');
        $this->put('/accounts/mappings', ['mappings' => $bad])->assertSessionHasErrors('mappings.cash');
        $this->assertSame($cash->id, AccountMapping::where('key', 'cash')->value('chart_of_account_id'));
        $mappings['cash'] = $replacement->id;
        $this->put('/accounts/mappings', ['mappings' => $mappings])->assertSessionHasNoErrors();
        $this->delete('/accounts/'.$cash->id, ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->assertSoftDeleted($cash);
        $this->seed(ChartOfAccountSeeder::class);
        $this->assertSame($replacement->id, AccountMapping::where('key', 'cash')->value('chart_of_account_id'));
        $this->assertSoftDeleted($cash);
    }

    public function test_coa_rejects_non_admin_writes_and_invalid_types(): void
    {
        foreach (['operational', 'finance', 'management'] as $role) {
            $this->login($role);
            $this->get('/accounts')->assertForbidden();
            $this->post('/accounts', ['code' => '99', 'name' => 'Wrong', 'type' => 'asset'])->assertForbidden();
            $this->put('/accounts/mappings', [])->assertForbidden();
        }
        $this->login();
        $this->post('/accounts', ['code' => '11', 'name' => 'Invalid', 'type' => 'whatever'])->assertSessionHasErrors('type');
        $this->put('/accounts/mappings', ['mappings' => []])->assertSessionHasErrors();
    }
}
