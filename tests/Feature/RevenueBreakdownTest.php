<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Job;
use App\Models\JobCost;
use App\Models\User;
use App\Services\FinancialReportService;
use App\Services\JournalService;
use App\Support\Money;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RevenueBreakdownTest extends TestCase
{
    private function scenario(callable $test): void
    {
        $previous = DB::getDefaultConnection();
        config(['database.connections.revenue_detail_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('revenue_detail_test');
        try {
            Artisan::call('migrate', ['--database' => 'revenue_detail_test', '--force' => true]);
            foreach ([RolePermissionSeeder::class, ChartOfAccountSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class] as $seeder) {
                app($seeder)->run();
            }
            $manager = User::where('email', 'finance-manager@jobfinance.test')->firstOrFail();
            $job = Job::factory()->open()->create(['service_type' => 'imp_sea']);
            $post = function ($type, $date, $lines) use ($job, $manager) {
                return DB::transaction(function () use ($type, $date, $lines, $job, $manager) {
                    $entries = [];
                    $net = Money::decimal(0);
                    foreach ($lines as [$code, $description, $signedCredit]) {
                        $amount = Money::decimal($signedCredit);
                        $net = $net->plus($amount);
                        $entries[] = ['account_id' => ChartOfAccount::where('code', $code)->value('id'),
                            'description' => $description,
                            'debit' => $amount->isNegative() ? (string) $amount->abs() : '0',
                            'credit' => $amount->isPositive() ? (string) $amount : '0'];
                    }
                    if (! $net->isZero()) {
                        $entries[] = ['account_id' => ChartOfAccount::where('code', '11121')->value('id'), 'description' => 'Fixture counterpart',
                            'debit' => $net->isPositive() ? (string) $net : '0', 'credit' => $net->isNegative() ? (string) $net->abs() : '0'];
                    }

                    return app(JournalService::class)->post($type, Job::class, $job->id, $date, 'Revenue fixture', $entries, $manager);
                });
            };
            $test($job, $manager, $post, app(FinancialReportService::class));
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('revenue_detail_test');
        }
    }

    private function total($rows): string
    {
        return (string) $rows->reduce(fn ($sum, $row) => $sum->plus($row->total_price), Money::decimal(0));
    }

    public function test_unclosed_details_reconcile_revisions_and_ignore_unposted_costs(): void
    {
        $this->scenario(function ($job, $manager, $post, $report) {
            $original = $post('job_invoice', '2026-10-01', [
                ['41100', 'Pendapatan Unclose · OCEAN FREIGHT · '.$job->number, '1000000.15'],
                ['41100', 'Pendapatan Unclose · TRUCKING · '.$job->number, '500000.10'],
            ]);
            app(JournalService::class)->reverse($original->fresh(), ['lock_version' => $original->fresh()->lock_version,
                'reversal_date' => '2026-10-02', 'reason' => 'Invoice amendment'], $manager);
            $post('job_invoice_amendment', '2026-10-02', [
                ['41100', 'Pendapatan Unclose · OCEAN FREIGHT · '.$job->number, '1200000.15'],
                ['41100', 'Pendapatan Unclose · TRUCKING · '.$job->number, '600000.10'],
            ]);
            $post('adjustment', '2026-10-03', [['41100', 'Koreksi pendapatan manual', '-100.25']]);
            $draft = JobCost::factory()->create(['job_id' => $job->id, 'description' => 'DRAFT NOT POSTED', 'total_price' => '9000000']);
            $r = $report->incomeStatement('2026-10-01', '2026-10-31');
            $this->assertSame('1200000.15', $r['revenueUnclosedBreakdown']->firstWhere('description', 'OCEAN FREIGHT')->total_price);
            $this->assertSame('600000.10', $r['revenueUnclosedBreakdown']->firstWhere('description', 'TRUCKING')->total_price);
            $this->assertSame('1799900.00', $this->total($r['revenueUnclosedBreakdown']));
            $this->assertSame($r['totalPendapatanUnclosed'], $this->total($r['revenueUnclosedBreakdown']));
            $this->assertFalse($r['revenueUnclosedBreakdown']->contains('description', $draft->description));
            $this->assertSame('-100.25', $r['revenueUnclosedBreakdown']->firstWhere('description', 'Koreksi pendapatan manual')->total_price);
            foreach (['finance@jobfinance.test', 'finance-manager@jobfinance.test'] as $email) {
                $this->actingAs(User::where('email', $email)->firstOrFail());
                $html = view('reports.income-statement', $r + [
                    'from' => '2026-10-01', 'to' => '2026-10-31', 'errors' => new \Illuminate\Support\ViewErrorBag,
                ])->render();
                $this->assertStringContainsString('revenue-unclosed-detail', $html);
                $this->assertStringContainsString('OCEAN FREIGHT', $html);
                $this->assertStringContainsString('TRUCKING', $html);
                $this->assertStringContainsString('Nilai Jual / Pendapatan', $html);
            }
        });
    }

    public function test_closing_details_use_historical_invoice_and_net_unclosed_to_zero(): void
    {
        $this->scenario(function ($job, $manager, $post, $report) {
            $post('job_invoice', '2026-10-01', [
                ['41100', 'Pendapatan Unclose · OCEAN FREIGHT · '.$job->number, '1000000.15'],
                ['41100', 'Pendapatan Unclose · TRUCKING · '.$job->number, '500000.10'],
            ]);
            $closing = $post('job_closing', '2026-10-02', [
                ['41100', 'Reklasifikasi Pendapatan Unclose '.$job->number, '-1500000.25'],
                ['41000', 'Pendapatan Close '.$job->number, '1500000.25'],
            ]);
            JobCost::factory()->create(['job_id' => $job->id, 'description' => 'CURRENT DIFFERENT CHARGE', 'total_price' => '9999999']);
            $r = $report->incomeStatement('2026-10-01', '2026-10-31');
            $this->assertCount(0, $r['revenueUnclosedBreakdown']);
            $this->assertSame($r['totalPendapatanClosed'], $this->total($r['revenueClosedBreakdown']));
            $this->assertSame('1000000.15', $r['revenueClosedBreakdown']->firstWhere('description', 'OCEAN FREIGHT')->total_price);
            $this->assertSame('500000.10', $r['revenueClosedBreakdown']->firstWhere('description', 'TRUCKING')->total_price);
            app(JournalService::class)->reverse($closing->fresh(), ['lock_version' => $closing->fresh()->lock_version,
                'reversal_date' => '2026-11-01', 'reason' => 'Reopen fixture'], $manager);
            $october = $report->incomeStatement('2026-10-01', '2026-10-31');
            $november = $report->incomeStatement('2026-11-01', '2026-11-30');
            $this->assertSame('1500000.25', $this->total($october['revenueClosedBreakdown']));
            $this->assertSame('-1500000.25', $this->total($november['revenueClosedBreakdown']));
            $this->assertSame('1500000.25', $this->total($november['revenueUnclosedBreakdown']));
        });
    }

    public function test_legacy_aggregate_without_charge_history_is_not_dropped(): void
    {
        $this->scenario(function ($job, $manager, $post, $report) {
            $post('job_closing', '2026-10-01', [['41000', 'Pendapatan Close '.$job->number, '750000.55']]);
            $r = $report->incomeStatement('2026-10-01', '2026-10-31');
            $this->assertSame('750000.55', $this->total($r['revenueClosedBreakdown']));
            $this->assertSame($r['totalPendapatanClosed'], $this->total($r['revenueClosedBreakdown']));
            $this->assertStringContainsString('Pendapatan Close', $r['revenueClosedBreakdown']->first()->description);
            foreach (['reports/income-statement.blade.php', 'reports/partials/revenue-charge-detail.blade.php'] as $path) {
                $php = app('blade.compiler')->compileString(file_get_contents(resource_path('views/'.$path)));
                token_get_all($php, TOKEN_PARSE);
                $this->assertNotEmpty($php);
            }
        });
    }
}
