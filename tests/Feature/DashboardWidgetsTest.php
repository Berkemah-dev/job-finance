<?php

namespace Tests\Feature;

use App\Enums\QuotationStatus;
use App\Models\Customer;
use App\Models\Job;
use App\Models\JobCost;
use App\Models\Quotation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private User $sales;

    private User $operator;

    private User $cs;

    private User $finance;

    private User $management;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->customer = Customer::factory()->create(['code' => 'CUS-DASH', 'name' => 'PT Dashboard Client']);
        $this->sales = User::where('email', 'sales@jobfinance.test')->firstOrFail();
        $this->operator = User::where('email', 'operational@jobfinance.test')->firstOrFail();
        $this->cs = User::where('email', 'customer-service@jobfinance.test')->firstOrFail();
        $this->finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
        $this->management = User::where('email', 'management@jobfinance.test')->firstOrFail();
        $this->admin = User::where('email', 'admin@jobfinance.test')->firstOrFail();

        foreach ([QuotationStatus::Draft, QuotationStatus::Submitted, QuotationStatus::Approved, QuotationStatus::Rejected] as $status) {
            Quotation::factory()->create(['customer_id' => $this->customer->id, 'status' => $status]);
        }

        $converted = Quotation::factory()->create(['customer_id' => $this->customer->id, 'status' => QuotationStatus::Converted]);
        $job = Job::factory()->create(['quotation_id' => $converted->id, 'customer_id' => $this->customer->id, 'status' => 'open', 'job_date' => today()->toDateString()]);
        JobCost::factory()->create(['job_id' => $job->id, 'status' => 'final', 'type' => 'temporary', 'description' => 'Dokumen', 'quantity' => '1.00', 'unit_cost' => '5000000.00', 'unit_price' => '5000000.00', 'total_cost' => '5000000.00', 'total_price' => '5000000.00', 'finalized_by' => $this->finance->id, 'finalized_at' => now()]);
        JobCost::factory()->create(['job_id' => $job->id, 'status' => 'final', 'type' => 'provision', 'description' => 'Trucking', 'quantity' => '1.00', 'unit_cost' => '3000000.00', 'unit_price' => '4500000.00', 'total_cost' => '3000000.00', 'total_price' => '4500000.00', 'finalized_by' => $this->finance->id, 'finalized_at' => now()]);
        $this->actingAs($this->finance)->post('/closing/'.$job->id, ['lock_version' => $job->fresh()->lock_version, 'closing_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString(), 'funding_account' => 'bank', 'tax' => '0'])->assertSessionHasNoErrors()->assertRedirect();

        foreach ([['booked', 3, 5, $this->cs], ['in_progress', null, null, null]] as [$shipmentStatus, $etdIn, $etaIn, $assigned]) {
            $attributes = ['customer_id' => $this->customer->id, 'status' => 'open', 'job_date' => today()->toDateString(), 'shipment_status' => $shipmentStatus];
            if ($etdIn !== null) {
                $attributes['etd'] = today()->addDays($etdIn)->toDateString();
            }
            if ($etaIn !== null) {
                $attributes['eta'] = today()->addDays($etaIn)->toDateString();
            }
            if ($assigned !== null) {
                $attributes['cs_id'] = $assigned->id;
            }
            $openQuotation = Quotation::factory()->create(['customer_id' => $this->customer->id, 'status' => QuotationStatus::Converted]);
            $attributes['quotation_id'] = $openQuotation->id;
            Job::factory()->create($attributes);
        }
    }

    public function test_sales_sees_quotation_pipeline_and_own_quotes(): void
    {
        $this->actingAs($this->sales)->get('/dashboard')
            ->assertOk()
            ->assertSee('Pipeline quotation')
            ->assertSee('Quotation saya')
            ->assertSee('Pengiriman berjalan')
            ->assertDontSee('Jurnal bulan ini');
    }

    public function test_operation_sees_shipment_widgets_but_no_finance(): void
    {
        $this->actingAs($this->operator)->get('/dashboard')
            ->assertOk()
            ->assertSee('Pengiriman berjalan')
            ->assertSee('ETD mendatang')
            ->assertSee('Booked')
            ->assertDontSee('Pipeline quotation')
            ->assertDontSee('Jurnal bulan ini')
            ->assertDontSee('Piutang jatuh tempo');
    }

    public function test_customer_service_sees_own_open_job_counter(): void
    {
        $this->actingAs($this->cs)->get('/dashboard')
            ->assertOk()
            ->assertSee('Pengiriman berjalan')
            ->assertSee('CS menangani 1 job terbuka')
            ->assertDontSee('Quotation saya');
    }

    public function test_finance_sees_financial_widgets_and_top_jobs(): void
    {
        $this->actingAs($this->finance)->get('/dashboard')
            ->assertOk()
            ->assertSee('Pengiriman berjalan')
            ->assertSee('Jurnal bulan ini')
            ->assertSee('Reimbursement menunggu')
            ->assertSee('Job paling menguntungkan')
            ->assertDontSee('Pengguna sistem')
            ->assertDontSee('Pipeline quotation');
    }

    public function test_management_sees_only_general_summary(): void
    {
        $this->actingAs($this->management)->get('/dashboard')
            ->assertOk()
            ->assertSee('Ringkasan pekerjaan')
            ->assertDontSee('Pipeline quotation')
            ->assertDontSee('Pengiriman berjalan')
            ->assertDontSee('Jurnal bulan ini')
            ->assertDontSee('Quotation saya');
    }

    public function test_super_admin_admin_widgets_render(): void
    {
        $this->actingAs($this->admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Pengguna sistem')
            ->assertSee('Aktivitas 7 hari')
            ->assertSee('Jurnal bulan ini');
    }
}
