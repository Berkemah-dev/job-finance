<?php

namespace Tests\Feature;

use App\Models\BookingConfirmation;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Job;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingConfirmationFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'admin@jobfinance.test')->firstOrFail());
    }

    public function test_booking_confirmation_shows_consignee_as_to_and_customer_as_shipper_with_docs(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'PT Ekspor Sejahtera',
            'address' => 'Jl. Industri No. 10 Jakarta',
            'tax_number' => '01.234.567.8-901.000',
            'npwp_file' => 'customers/npwp.pdf',
            'nib_file' => 'customers/nib.pdf',
        ]);

        $consignee = CustomerContact::create([
            'customer_id' => $customer->id,
            'type' => 'consignee',
            'name' => 'Global Imports Ltd',
            'address' => '123 Main St, Singapore',
            'phone' => '+65-9123-4567',
        ]);

        $job = Job::factory()->create([
            'customer_id' => $customer->id,
            'service_type' => 'exp_sea',
            'shipper_name' => $customer->name,
            'shipper_address' => $customer->address,
            'consignee_name' => $consignee->name,
            'consignee_address' => $consignee->address,
        ]);

        $bc = BookingConfirmation::create([
            'number' => 'BC-JOB/EXP/26090001',
            'booking_date' => '2026-09-12',
            'job_id' => $job->id,
            'customer_id' => $customer->id,
            'shipper_name' => $customer->name,
            'consignee_name' => $consignee->name,
            'consignee_address' => $consignee->address,
            'contact_person' => 'Mr. John Tan',
            'service_term' => 'CY/CY',
            'status' => 'confirmed',
            'created_by' => auth()->id(),
        ]);

        // Test Show view
        $showRes = $this->get(route('booking-confirmations.show', $bc));
        $showRes->assertOk();
        $showRes->assertSee('Consignee (Penerima)');
        $showRes->assertSee('Global Imports Ltd');
        $showRes->assertSee('PT Ekspor Sejahtera');

        // Test Job Order index displays direct BC shortcut for export
        $jobsRes = $this->get(route('jobs.index'));
        $jobsRes->assertOk();
        $jobsRes->assertSee(route('booking-confirmations.preview', $bc));

        // Test PDF generation
        $pdfRes = $this->get(route('booking-confirmations.pdf', ['bookingConfirmation' => $bc, 'mode' => 'inline']));
        $pdfRes->assertOk();
        $this->assertSame('application/pdf', $pdfRes->headers->get('content-type'));
    }

    public function test_booking_confirmation_can_be_stored_without_customer_id_following_job_order(): void
    {
        $customer = Customer::factory()->create(['name' => 'PT Sumber Makmur']);
        $job = Job::factory()->create([
            'customer_id'    => $customer->id,
            'shipper_name'   => 'Shipper Asli JO',
            'consignee_name' => 'Consignee Asli JO',
            'vessel_voyage'  => 'KM LOGISTIK 01',
        ]);

        $res = $this->post(route('booking-confirmations.store'), [
            'number'        => 'BC-TEST-001',
            'booking_date'  => '2026-09-17',
            'job_id'        => $job->id,
            'shipper_name'  => $job->shipper_name,
            'consignee_name'=> $job->consignee_name,
            'vessel_voyage' => $job->vessel_voyage,
            'service_term'  => 'CY/CY',
            'status'        => 'confirmed',
        ]);

        $res->assertRedirect(route('jobs.show', $job->id).'#tab-booking');
        $this->assertDatabaseHas('booking_confirmations', [
            'number'        => 'BC-TEST-001',
            'customer_id'   => $customer->id,
            'shipper_name'  => 'Shipper Asli JO',
            'consignee_name'=> 'Consignee Asli JO',
            'vessel_voyage' => 'KM LOGISTIK 01',
        ]);
    }

    public function test_shipping_instruction_can_be_stored_without_shipment_term(): void
    {
        $customer = Customer::factory()->create();
        $job = Job::factory()->create(['customer_id' => $customer->id]);

        $res = $this->post(route('shipping-instructions.store'), [
            'number'            => 'SI-TEST-001',
            'si_date'           => '2026-09-17',
            'job_id'            => $job->id,
            'to_carrier'        => 'ONE LINE',
            'shipper_name'      => 'PT Shipper',
            'consignee_name'    => 'PT Consignee',
            'vessel_voyage'     => 'MV WAN HAI',
            'pol'               => 'JAKARTA',
            'pod'               => 'SINGAPORE',
            'cargo_description' => 'General Cargo',
            'status'            => 'submitted',
        ]);

        $res->assertRedirect(route('jobs.show', $job->id).'#tab-si');
        $this->assertDatabaseHas('shipping_instructions', [
            'number'        => 'SI-TEST-001',
            'shipment_term' => 'PREPAID',
            'vessel_voyage' => 'MV WAN HAI',
        ]);
    }

    public function test_shipping_instruction_create_renders_custom_selects_with_job(): void
    {
        $job = Job::factory()->create([
            'vessel_voyage' => 'MV CUSTOM VESSEL 888',
            'pol'           => 'JAKARTA',
            'pod'           => 'SHANGHAI',
        ]);

        $res = $this->get(route('shipping-instructions.create', ['job_id' => $job->id]));
        $res->assertOk();
        $res->assertSee('data-custom-select');
        $res->assertSee('MV CUSTOM VESSEL 888');
    }
}
