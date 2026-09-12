<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\Invoice;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    public function index()
    {
        return view('payments.index', ['invoices' => Invoice::where('balance', '>', 0)->orderBy('due_date')->paginate(10)]);
    }

    public function create(Invoice $invoice)
    {
        return view('payments.create', compact('invoice'));
    }

    public function store(PaymentRequest $request, Invoice $invoice, PaymentService $service)
    {
        $service->create($invoice, $request->validated(), $request->user());

        return redirect()->route('invoices.show', $invoice)->with('success', 'Pembayaran dan jurnal berhasil dicatat.');
    }
}
