<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClosingRequest;
use App\Models\Job;
use App\Services\JobClosingService;
use App\Services\JobCostService;
use Illuminate\Support\Facades\Gate;

class ClosingController extends Controller
{
    public function index()
    {
        return view('closing.index', ['jobs' => Job::where('status', 'open')->withCount(['costs', 'costs as draft_costs_count' => fn ($q) => $q->where('status', 'draft')])->latest('id')->paginate(15)]);
    }

    public function create(Job $job, JobCostService $service)
    {
        Gate::authorize('jobs.close');

        return view('closing.create', ['job' => $job, 'summary' => $service->summary($job)['final']]);
    }

    public function store(ClosingRequest $request, Job $job, JobClosingService $service)
    {
        $invoice = $service->close($job, $request->validated(), $request->user());

        return redirect()->route('invoices.show', $invoice)->with('success','Job ditutup. Invoice dan jurnal seimbang berhasil dibuat.');
    }
}
