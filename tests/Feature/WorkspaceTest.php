<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guests_must_login_and_public_registration_is_unavailable(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk ke workspace');
        foreach (['/dashboard', '/users', '/activity'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->get('/register')->assertNotFound();
    }

    public function test_login_and_logout_record_activity_and_protect_workspace(): void
    {
        $user = User::where('email', 'admin@jobfinance.test')->firstOrFail();
        $this->post('/login', ['email' => $user->email, 'password' => 'JobFinance!2026', 'remember' => '1'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'action' => 'auth.login']);
        $this->get('/dashboard')->assertOk()->assertSee('Halo, Super Admin!');
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'action' => 'auth.logout']);
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_invalid_credentials_are_throttled(): void
    {
        $this->freezeTime();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'admin@jobfinance.test', 'password' => 'incorrect'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => 'admin@jobfinance.test', 'password' => 'JobFinance!2026'])
            ->assertSessionHasErrors(['email' => 'Terlalu banyak percobaan. Coba lagi dalam 60 detik.']);
        $this->assertGuest();
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_login_validates_required_fields(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_only_admin_can_view_users_and_activity_even_by_direct_url(): void
    {
        foreach (['operational', 'finance', 'management'] as $role) {
            $user = User::where('email', $role.'@jobfinance.test')->firstOrFail();
            $this->actingAs($user)->get('/dashboard')->assertOk()->assertDontSee('Pengguna &amp; Akses', false);
            $this->get('/users')->assertForbidden();
            $this->assertFalse(Gate::forUser($user)->allows('viewAny', User::class));
        }
        foreach (['operational', 'management'] as $role) {
            $this->actingAs(User::where('email', $role.'@jobfinance.test')->firstOrFail())->get('/activity')->assertForbidden();
        }
        $this->actingAs(User::where('email', 'finance@jobfinance.test')->firstOrFail())->get('/activity')->assertOk();
        $financeManager = User::where('email', 'finance-manager@jobfinance.test')->firstOrFail();
        $this->actingAs($financeManager)->get('/users')->assertOk()->assertSee('Pengguna terdaftar');
        $this->assertTrue(Gate::forUser($financeManager)->allows('viewAny', User::class));
        $this->get('/users/create')->assertForbidden();
        $admin = User::where('email', 'admin@jobfinance.test')->firstOrFail();
        $this->actingAs($admin)->get('/users')->assertOk()->assertSee('Pengguna terdaftar');
        $this->get('/activity')->assertOk();
    }

    public function test_role_permissions_and_navigation_match_business_responsibilities(): void
    {
        foreach (config('jobfinance.roles') as $name => $settings) {
            $user = User::where('role_id', Role::where('name', $name)->value('id'))->firstOrFail();
            foreach (config('jobfinance.permissions') as $permission) {
                $expected = $settings['permissions'] === ['*'] || in_array($permission, $settings['permissions'], true);
                $this->assertSame($expected, Gate::forUser($user)->allows($permission), $name.' '.$permission);
            }
        }
        $this->actingAs(User::where('email', 'operational@jobfinance.test')->firstOrFail())
            ->get('/dashboard')->assertSee('Job Order')->assertDontSee('Piutang Customer')->assertDontSee('Laporan Keuangan');
        $this->actingAs(User::where('email', 'management@jobfinance.test')->firstOrFail())
            ->get('/dashboard')->assertDontSee('Laporan Keuangan')->assertDontSee('Closing Job');
    }

    public function test_user_without_role_is_denied_and_role_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->create();
        $user->fill(['role_id' => Role::where('name', 'super-admin')->value('id')]);
        $this->assertNull($user->role_id);
        $this->actingAs($user)->get('/dashboard')->assertForbidden();
    }

    public function test_seeding_is_repeatable_without_resetting_user_password(): void
    {
        $user = User::where('email', 'admin@jobfinance.test')->firstOrFail();
        $user->update(['password' => 'ChangedPassword!']);
        $hash = $user->password;
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('users', 8);
        $this->assertDatabaseCount('roles', 8);
        $this->assertSame($hash, $user->fresh()->password);
    }
}
