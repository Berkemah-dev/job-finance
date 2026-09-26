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
        $category = in_array($request->input('category'), ['payment_request', 'reimbursement', 'debit_note', 'credit_note'], true) ? $request->input('category') : null;
        $scope = fn ($q) => $category ? $q->where('cost_category', $category) : $q;
        $jobs = Job::withCount([
            'costs' => $scope,
            'costs as draft_costs_count' => fn ($q) => $scope($q)->where('status', 'draft'),
            'costs as final_costs_count' => fn ($q) => $scope($q)->where('status', 'final'),
        ])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', '%'.$search.'%')->orWhere('subject', 'like', '%'.$search.'%')))
            ->when(in_array($request->input('status'), array_keys(config('operations.job_statuses')), true), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('id')->paginate(10)->withQueryString();

        return view('costs.overview', compact('jobs', 'search', 'category'));
    }

    public function index(Request $request, Job $job, JobCostService $service)
    {
        $costs = $job->costs()->with('creator')->when(in_array($request->input('status'), ['draft', 'final'], true), fn ($q) => $q->where('status', $request->input('status')))
            ->when(in_array($request->input('category'), ['payment_request', 'reimbursement', 'debit_note', 'credit_note'], true), fn ($q) => $q->where('cost_category', $request->input('category')))
            ->latest('id')->paginate(10)->withQueryString();

        return view('costs.index', ['job' => $job, 'costs' => $costs, 'summary' => $service->summary($job)]);
    }

    public function create(Job $job)
    {
        Gate::authorize('create', [JobCost::class, $job]);
        $chargeTypes = \App\Models\ChargeType::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $vendors = \App\Models\Vendor::where('is_active', true)->with('categories')->orderBy('name')->get();

        return view('costs.form', ['job' => $job, 'cost' => new JobCost, 'chargeTypes' => $chargeTypes, 'vendors' => $vendors]);
    }

    public function store(JobCostRequest $request, Job $job, JobCostService $service)
    {
        $cost = $service->save($job, null, $request->validated(), $request->user());

        return redirect()->route('jobs.costs.show', [$job, $cost])->with('success', 'Draft biaya berhasil disimpan. Periksa sebelum finalisasi.');
    }

    public function show(Job $job, JobCost $cost)
    {
        Gate::authorize('view', $cost);

        $bankAccounts = \App\Models\ChartOfAccount::where('type', 'asset')->where(fn ($q) => $q->where('name', 'like', '%Bank%')->orWhere('name', 'like', '%Kas%')->orWhere('name', 'like', '%Cash%'))->orderBy('code')->get();
        return view('costs.show', ['job' => $job, 'cost' => $cost->load(['creator', 'finalizer', 'paymentAccount']), 'bankAccounts' => $bankAccounts]);
    }

    public function edit(Job $job, JobCost $cost)
    {
        Gate::authorize('update', $cost);
        $chargeTypes = \App\Models\ChargeType::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $vendors = \App\Models\Vendor::where('is_active', true)->with('categories')->orderBy('name')->get();

        return view('costs.form', compact('job', 'cost', 'chargeTypes', 'vendors'));
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

    public function approve(CostVersionRequest $request, Job $job, JobCost $cost, JobCostService $service)
    {
        $service->approve($job, $cost, $request->validated(), $request->user());
        return redirect()->route('jobs.costs.show', [$job, $cost])->with('success', 'Transaksi disetujui dan siap ditutup bersama Job.');
    }

    public function pay(Request $request, Job $job, JobCost $cost, JobCostService $service)
    {
        $data = $request->validate(['job_version'=>['required','integer'],'paid_date'=>['required','date','before_or_equal:today'],'payment_account_id'=>['required','integer','exists:chart_of_accounts,id'],'pph23_amount'=>['nullable','numeric','min:0']]);
        $service->markPaid($job,$cost,$data,$request->user());
        return redirect()->route('jobs.costs.show',[$job,$cost])->with('success','Biaya ditandai PAID dan jurnal pembayaran dibuat.');
    }
}
