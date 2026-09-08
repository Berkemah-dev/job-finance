<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobRequest;
use App\Http\Requests\VersionRequest;
use App\Models\Job;
use App\Services\JobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $jobs = Job::with('customer')->when($search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', '%'.$search.'%')->orWhere('subject', 'like', '%'.$search.'%')))
            ->when(in_array($request->input('status'), array_keys(config('operations.job_statuses')), true), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('id')->paginate(15)->withQueryString();

        return view('jobs.index', compact('jobs', 'search'));
    }

    public function show(Job $job)
    {
        return view('jobs.show', ['job' => $job->load(['quotation', 'customer'])]);
    }

    public function edit(Job $job)
    {
        Gate::authorize('update', $job);

        return view('jobs.form', compact('job'));
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
