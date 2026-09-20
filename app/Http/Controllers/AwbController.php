<?php

namespace App\Http\Controllers;

use App\Models\Awb;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Vendor;
use Illuminate\Http\Request;

class AwbController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->query('search');
        $status   = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        $query = Awb::with(['customer', 'job', 'creator'])
            ->latest('awb_date')
            ->latest('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('airline', 'like', "%{$search}%")
                    ->orWhere('hawb_number', 'like', "%{$search}%")
                    ->orWhere('mawb_number', 'like', "%{$search}%")
                    ->orWhere('shipper_on_hawb', 'like', "%{$search}%")
                    ->orWhere('consignee_on_hawb', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('job', fn($jq) => $jq->where('number', 'like', "%{$search}%"));
            });
        }

        if ($status) $query->where('status', $status);
        if ($dateFrom) $query->whereDate('awb_date', '>=', $dateFrom);
        if ($dateTo) $query->whereDate('awb_date', '<=', $dateTo);

        $awbs = $query->paginate(15)->withQueryString();

        return view('awbs.index', compact('awbs', 'search', 'status', 'dateFrom', 'dateTo'));
    }

    public function create(Request $request)
    {
        $selectedJob = null;
        if ($jobId = $request->query('job_id')) {
            $selectedJob = Job::with(['customer', 'shippingInstructions', 'awbs'])->find($jobId);
            if ($selectedJob && $selectedJob->awbs->isNotEmpty()) {
                return redirect()->to(route('jobs.show', $selectedJob->id).'#tab-awb')
                    ->with('warning', 'Job Order ini sudah memiliki Air Waybill (' . $selectedJob->awbs->first()->number . '). Dokumen hanya dapat dibuat 1 kali per Job Order.');
            }
        }

        $jobs      = Job::with(['customer', 'shippingInstructions'])->latest('id')->limit(50)->get();
        $customers = Customer::orderBy('name')->get();
        $airlines  = Vendor::orderBy('name')->get();

        $defaultNumber = Awb::generateNumber();

        return view('awbs.create', compact('selectedJob', 'jobs', 'customers', 'airlines', 'defaultNumber'));
    }

    public function store(Request $request)
    {
        $decimalFields = ['exchange_rate', 'gross_weight', 'chargeable_weight', 'volume'];
        foreach ($decimalFields as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $request->merge([$field => str_replace(',', '.', trim($request->input($field)))]);
            }
        }

        $validated = $request->validate([
            'number'                 => 'nullable|string|max:60|unique:awbs,number',
            'awb_date'               => 'required|date',
            'job_id'                 => 'nullable|exists:jobs,id',
            'customer_id'            => 'nullable|exists:customers,id',
            'hawb_number'            => 'nullable|string|max:100',
            'mawb_number'            => 'nullable|string|max:100',
            'freight_term'           => 'required|string|in:PREPAID,COLLECT',
            'currency'               => 'nullable|string|max:10',
            'exchange_rate'          => 'nullable|numeric|min:0',
            'shipper_on_hawb'        => 'nullable|string|max:160',
            'shipper_on_mawb'        => 'nullable|string|max:160',
            'consignee_on_hawb'      => 'nullable|string|max:160',
            'consignee_on_mawb'      => 'nullable|string|max:160',
            'notify_party'           => 'nullable|string',
            'agent_name'             => 'nullable|string|max:160',
            'flight_number'          => 'nullable|string|max:60',
            'flight_date'            => 'nullable|date',
            'connecting_flight'      => 'nullable|string|max:60',
            'connecting_flight_date' => 'nullable|date',
            'etd'                    => 'nullable|date',
            'eta'                    => 'nullable|date',
            'airport_of_departure'   => 'nullable|string|max:120',
            'transit_airport'        => 'nullable|string|max:120',
            'airport_of_destination' => 'nullable|string|max:120',
            'airline'                => 'nullable|string|max:160',
            'airline_code'           => 'nullable|string|max:20',
            'account_number'         => 'nullable|string|max:100',
            'value_of_carriage'      => 'nullable|string|max:60',
            'value_of_customs'       => 'nullable|string|max:60',
            'pieces'                 => 'nullable|integer|min:0',
            'gross_weight'           => 'nullable|numeric|min:0',
            'gross_weight_unit'      => 'nullable|string|max:20',
            'chargeable_weight'      => 'nullable|numeric|min:0',
            'volume'                 => 'nullable|numeric|min:0',
            'commodity'              => 'nullable|string',
            'remarks'                => 'nullable|string',
            'status'                 => 'required|string|in:draft,issued,completed,cancelled',
        ]);

        if (empty($validated['number'])) {
            $validated['number'] = Awb::generateNumber();
        }

        $validated['created_by'] = auth()->id();
        $awb = Awb::create($validated);

        return redirect()->route('awbs.show', $awb)
            ->with('success', 'AWB ' . $awb->number . ' berhasil diterbitkan.');
    }

    public function show(Awb $awb)
    {
        $awb->load(['customer', 'job', 'creator']);
        return view('awbs.show', compact('awb'));
    }

    public function edit(Awb $awb)
    {
        $jobs      = Job::with(['customer', 'shippingInstructions'])->latest('id')->limit(50)->get();
        $customers = Customer::orderBy('name')->get();
        $airlines  = Vendor::orderBy('name')->get();

        return view('awbs.edit', compact('awb', 'jobs', 'customers', 'airlines'));
    }

    public function update(Request $request, Awb $awb)
    {
        $decimalFields = ['exchange_rate', 'gross_weight', 'chargeable_weight', 'volume'];
        foreach ($decimalFields as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $request->merge([$field => str_replace(',', '.', trim($request->input($field)))]);
            }
        }

        $validated = $request->validate([
            'number'                 => 'required|string|max:60|unique:awbs,number,' . $awb->id,
            'awb_date'               => 'required|date',
            'job_id'                 => 'nullable|exists:jobs,id',
            'customer_id'            => 'nullable|exists:customers,id',
            'hawb_number'            => 'nullable|string|max:100',
            'mawb_number'            => 'nullable|string|max:100',
            'freight_term'           => 'required|string|in:PREPAID,COLLECT',
            'currency'               => 'nullable|string|max:10',
            'exchange_rate'          => 'nullable|numeric|min:0',
            'shipper_on_hawb'        => 'nullable|string|max:160',
            'shipper_on_mawb'        => 'nullable|string|max:160',
            'consignee_on_hawb'      => 'nullable|string|max:160',
            'consignee_on_mawb'      => 'nullable|string|max:160',
            'notify_party'           => 'nullable|string',
            'agent_name'             => 'nullable|string|max:160',
            'flight_number'          => 'nullable|string|max:60',
            'flight_date'            => 'nullable|date',
            'connecting_flight'      => 'nullable|string|max:60',
            'connecting_flight_date' => 'nullable|date',
            'etd'                    => 'nullable|date',
            'eta'                    => 'nullable|date',
            'airport_of_departure'   => 'nullable|string|max:120',
            'transit_airport'        => 'nullable|string|max:120',
            'airport_of_destination' => 'nullable|string|max:120',
            'airline'                => 'nullable|string|max:160',
            'airline_code'           => 'nullable|string|max:20',
            'account_number'         => 'nullable|string|max:100',
            'value_of_carriage'      => 'nullable|string|max:60',
            'value_of_customs'       => 'nullable|string|max:60',
            'pieces'                 => 'nullable|integer|min:0',
            'gross_weight'           => 'nullable|numeric|min:0',
            'gross_weight_unit'      => 'nullable|string|max:20',
            'chargeable_weight'      => 'nullable|numeric|min:0',
            'volume'                 => 'nullable|numeric|min:0',
            'commodity'              => 'nullable|string',
            'remarks'                => 'nullable|string',
            'status'                 => 'required|string|in:draft,issued,completed,cancelled',
        ]);

        $awb->update($validated);

        return redirect()->route('awbs.show', $awb)
            ->with('success', 'AWB ' . $awb->number . ' berhasil diperbarui.');
    }

    public function destroy(Awb $awb)
    {
        $jobId  = $awb->job_id;
        $number = $awb->number;
        $awb->delete();

        if ($jobId) {
            return redirect()->to(route('jobs.show', $jobId) . '#tab-awb')
                ->with('success', 'AWB ' . $number . ' berhasil dihapus.');
        }

        return redirect()->route('awbs.index')
            ->with('success', 'AWB ' . $number . ' berhasil dihapus.');
    }

    public function preview(Awb $awb)
    {
        $type = strtolower(request('type', 'hawb'));

        return view('documents.pdf-preview', [
            'title'       => strtoupper($type) . ' ' . $awb->number,
            'backUrl'     => route('awbs.show', $awb),
            'pdfUrl'      => route('awbs.pdf', ['awb' => $awb, 'type' => $type, 'mode' => 'inline', 't' => time()]),
            'downloadUrl' => route('awbs.pdf', ['awb' => $awb, 'type' => $type, 'mode' => 'download']),
        ]);
    }

    public function pdf(Request $request, Awb $awb)
    {
        $awb->load(['job.customer', 'customer']);
        $type = strtolower($request->query('type', 'hawb'));
        $pdf = app('dompdf.wrapper')->loadView('documents.pdf.air-waybill', [
            'awb'  => $awb,
            'type' => $type,
        ])->setPaper('a4', 'portrait');

        $filename = strtoupper($type) . '_' . str_replace(['/', '\\'], '-', $awb->number) . '.pdf';

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
