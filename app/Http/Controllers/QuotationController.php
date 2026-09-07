<?php

namespace App\Http\Controllers;

use App\Enums\QuotationStatus;
use App\Http\Requests\QuotationRequest;
use App\Http\Requests\VersionRequest;
use App\Models\Customer;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $quotations = Quotation::with('customer')->when($search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', '%'.$search.'%')->orWhere('subject', 'like', '%'.$search.'%')->orWhereHas('customer', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))))
            ->when(QuotationStatus::tryFrom((string) $request->input('status')), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('id')->paginate(15)->withQueryString();

        return view('quotations.index', compact('quotations', 'search'));
    }

    public function create()
    {
        return view('quotations.form', ['quotation' => new Quotation, 'customers' => Customer::orderBy('name')->get(['id', 'code', 'name'])]);
    }

    public function store(QuotationRequest $request, QuotationService $service)
    {
        $quotation = $service->save(null, $request->validated(), $request->user());

        return redirect()->route('quotations.show', $quotation)->with('success', 'Draft quotation berhasil dibuat.');
    }

    public function show(Quotation $quotation)
    {
        Gate::authorize('view', $quotation);

        return view('quotations.show', ['quotation' => $quotation->load(['items', 'customer', 'job', 'creator', 'approver'])]);
    }

    public function edit(Quotation $quotation)
    {
        Gate::authorize('update', $quotation);

        return view('quotations.form', ['quotation' => $quotation->load('items'), 'customers' => Customer::orderBy('name')->get(['id', 'code', 'name'])]);
    }

    public function update(QuotationRequest $request, Quotation $quotation, QuotationService $service)
    {
        $quotation = $service->save($quotation, $request->validated(), $request->user());

        return redirect()->route('quotations.show', $quotation)->with('success', 'Draft quotation berhasil diperbarui.');
    }

    public function submit(VersionRequest $request, Quotation $quotation, QuotationService $service)
    {
        return $this->change($request, $quotation, $service, 'submit');
    }

    public function approve(VersionRequest $request, Quotation $quotation, QuotationService $service)
    {
        return $this->change($request, $quotation, $service, 'approve');
    }

    public function reject(VersionRequest $request, Quotation $quotation, QuotationService $service)
    {
        return $this->change($request, $quotation, $service, 'reject');
    }

    private function change(VersionRequest $request, Quotation $quotation, QuotationService $service, string $action)
    {
        $service->transition($quotation, $action, $request->validated(), $request->user());

        return redirect()->route('quotations.show', $quotation)->with('success', 'Status quotation berhasil diperbarui.');
    }

    public function convert(VersionRequest $request, Quotation $quotation, QuotationService $service)
    {
        $job = $service->convert($quotation,$request->validated(),$request->user());

        return redirect()->route('jobs.show',$job)->with('success','Quotation berhasil dikonversi menjadi Job Order Draft.');
    }
}
