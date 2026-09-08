<?php

namespace App\Http\Controllers;

use App\Http\Requests\CostVersionRequest;
use App\Http\Requests\JobCostRequest;
use App\Models\Job;
use App\Models\JobCost;
use App\Services\JobCostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class JobCostController extends Controller
{
    public function overview(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $jobs = Job::withCount(['costs', 'costs as draft_costs_count' => fn ($q) => $q->where('status', 'draft'), 'costs as final_costs_count' => fn ($q) => $q->where('status', 'final')])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', '%'.$search.'%')->orWhere('subject', 'like', '%'.$search.'%')))
            ->when(in_array($request->input('status'), array_keys(config('operations.job_statuses')), true), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('id')->paginate(15)->withQueryString();

        return view('costs.overview', compact('jobs', 'search'));
    }

    public function index(Request $request, Job $job, JobCostService $service)
    {
        $costs = $job->costs()->with('creator')->when(in_array($request->input('status'), ['draft', 'final'], true), fn ($q) => $q->where('status', $request->input('status')))
            ->when(in_array($request->input('type'), ['temporary', 'provision'], true), fn ($q) => $q->where('type', $request->input('type')))
            ->latest('id')->paginate(15)->withQueryString();

        return view('costs.index', ['job' => $job, 'costs' => $costs, 'summary' => $service->summary($job)]);
    }

    public function create(Job $job)
    {
        Gate::authorize('create', [JobCost::class, $job]);

        return view('costs.form', ['job' => $job, 'cost' => new JobCost]);
    }

    public function store(JobCostRequest $request, Job $job, JobCostService $service)
    {
        $cost = $service->save($job, null, $request->validated(), $request->user());

        return redirect()->route('jobs.costs.show', [$job, $cost])->with('success', 'Draft biaya berhasil disimpan. Periksa sebelum finalisasi.');
    }

    public function show(Job $job, JobCost $cost)
    {
        Gate::authorize('view', $cost);

        return view('costs.show', ['job' => $job, 'cost' => $cost->load(['creator', 'finalizer'])]);
    }

    public function edit(Job $job, JobCost $cost)
    {
        Gate::authorize('update', $cost);

        return view('costs.form', compact('job', 'cost'));
    }

    public function update(JobCostRequest $request, Job $job, JobCost $cost, JobCostService $service)
    {
        $cost = $service->save($job, $cost, $request->validated(), $request->user());

        return redirect()->route('jobs.costs.show', [$job, $cost])->with('success', 'Draft biaya berhasil diperbarui.');
    }

    public function destroy(CostVersionRequest $request, Job $job, JobCost $cost, JobCostService $service)
    {
        $service->delete($job, $cost, $request->validated(), $request->user());

        return redirect()->route('jobs.costs.index', $job)->with('success', 'Draft biaya dihapus dari perhitungan. Riwayat tetap tersimpan.');
    }

    public function finalize(CostVersionRequest $request, Job $job, JobCost $cost, JobCostService $service)
    {
        $service->finalize($job, $cost, $request->validated(), $request->user());

        return redirect()->route('jobs.costs.show', [$job, $cost])->with('success', 'Biaya berhasil difinalisasi dan dikunci.');
    }
}
