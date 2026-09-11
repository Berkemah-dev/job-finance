<?php

namespace App\Http\Controllers;

use App\Enums\QuotationStatus;
use App\Http\Requests\QuotationRequest;
use App\Http\Requests\VersionRequest;
use App\Models\Customer;
use App\Models\ContainerUnit;
use App\Models\ChargeType;
use App\Models\Port;
use App\Models\Quotation;
use App\Models\User;
use App\Services\MasterDataService;
use App\Services\QuotationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $status = QuotationStatus::tryFrom((string) $request->input('status'));
        $customerId = $request->integer('customer_id') ?: null;
        $salesId = $request->integer('sales_id') ?: null;
        $serviceType = (string) $request->input('service_type', '');
        $dateFrom = (string) $request->input('date_from', '');
        $dateTo = (string) $request->input('date_to', '');
        $quotations = Quotation::with(['customer', 'creator'])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', '%'.$search.'%')->orWhere('subject', 'like', '%'.$search.'%')->orWhereHas('customer', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when($salesId, fn ($q) => $q->where(fn ($q) => $q->where('sales_id', $salesId)->orWhere(fn ($q) => $q->whereNull('sales_id')->where('created_by', $salesId))))
            ->when($serviceType !== '' && in_array($serviceType, array_keys(config('operations.service_types')), true), fn ($q) => $q->where('service_type', $serviceType))
            ->when($dateFrom, fn ($q) => $q->whereDate('quotation_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('quotation_date', '<=', $dateTo))
            ->latest('id')->paginate(15)->withQueryString();

        return view('quotations.index', ['quotations' => $quotations, 'search' => $search, 'status' => $status, 'customerId' => $customerId, 'salesId' => $salesId, 'serviceType' => $serviceType, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'customers' => Customer::orderBy('name')->get(['id', 'code', 'name']), 'sales' => User::whereHas('role', function ($q) {
            $q->whereIn('name', ['sales', 'sales-manager']);
        })->orderBy('name')->get(['id', 'name'])]);
    }

    public function create()
    {
        return view('quotations.form', [
            'quotation' => new Quotation,
            'customers' => Customer::orderBy('name')->get(['id', 'code', 'name']),
            'sales' => $this->salesUsers(),
            'ports' => Port::orderBy('name')->get(['id', 'code', 'name']),
            'units' => ContainerUnit::where('is_active', true)->orderBy('name')->get(['name']),
            'charges' => ChargeType::where('is_active', true)->orderBy('name')->get(['name']),
        ]);
    }

    public function store(QuotationRequest $request, QuotationService $service)
    {
        $quotation = $service->save(null, $request->validated(), $request->user());

        return redirect()->route('quotations.show', $quotation)->with('success', 'Draft quotation berhasil dibuat.');
    }

    public function show(Quotation $quotation)
    {
        Gate::authorize('view', $quotation);

        $quotation->load(['items', 'customer', 'job', 'creator', 'approver', 'revisedBy', 'statusHistory.user']);

        if (! Gate::allows('financial.view')) {
            foreach ($quotation->items as $item) {
                $item->unit_cost = null;
            }
        }

        return view('quotations.show', ['quotation' => $quotation]);
    }

    public function print(Quotation $quotation)
    {
        Gate::authorize('view', $quotation);

        $quotation->load(['items', 'customer', 'creator']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('quotations.pdf', ['quotation' => $quotation]);
        
        return $pdf->download('Quotation_'.$quotation->number.'.pdf');
    }

    public function edit(Quotation $quotation)
    {
        Gate::authorize('update', $quotation);

        return view('quotations.form', [
            'quotation' => $quotation->load('items'),
            'customers' => Customer::orderBy('name')->get(['id', 'code', 'name']),
            'sales' => $this->salesUsers(),
            'ports' => Port::orderBy('name')->get(['id', 'code', 'name']),
            'units' => ContainerUnit::where('is_active', true)->orderBy('name')->get(['name']),
            'charges' => ChargeType::where('is_active', true)->orderBy('name')->get(['name']),
        ]);
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

    public function revise(VersionRequest $request, Quotation $quotation, QuotationService $service)
    {
        return $this->change($request, $quotation, $service, 'revise');
    }

    public function duplicate(Request $request, Quotation $quotation, QuotationService $service)
    {
        $copy = $service->duplicate($quotation, $request->user());

        return redirect()->route('quotations.edit', $copy)->with('success', 'Draft quotation disalin. Nomor <strong>'.$copy->number.'</strong> dihasilkan otomatis.');
    }

    private function change(VersionRequest $request, Quotation $quotation, QuotationService $service, string $action)
    {
        $service->transition($quotation, $action, $request->validated(), $request->user());

        return redirect()->route('quotations.show', $quotation)->with('success', 'Status quotation berhasil diperbarui.');
    }

    public function preview(Quotation $quotation)
    {
        Gate::authorize('view', $quotation);

        return view('documents.pdf-preview', [
            'title'       => 'Quotation '.$quotation->number,
            'backUrl'     => route('quotations.show', $quotation),
            'pdfUrl'      => route('quotations.pdf', ['quotation' => $quotation, 'mode' => 'inline']),
            'downloadUrl' => route('quotations.pdf', ['quotation' => $quotation, 'mode' => 'download']),
        ]);
    }

    public function pdf(Request $request, Quotation $quotation, MasterDataService $master)
    {
        Gate::authorize('view', $quotation);

        $quotation->load(['items', 'customer', 'creator', 'approver', 'sales']);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.quotation', ['quotation' => $quotation])->setPaper('a4');
        $filename = $quotation->number.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF quotation '.$quotation->number, ['module' => 'quotation', 'record_id' => $quotation->id]);

        return $request->query('mode') === 'download'
            ? $pdf->download($filename)
            : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }

    private function salesUsers()
    {
        return User::whereHas('role', function ($q) {
            $q->whereIn('name', ['sales', 'sales-manager']);
        })->orderBy('name')->get(['id', 'name']);
    }

    public function convert(VersionRequest $request, Quotation $quotation, QuotationService $service)
    {
        $job = $service->convert($quotation, $request->validated(), $request->user());

        return redirect()->route('jobs.show', $job)->with('success', 'Quotation berhasil dikonversi menjadi Job Order Draft.');
    }
}
