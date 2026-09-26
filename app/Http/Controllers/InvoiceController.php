<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\CoretaxService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 80);
        $status = (string) $request->input('status', '');
        $deliveryStatus = (string) $request->input('delivery_status', '');
        $q = Invoice::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('number', 'like', '%'.$search.'%')
                ->orWhere('customer_snapshot->name', 'like', '%'.$search.'%')
                ->orWhereHas('job', fn ($job) => $job->where('number', 'like', '%'.$search.'%'))))
            ->when(in_array($status, ['issued', 'partially_paid', 'paid'], true), fn ($q) => $q->where('status', $status))
            ->when(in_array($deliveryStatus, ['not_sent', 'sent', 'received'], true), fn ($q) => $q->where('delivery_status', $deliveryStatus))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('invoices.index', ['invoices' => $q, 'search' => $search, 'status' => $status, 'deliveryStatus' => $deliveryStatus]);
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

        return view('invoices.coretax', ['invoices' => $q->paginate(10)->withQueryString(), 'search' => $search, 'status' => $status]);
    }

    public function show(Invoice $invoice)
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

        return view('invoices.show', [
            'invoice' => $invoice->load(['items', 'payments.account', 'job', 'snapshot']),
            'bankAccounts' => $bankAccounts,
        ]);
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

    public function coretaxSelected(Request $request, Invoice $invoice, CoretaxService $service)
    {
        $data = $request->validate(['item_ids' => ['required', 'array', 'min:1'], 'item_ids.*' => ['integer']]);
        $items = $invoice->items()->whereIn('id', $data['item_ids'])->where('type', 'provision')->get();
        $xml = $service->generate($invoice, $request->user(), true, $items);
        return response($xml, 200, ['Content-Type' => 'application/xml', 'Content-Disposition' => 'attachment; filename=faktur-'.$invoice->number.'-charges.xml']);
    }

    public function preview(Invoice $invoice)
    {
        return view('documents.pdf-preview', [
            'title'       => 'Invoice '.$invoice->number,
            'backUrl'     => route('invoices.show', $invoice),
            'pdfUrl'      => route('invoices.pdf', ['invoice' => $invoice, 'mode' => 'inline']),
            'downloadUrl' => route('invoices.pdf', ['invoice' => $invoice, 'mode' => 'download']),
        ]);
    }

    public function pdf(Request $request, Invoice $invoice, \App\Services\MasterDataService $master)
    {
        $invoice->load(['items', 'job.customer', 'job.quotation', 'snapshot']);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.invoice', ['invoice' => $invoice])->setPaper('a4');
        $filename = 'Invoice_'.$invoice->number.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF invoice '.$invoice->number, ['module' => 'invoice', 'record_id' => $invoice->id]);

        return $request->query('mode') === 'download'
            ? $pdf->download($filename)
            : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$filename.'"']);
    }

    public function updateDelivery(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'delivery_status'  => ['required', 'in:not_sent,sent,received'],
            'sent_at'          => ['nullable', 'date'],
            'received_at'      => ['nullable', 'date'],
            'tracking_number'  => ['nullable', 'string', 'max:100'],
            'delivery_notes'   => ['nullable', 'string', 'max:2000'],
            'tax_invoice_number' => ['nullable', 'string', 'max:100'],
            'tax_invoice_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:3072'],
        ]);

        if ($request->hasFile('tax_invoice_file')) {
            $validated['tax_invoice_file'] = $request->file('tax_invoice_file')->store('invoice-tax-documents', 'private');
        }

        $validated['sent_by'] = $request->user()->id;
        $invoice->update($validated);

        return back()->with('success', 'Status pengiriman invoice berhasil diperbarui.');
    }

    public function taxInvoiceFile(Invoice $invoice)
    {
        abort_unless($invoice->tax_invoice_file && Storage::disk('private')->exists($invoice->tax_invoice_file), 404);

        return response(Storage::disk('private')->get($invoice->tax_invoice_file), 200, [
            'Content-Type' => Storage::disk('private')->mimeType($invoice->tax_invoice_file) ?: 'application/pdf',
            'Content-Disposition' => 'inline; filename="faktur-pajak-'.$invoice->number.'"',
        ]);
    }
}
