<?php

namespace Tests\Feature;

use App\Models\Awb;
use App\Models\BillOfLading;
use App\Models\BookingConfirmation;
use App\Models\Customer;
use App\Models\Job;
use App\Models\ShippingInstruction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AirWaybillAndBillOfLadingPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private Job $seaJob;
    private Job $airJob;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@jobfinance.test')->firstOrFail();
        $this->actingAs($this->admin);

        $this->customer = Customer::factory()->create([
            'name' => 'PT Test Customer Logistics',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->seaJob = Job::factory()->create([
            'customer_id'   => $this->customer->id,
            'service_type'  => 'sea',
            'shipper_name'  => 'PT Shipper Laut Mandiri',
            'consignee_name'=> 'PT Penerima Sejahtera',
            'created_by'    => $this->admin->id,
            'updated_by'    => $this->admin->id,
        ]);

        $this->airJob = Job::factory()->create([
            'customer_id'   => $this->customer->id,
            'service_type'  => 'air',
            'shipper_name'  => 'PT Shipper Udara Kilat',
            'consignee_name'=> 'PT Penerima Angkasa',
            'created_by'    => $this->admin->id,
            'updated_by'    => $this->admin->id,
        ]);
    }

    public function test_bill_of_lading_preview_and_pdf_generation(): void
    {
        $bl = BillOfLading::create([
            'number'            => 'RDXL-BL-2026-001',
            'bl_date'           => '2026-09-20',
            'job_id'            => $this->seaJob->id,
            'customer_id'       => $this->customer->id,
            'hbl_number'        => 'RDXL-BL-2026-001',
            'mbl_number'        => 'MBL-EVERGREEN-999',
            'bl_type'           => 'original',
            'freight_term'      => 'PREPAID',
            'carrier'           => 'EVERGREEN LINE',
            'vessel_voyage'     => 'EVER GIVEN V.100W',
            'shipper_name'      => 'PT Shipper Laut Mandiri',
            'consignee_name'    => 'PT Penerima Sejahtera',
            'notify_party'      => 'SAME AS CONSIGNEE',
            'pol'               => 'TANJUNG PRIOK, JAKARTA',
            'pod'               => 'SINGAPORE PORT',
            'package_count'     => 100,
            'package_unit'      => 'CARTONS',
            'marks_numbers'     => 'PKG 1-100',
            'cargo_description' => 'SPARE PARTS AND TEXTILES',
            'gross_weight'      => 2500.50,
            'measurement'       => 15.250,
            'status'            => 'issued',
            'created_by'        => $this->admin->id,
        ]);

        // Preview page
        $this->get(route('bills-of-lading.preview', $bl))->assertOk()->assertSee('Bill of Lading');

        // PDF Draft
        $responseDraft = $this->get(route('bills-of-lading.pdf', ['billOfLading' => $bl, 'type' => 'draft']));
        $responseDraft->assertOk();
        $this->assertSame('application/pdf', $responseDraft->headers->get('Content-Type'));

        // PDF Original
        $responseOriginal = $this->get(route('bills-of-lading.pdf', ['billOfLading' => $bl, 'type' => 'original']));
        $responseOriginal->assertOk();
        $this->assertSame('application/pdf', $responseOriginal->headers->get('Content-Type'));

        // PDF Copy
        $responseCopy = $this->get(route('bills-of-lading.pdf', ['billOfLading' => $bl, 'type' => 'copy']));
        $responseCopy->assertOk();
        $this->assertSame('application/pdf', $responseCopy->headers->get('Content-Type'));
    }

    public function test_air_waybill_preview_and_pdf_generation(): void
    {
        $awb = Awb::create([
            'number'                 => 'AWB-2026-0001',
            'awb_date'               => '2026-09-20',
            'job_id'                 => $this->airJob->id,
            'customer_id'            => $this->customer->id,
            'hawb_number'            => 'HAWB-2026-0001',
            'mawb_number'            => '126-12345678',
            'airline'                => 'GARUDA INDONESIA',
            'airline_code'           => 'GA',
            'flight_number'          => 'GA-880',
            'flight_date'            => '2026-09-21',
            'airport_of_departure'   => 'CGK - SOEKARNO HATTA',
            'airport_of_destination' => 'NRT - NARITA TOKYO',
            'freight_term'           => 'PREPAID',
            'shipper_on_hawb'        => 'PT Shipper Udara Kilat',
            'consignee_on_hawb'      => 'PT Penerima Angkasa',
            'pieces'                 => 10,
            'gross_weight'           => 150.00,
            'gross_weight_unit'      => 'KGS',
            'chargeable_weight'      => 180.00,
            'commodity'              => 'GARMENT SAMPLES',
            'status'                 => 'issued',
            'created_by'             => $this->admin->id,
        ]);

        // Preview page
        $this->get(route('awbs.preview', ['awb' => $awb, 'type' => 'hawb']))->assertOk()->assertSee('HAWB');

        // PDF HAWB (Blue)
        $responseHawb = $this->get(route('awbs.pdf', ['awb' => $awb, 'type' => 'hawb']));
        $responseHawb->assertOk();
        $this->assertSame('application/pdf', $responseHawb->headers->get('Content-Type'));

        // PDF MAWB (Red)
        $responseMawb = $this->get(route('awbs.pdf', ['awb' => $awb, 'type' => 'mawb']));
        $responseMawb->assertOk();
        $this->assertSame('application/pdf', $responseMawb->headers->get('Content-Type'));

        // PDF Draft
        $responseDraft = $this->get(route('awbs.pdf', ['awb' => $awb, 'type' => 'draft']));
        $responseDraft->assertOk();
        $this->assertSame('application/pdf', $responseDraft->headers->get('Content-Type'));
    }

    public function test_operational_documents_pdf_generation(): void
    {
        // Booking Confirmation PDF
        $bc = BookingConfirmation::create([
            'number'             => 'BC-TEST-001',
            'booking_date'       => '2026-09-20',
            'job_id'             => $this->seaJob->id,
            'customer_id'        => $this->customer->id,
            'carrier'            => 'ONE LINE',
            'vessel_voyage'      => 'ONE TRIUMPH / 020E',
            'pol'                => 'JAKARTA',
            'pod'                => 'SINGAPORE',
            'etd'                => '2026-09-25',
            'eta'                => '2026-09-28',
            'doc_cutoff_at'      => '2026-09-24 17:00:00',
            'cy_cutoff_at'       => '2026-09-24 12:00:00',
            'cargo_description'  => "1x20'GP SPARE PARTS",
            'gross_weight'       => 10000.00,
            'volume'             => 25.000,
            'status'             => 'confirmed',
            'created_by'         => $this->admin->id,
        ]);
        $responseBc = $this->get(route('booking-confirmations.pdf', $bc));
        $responseBc->assertOk();
        $this->assertSame('application/pdf', $responseBc->headers->get('Content-Type'));

        // Shipping Instruction PDF
        $si = ShippingInstruction::create([
            'number'             => 'SI-TEST-001',
            'si_date'            => '2026-09-20',
            'job_id'             => $this->seaJob->id,
            'customer_id'        => $this->customer->id,
            'carrier'            => 'ONE LINE',
            'vessel_voyage'      => 'ONE TRIUMPH / 020E',
            'pol'                => 'JAKARTA',
            'pod'                => 'SINGAPORE',
            'shipper_name'       => 'PT Shipper Laut Mandiri',
            'consignee_name'     => 'PT Penerima Sejahtera',
            'notify_party'       => 'SAME AS CONSIGNEE',
            'cargo_description'  => "1x20'GP SPARE PARTS",
            'gross_weight'       => 10000.00,
            'measurement'        => 25.000,
            'status'             => 'sent',
            'created_by'         => $this->admin->id,
        ]);
        $responseSi = $this->get(route('shipping-instructions.pdf', $si));
        $responseSi->assertOk();
        $this->assertSame('application/pdf', $responseSi->headers->get('Content-Type'));

        // Job direct PDFs: Surat Jalan, SK DO, SK Pabean
        $responseSj = $this->get(route('jobs.surat-jalan.pdf', $this->seaJob));
        $responseSj->assertOk();
        $this->assertSame('application/pdf', $responseSj->headers->get('Content-Type'));

        $responseSkDo = $this->get(route('jobs.sk-do.pdf', $this->seaJob));
        $responseSkDo->assertOk();
        $this->assertSame('application/pdf', $responseSkDo->headers->get('Content-Type'));

        $responseSkPabean = $this->get(route('jobs.sk-pabean.pdf', $this->seaJob));
        $responseSkPabean->assertOk();
        $this->assertSame('application/pdf', $responseSkPabean->headers->get('Content-Type'));
    }
}
