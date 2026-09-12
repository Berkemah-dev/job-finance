<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Job;
use App\Models\Port;
use App\Models\ShippingInstruction;
use App\Models\Vendor;
use Illuminate\Http\Request;

class ShippingInstructionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = ShippingInstruction::with(['customer', 'job', 'creator'])
            ->latest('si_date')
            ->latest('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('to_carrier', 'like', "%{$search}%")
                    ->orWhere('shipper_name', 'like', "%{$search}%")
                    ->orWhere('consignee_name', 'like', "%{$search}%")
                    ->orWhere('vessel_voyage', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('job', function ($jq) use ($search) {
                        $jq->where('number', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->whereDate('si_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('si_date', '<=', $dateTo);
        }

        $shippingInstructions = $query->paginate(10)->withQueryString();

        return view('shipping-instructions.index', [
            'shippingInstructions' => $shippingInstructions,
            'search'               => $search,
            'status'               => $status,
            'dateFrom'             => $dateFrom,
            'dateTo'               => $dateTo,
        ]);
    }

    public function create(Request $request)
    {
        $selectedJob = null;
        if ($jobId = $request->query('job_id')) {
            $selectedJob = Job::with(['customer', 'quotation'])->find($jobId);
        }

        $jobs = Job::with('customer')->latest('id')->limit(50)->get();
        $customers = Customer::orderBy('name')->get();
        $carriers = Vendor::orderBy('name')->get();
        $ports = Port::orderBy('name')->get();

        $defaultNumber = ShippingInstruction::generateNumber();

        return view('shipping-instructions.create', [
            'selectedJob'   => $selectedJob,
            'jobs'          => $jobs,
            'customers'     => $customers,
            'carriers'      => $carriers,
            'ports'         => $ports,
            'defaultNumber' => $defaultNumber,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'number'            => 'required|string|max:60|unique:shipping_instructions,number',
            'si_date'           => 'required|date',
            'job_id'            => 'nullable|exists:jobs,id',
            'customer_id'       => 'nullable|exists:customers,id',
            'to_carrier'        => 'required|string|max:160',
            'carrier_attn'      => 'nullable|string|max:120',
            'carrier_contact'   => 'nullable|string|max:120',
            'shipper_name'      => 'required|string|max:160',
            'shipper_address'   => 'nullable|string',
            'consignee_name'    => 'required|string|max:160',
            'consignee_address' => 'nullable|string',
            'notify_party'      => 'nullable|string',
            'vessel_voyage'     => 'nullable|string|max:120',
            'connecting_vessel' => 'nullable|string|max:120',
            'etd'               => 'nullable|date',
            'eta'               => 'nullable|date',
            'shipment_term'     => 'required|string|in:PREPAID,COLLECT',
            'pol'               => 'required|string|max:120',
            'pod'               => 'required|string|max:120',
            'marks_numbers'     => 'nullable|string',
            'cargo_description' => 'required|string',
            'gross_weight'      => 'nullable|numeric|min:0',
            'net_weight'        => 'nullable|numeric|min:0',
            'measurement'       => 'nullable|numeric|min:0',
            'remarks'           => 'nullable|string',
            'status'            => 'required|string|in:draft,submitted,completed,cancelled',
        ]);

        $validated['created_by'] = auth()->id();

        $si = ShippingInstruction::create($validated);

        return redirect()->route('shipping-instructions.show', $si)
            ->with('success', 'Shipping Instruction ' . $si->number . ' berhasil diterbitkan.');
    }

    public function show(ShippingInstruction $shippingInstruction)
    {
        $shippingInstruction->load(['customer', 'job', 'creator']);

        return view('shipping-instructions.show', [
            'si'  => $shippingInstruction,
            'job' => $shippingInstruction->job,
        ]);
    }

    public function edit(ShippingInstruction $shippingInstruction)
    {
        $jobs = Job::with('customer')->latest('id')->limit(50)->get();
        $customers = Customer::orderBy('name')->get();
        $carriers = Vendor::orderBy('name')->get();
        $ports = Port::orderBy('name')->get();

        return view('shipping-instructions.edit', [
            'si'        => $shippingInstruction,
            'jobs'      => $jobs,
            'customers' => $customers,
            'carriers'  => $carriers,
            'ports'     => $ports,
        ]);
    }

    public function update(Request $request, ShippingInstruction $shippingInstruction)
    {
        $validated = $request->validate([
            'number'            => 'required|string|max:60|unique:shipping_instructions,number,' . $shippingInstruction->id,
            'si_date'           => 'required|date',
            'job_id'            => 'nullable|exists:jobs,id',
            'customer_id'       => 'nullable|exists:customers,id',
            'to_carrier'        => 'required|string|max:160',
            'carrier_attn'      => 'nullable|string|max:120',
            'carrier_contact'   => 'nullable|string|max:120',
            'shipper_name'      => 'required|string|max:160',
            'shipper_address'   => 'nullable|string',
            'consignee_name'    => 'required|string|max:160',
            'consignee_address' => 'nullable|string',
            'notify_party'      => 'nullable|string',
            'vessel_voyage'     => 'nullable|string|max:120',
            'connecting_vessel' => 'nullable|string|max:120',
            'etd'               => 'nullable|date',
            'eta'               => 'nullable|date',
            'shipment_term'     => 'required|string|in:PREPAID,COLLECT',
            'pol'               => 'required|string|max:120',
            'pod'               => 'required|string|max:120',
            'marks_numbers'     => 'nullable|string',
            'cargo_description' => 'required|string',
            'gross_weight'      => 'nullable|numeric|min:0',
            'net_weight'        => 'nullable|numeric|min:0',
            'measurement'       => 'nullable|numeric|min:0',
            'remarks'           => 'nullable|string',
            'status'            => 'required|string|in:draft,submitted,completed,cancelled',
        ]);

        $shippingInstruction->update($validated);

        return redirect()->route('shipping-instructions.show', $shippingInstruction)
            ->with('success', 'Shipping Instruction ' . $shippingInstruction->number . ' berhasil diperbarui.');
    }

    public function destroy(ShippingInstruction $shippingInstruction)
    {
        $number = $shippingInstruction->number;
        $shippingInstruction->delete();

        return redirect()->route('shipping-instructions.index')
            ->with('success', 'Shipping Instruction ' . $number . ' berhasil dihapus.');
    }

    public function preview(ShippingInstruction $shippingInstruction)
    {
        return view('documents.pdf-preview', [
            'title'       => 'Shipping Instruction ' . $shippingInstruction->number,
            'backUrl'     => route('shipping-instructions.show', $shippingInstruction),
            'pdfUrl'      => route('shipping-instructions.pdf', ['shippingInstruction' => $shippingInstruction, 'mode' => 'inline']),
            'downloadUrl' => route('shipping-instructions.pdf', ['shippingInstruction' => $shippingInstruction, 'mode' => 'download']),
        ]);
    }

    public function pdf(Request $request, ShippingInstruction $shippingInstruction)
    {
        $shippingInstruction->load(['customer', 'job', 'creator']);

        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.shipping-instruction', [
            'si'  => $shippingInstruction,
            'job' => $shippingInstruction->job,
        ])->setPaper('a4');

        $filename = 'SHIPPING_INSTRUCTION_' . $shippingInstruction->number . '.pdf';

        return $request->query('mode') === 'download'
            ? $pdf->download($filename)
            : response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }
}
