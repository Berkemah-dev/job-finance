<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobRequest;
use App\Http\Requests\ShipmentStatusRequest;
use App\Http\Requests\VersionRequest;
use App\Models\Job;
use App\Models\ServiceType;
use App\Models\ContainerUnit;
use App\Models\User;
use App\Services\JobCostService;
use App\Services\JobService;
use App\Services\MasterDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $salesId = $request->integer('sales_id') ?: null;
        $csId = $request->integer('cs_id') ?: null;
        $serviceType = (string) $request->input('service_type', '');
        $shipmentStatus = (string) $request->input('shipment_status', '');
        $dateFrom = (string) $request->input('date_from', '');
        $dateTo = (string) $request->input('date_to', '');
        $serviceTypes = ServiceType::options();
        $jobs = Job::with(['customer', 'sales', 'cs', 'quotation', 'bookingConfirmations', 'documents.documentType'])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', '%'.$search.'%')->orWhere('subject', 'like', '%'.$search.'%')->orWhereHas('customer', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))))
            ->when(in_array($request->input('status'), array_keys(config('operations.job_statuses')), true), fn ($q) => $q->where('status', $request->input('status')))
            ->when($salesId, fn ($q) => $q->where('sales_id', $salesId))
            ->when($csId, fn ($q) => $q->where('cs_id', $csId))
            ->when($serviceType !== '' && in_array($serviceType, ServiceType::allowedKeys(), true), fn ($q) => $q->where('service_type', $serviceType))
            ->when($shipmentStatus !== '' && array_key_exists($shipmentStatus, config('operations.shipment_statuses')), fn ($q) => $q->where('shipment_status', $shipmentStatus))
            ->when($dateFrom, fn ($q) => $q->whereDate('job_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('job_date', '<=', $dateTo))
            ->latest('id')->paginate(10)->withQueryString();

        return view('jobs.index', ['jobs' => $jobs, 'search' => $search, 'salesId' => $salesId, 'csId' => $csId, 'serviceType' => $serviceType, 'serviceTypes' => $serviceTypes, 'shipmentStatus' => $shipmentStatus, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'assignees' => User::whereHas('role', function ($q) {
            $q->whereIn('name', ['sales', 'sales-manager', 'customer-service']);
        })->orderBy('name')->get(['id', 'name'])]);
    }

    public function show(Job $job, JobCostService $costs)
    {
        $job->load([
            'quotation', 'customer', 'sales', 'cs', 'bookingConfirmations', 'shippingInstructions', 'statusHistory.user', 
            'shipmentStatusHistory.user', 'documents.documentType', 'documents.uploader'
        ]);
        $documentTypes = \App\Models\DocumentType::active()->forService($job->service_type)->orderBy('sort_order')->get();
        $summary = [];

        if (Gate::allows('financial.view')) {
            $summary = $costs->summary($job)['final'];
        } else {
            $snapshot = $job->quotation_snapshot;
            $snapshot['items'] = array_map(fn ($item) => Arr::except($item, ['unit_cost', 'total_cost']), $snapshot['items'] ?? []);
            $snapshot['totals'] = Arr::except($snapshot['totals'] ?? [], ['profit']);
            $job->quotation_snapshot = $snapshot;
        }

        return view('jobs.show', ['job' => $job, 'summary' => $summary, 'documentTypes' => $documentTypes]);
    }

    public function edit(Job $job)
    {
        Gate::authorize('update', $job);

        return view('jobs.form', ['job' => $job->load(['sales', 'cs']), 'serviceTypes' => ServiceType::options(), 'containerUnits' => ContainerUnit::options(), 'assignees' => User::whereHas('role', function ($q) {
            $q->whereIn('name', ['sales', 'sales-manager', 'customer-service']);
        })->orderBy('name')->get(['id', 'name'])]);
    }

    public function update(JobRequest $request, Job $job, JobService $service)
    {
        $service->update($job, $request->validated(), $request->user());

        return redirect()->to(route('jobs.show', $job).'#tab-shipping')->with('success', 'Data operasional berhasil diperbarui.');
    }

    public function open(VersionRequest $request, Job $job, JobService $service)
    {
        $service->transition($job, 'open', $request->validated(), $request->user());

        return redirect()->route('jobs.show', $job)->with('success', 'Job berhasil dibuka. Finance dapat mulai mencatat biaya.');
    }

    public function cancel(VersionRequest $request, Job $job, JobService $service)
    {
        $service->transition($job, 'cancel', $request->validated(), $request->user());

        return redirect()->route('jobs.show', $job)->with('success', 'Job berhasil dibatalkan.');
    }

    public function confirmDo(Request $request, Job $job, JobService $service)
    {
        $service->confirmDo($job, $request->user());

        return redirect()->route('jobs.show', $job)->with('success', 'DO Selesai berhasil dikonfirmasi.');
    }

    public function shipmentStatus(ShipmentStatusRequest $request, Job $job, JobService $service)
    {
        $service->updateShipmentStatus($request->validated(), $job, $request->user());

        return redirect()->route('jobs.show', $job)->with('success', 'Status pengiriman berhasil diperbarui.');
    }

    public function preview(Job $job)
    {
        Gate::authorize('view', $job);

        return view('documents.pdf-preview', [
            'title'       => 'Job Order ' . $job->number,
            'backUrl'     => route('jobs.show', $job),
            'pdfUrl'      => route('jobs.pdf', ['job' => $job, 'mode' => 'inline']),
            'downloadUrl' => route('jobs.pdf', ['job' => $job, 'mode' => 'download']),
        ]);
    }

    public function pdf(Request $request, Job $job, MasterDataService $master)
    {
        Gate::authorize('view', $job);

        $job->load(['customer', 'sales', 'cs', 'quotation']);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.job-order', [
            'job'       => $job,
            'quotation' => $job->quotation,
        ])->setPaper('a4');
        $filename = $job->number . '.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF Job Order ' . $job->number, ['module' => 'job_order', 'record_id' => $job->id]);

        return $request->query('mode') === 'download'
            ? $pdf->download($filename)
            : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }
}
