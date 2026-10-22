<?php

namespace Tests\Feature;

use App\Services\FinancialReportService;
use App\Support\Money;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashFlowReversalTest extends TestCase
{
    public function test_cash_flow_includes_reversals_in_their_period_and_matches_ledger(): void
    {
        $previous = DB::getDefaultConnection();
        config(['database.connections.cash_flow_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::setDefaultConnection('cash_flow_test');
        try {
            Schema::create('chart_of_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('code');
                $table->string('name');
                $table->string('type');
                $table->softDeletes();
            });
            Schema::create('journals', function (Blueprint $table) {
                $table->id();
                $table->date('journal_date');
                $table->string('type');
                $table->unsignedBigInteger('reversal_of_id')->nullable();
                $table->timestamp('reversed_at')->nullable();
            });
            Schema::create('journal_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('journal_id');
                $table->unsignedBigInteger('chart_of_account_id');
                $table->decimal('debit', 18, 2);
                $table->decimal('credit', 18, 2);
            });
            DB::table('chart_of_accounts')->insert([
                ['id' => 1, 'code' => '11121', 'name' => 'BCA IDR', 'type' => 'asset'],
                ['id' => 2, 'code' => '11122', 'name' => 'Mandiri IDR', 'type' => 'asset'],
                ['id' => 3, 'code' => '21000', 'name' => 'Hutang Vendor', 'type' => 'liability'],
            ]);
            $post = function ($id, $date, $type, $signedAmount, $reversalOf = null, $account = 1) {
                $amount = Money::decimal($signedAmount);
                DB::table('journals')->insert(['id' => $id, 'journal_date' => $date, 'type' => $type, 'reversal_of_id' => $reversalOf]);
                DB::table('journal_entries')->insert([
                    ['journal_id' => $id, 'chart_of_account_id' => $account, 'debit' => $amount->isPositive() ? (string) $amount : '0', 'credit' => $amount->isNegative() ? (string) $amount->abs() : '0'],
                    ['journal_id' => $id, 'chart_of_account_id' => 3, 'debit' => $amount->isNegative() ? (string) $amount->abs() : '0', 'credit' => $amount->isPositive() ? (string) $amount : '0'],
                ]);
                if ($reversalOf) {
                    DB::table('journals')->where('id', $reversalOf)->update(['reversed_at' => $date.' 12:00:00']);
                }
            };
            $post(1, '2026-09-29', 'adjustment', '50000000');
            $post(2, '2026-10-22', 'job_cost_payment', '-6767500');
            foreach (['487500', '1800000', '487500', '1800000'] as $index => $amount) {
                $id = 3 + $index;
                $post($id, '2026-10-15', 'job_cost_payment', '-'.$amount);
                $post($id + 4, '2026-10-22', 'journal_reversal', $amount, $id);
            }
            $service = new FinancialReportService;
            $report = $service->cashFlow('2026-10-01', '2026-10-31');
            $this->assertEquals(43232500, $report['closing']);
            $this->assertEquals(43232500, $report['accounts']->firstWhere('id', 1)->closing);
            $this->assertEquals(50000000, $report['opening']);
            $this->assertEquals(4575000, $report['inflow']);
            $this->assertEquals(11342500, $report['outflow']);
            $this->assertEquals(-6767500, $report['job_cost_payment']);
            $this->assertEquals(0, $report['other']);
            $this->assertCount(9, $report['entries']);

            // Reversing in November must not remove October cash movement.
            $post(11, '2026-11-05', 'journal_reversal', '6767500', 2);
            $this->assertEquals(43232500, $service->cashFlow('2026-10-01', '2026-10-31')['closing']);
            $november = $service->cashFlow('2026-11-01', '2026-11-30');
            $this->assertEquals(43232500, $november['opening']);
            $this->assertEquals(50000000, $november['closing']);
            $this->assertEquals(6767500, $november['job_cost_payment']);
            $this->assertEquals(50000000, $service->cashFlow('2026-12-01', '2026-12-31')['opening']);

            $post(12, '2026-09-20', 'adjustment', '1234.56', null, 2);
            $post(13, '2026-10-10', 'job_cost_payment', '-234.56', null, 2);
            $post(14, '2026-11-10', 'journal_reversal', '234.56', 13, 2);
            foreach ([['2026-10-01', '2026-10-31'], ['2026-11-01', '2026-11-30'], ['2026-12-01', '2026-12-31']] as [$from, $to]) {
                $report = $service->cashFlow($from, $to);
                $total = Money::decimal(0);
                foreach ($report['accounts'] as $account) {
                    $ledger = DB::table('journal_entries')->join('journals', 'journals.id', '=', 'journal_entries.journal_id')
                        ->where('chart_of_account_id', $account->id)->where('journal_date', '<=', $to)
                        ->selectRaw('COALESCE(SUM(debit), 0) AS debit, COALESCE(SUM(credit), 0) AS credit')->first();
                    $balance = Money::decimal((string) $ledger->debit)->minus((string) $ledger->credit);
                    $this->assertTrue($balance->isEqualTo(Money::decimal($account->closing)));
                    $total = $total->plus($balance);
                }
                $this->assertTrue($total->isEqualTo(Money::decimal($report['closing'])));
            }
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('cash_flow_test');
        }
    }
}
