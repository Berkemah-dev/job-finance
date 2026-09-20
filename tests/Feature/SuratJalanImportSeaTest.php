<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Job;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratJalanImportSeaTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actor = User::where('email', 'sales-manager@jobfinance.test')->firstOrFail();
        $this->customer = Customer::factory()->create([
            'created_by' => $this->actor->id,
            'updated_by' => $this->actor->id,
        ]);
        $this->actingAs($this->actor);
    }

    private function createTruckingVendor(): Vendor
    {
        return Vendor::create([
            'code' => 'VND-TRK-001',
            'name' => 'PT TRUCKING NUSANTARA',
            'type' => 'trucking',
            'is_active' => true,
            'created_by' => $this->actor->id,
            'updated_by' => $this->actor->id,
        ]);
    }

    private function approvedQuotation(): Quotation
    {
        $sales = User::where('email', 'sales@jobfinance.test')->firstOrFail();
        $salesManager = User::where('email', 'sales-manager@jobfinance.test')->firstOrFail();

        $this->actingAs($sales)->post('/quotations', [
            'customer_id' => $this->customer->id,
            'subject' => 'Pengiriman Impor Laut',
            'quotation_date' => '2026-09-08',
            'valid_until' => '2026-10-08',
            'items' => [
                ['description' => 'Freight Laut', 'type' => 'provision', 'unit' => 'Layanan', 'quantity' => '1', 'unit_cost' => '3000000', 'unit_price' => '4500000'],
            ],
        ])->assertSessionHasNoErrors();

        $quotation = Quotation::firstOrFail();
        $this->actingAs($sales)->post('/quotations/'.$quotation->id.'/submit', ['lock_version' => 0])->assertSessionHasNoErrors();
        $this->actingAs($salesManager)->post('/quotations/'.$quotation->id.'/approve', ['lock_version' => 1])->assertSessionHasNoErrors();
        $this->actingAs($this->actor);

        return $quotation->fresh();
    }

    public function test_vendor_truck_crud_and_api(): void
    {
        $vendor = $this->createTruckingVendor();

        // 1. Create truck
        $response = $this->post(route('vendors.trucks.store', $vendor), [
            'driver_name' => 'Slamet Riyadi',
            'driver_phone' => '081234567890',
            'plate_number' => 'B 9876 XYZ',
            'vehicle_type' => 'Trailer 40ft',
            'notes' => 'Armada utama',
        ]);
        $response->assertRedirect(route('vendors.show', $vendor));

        $this->assertDatabaseHas('vendor_trucks', [
            'vendor_id' => $vendor->id,
            'driver_name' => 'Slamet Riyadi',
            'plate_number' => 'B 9876 XYZ',
        ]);

        $truck = $vendor->trucks()->firstOrFail();

        // 2. API list
        $apiResponse = $this->get(route('api.vendors.trucks', $vendor));
        $apiResponse->assertOk();
        $apiResponse->assertJsonFragment([
            'id' => $truck->id,
            'driver_name' => 'Slamet Riyadi',
            'plate_number' => 'B 9876 XYZ',
        ]);

        // 3. Update truck
        $updateResponse = $this->put(route('vendors.trucks.update', [$vendor, $truck]), [
            'driver_name' => 'Slamet Riyadi Updated',
            'driver_phone' => '081234567899',
            'plate_number' => 'B 9876 ABC',
            'vehicle_type' => 'Trailer 20ft',
            'is_active' => true,
        ]);
        $updateResponse->assertRedirect(route('vendors.show', $vendor));
        $this->assertDatabaseHas('vendor_trucks', [
            'id' => $truck->id,
            'driver_name' => 'Slamet Riyadi Updated',
            'plate_number' => 'B 9876 ABC',
        ]);

        // 4. Delete truck
        $delResponse = $this->delete(route('vendors.trucks.destroy', [$vendor, $truck]));
        $delResponse->assertRedirect(route('vendors.show', $vendor));
        $this->assertDatabaseMissing('vendor_trucks', ['id' => $truck->id]);
    }

    public function test_customer_address_crud_and_api(): void
    {
        // 1. Create address
        $response = $this->post(route('customer-addresses.store'), [
            'customer_id' => $this->customer->id,
            'location_name' => 'Gudang Cikarang Barat Blok A',
            'address' => 'Kawasan GIIC Blok AB No 12, Cikarang Pusat, Bekasi',
            'pic_name' => 'Pak Joko',
            'pic_phone' => '081987654321',
            'is_default' => true,
        ]);
        $response->assertRedirect(route('customer-addresses.index', ['customer_id' => $this->customer->id]));

        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $this->customer->id,
            'location_name' => 'Gudang Cikarang Barat Blok A',
            'is_default' => true,
        ]);

        $addr = $this->customer->addresses()->firstOrFail();

        // 2. API list
        $apiResponse = $this->get(route('api.customers.addresses', $this->customer));
        $apiResponse->assertOk();
        $apiResponse->assertJsonFragment([
            'id' => $addr->id,
            'location_name' => 'Gudang Cikarang Barat Blok A',
        ]);

        // 3. Update address
        $this->put(route('customer-addresses.update', $addr), [
            'location_name' => 'Gudang Cikarang Barat Blok B',
            'address' => 'Kawasan GIIC Blok CD No 99, Cikarang',
            'pic_name' => 'Pak Joko Santoso',
            'pic_phone' => '081987654322',
            'is_default' => true,
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customer_addresses', [
            'id' => $addr->id,
            'location_name' => 'Gudang Cikarang Barat Blok B',
        ]);

        // 4. Delete address
        $this->delete(route('customer-addresses.destroy', $addr))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('customer_addresses', ['id' => $addr->id]);
    }

    public function test_surat_jalan_import_sea_can_be_saved_and_rendered_in_pdf(): void
    {
        $quotation = $this->approvedQuotation();
        $this->actingAs(User::where('email', 'sales-manager@jobfinance.test')->firstOrFail());
        $this->post('/quotations/'.$quotation->id.'/convert', ['lock_version' => 2])->assertSessionHasNoErrors();

        $this->actingAs($this->actor);
        $job = Job::firstOrFail();
        $job->update(['service_type' => 'imp_sea']);

        $vendor = $this->createTruckingVendor();
        $truck = $vendor->trucks()->create([
            'driver_name' => 'Agus Budiman',
            'driver_phone' => '081399887766',
            'plate_number' => 'B 9234 KAA',
            'vehicle_type' => 'Trailer 40ft',
            'is_active' => true,
        ]);

        $address = CustomerAddress::create([
            'customer_id' => $job->customer_id,
            'location_name' => 'Pabrik Karawang Timur',
            'address' => 'Jl. Kawasan Industri Surya Cipta Kav 8, Karawang',
            'pic_name' => 'Pak Hendra',
            'pic_phone' => '081211223344',
            'is_default' => true,
            'is_active' => true,
        ]);

        $updateResponse = $this->put(route('jobs.update', $job), [
            'lock_version' => $job->lock_version,
            'subject' => $job->subject,
            'job_date' => $job->job_date->format('Y-m-d'),
            'redirect_tab' => 'delivery',
            'container_number' => 'TCLU 582910-1 / 40HC',
            'vendor_trucking_id' => $vendor->id,
            'vendor_truck_id' => $truck->id,
            'customer_address_id' => $address->id,
            'truck_plate_number' => 'B 9234 KAA',
            'driver_name' => 'Agus Budiman',
            'driver_phone' => '081399887766',
            'vehicle_type' => 'Trailer 40ft',
            'delivery_address' => 'Jl. Kawasan Industri Surya Cipta Kav 8, Karawang',
        ]);

        $updateResponse->assertRedirect(route('jobs.show', $job).'#tab-delivery');

        $job->refresh();
        $this->assertSame('TCLU 582910-1 / 40HC', $job->container_number);
        $this->assertSame($vendor->id, $job->vendor_trucking_id);
        $this->assertSame($truck->id, $job->vendor_truck_id);
        $this->assertSame($address->id, $job->customer_address_id);
        $this->assertSame('B 9234 KAA', $job->truck_plate_number);
        $this->assertSame('Agus Budiman', $job->driver_name);
        $this->assertSame('081399887766', $job->driver_phone);
        $this->assertSame('Trailer 40ft', $job->vehicle_type);
        $this->assertSame('Jl. Kawasan Industri Surya Cipta Kav 8, Karawang', $job->delivery_address);

        // Verify HTML/Blade rendering contains trucking data
        $viewContent = view('documents.pdf.surat-jalan', ['job' => $job, 'quotation' => $job->quotation])->render();
        $this->assertStringContainsString('B 9234 KAA', $viewContent);
        $this->assertStringContainsString('Agus Budiman', $viewContent);
        $this->assertStringContainsString('081399887766', $viewContent);
        $this->assertStringContainsString('TCLU 582910-1 / 40HC', $viewContent);
        $this->assertStringContainsString('Jl. Kawasan Industri Surya Cipta Kav 8, Karawang', $viewContent);

        // PDF Generation
        $pdfResponse = $this->get(route('jobs.surat-jalan.pdf', $job));
        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('content-type', 'application/pdf');
    }
}
