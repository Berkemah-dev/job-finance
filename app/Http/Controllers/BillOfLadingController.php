<?php

namespace App\Http\Controllers;

use App\Models\BillOfLading;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Port;
use App\Models\Vendor;
use Illuminate\Http\Request;

class BillOfLadingController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->query('search');
        $status   = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        $query = BillOfLading::with(['customer', 'job', 'creator'])
            ->latest('bl_date')
            ->latest('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('carrier', 'like', "%{$search}%")
                    ->orWhere('shipper_name', 'like', "%{$search}%")
                    ->orWhere('consignee_name', 'like', "%{$search}%")
                    ->orWhere('vessel_voyage', 'like', "%{$search}%")
                    ->orWhere('hbl_number', 'like', "%{$search}%")
                    ->orWhere('mbl_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('job', fn($jq) => $jq->where('number', 'like', "%{$search}%"));
            });
        }

        if ($status) $query->where('status', $status);
        if ($dateFrom) $query->whereDate('bl_date', '>=', $dateFrom);
        if ($dateTo) $query->whereDate('bl_date', '<=', $dateTo);

        $bls = $query->paginate(15)->withQueryString();

        return view('bills-of-lading.index', compact('bls', 'search', 'status', 'dateFrom', 'dateTo'));
    }

    public function create(Request $request)
    {
        $selectedJob = null;
        if ($jobId = $request->query('job_id')) {
            $selectedJob = Job::with(['customer', 'shippingInstructions', 'bookingConfirmations', 'billsOfLading'])->find($jobId);
            if ($selectedJob && $selectedJob->billsOfLading->isNotEmpty()) {
                return redirect()->to(route('jobs.show', $selectedJob->id).'#tab-bl')
                    ->with('warning', 'Job Order ini sudah memiliki Bill of Lading (' . $selectedJob->billsOfLading->first()->number . '). Dokumen hanya dapat dibuat 1 kali per Job Order.');
            }
        }

        $jobs = Job::with(['customer', 'shippingInstructions', 'bookingConfirmations'])->latest('id')->limit(50)->get();
        $customers = Customer::orderBy('name')->get();
        $ports = Port::orderBy('name')->get();

        $internationalAgents = Vendor::where(function ($q) {
            $q->where('type', 'international_agent')
              ->orWhereHas('categories', fn ($cq) => $cq->where('category', 'international_agent'));
        })->orderBy('name')->get();

        $shippingLines = Vendor::where(function ($q) {
            $q->where('type', 'shipping_line')
              ->orWhereHas('categories', fn ($cq) => $cq->where('category', 'shipping_line'));
        })->orderBy('name')->get();

        $defaultNumber = BillOfLading::generateNumber();

        return view('bills-of-lading.create', compact(
            'selectedJob',
            'jobs',
            'customers',
            'internationalAgents',
            'shippingLines',
            'ports',
            'defaultNumber'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'number'              => 'required|string|max:60|unique:bills_of_lading,number',
            'bl_date'             => 'required|date',
            'job_id'              => 'nullable|exists:jobs,id',
            'customer_id'         => 'nullable|exists:customers,id',
            'hbl_number'          => 'nullable|string|max:100',
            'mbl_number'          => 'nullable|string|max:100',
            'bl_type'             => 'nullable|string|max:50',
            'original_bl_count'   => 'nullable|integer|min:0',
            'place_of_issue'      => 'nullable|string|max:60',
            'date_of_issue'       => 'nullable|date',
            'shipped_on_board_date' => 'nullable|date',
            'freight_term'        => 'required|string|in:PREPAID,COLLECT',
            'freight_payable_at'  => 'nullable|string|max:60',
            'customer_ref_number' => 'nullable|string|max:100',
            'carrier'             => 'nullable|string|max:160',
            'carrier_bl_number'   => 'nullable|string|max:100',
            'shipper_name'        => 'nullable|string|max:160',
            'consignee_name'      => 'nullable|string|max:160',
            'notify_party'        => 'nullable|string',
            'agent_name'          => 'nullable|string|max:160',
            'is_switch_bl'        => 'nullable|boolean',
            'shipper_switch'      => 'nullable|string|max:160',
            'consignee_switch'    => 'nullable|string|max:160',
            'pre_carriage'        => 'nullable|string|max:160',
            'vessel_voyage'       => 'nullable|string|max:120',
            'etd'                 => 'nullable|date',
            'eta'                 => 'nullable|date',
            'pol'                 => 'nullable|string|max:120',
            'place_of_receipt'    => 'nullable|string|max:120',
            'pod'                 => 'nullable|string|max:120',
            'place_of_delivery'   => 'nullable|string|max:120',
            'final_destination'   => 'nullable|string|max:120',
            'party'               => 'nullable|string|max:100',
            'package_count'       => 'nullable|integer|min:0',
            'package_unit'        => 'nullable|string|max:50',
            'marks_numbers'       => 'nullable|string',
            'cargo_description'   => 'nullable|string',
            'gross_weight'        => 'nullable|numeric|min:0',
            'net_weight'          => 'nullable|numeric|min:0',
            'measurement'         => 'nullable|numeric|min:0',
            'remarks'             => 'nullable|string',
            'status'              => 'nullable|string|in:draft,issued,released,completed,cancelled',
        ]);

        if (empty($validated['bl_type'])) {
            $validated['bl_type'] = 'original';
        }

        if (!empty($validated['job_id'])) {
            $existingBl = BillOfLading::where('job_id', $validated['job_id'])->first();
            if ($existingBl) {
                return redirect()->to(route('jobs.show', $validated['job_id']) . '#tab-bl')
                    ->with('warning', 'Job Order ini sudah memiliki Bill of Lading (' . $existingBl->number . '). Dokumen hanya dapat dibuat 1 kali per Job Order.');
            }
        }

        if (empty($validated['status'])) {
            $validated['status'] = 'draft';
        }

        if (empty($validated['customer_id']) && !empty($validated['job_id'])) {
            $validated['customer_id'] = Job::find($validated['job_id'])?->customer_id;
        }

        if (empty($validated['hbl_number']) && !empty($validated['number'])) {
            $validated['hbl_number'] = $validated['number'];
        } elseif (empty($validated['number']) && !empty($validated['hbl_number'])) {
            $validated['number'] = $validated['hbl_number'];
        }

        if ($request->has('is_switch_bl') && ! $request->boolean('is_switch_bl')) {
            $validated['shipper_switch'] = null;
            $validated['consignee_switch'] = null;
        }
        unset($validated['is_switch_bl']);

        $validated['created_by'] = auth()->id();
        $bl = BillOfLading::create($validated);

        if (!empty($validated['job_id']) && !empty($bl->number)) {
            $parentJob = Job::find($validated['job_id']);
            if ($parentJob && empty($parentJob->hbl_number)) {
                $parentJob->update(['hbl_number' => $bl->number]);
            }
        }

        return redirect()->route('bills-of-lading.show', $bl)
            ->with('success', 'B/L ' . $bl->number . ' berhasil diterbitkan.');
    }

    public function show(BillOfLading $billOfLading)
    {
        $billOfLading->load(['customer', 'job', 'creator']);
        return view('bills-of-lading.show', ['bl' => $billOfLading]);
    }

    public function edit(BillOfLading $billOfLading)
    {
        $jobs = Job::with(['customer', 'shippingInstructions', 'bookingConfirmations'])->latest('id')->limit(50)->get();
        $customers = Customer::orderBy('name')->get();
        $ports = Port::orderBy('name')->get();

        $internationalAgents = Vendor::where(function ($q) {
            $q->where('type', 'international_agent')
              ->orWhereHas('categories', fn ($cq) => $cq->where('category', 'international_agent'));
        })->orderBy('name')->get();

        $shippingLines = Vendor::where(function ($q) {
            $q->where('type', 'shipping_line')
              ->orWhereHas('categories', fn ($cq) => $cq->where('category', 'shipping_line'));
        })->orderBy('name')->get();

        return view('bills-of-lading.edit', [
            'bl'                  => $billOfLading,
            'jobs'                => $jobs,
            'customers'           => $customers,
            'internationalAgents' => $internationalAgents,
            'shippingLines'       => $shippingLines,
            'ports'               => $ports,
        ]);
    }

    public function update(Request $request, BillOfLading $billOfLading)
    {
        $validated = $request->validate([
            'number'              => 'required|string|max:60|unique:bills_of_lading,number,' . $billOfLading->id,
            'bl_date'             => 'required|date',
            'job_id'              => 'nullable|exists:jobs,id',
            'customer_id'         => 'nullable|exists:customers,id',
            'hbl_number'          => 'nullable|string|max:100',
            'mbl_number'          => 'nullable|string|max:100',
            'bl_type'             => 'nullable|string|max:50',
            'original_bl_count'   => 'nullable|integer|min:0',
            'place_of_issue'      => 'nullable|string|max:60',
            'date_of_issue'       => 'nullable|date',
            'shipped_on_board_date' => 'nullable|date',
            'freight_term'        => 'required|string|in:PREPAID,COLLECT',
            'freight_payable_at'  => 'nullable|string|max:60',
            'customer_ref_number' => 'nullable|string|max:100',
            'carrier'             => 'nullable|string|max:160',
            'carrier_bl_number'   => 'nullable|string|max:100',
            'shipper_name'        => 'nullable|string|max:160',
            'consignee_name'      => 'nullable|string|max:160',
            'notify_party'        => 'nullable|string',
            'agent_name'          => 'nullable|string|max:160',
            'is_switch_bl'        => 'nullable|boolean',
            'shipper_switch'      => 'nullable|string|max:160',
            'consignee_switch'    => 'nullable|string|max:160',
            'pre_carriage'        => 'nullable|string|max:160',
            'vessel_voyage'       => 'nullable|string|max:120',
            'etd'                 => 'nullable|date',
            'eta'                 => 'nullable|date',
            'pol'                 => 'nullable|string|max:120',
            'place_of_receipt'    => 'nullable|string|max:120',
            'pod'                 => 'nullable|string|max:120',
            'place_of_delivery'   => 'nullable|string|max:120',
            'final_destination'   => 'nullable|string|max:120',
            'party'               => 'nullable|string|max:100',
            'package_count'       => 'nullable|integer|min:0',
            'package_unit'        => 'nullable|string|max:50',
            'marks_numbers'       => 'nullable|string',
            'cargo_description'   => 'nullable|string',
            'gross_weight'        => 'nullable|numeric|min:0',
            'net_weight'          => 'nullable|numeric|min:0',
            'measurement'         => 'nullable|numeric|min:0',
            'remarks'             => 'nullable|string',
            'status'              => 'nullable|string|in:draft,issued,released,completed,cancelled',
        ]);

        if (empty($validated['status'])) {
            $validated['status'] = $billOfLading->status ?? 'draft';
        }

        if (empty($validated['customer_id']) && !empty($validated['job_id'])) {
            $validated['customer_id'] = Job::find($validated['job_id'])?->customer_id;
        }

        if (empty($validated['hbl_number']) && !empty($validated['number'])) {
            $validated['hbl_number'] = $validated['number'];
        } elseif (empty($validated['number']) && !empty($validated['hbl_number'])) {
            $validated['number'] = $validated['hbl_number'];
        }

        if ($request->has('is_switch_bl') && ! $request->boolean('is_switch_bl')) {
            $validated['shipper_switch'] = null;
            $validated['consignee_switch'] = null;
        }
        unset($validated['is_switch_bl']);

        $billOfLading->update($validated);

        if (!empty($validated['job_id']) && !empty($billOfLading->number)) {
            $parentJob = Job::find($validated['job_id']);
            if ($parentJob && empty($parentJob->hbl_number)) {
                $parentJob->update(['hbl_number' => $billOfLading->number]);
            }
        }

        return redirect()->route('bills-of-lading.show', $billOfLading)
            ->with('success', 'B/L ' . $billOfLading->number . ' berhasil diperbarui.');
    }

    public function destroy(BillOfLading $billOfLading)
    {
        $jobId  = $billOfLading->job_id;
        $number = $billOfLading->number;
        $billOfLading->delete();

        if ($jobId) {
            return redirect()->to(route('jobs.show', $jobId) . '#tab-bl')
                ->with('success', 'B/L ' . $number . ' berhasil dihapus.');
        }

        return redirect()->route('bills-of-lading.index')
            ->with('success', 'B/L ' . $number . ' berhasil dihapus.');
    }

    public function preview(BillOfLading $billOfLading)
    {
        $type = request('type', $billOfLading->status === 'draft' ? 'draft' : 'original');

        return view('documents.pdf-preview', [
            'title'       => 'Bill of Lading ' . $billOfLading->number,
            'backUrl'     => route('bills-of-lading.show', $billOfLading),
            'pdfUrl'      => route('bills-of-lading.pdf', ['billOfLading' => $billOfLading, 'type' => $type, 'mode' => 'inline', 't' => time()]),
            'downloadUrl' => route('bills-of-lading.pdf', ['billOfLading' => $billOfLading, 'type' => $type, 'mode' => 'download']),
        ]);
    }

    public function pdf(Request $request, BillOfLading $billOfLading)
    {
        $billOfLading->load(['job.customer', 'customer']);
        $type = strtolower($request->query('type', $billOfLading->status === 'draft' ? 'draft' : 'original'));
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.bill-of-lading', [
            'bl'   => $billOfLading,
            'type' => $type,
        ])->setPaper('a4', 'portrait');

        $filename = 'BL_' . str_replace(['/', '\\'], '-', $billOfLading->number) . '_' . strtoupper($type) . '.pdf';

        return $request->query('mode') === 'download'
            ? $pdf->download($filename)
            : response($pdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'              => 'no-cache',
            ]);
    }
}
