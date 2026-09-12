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
        $showRes->assertSee('To (Consignee):');
        $showRes->assertSee('Global Imports Ltd');
        $showRes->assertSee('Mr. John Tan');
        $showRes->assertSee('PT Ekspor Sejahtera');
        $showRes->assertSee('NPWP: 01.234.567.8-901.000');
        $showRes->assertSee('NPWP (Terlampir)');

        // Test Job Order index displays direct BC shortcut for export
        $jobsRes = $this->get(route('jobs.index'));
        $jobsRes->assertOk();
        $jobsRes->assertSee(route('booking-confirmations.preview', $bc));

        // Test PDF generation
        $pdfRes = $this->get(route('booking-confirmations.pdf', ['bookingConfirmation' => $bc, 'mode' => 'inline']));
        $pdfRes->assertOk();
        $this->assertSame('application/pdf', $pdfRes->headers->get('content-type'));
    }
}
