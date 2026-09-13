<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClosingRequest;
use App\Models\Job;
use App\Services\JobClosingService;
use App\Services\JobCostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClosingController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 80);
        $jobs = Job::where('status', 'open')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('number', 'like', '%'.$search.'%')
                ->orWhere('subject', 'like', '%'.$search.'%')
                ->orWhere('quotation_snapshot->customer->name', 'like', '%'.$search.'%')))
            ->withCount(['costs', 'costs as draft_costs_count' => fn ($q) => $q->where('status', 'draft')])
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('closing.index', compact('jobs', 'search'));
    }

    public function create(Job $job, JobCostService $service)
    {
        Gate::authorize('jobs.close');

        return view('closing.create', ['job' => $job, 'summary' => $service->summary($job)['final']]);
    }

    public function store(ClosingRequest $request, Job $job, JobClosingService $service)
    {
        $invoice = $service->close($job, $request->validated(), $request->user());

        return redirect()->route('invoices.show', $invoice)->with('success', 'Job ditutup. Invoice dan jurnal seimbang berhasil dibuat.');
    }
}
