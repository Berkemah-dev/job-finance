<?php

namespace App\Http\Controllers;

use App\Enums\QuotationStatus;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Quotation;
use App\Services\MasterDataService;
use Illuminate\Http\Request;

class OperationalDocumentController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $status = (string) $request->input('status', '');
        $quotationStatus = QuotationStatus::tryFrom((string) $request->input('quotation_status'));
        $jobStatus = (string) $request->input('job_status', '');
        $customerId = $request->integer('customer_id') ?: null;
        $periodFrom = (string) $request->input('period_from', '');
        $periodTo = (string) $request->input('period_to', '');
        $myJobs = $request->boolean('my_jobs');
        $documents = Quotation::with(['customer', 'job.invoice', 'job.closingSnapshot'])
            ->when($myJobs, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('created_by', $request->user()->id)
                      ->orWhereHas('job', fn ($jobQ) => $jobQ->where('cs_id', $request->user()->id)->orWhere('sales_id', $request->user()->id));
                });
            })
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('number', 'like', '%'.$search.'%')
                ->orWhere('subject', 'like', '%'.$search.'%')
                ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                ->orWhereHas('job', fn ($query) => $query->where('number', 'like', '%'.$search.'%')->orWhere('subject', 'like', '%'.$search.'%'))))
            ->when($customerId, fn ($query) => $query->where('customer_id', $customerId))
            ->when($periodFrom, fn ($query) => $query->whereDate('quotation_date', '>=', $periodFrom))
            ->when($periodTo, fn ($query) => $query->whereDate('quotation_date', '<=', $periodTo))
            ->when($quotationStatus, fn ($query) => $query->where('status', $quotationStatus))
            ->when(in_array($jobStatus, array_keys(config('operations.job_statuses')), true), fn ($query) => $query->whereHas('job', fn ($query) => $query->where('status', $jobStatus)))
            ->when($status === 'draft', fn ($query) => $query->where('status', QuotationStatus::Draft))
            ->when($status === 'approval', fn ($query) => $query->where('status', QuotationStatus::Submitted))
            ->when($status === 'approved', fn ($query) => $query->where('status', QuotationStatus::Approved)->whereDoesntHave('job'))
            ->when($status === 'running', fn ($query) => $query->whereHas('job', fn ($query) => $query->where('status', 'open')))
            ->when($status === 'done', fn ($query) => $query->whereHas('job', fn ($query) => $query->where('status', 'closed')))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $baseJobQuery = Job::query()->when($myJobs, fn ($q) => $q->where(fn ($wq) => $wq->where('cs_id', $request->user()->id)->orWhere('sales_id', $request->user()->id)));
        $baseQuoteQuery = Quotation::query()->when($myJobs, fn ($q) => $q->where('created_by', $request->user()->id));

        $quoteStatusCounts = (clone $baseQuoteQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $jobStatusCounts = (clone $baseJobQuery)
            ->selectRaw('status, count(*) as total')
            ->whereIn('status', ['open', 'closed'])
            ->groupBy('status')
            ->pluck('total', 'status');
        $summary = [
            'total' => (int) $quoteStatusCounts->sum(),
            'draft' => (int) ($quoteStatusCounts[QuotationStatus::Draft->value] ?? 0),
            'approval' => (int) ($quoteStatusCounts[QuotationStatus::Submitted->value] ?? 0),
            'approved' => (clone $baseQuoteQuery)->where('status', QuotationStatus::Approved)->whereDoesntHave('job')->count(),
            'running' => (int) ($jobStatusCounts['open'] ?? 0),
            'done' => (int) ($jobStatusCounts['closed'] ?? 0),
        ];
        $customers = Customer::orderBy('name')->get(['id', 'name', 'code']);

        return view('documents.index', compact('documents', 'summary', 'customers', 'search', 'myJobs'));
    }

    public function show(Quotation $quotation)
    {
        return view('documents.show', [
            'quotation' => $quotation->load(['items', 'customer', 'job.invoice', 'job.closingSnapshot', 'creator', 'approver']),
        ]);
    }

    public function quotationPreview(Quotation $quotation)
    {
        return view('documents.pdf-preview', [
            'title' => 'Quotation '.$quotation->number,
            'backUrl' => route('documents.show', $quotation),
            'pdfUrl' => route('documents.quotation.pdf', ['quotation' => $quotation, 'mode' => 'inline', 't' => time()]),
            'downloadUrl' => route('documents.quotation.pdf', ['quotation' => $quotation, 'mode' => 'download']),
        ]);
    }

    public function jobPreview(Quotation $quotation)
    {
        abort_unless($quotation->job, 404);

        return view('documents.pdf-preview', [
            'title' => 'Job Order '.$quotation->job->number,
            'backUrl' => route('documents.show', $quotation),
            'pdfUrl' => route('documents.job.pdf', ['quotation' => $quotation, 'mode' => 'inline']),
            'downloadUrl' => route('documents.job.pdf', ['quotation' => $quotation, 'mode' => 'download']),
        ]);
    }

    public function quotationPdf(Request $request, Quotation $quotation, MasterDataService $master)
    {
        $quotation->load(['items', 'customer', 'creator', 'approver']);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.quotation', ['quotation' => $quotation])->setPaper('a4');
        $filename = $quotation->number.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF quotation '.$quotation->number, ['module' => 'document', 'record_id' => $quotation->id]);

        return $request->query('mode') === 'download'
            ? $pdf->download($filename)
            : response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]);
    }

    public function jobPdf(Request $request, Quotation $quotation, MasterDataService $master)
    {
        $quotation->load(['job.customer', 'job.quotation']);
        abort_unless($quotation->job, 404);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.job-order', ['job' => $quotation->job, 'quotation' => $quotation])->setPaper('a4');
        $filename = $quotation->job->number.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF job order '.$quotation->job->number, ['module' => 'document', 'record_id' => $quotation->job->id]);

        return $request->query('mode') === 'download' ? $pdf->download($filename) : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }

    public function suratJalanPdf(Request $request, Quotation $quotation, MasterDataService $master)
    {
        $quotation->load(['job.customer']);
        abort_unless($quotation->job, 404);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.surat-jalan', ['job' => $quotation->job, 'quotation' => $quotation])->setPaper('a4');
        $filename = 'Surat_Jalan_'.$quotation->job->number.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF Surat Jalan '.$quotation->job->number, ['module' => 'document', 'record_id' => $quotation->job->id]);

        return $request->query('mode') === 'download' ? $pdf->download($filename) : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }

    public function tandaTerimaPdf(Request $request, Quotation $quotation, MasterDataService $master)
    {
        $quotation->load(['job.customer']);
        abort_unless($quotation->job, 404);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.tanda-terima', ['job' => $quotation->job, 'quotation' => $quotation])->setPaper('a4');
        $filename = 'Tanda_Terima_'.$quotation->job->number.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF Tanda Terima '.$quotation->job->number, ['module' => 'document', 'record_id' => $quotation->job->id]);

        return $request->query('mode') === 'download' ? $pdf->download($filename) : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }

    public function skDoPdf(Request $request, Quotation $quotation, MasterDataService $master)
    {
        $quotation->load(['job.customer']);
        abort_unless($quotation->job, 404);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.sk-do', ['job' => $quotation->job, 'quotation' => $quotation])->setPaper('a4');
        $filename = 'SK_DO_'.$quotation->job->number.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF SK DO '.$quotation->job->number, ['module' => 'document', 'record_id' => $quotation->job->id]);

        return $request->query('mode') === 'download' ? $pdf->download($filename) : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }

    public function dnpPdf(Request $request, Quotation $quotation, MasterDataService $master)
    {
        $quotation->load(['job.customer']);
        abort_unless($quotation->job, 404);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.dnp', ['job' => $quotation->job, 'quotation' => $quotation])->setPaper('a4');
        $filename = 'DNP_'.$quotation->job->number.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF DNP '.$quotation->job->number, ['module' => 'document', 'record_id' => $quotation->job->id]);

        return $request->query('mode') === 'download' ? $pdf->download($filename) : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }

    public function skPabeaPdf(Request $request, Quotation $quotation, MasterDataService $master)
    {
        $quotation->load(['job.customer']);
        abort_unless($quotation->job, 404);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.sk-pabean', ['job' => $quotation->job, 'quotation' => $quotation])->setPaper('a4');
        $filename = 'SK_Pabean_'.$quotation->job->number.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF SK Pabean '.$quotation->job->number, ['module' => 'document', 'record_id' => $quotation->job->id]);

        return $request->query('mode') === 'download' ? $pdf->download($filename) : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }

    public function jobSuratJalanPdf(Request $request, Job $job, MasterDataService $master)
    {
        $job->load(['customer', 'quotation']);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.surat-jalan', ['job' => $job, 'quotation' => $job->quotation])->setPaper('a4');
        $filename = 'Surat_Jalan_'.$job->number.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF Surat Jalan '.$job->number, ['module' => 'job_order', 'record_id' => $job->id]);

        return $request->query('mode') === 'download' ? $pdf->download($filename) : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }

    public function jobTandaTerimaPdf(Request $request, Job $job, MasterDataService $master)
    {
        $job->load(['customer', 'quotation']);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.tanda-terima', ['job' => $job, 'quotation' => $job->quotation])->setPaper('a4');
        $filename = 'Tanda_Terima_'.$job->number.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF Tanda Terima '.$job->number, ['module' => 'job_order', 'record_id' => $job->id]);

        return $request->query('mode') === 'download' ? $pdf->download($filename) : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }

    public function jobSkDoPdf(Request $request, Job $job, MasterDataService $master)
    {
        $job->load(['customer', 'quotation']);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.sk-do', ['job' => $job, 'quotation' => $job->quotation])->setPaper('a4');
        $filename = 'SK_DO_'.$job->number.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF SK DO '.$job->number, ['module' => 'job_order', 'record_id' => $job->id]);

        return $request->query('mode') === 'download' ? $pdf->download($filename) : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }

    public function jobDnpPdf(Request $request, Job $job, MasterDataService $master)
    {
        $job->load(['customer', 'quotation']);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.dnp', ['job' => $job, 'quotation' => $job->quotation])->setPaper('a4');
        $filename = 'DNP_'.$job->number.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF DNP '.$job->number, ['module' => 'job_order', 'record_id' => $job->id]);

        return $request->query('mode') === 'download' ? $pdf->download($filename) : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }

    public function jobSkPabeanPdf(Request $request, Job $job, MasterDataService $master)
    {
        $job->load(['customer', 'quotation']);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.sk-pabean', ['job' => $job, 'quotation' => $job->quotation])->setPaper('a4');
        $filename = 'SK_Pabean_'.$job->number.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF SK Pabean '.$job->number, ['module' => 'job_order', 'record_id' => $job->id]);

        return $request->query('mode') === 'download' ? $pdf->download($filename) : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }

    public function invoicePdf(Request $request, \App\Models\Invoice $invoice, MasterDataService $master)
    {
        $invoice->load(['items', 'job.customer', 'job.quotation']);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.invoice', ['invoice' => $invoice])->setPaper('a4');
        $filename = 'Invoice_'.$invoice->number.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF Invoice '.$invoice->number, ['module' => 'document', 'record_id' => $invoice->id]);

        return $request->query('mode') === 'download' ? $pdf->download($filename) : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }

    public function soaPdf(Request $request, string $customerId, MasterDataService $master)
    {
        $customer = \App\Models\Customer::withTrashed()->findOrFail($customerId);
        $service = app(\App\Services\StatementOfAccountService::class);
        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();
        $statement = $service->statement($customer, $from, $to);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.soa', ['customer' => $customer, 'statement' => $statement, 'from' => $from, 'to' => $to])->setPaper('a4');
        $filename = 'SOA_'.$customer->code.'_'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF SOA '.$customer->name, ['module' => 'document', 'record_id' => $customer->id]);

        return $request->query('mode') === 'download' ? $pdf->download($filename) : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }
}
