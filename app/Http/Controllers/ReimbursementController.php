<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReimbursementRequest;
use App\Models\Reimbursement;
use App\Models\User;
use App\Services\ReimbursementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReimbursementController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('reimbursements.manage');
        $query = Reimbursement::query()->with('employee')->latest('id');
        $status = $request->string('status')->toString();
        $category = $request->string('category')->toString();
        $search = mb_substr($request->string('search')->toString(), 0, 60);
        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($category !== '') {
            $query->where('category', $category);
        }
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('number', 'like', '%'.$search.'%')->orWhere('description', 'like', '%'.$search.'%')->orWhereHas('employee', fn ($e) => $e->where('name', 'like', '%'.$search.'%')));
        }
        if ($request->date('date_from')) {
            $query->whereDate('reimbursement_date', '>=', $request->date('date_from'));
        }
        if ($request->date('date_to')) {
            $query->whereDate('reimbursement_date', '<=', $request->date('date_to'));
        }
        $totals = Reimbursement::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->mapWithKeys(fn ($count, $key) => [$key => (int) $count])->all();

        return view('reimbursements.index', ['reimbursements' => $query->paginate(15)->withQueryString(), 'status' => $status, 'category' => $category, 'search' => $search, 'totals' => $totals]);
    }

    public function create()
    {
        Gate::authorize('reimbursements.manage');

        return view('reimbursements.form', ['employees' => User::query()->where('name', '<>', '')->orderBy('name')->get(['id', 'name'])]);
    }

    public function store(ReimbursementRequest $request, ReimbursementService $service)
    {
        $reimbursement = $service->create($request->validated(), $request->user());

        return redirect()->route('reimbursements.show', $reimbursement)->with('success', 'Reimbursement '.$reimbursement->number.' dicatat sebagai Menunggu persetujuan.');
    }

    public function show(Reimbursement $reimbursement)
    {
        Gate::authorize('reimbursements.manage');

        return view('reimbursements.show', ['reimbursement' => $reimbursement->load(['employee', 'createdBy', 'reviewedBy', 'fundingAccount', 'journals'])]);
    }

    public function approve(Request $request, Reimbursement $reimbursement, ReimbursementService $service)
    {
        $data = $this->validTransition($request);

        return $this->respond($service->approve($reimbursement, $data, $request->user()), 'Reimbursement '.$reimbursement->number.' disetujui.');
    }

    public function reject(Request $request, Reimbursement $reimbursement, ReimbursementService $service)
    {
        $data = $this->validTransition($request);

        return $this->respond($service->reject($reimbursement, $data, $request->user()), 'Reimbursement '.$reimbursement->number.' ditolak.');
    }

    public function pay(Request $request, Reimbursement $reimbursement, ReimbursementService $service)
    {
        $data = $request->validate([
            'lock_version' => ['required', 'integer'],
            'paid_date' => ['required', 'date'],
            'funding_account' => ['required', Rule::in(['cash', 'bank'])],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->respond($service->pay($reimbursement, $data, $request->user()), 'Reimbursement '.$reimbursement->number.' dibayar. Jurnal tercatat seimbang.');
    }

    private function validTransition(Request $request): array
    {
        return $request->validate([
            'lock_version' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function respond(Reimbursement $reimbursement, string $message)
    {
        return redirect()->route('reimbursements.show', $reimbursement)->with('success', $message);
    }
}
