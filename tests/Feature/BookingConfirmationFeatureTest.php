<?php

namespace Tests\Feature;

use App\Models\BookingConfirmation;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Job;
use App\Models\ShippingInstruction;
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
            'vessel_voyage' => 'KM LOGISTIK 01',
            'quantity'      => '150',
            'package_unit'  => 'Box',
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
            'quantity'      => '150',
            'package_unit'  => 'Box',
        ]);

        // Verify parent job gets synced if empty
        $emptyJob = Job::factory()->create(['vessel_voyage' => null]);
        $this->post(route('booking-confirmations.store'), [
            'number'        => 'BC-TEST-SYNC',
            'booking_date'  => '2026-09-17',
            'job_id'        => $emptyJob->id,
            'vessel_voyage' => 'MV SYNC VESSEL',
            'service_term'  => 'CY/CY',
            'status'        => 'confirmed',
        ]);
        $this->assertEquals('MV SYNC VESSEL', $emptyJob->fresh()->vessel_voyage);
    }

    public function test_shipping_instruction_can_be_stored_without_shipment_term_and_status(): void
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
            'quantity'          => '500',
            'package_unit'      => 'Carton',
            'cargo_description' => 'General Cargo',
            // status omitted to verify it defaults automatically
        ]);

        $res->assertRedirect(route('jobs.show', $job->id).'#tab-si');
        $this->assertDatabaseHas('shipping_instructions', [
            'number'        => 'SI-TEST-001',
            'shipment_term' => 'PREPAID',
            'vessel_voyage' => 'MV WAN HAI',
            'quantity'      => '500',
            'package_unit'  => 'Carton',
            'status'        => 'submitted',
        ]);
    }

    public function test_shipping_instruction_create_renders_custom_selects_and_falls_back_to_bc_vessel(): void
    {
        $job = Job::factory()->create([
            'vessel_voyage' => null,
            'pol'           => 'JAKARTA',
            'pod'           => 'SHANGHAI',
        ]);

        BookingConfirmation::create([
            'number'        => 'BC-FALLBACK-01',
            'booking_date'  => '2026-09-17',
            'job_id'        => $job->id,
            'vessel_voyage' => 'MV FALLBACK FROM BC',
            'carrier_name'  => 'EVERGREEN',
            'quantity'      => '250',
            'package_unit'  => 'Pallet',
            'service_term'  => 'CY/CY',
            'status'        => 'confirmed',
        ]);

        $res = $this->get(route('shipping-instructions.create', ['job_id' => $job->id]));
        $res->assertOk();
        $res->assertSee('data-custom-select');
        $res->assertSee('MV FALLBACK FROM BC');
        $res->assertSee('250');
        $res->assertDontSee('<select id="status"', false);
    }

    public function test_cannot_create_duplicate_booking_confirmation_for_same_job(): void
    {
        $job = Job::factory()->create();
        BookingConfirmation::create([
            'number'        => 'BC-FIRST-001',
            'booking_date'  => '2026-09-17',
            'job_id'        => $job->id,
            'service_term'  => 'CY/CY',
            'status'        => 'confirmed',
        ]);

        // Direct create page access should redirect to job order
        $createRes = $this->get(route('booking-confirmations.create', ['job_id' => $job->id]));
        $createRes->assertRedirect(route('jobs.show', $job->id).'#tab-booking');
        $createRes->assertSessionHas('warning');

        // Storing another BC for same job_id must fail validation
        $storeRes = $this->post(route('booking-confirmations.store'), [
            'number'        => 'BC-SECOND-002',
            'booking_date'  => '2026-09-18',
            'job_id'        => $job->id,
            'service_term'  => 'CY/CY',
            'status'        => 'confirmed',
        ]);
        $storeRes->assertSessionHasErrors(['job_id']);
        $this->assertDatabaseMissing('booking_confirmations', ['number' => 'BC-SECOND-002']);
    }

    public function test_cannot_create_duplicate_shipping_instruction_for_same_job(): void
    {
        $job = Job::factory()->create();
        ShippingInstruction::create([
            'number'            => 'SI-FIRST-001',
            'si_date'           => '2026-09-17',
            'job_id'            => $job->id,
            'to_carrier'        => 'ONE LINE',
            'shipper_name'      => 'PT Shipper',
            'consignee_name'    => 'PT Consignee',
            'pol'               => 'JAKARTA',
            'pod'               => 'SINGAPORE',
            'cargo_description' => 'General Cargo',
            'status'            => 'submitted',
        ]);

        // Direct create page access should redirect to job order
        $createRes = $this->get(route('shipping-instructions.create', ['job_id' => $job->id]));
        $createRes->assertRedirect(route('jobs.show', $job->id).'#tab-si');
        $createRes->assertSessionHas('warning');

        // Storing another SI for same job_id must fail validation
        $storeRes = $this->post(route('shipping-instructions.store'), [
            'number'            => 'SI-SECOND-002',
            'si_date'           => '2026-09-18',
            'job_id'            => $job->id,
            'to_carrier'        => 'EVERGREEN',
            'shipper_name'      => 'PT Shipper',
            'consignee_name'    => 'PT Consignee',
            'pol'               => 'JAKARTA',
            'pod'               => 'SINGAPORE',
            'cargo_description' => 'General Cargo',
            'status'            => 'submitted',
        ]);
        $storeRes->assertSessionHasErrors(['job_id']);
        $this->assertDatabaseMissing('shipping_instructions', ['number' => 'SI-SECOND-002']);
    }
}
