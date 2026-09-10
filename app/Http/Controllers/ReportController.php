<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Models\ChartOfAccount;
use App\Services\FinancialReportService;
use Carbon\Carbon;

class ReportController extends Controller
{
    private function period(ReportFilterRequest $request): array
    {
        $to = $request->input('to', today()->toDateString());

        return [$request->input('from', Carbon::parse($to)->startOfYear()->toDateString()), $to];
    }

    public function ledger(ReportFilterRequest $request, FinancialReportService $service)
    {
        [$from, $to] = $this->period($request);
        $accounts = ChartOfAccount::withTrashed()->orderBy('code')->get();
        $accountId = (int) ($request->input('account_id') ?: $accounts->first()?->id);

        return view('reports.ledger', $service->ledger($accountId, $from, $to) + compact('accounts', 'from', 'to'));
    }

    public function trialBalance(ReportFilterRequest $request, FinancialReportService $service)
    {
        $to = $request->input('to', today()->toDateString());

        return view('reports.trial-balance', ['rows' => $service->trialBalance($to), 'to' => $to]);
    }

    public function balanceSheet(ReportFilterRequest $request, FinancialReportService $service)
    {
        $to = $request->input('to', today()->toDateString());

        return view('reports.balance-sheet', $service->balanceSheet($to) + compact('to'));
    }

    public function incomeStatement(ReportFilterRequest $request, FinancialReportService $service)
    {
        [$from, $to] = $this->period($request);

        return view('reports.income-statement', $service->incomeStatement($from, $to) + compact('from', 'to'));
    }

    public function cashFlow(ReportFilterRequest $request, FinancialReportService $service)
    {
        [$from, $to] = $this->period($request);

        return view('reports.cash-flow', $service->cashFlow($from, $to) + compact('from', 'to'));
    }

    public function profitPerJob(ReportFilterRequest $request, FinancialReportService $service)
    {
        [$from, $to] = $this->period($request);

        return view('reports.profit-per-job', ['rows' => $service->profitPerJob($from, $to), 'from' => $from, 'to' => $to]);
    }

    public function profitMonthly(ReportFilterRequest $request, FinancialReportService $service)
    {
        $year = max(2000, min(2100, (int) $request->input('year', today()->year)));

        return view('reports.profit-monthly', $service->monthlyProfit($year) + compact('year'));
    }
}
