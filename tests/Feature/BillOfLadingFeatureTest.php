<?php

namespace Tests\Feature;

use App\Models\BillOfLading;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Port;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillOfLadingFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Customer $customer;
    private Job $job;
    private Vendor $shippingLine;
    private Vendor $internationalAgent;
    private Port $polPort;
    private Port $podPort;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->actor = User::where('email', 'operational@jobfinance.test')->firstOrFail();
        $this->actingAs($this->actor);

        $this->customer = Customer::factory()->create([
            'name' => 'PT Customer Utama',
        ]);

        $this->shippingLine = Vendor::factory()->create([
            'name'       => 'Maersk Line Indonesia',
            'type'       => 'shipping_line',
            'is_active'  => true,
            'created_by' => $this->actor->id,
            'updated_by' => $this->actor->id,
        ]);

        $this->internationalAgent = Vendor::factory()->create([
            'name'       => 'Global Freight Shanghai Ltd',
            'type'       => 'international_agent',
            'is_active'  => true,
            'created_by' => $this->actor->id,
            'updated_by' => $this->actor->id,
        ]);

        $this->polPort = Port::create([
            'code'       => 'IDTPP',
            'name'       => 'Tanjung Priok, Jakarta',
            'country'    => 'Indonesia',
            'type'       => 'seaport',
            'status'     => 'active',
            'created_by' => $this->actor->id,
            'updated_by' => $this->actor->id,
        ]);

        $this->podPort = Port::create([
            'code'       => 'CNSHA',
            'name'       => 'Shanghai Port',
            'country'    => 'China',
            'type'       => 'seaport',
            'status'     => 'active',
            'created_by' => $this->actor->id,
            'updated_by' => $this->actor->id,
        ]);

        $this->job = Job::factory()->create([
            'customer_id'       => $this->customer->id,
            'service_type'      => 'exp_sea',
            'bl_number'         => 'MBL-MAEU-987654321',
            'hbl_number'        => 'HBL-RDX-123456789',
            'shipper_name'      => 'PT Shipper Tekstil',
            'consignee_name'    => 'Shanghai Garment Corp',
            'cargo_description' => 'Textiles & Garments',
            'package_count'     => 120,
            'container_type'    => 'Pallet',
            'gross_weight'      => 4500.50,
            'volume'            => 28.50,
            'pol'               => $this->polPort->name,
            'pod'               => $this->podPort->name,
            'vessel_voyage'     => 'MV. MAERSK MC-KINNEY MOLLER V.001E',
        ]);
    }

    public function test_bl_create_page_renders_custom_selects_and_hides_unwanted_elements(): void
    {
        $response = $this->get(route('bills-of-lading.create', ['job_id' => $this->job->id]));

        $response->assertOk();
        // Point 1: MBL from Job Order
        $response->assertSee($this->job->bl_number);
        // Point 2: Status field removed from visible form
        $response->assertDontSee('<select id="status"', false);
        $response->assertSee('type="hidden" name="status"', false);
        // Point 3: Agent uses International Agent vendors
        $response->assertSee($this->internationalAgent->name);
        $response->assertSee('Vendor (International Agent)');
        // Point 4: Carrier uses Shipping Lines vendors
        $response->assertSee($this->shippingLine->name);
        $response->assertSee('Vendor (Shipping Lines)');
        // Point 5: Switch BL checkbox toggle
        $response->assertSee('id="toggle_switch_bl"', false);
        $response->assertSee('id="switch_bl_container"', false);
        // Point 6: Master Port used with custom select
        $response->assertSee('data-custom-select data-allow-custom="true" aria-label="Port of Loading"', false);
        $response->assertSee('data-custom-select data-allow-custom="true" aria-label="Port of Discharge"', false);
        $response->assertDontSee('id="port_list"', false); // No native ugly datalist
        // Point 7: Carrier BL number field removed
        $response->assertDontSee('name="carrier_bl_number"', false);
    }

    public function test_bl_can_be_stored_without_status_and_defaults_to_draft(): void
    {
        $postData = [
            'number'            => 'BL/EXP/SEA/26090001',
            'bl_date'           => '2026-09-17',
            'job_id'            => $this->job->id,
            'mbl_number'        => $this->job->bl_number,
            'hbl_number'        => $this->job->hbl_number,
            'bl_type'           => 'original',
            'original_bl_count' => 3,
            'place_of_issue'    => 'JAKARTA',
            'freight_term'      => 'PREPAID',
            'freight_payable_at'=> 'JAKARTA',
            'carrier'           => $this->shippingLine->name,
            'agent_name'        => $this->internationalAgent->name,
            'shipper_name'      => $this->job->shipper_name,
            'consignee_name'    => $this->job->consignee_name,
            'vessel_voyage'     => $this->job->vessel_voyage,
            'pol'               => $this->polPort->name,
            'pod'               => $this->podPort->name,
            'cargo_description' => 'Cotton Fabrics',
        ];

        $response = $this->post(route('bills-of-lading.store'), $postData);
        $response->assertSessionHasNoErrors();

        $bl = BillOfLading::where('number', 'BL/EXP/SEA/26090001')->firstOrFail();
        $this->assertEquals('draft', $bl->status);
        $this->assertEquals($this->job->bl_number, $bl->mbl_number);
        $this->assertEquals($this->shippingLine->name, $bl->carrier);
        $this->assertEquals($this->internationalAgent->name, $bl->agent_name);
        $this->assertNull($bl->shipper_switch);
        $this->assertNull($bl->consignee_switch);
    }

    public function test_bl_stores_switch_bl_parties_when_checked(): void
    {
        $postData = [
            'number'            => 'BL/EXP/SEA/26090002',
            'bl_date'           => '2026-09-17',
            'job_id'            => $this->job->id,
            'mbl_number'        => $this->job->bl_number,
            'bl_type'           => 'original',
            'freight_term'      => 'PREPAID',
            'is_switch_bl'      => '1',
            'shipper_name'      => 'Original Shipper',
            'consignee_name'    => 'Original Consignee',
            'shipper_switch'    => 'PT Switch Trading Indonesia',
            'consignee_switch'  => 'Hong Kong Logistics Hub Ltd',
            'pol'               => $this->polPort->name,
            'pod'               => $this->podPort->name,
            'cargo_description' => 'Textiles',
        ];

        $response = $this->post(route('bills-of-lading.store'), $postData);
        $response->assertSessionHasNoErrors();

        $bl = BillOfLading::where('number', 'BL/EXP/SEA/26090002')->firstOrFail();
        $this->assertEquals('PT Switch Trading Indonesia', $bl->shipper_switch);
        $this->assertEquals('Hong Kong Logistics Hub Ltd', $bl->consignee_switch);
    }

    public function test_bl_edit_page_renders_custom_selects_and_updates_successfully(): void
    {
        $bl = BillOfLading::create([
            'number'            => 'BL/EXP/SEA/26090003',
            'bl_date'           => '2026-09-17',
            'job_id'            => $this->job->id,
            'mbl_number'        => $this->job->bl_number,
            'bl_type'           => 'original',
            'freight_term'      => 'PREPAID',
            'carrier'           => $this->shippingLine->name,
            'agent_name'        => $this->internationalAgent->name,
            'pol'               => $this->polPort->name,
            'pod'               => $this->podPort->name,
            'status'            => 'draft',
            'created_by'        => $this->actor->id,
        ]);

        $response = $this->get(route('bills-of-lading.edit', $bl));
        $response->assertOk();
        $response->assertDontSee('<select id="status"', false);
        $response->assertDontSee('name="carrier_bl_number"', false);
        $response->assertDontSee('id="port_list"', false);
        $response->assertSee('data-custom-select data-allow-custom="true" aria-label="Port of Loading"', false);
        $response->assertSee('data-custom-select data-allow-custom="true" aria-label="Carrier (Pelayaran)"', false);
        $response->assertSee('data-custom-select data-allow-custom="true" aria-label="Agent"', false);

        $updateData = [
            'number'            => 'BL/EXP/SEA/26090003',
            'bl_date'           => '2026-09-17',
            'job_id'            => $this->job->id,
            'mbl_number'        => 'MBL-UPDATED-999',
            'bl_type'           => 'original',
            'freight_term'      => 'PREPAID',
            'carrier'           => $this->shippingLine->name,
            'agent_name'        => $this->internationalAgent->name,
            'pol'               => $this->polPort->name,
            'pod'               => $this->podPort->name,
            'cargo_description' => 'Updated Commodities',
        ];

        $updateResponse = $this->put(route('bills-of-lading.update', $bl), $updateData);
        $updateResponse->assertSessionHasNoErrors();

        $bl->refresh();
        $this->assertEquals('MBL-UPDATED-999', $bl->mbl_number);
        $this->assertEquals('Updated Commodities', $bl->cargo_description);
        $this->assertEquals('draft', $bl->status);
    }
}

