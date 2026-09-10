<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorTest extends TestCase
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

    public function test_vendor_with_multi_category_country_notes_and_show_page(): void
    {
        $this->login('admin');
        $this->post('/vendors', [
            'code' => 'VND-001', 'name' => 'PT Mitra Laut', 'type' => 'shipping_line',
            'country' => 'Singapura', 'notes' => 'Agent utama.',
            'categories' => ['shipping_line', 'national_agent'],
            'email' => 'laut@mitra.test',
        ])->assertSessionHasNoErrors()->assertRedirect('/vendors/1');

        $vendor = Vendor::firstOrFail();
        $this->assertEqualsCanonicalizing(['shipping_line', 'national_agent'], $vendor->categories->pluck('category')->all());
        $this->get('/vendors/'.$vendor->id)->assertOk()->assertSee('Singapura')->assertSee('National Agent')->assertSee('Agent utama.');
        $this->get('/vendors?category=shipping_line')->assertOk()->assertSee('PT Mitra Laut');
        $this->get('/vendors?search=Singapura')->assertOk()->assertSee('PT Mitra Laut');
    }

    public function test_vendor_toggle_active_and_archive_restore(): void
    {
        $this->login('admin');
        $vendor = Vendor::forceCreate(['code' => 'VND-002', 'name' => 'PT Angkut Cepat', 'type' => 'trucking', 'is_active' => true, 'created_by' => 1, 'updated_by' => 1]);
        $this->post('/vendors/'.$vendor->id.'/toggle', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->assertFalse($vendor->fresh()->is_active);
        $this->post('/vendors/'.$vendor->id.'/toggle', ['lock_version' => 1])->assertSessionHasNoErrors();
        $this->assertTrue($vendor->fresh()->is_active);
        $this->delete('/vendors/'.$vendor->id, ['lock_version' => 2])->assertSessionHasNoErrors();
        $this->assertSoftDeleted($vendor);
        $this->post('/vendors/'.$vendor->id.'/restore', ['lock_version' => 3])->assertSessionHasNoErrors();
        $this->assertNotSoftDeleted($vendor);
        $this->assertDatabaseHas('activity_logs', ['action' => 'vendor.deactivated']);
    }

    public function test_vendor_rejects_invalid_category_and_requires_permission(): void
    {
        $this->login('finance');
        $this->post('/vendors', ['code' => 'X1', 'name' => 'X'])->assertForbidden();
        $this->login('admin');
        $this->post('/vendors', ['code' => 'VND-003', 'name' => 'PT Salah', 'type' => 'unknown'])->assertSessionHasErrors('type');
        $this->post('/vendors', ['code' => 'VND-004', 'name' => 'PT Salah Kategori', 'type' => 'trucking', 'categories' => ['bogus']])->assertSessionHasErrors('categories.*');
    }
}
