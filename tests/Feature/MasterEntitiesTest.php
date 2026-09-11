<?php

namespace Tests\Feature;

use App\Models\ChargeType;
use App\Models\ContainerUnit;
use App\Models\Port;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterEntitiesTest extends TestCase
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

    public function test_ports_crud_and_toggle(): void
    {
        $this->login('operational');

        // 1. Index page
        $this->get('/ports')->assertOk()->assertSee('Data Port');

        // 2. Create
        $this->post('/ports', [
            'name' => 'Bitung, Sulawesi Utara',
            'code' => 'IDBIT',
        ])->assertSessionHasNoErrors();

        $port = Port::where('code', 'IDBIT')->firstOrFail();
        $this->assertSame('BITUNG, SULAWESI UTARA', $port->name);
        $this->assertTrue($port->is_active);

        // 3. Edit page & Update
        $this->get('/ports/'.$port->id.'/edit')->assertOk()->assertSee('Edit Port');
        $this->put('/ports/'.$port->id, [
            'name' => 'Bitung International Port',
            'code' => 'IDBIP',
        ])->assertSessionHasNoErrors()->assertRedirect('/ports');

        $this->assertSame('BITUNG INTERNATIONAL PORT', $port->fresh()->name);

        // 4. Toggle
        $this->patch('/ports/'.$port->id.'/toggle')->assertSessionHasNoErrors();
        $this->assertFalse($port->fresh()->is_active);

        // 5. Delete
        $this->delete('/ports/'.$port->id)->assertSessionHasNoErrors();
        $this->assertSoftDeleted($port);
    }

    public function test_charge_types_crud_and_toggle(): void
    {
        $this->login('operational');

        // 1. Index page
        $this->get('/charge-types')->assertOk()->assertSee('Jenis Biaya');

        // 2. Create
        $this->post('/charge-types', [
            'name' => 'Ocean Freight Special',
        ])->assertSessionHasNoErrors();

        $charge = ChargeType::where('name', 'OCEAN FREIGHT SPECIAL')->firstOrFail();
        $this->assertTrue($charge->is_active);

        // 3. Edit page & Update
        $this->get('/charge-types/'.$charge->id.'/edit')->assertOk()->assertSee('Edit Jenis Biaya');
        $this->put('/charge-types/'.$charge->id, [
            'name' => 'Ocean Freight Premium',
        ])->assertSessionHasNoErrors()->assertRedirect('/charge-types');

        $this->assertSame('OCEAN FREIGHT PREMIUM', $charge->fresh()->name);

        // 4. Toggle
        $this->patch('/charge-types/'.$charge->id.'/toggle')->assertSessionHasNoErrors();
        $this->assertFalse($charge->fresh()->is_active);

        // 5. Delete
        $this->delete('/charge-types/'.$charge->id)->assertSessionHasNoErrors();
        $this->assertSoftDeleted($charge);
    }

    public function test_container_units_crud_and_toggle(): void
    {
        $this->login('operational');

        // 1. Index page
        $this->get('/container-units')->assertOk()->assertSee('Satuan (Unit)');

        // 2. Create
        $this->post('/container-units', [
            'name' => '45HC',
        ])->assertSessionHasNoErrors();

        $unit = ContainerUnit::where('name', '45HC')->firstOrFail();
        $this->assertTrue($unit->is_active);

        // 3. Edit page & Update
        $this->get('/container-units/'.$unit->id.'/edit')->assertOk()->assertSee('Edit Satuan');
        $this->put('/container-units/'.$unit->id, [
            'name' => '45 High Cube',
        ])->assertSessionHasNoErrors()->assertRedirect('/container-units');

        $this->assertSame('45 High Cube', $unit->fresh()->name);

        // 4. Toggle
        $this->patch('/container-units/'.$unit->id.'/toggle')->assertSessionHasNoErrors();
        $this->assertFalse($unit->fresh()->is_active);

        // 5. Delete
        $this->delete('/container-units/'.$unit->id)->assertSessionHasNoErrors();
        $this->assertSoftDeleted($unit);
    }

    public function test_non_privileged_roles_cannot_manage_master_entities(): void
    {
        $this->login('management');
        $this->get('/ports')->assertForbidden();
        $this->get('/charge-types')->assertForbidden();
        $this->get('/container-units')->assertForbidden();
    }
}
