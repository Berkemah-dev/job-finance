<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\CoretaxService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q = Invoice::query()->when(in_array($request->input('status'), ['issued', 'partially_paid', 'paid'], true), fn ($q) => $q->where('status', $request->input('status')))->latest('id')->paginate(15)->withQueryString();

        return view('invoices.index', ['invoices' => $q]);
    }

    public function coretaxIndex(Request $request)
    {
        $q = Invoice::query()->with('job')->where('tax', '>', 0)->where('subtotal', '>', 0)->latest('id');
        $search = mb_substr($request->string('search')->toString(), 0, 60);
        $status = $request->string('status')->toString();
        if (in_array($status, ['issued', 'partially_paid', 'paid'], true)) {
            $q->where('status', $status);
        }
        if ($search !== '') {
            $q->where(fn ($x) => $x->where('number', 'like', '%'.$search.'%')->orWhere('customer_snapshot->name', 'like', '%'.$search.'%'));
        }

        return view('invoices.coretax', ['invoices' => $q->paginate(15)->withQueryString(), 'search' => $search, 'status' => $status]);
    }

    public function show(Invoice $invoice)
    {
        return view('invoices.show', ['invoice' => $invoice->load(['items', 'payments.account', 'job', 'snapshot'])]);
    }

    public function coretax(Invoice $invoice, CoretaxService $service)
    {
        $xml = $service->generate($invoice, request()->user());

        return response($xml, 200, ['Content-Type' => 'application/xml', 'Content-Disposition' => 'attachment; filename=faktur-'.$invoice->number.'.xml']);
    }

    public function coretaxPreview(Invoice $invoice, CoretaxService $service)
    {
        $xml = $service->generate($invoice, request()->user(), false);

        return response($xml, 200, ['Content-Type' => 'text/plain; charset=utf-8', 'X-Robots-Tag' => 'noindex']);
    }
}
