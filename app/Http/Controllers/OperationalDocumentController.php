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
        $perPage = min(max($request->integer('per_page', 10), 5), 50);

        $documents = Quotation::with(['customer', 'job.invoice', 'job.closingSnapshot'])
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
            ->paginate($perPage)
            ->withQueryString();

        $summary = [
            'total' => Quotation::count(),
            'draft' => Quotation::where('status', QuotationStatus::Draft)->count(),
            'approval' => Quotation::where('status', QuotationStatus::Submitted)->count(),
            'running' => Job::where('status', 'open')->count(),
            'done' => Job::where('status', 'closed')->count(),
        ];
        $customers = Customer::orderBy('name')->get(['id', 'name', 'code']);

        return view('documents.index', compact('documents', 'summary', 'customers', 'search', 'perPage'));
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
            'pdfUrl' => route('documents.quotation.pdf', ['quotation' => $quotation, 'mode' => 'inline']),
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

        return $request->query('mode') === 'download' ? $pdf->download($filename) : $pdf->stream($filename);
    }

    public function jobPdf(Request $request, Quotation $quotation, MasterDataService $master)
    {
        $quotation->load(['job.customer', 'job.quotation']);
        abort_unless($quotation->job, 404);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.job-order', ['job' => $quotation->job, 'quotation' => $quotation])->setPaper('a4');
        $filename = $quotation->job->number.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF job order '.$quotation->job->number, ['module' => 'document', 'record_id' => $quotation->job->id]);

        return $request->query('mode') === 'download' ? $pdf->download($filename) : $pdf->stream($filename);
    }
}
