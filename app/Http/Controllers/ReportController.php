<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\JobCost;
use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

        return view('reports.ledger', $service->ledger($accountId, $from, $to, $request->only(['payment_status', 'customer', 'job_number', 'invoice_number', 'cost_name', 'pr_number', 'vendor', 'agent'])) + compact('accounts', 'from', 'to'));
    }

    public function ledgerPdf(ReportFilterRequest $request, FinancialReportService $service)
    {
        [$from, $to] = $this->period($request);
        $accountId = (int) $request->input('account_id');
        abort_if($accountId < 1, 422, 'Pilih akun terlebih dahulu.');

        $report = $service->ledgerExport($accountId, $from, $to);
        $pdf = app('dompdf.wrapper')
            ->loadView('reports.pdf.ledger', $report + compact('from', 'to'))
            ->setPaper('a4', 'landscape');
        $filename = 'Buku_Besar_'.$report['account']->code.'_'.$from.'_sd_'.$to.'.pdf';

        return $request->query('mode') === 'download'
            ? $pdf->download($filename)
            : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$filename.'"']);
    }

    /** Simpan nomor bukti potong tanpa mengubah nominal atau jurnal pembayaran. */
    public function updateWithholdingDocument(Request $request, JobCost $cost)
    {
        $validated = $request->validate([
            'withholding_document_number' => ['nullable', 'string', 'max:100'],
        ]);

        $cost->update($validated);

        return back()->with('success', 'Nomor Bukti Potong berhasil diperbarui.');
    }

    /** Simpan nomor seri faktur pajak langsung dari rincian PPN Keluaran. */
    public function updateTaxInvoiceNumber(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'tax_invoice_number' => ['nullable', 'string', 'max:100'],
        ]);

        $invoice->update($validated);

        return back()->with('success', 'Nomor Seri Faktur Pajak berhasil diperbarui.');
    }

    public function trialBalance(ReportFilterRequest $request, FinancialReportService $service)
    {
        [$from, $to] = $this->period($request);

        return view('reports.trial-balance', $service->trialBalance($to, $from) + compact('from', 'to'));
    }

    public function balanceSheet(ReportFilterRequest $request, FinancialReportService $service)
    {
        [$from, $to] = $this->period($request);

        return view('reports.balance-sheet', $service->balanceSheet($to, $from) + compact('from', 'to'));
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

        return view('reports.profit-per-job', $service->profitPerJob($from, $to) + compact('from', 'to'));
    }

    public function profitMonthly(ReportFilterRequest $request, FinancialReportService $service)
    {
        $year = max(2000, min(2100, (int) $request->input('year', today()->year)));

        return view('reports.profit-monthly', $service->monthlyProfit($year) + compact('year'));
    }

    public function hppByCostType(ReportFilterRequest $request, FinancialReportService $service)
    {
        [$from, $to] = $this->period($request);

        return view('reports.hpp-by-cost-type', $service->hppByCostType($from, $to) + compact('from', 'to'));
    }
}
