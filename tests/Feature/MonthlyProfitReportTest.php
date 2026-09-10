<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Job;
use App\Models\JobClosingSnapshot;
use App\Models\User;
use App\Services\FinancialReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyProfitReportTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;

    private User $management;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->finance = User::where('email', 'finance@jobfinance.test')->firstOrFail();
        $this->management = User::where('email', 'management@jobfinance.test')->firstOrFail();
    }

    private function closedSnapshot(string $month, string $profit, string $revenue = '4500000.00'): JobClosingSnapshot
    {
        $job = Job::factory()->open()->create();
        $job->update(['status' => 'closed']);

        return JobClosingSnapshot::create(['job_id' => $job->id, 'closing_date' => '2026-'.$month.'-15', 'customer_snapshot' => ['name' => 'PT Test'],
            'costs_snapshot' => [], 'total_temporary' => '5000000.00', 'total_provision_cost' => '3000000.00', 'total_provision_sell' => $revenue,
            'subtotal' => '9500000.00', 'tax' => '1100000.00', 'total' => '10600000.00', 'profit' => $profit, 'margin' => '33.33',
            'funding_account_id' => ChartOfAccount::firstOrFail()->id, 'closed_by' => $this->finance->id, 'closed_at' => now()]);
    }

    public function test_monthly_report_groups_profit_by_month_for_selected_year(): void
    {
        $this->closedSnapshot('07', '1500000.00');
        $this->closedSnapshot('07', '900000.00', '4000000.00');
        $this->closedSnapshot('01', '2500000.00', '6000000.00');

        $service = app(FinancialReportService::class);
        $year = today()->year;
        $result = $service->monthlyProfit($year);

        $this->assertCount(12, $result['rows']);
        $july = $result['rows'][6];
        $this->assertSame(2, $july['jobs']);
        $this->assertSame('2400000.00', $july['profit']);
        $this->assertSame('8500000.00', $july['revenue']);
        $this->assertSame(0, $result['rows'][2]['jobs']);
        $this->assertSame(3, $result['totals']['jobs']);
        $this->assertSame('4900000.00', $result['totals']['profit']);
        $this->assertSame('14500000.00', $result['totals']['revenue']);

        $this->actingAs($this->finance);
        $this->get('/reports/profit-bulanan?year='.$year)->assertOk()->assertSee('Profit Bulanan')->assertSee('Juli '.$year)->assertSee('2')->assertSee('2.400.000,00')->assertSee('Total '.$year)->assertSee('4.900.000,00');
    }

    public function test_monthly_report_ignores_other_years_and_defaults_to_current_year(): void
    {
        $this->closedSnapshot('07', '1500000.00');
        $last = JobClosingSnapshot::orderByDesc('id')->firstOrFail();
        $last->update(['closing_date' => '2025-07-15']);

        $this->actingAs($this->finance);
        $this->get('/reports/profit-bulanan')->assertOk()->assertSee('Juli '.today()->year)->assertSee('0');
        $this->get('/reports/profit-bulanan?year=2025')->assertOk()->assertSee('Juli 2025')->assertSee('1.500.000,00')->assertDontSee('Juli '.today()->year);
    }

    public function test_non_finance_roles_cannot_open_monthly_profit_report(): void
    {
        $this->actingAs($this->management);
        $this->get('/reports/profit-bulanan')->assertForbidden();
    }
}
