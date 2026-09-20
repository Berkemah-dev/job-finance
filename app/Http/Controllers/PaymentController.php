<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\Invoice;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 80);
        $status = (string) $request->input('status', '');
        $invoices = Invoice::where('balance', '>', 0)
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('number', 'like', '%'.$search.'%')
                ->orWhere('customer_snapshot->name', 'like', '%'.$search.'%')
                ->orWhereHas('job', fn ($job) => $job->where('number', 'like', '%'.$search.'%'))))
            ->when(in_array($status, ['issued', 'partially_paid'], true), fn ($q) => $q->where('status', $status))
            ->orderBy('due_date')
            ->paginate(10)
            ->withQueryString();

        return view('payments.index', compact('invoices', 'search', 'status'));
    }

    public function create(Invoice $invoice)
    {
        $bankAccounts = \App\Models\ChartOfAccount::query()
            ->where('type', 'asset')
            ->where(function ($q) {
                $q->whereIn('code', ['11100', '11101', '11120', '11121', '11122', '11123'])
                    ->orWhere('code', 'like', '1110%')
                    ->orWhere('code', 'like', '1112%')
                    ->orWhere('name', 'like', '%Bank%')
                    ->orWhere('name', 'like', '%Cash%')
                    ->orWhere('name', 'like', '%Kas%');
            })
            ->where('code', '!=', '1103')
            ->orderBy('code')
            ->get();

        return view('payments.create', compact('invoice', 'bankAccounts'));
    }

    public function store(PaymentRequest $request, Invoice $invoice, PaymentService $service)
    {
        $service->create($invoice, $request->validated(), $request->user());

        return redirect()->route('invoices.show', $invoice)->with('success', 'Pembayaran dan jurnal berhasil dicatat.');
    }
}
