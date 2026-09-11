<?php

namespace Tests\Feature;

use App\Models\Tps;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TpsTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->operator = User::where('email', 'operational@jobfinance.test')->firstOrFail();
        $this->finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
        $this->actingAs($this->operator);
    }

    public function test_operator_can_create_tps_sea(): void
    {
        $this->post('/tps', ['code' => 'tps-tjprik', 'name' => 'TPS Tanjung Priok', 'city' => 'Jakarta', 'mode' => 'sea', 'is_active' => '1'])
            ->assertSessionHasNoErrors()->assertRedirect(route('tps.index'));

        $tps = Tps::firstOrFail();
        $this->assertSame('TPS-TJPRIK', $tps->code);
        $this->assertSame('sea', $tps->mode);
        $this->assertTrue($tps->is_active);
        $this->get(route('tps.index'))->assertOk()->assertSee('TPS-TJPRIK');
    }

    public function test_operator_can_create_tps_air(): void
    {
        $this->post('/tps', ['code' => 'TPS-CGK', 'name' => 'TPS Bandara Soekarno-Hatta', 'city' => 'Tangerang', 'mode' => 'air'])
            ->assertSessionHasNoErrors()->assertRedirect(route('tps.index'));
        $this->assertSame('air', Tps::firstOrFail()->mode);
    }

    public function test_code_must_be_unique_and_uppercased(): void
    {
        Tps::create(['code' => 'TPS-123', 'name' => 'Lama', 'city' => 'Jakarta', 'mode' => 'sea']);
        $this->post('/tps', ['code' => 'tps-123', 'name' => 'Baru', 'city' => 'Jakarta', 'mode' => 'sea'])->assertSessionHasErrors('code');
        $this->assertSame(1, Tps::count());
    }

    public function test_mode_must_be_valid(): void
    {
        $this->post('/tps', ['code' => 'TPS-X', 'name' => 'X', 'city' => 'Jakarta', 'mode' => 'land'])->assertSessionHasErrors('mode');
        $this->assertSame(0, Tps::count());
    }

    public function test_operator_can_update_and_delete_tps(): void
    {
        $tps = Tps::create(['code' => 'TPS-EDIT', 'name' => 'TPS Lama', 'city' => 'Jakarta', 'mode' => 'sea']);
        $this->put('/tps/'.$tps->id, ['code' => 'TPS-EDIT', 'name' => 'TPS Baru', 'city' => 'Surabaya', 'mode' => 'sea', 'is_active' => ''])
            ->assertSessionHasNoErrors()->assertRedirect(route('tps.index'));
        $fresh = $tps->fresh();
        $this->assertSame('TPS Baru', $fresh->name);
        $this->assertSame('Surabaya', $fresh->city);
        $this->assertFalse($fresh->is_active);

        $this->delete('/tps/'.$tps->id)->assertSessionHasNoErrors()->assertRedirect(route('tps.index'));
        $this->assertDatabaseMissing('tps', ['id' => $tps->id]);
    }

    public function test_filter_by_mode_and_search(): void
    {
        Tps::create(['code' => 'TPS-A', 'name' => 'Alpha', 'city' => 'Jakarta', 'mode' => 'air']);
        Tps::create(['code' => 'TPS-B', 'name' => 'Beta', 'city' => 'Semarang', 'mode' => 'sea']);
        $this->get('/tps?mode=air')->assertOk()->assertSee('TPS-A')->assertDontSee('TPS-B');
        $this->get('/tps?search=Beta')->assertOk()->assertSee('TPS-B')->assertDontSee('TPS-A');
    }

    public function test_finance_cannot_access_tps_master(): void
    {
        $this->actingAs($this->finance);
        $this->get('/tps')->assertForbidden();
        $this->post('/tps', ['code' => 'TPS-NO', 'name' => 'X', 'city' => 'Jakarta', 'mode' => 'sea'])->assertForbidden();
        $this->assertSame(0, Tps::count());
    }
}