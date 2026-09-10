<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobRequest;
use App\Http\Requests\VersionRequest;
use App\Models\Job;
use App\Models\User;
use App\Services\JobCostService;
use App\Services\JobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $salesId = $request->integer('sales_id') ?: null;
        $csId = $request->integer('cs_id') ?: null;
        $serviceType = (string) $request->input('service_type', '');
        $dateFrom = (string) $request->input('date_from', '');
        $dateTo = (string) $request->input('date_to', '');
        $jobs = Job::with(['customer', 'sales', 'cs'])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', '%'.$search.'%')->orWhere('subject', 'like', '%'.$search.'%')->orWhereHas('customer', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))))
            ->when(in_array($request->input('status'), array_keys(config('operations.job_statuses')), true), fn ($q) => $q->where('status', $request->input('status')))
            ->when($salesId, fn ($q) => $q->where('sales_id', $salesId))
            ->when($csId, fn ($q) => $q->where('cs_id', $csId))
            ->when($serviceType !== '' && in_array($serviceType, array_keys(config('operations.service_types')), true), fn ($q) => $q->where('service_type', $serviceType))
            ->when($dateFrom, fn ($q) => $q->whereDate('job_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('job_date', '<=', $dateTo))
            ->latest('id')->paginate(15)->withQueryString();

        return view('jobs.index', ['jobs' => $jobs, 'search' => $search, 'salesId' => $salesId, 'csId' => $csId, 'serviceType' => $serviceType, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'assignees' => User::whereHas('role', function ($q) {
            $q->whereIn('name', ['sales', 'sales-manager', 'customer-service']);
        })->orderBy('name')->get(['id', 'name'])]);
    }

    public function show(Job $job, JobCostService $costs)
    {
        $job->load(['quotation', 'customer', 'sales', 'cs', 'statusHistory.user', 'documents.documentType', 'documents.uploader']);
        $documentTypes = \App\Models\DocumentType::active()->orderBy('sort_order')->get();

        return view('jobs.show', ['job' => $job, 'summary' => $costs->summary($job)['final'], 'documentTypes' => $documentTypes]);
    }

    public function edit(Job $job)
    {
        Gate::authorize('update', $job);

        return view('jobs.form', ['job' => $job->load(['sales', 'cs']), 'assignees' => User::whereHas('role', function ($q) {
            $q->whereIn('name', ['sales', 'sales-manager', 'customer-service']);
        })->orderBy('name')->get(['id', 'name'])]);
    }

    public function update(JobRequest $request, Job $job, JobService $service)
    {
        $service->update($job, $request->validated(), $request->user());

        return redirect()->route('jobs.show', $job)->with('success', 'Data operasional berhasil diperbarui.');
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
}
