<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q = Invoice::query()->when(in_array($request->input('status'), ['issued', 'partially_paid', 'paid'], true), fn ($q) => $q->where('status', $request->input('status')))->latest('id')->paginate(15)->withQueryString();

        return view('invoices.index', ['invoices' => $q]);
    }

    public function show(Invoice $invoice)
    {
        return view('invoices.show', ['invoice' => $invoice->load(['items', 'payments.account', 'job', 'snapshot'])]);
    }
}
