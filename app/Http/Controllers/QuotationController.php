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
use App\Models\ServiceType;
use App\Models\TruckingPrice;
use App\Models\User;
use App\Models\Vendor;
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
        $serviceTypes = ServiceType::options();
        $actor = $request->user();
        $quotations = Quotation::with(['customer', 'creator'])
            ->when($actor?->hasRole('sales') && ! $actor->hasRole(['sales-manager', 'super-admin', 'admin']), fn ($q) => $q->where(fn ($q) => $q->where('sales_id', $actor->id)->orWhere(fn ($q) => $q->whereNull('sales_id')->where('created_by', $actor->id))))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', '%'.$search.'%')->orWhere('subject', 'like', '%'.$search.'%')->orWhereHas('customer', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when($salesId, fn ($q) => $q->where(fn ($q) => $q->where('sales_id', $salesId)->orWhere(fn ($q) => $q->whereNull('sales_id')->where('created_by', $salesId))))
            ->when($serviceType !== '' && in_array($serviceType, ServiceType::allowedKeys(), true), fn ($q) => $q->where('service_type', $serviceType))
            ->when($dateFrom, fn ($q) => $q->whereDate('quotation_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('quotation_date', '<=', $dateTo))
            ->latest('id')->paginate(min(100, max(5, (int) request('per_page', 10))))->withQueryString();

        return view('quotations.index', ['quotations' => $quotations, 'search' => $search, 'status' => $status, 'customerId' => $customerId, 'salesId' => $salesId, 'serviceType' => $serviceType, 'serviceTypes' => $serviceTypes, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'customers' => Customer::where('approval_status', 'approved')->orderBy('name')->get(['id', 'code', 'name', 'default_payment_terms']), 'sales' => User::whereHas('role', function ($q) {
            $q->whereIn('name', ['sales', 'sales-manager']);
        })->orderBy('name')->get(['id', 'name'])]);
    }

    public function create()
    {
        return view('quotations.form', [
            'quotation' => new Quotation,
            'customers' => Customer::where('approval_status', 'approved')->orderBy('name')->get(['id', 'code', 'name', 'default_payment_terms']),
            'sales' => $this->salesUsers(),
            'ports' => Port::orderBy('name')->get(['id', 'code', 'name']),
            'units' => ContainerUnit::where('is_active', true)->orderBy('name')->get(['name']),
            'containerUnits' => ContainerUnit::options(),
            'charges' => ChargeType::where('is_active', true)->orderBy('name')->get(['name']),
            'serviceTypes' => ServiceType::options(),
            'truckingVendors' => $this->truckingVendors(),
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

        $canViewCost = Gate::allows('financial.view') || Gate::allows('quotations.approve') || (auth()->user() && auth()->user()->hasRole(['sales-manager', 'super-admin', 'admin']));
        if (! $canViewCost) {
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
        $date = $quotation->quotation_date ? $quotation->quotation_date->format('d-m-Y') : now()->format('d-m-Y');
        $parts = explode('-', str_replace('/', '-', $quotation->number));
        $seq = end($parts);
        $filename = 'QUO_RDX_'.$seq.'_'.$date.'.pdf';
        
        return $pdf->download($filename);
    }

    public function edit(Quotation $quotation)
    {
        Gate::authorize('update', $quotation);

        return view('quotations.form', [
            'quotation' => $quotation->load('items'),
            'customers' => Customer::where('approval_status', 'approved')->orderBy('name')->get(['id', 'code', 'name', 'default_payment_terms']),
            'sales' => $this->salesUsers(),
            'ports' => Port::orderBy('name')->get(['id', 'code', 'name']),
            'units' => ContainerUnit::where('is_active', true)->orderBy('name')->get(['name']),
            'containerUnits' => ContainerUnit::options(),
            'charges' => ChargeType::where('is_active', true)->orderBy('name')->get(['name']),
            'serviceTypes' => ServiceType::options(),
            'truckingVendors' => $this->truckingVendors(),
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
        if ($quotation->status === QuotationStatus::Approved) {
            return redirect()->route('quotations.show', $quotation)->with('info', 'Quotation ini sudah berstatus disetujui.');
        }

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
            'pdfUrl'      => route('quotations.pdf', ['quotation' => $quotation, 'mode' => 'inline', 't' => time()]),
            'downloadUrl' => route('quotations.pdf', ['quotation' => $quotation, 'mode' => 'download']),
        ]);
    }

    public function pdf(Request $request, Quotation $quotation, MasterDataService $master)
    {
        Gate::authorize('view', $quotation);

        $quotation->load(['items', 'customer', 'creator', 'approver', 'sales']);
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.quotation', ['quotation' => $quotation])->setPaper('a4');
        $date = $quotation->quotation_date ? $quotation->quotation_date->format('d-m-Y') : now()->format('d-m-Y');
        $parts = explode('-', str_replace('/', '-', $quotation->number));
        $seq = end($parts);
        $filename = 'QUO_RDX_'.$seq.'_'.$date.'.pdf';
        $master->log($request->user(), 'document.generated', 'Mengunduh PDF quotation '.$quotation->number, ['module' => 'quotation', 'record_id' => $quotation->id]);

        return $request->query('mode') === 'download'
            ? $pdf->download($filename)
            : response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]);
    }

    private function salesUsers()
    {
        return User::whereHas('role', function ($q) {
            $q->whereIn('name', ['sales', 'sales-manager']);
        })->orderBy('name')->get(['id', 'name']);
    }

    private function truckingVendors()
    {
        return Vendor::where('is_active', true)
            ->where(function ($q) {
                $q->whereIn('type', ['trucking', 'both'])
                    ->orWhereHas('categories', fn ($c) => $c->whereIn('category', ['trucking', 'both']))
                    ->orWhereIn('id', TruckingPrice::select('vendor_id'));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    public function convert(VersionRequest $request, Quotation $quotation, QuotationService $service)
    {
        Gate::authorize('convert', $quotation);

        $job = $service->convert($quotation, $request->validated(), $request->user());

        return redirect()->route('jobs.show', $job)->with('success', 'Quotation berhasil dikonversi menjadi Job Order (Status: Open). Finance dapat langsung mengisi biaya operasional.');
    }
}
