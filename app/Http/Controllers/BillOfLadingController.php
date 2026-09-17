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
            $selectedJob = Job::with(['customer'])->find($jobId);
        }

        $jobs      = Job::with('customer')->latest('id')->limit(50)->get();
        $customers = Customer::orderBy('name')->get();
        $carriers  = Vendor::orderBy('name')->get();
        $ports     = Port::orderBy('name')->get();

        $defaultNumber = BillOfLading::generateNumber();

        return view('bills-of-lading.create', compact('selectedJob', 'jobs', 'customers', 'carriers', 'ports', 'defaultNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'number'              => 'required|string|max:60|unique:bills_of_lading,number',
            'bl_date'             => 'required|date',
            'job_id'              => 'nullable|exists:jobs,id',
            'customer_id'         => 'nullable|exists:customers,id',
            'bl_type'             => 'required|string|in:original,telex,seaway',
            'carrier'             => 'nullable|string|max:160',
            'carrier_bl_number'   => 'nullable|string|max:100',
            'vessel_voyage'       => 'nullable|string|max:120',
            'etd'                 => 'nullable|date',
            'eta'                 => 'nullable|date',
            'pol'                 => 'nullable|string|max:120',
            'pod'                 => 'nullable|string|max:120',
            'place_of_delivery'   => 'nullable|string|max:120',
            'shipper_name'        => 'nullable|string|max:160',
            'consignee_name'      => 'nullable|string|max:160',
            'notify_party'        => 'nullable|string',
            'marks_numbers'       => 'nullable|string',
            'cargo_description'   => 'nullable|string',
            'gross_weight'        => 'nullable|numeric|min:0',
            'net_weight'          => 'nullable|numeric|min:0',
            'measurement'         => 'nullable|numeric|min:0',
            'freight_term'        => 'required|string|in:PREPAID,COLLECT',
            'remarks'             => 'nullable|string',
            'status'              => 'required|string|in:draft,issued,released,completed,cancelled',
        ]);

        $validated['created_by'] = auth()->id();
        $bl = BillOfLading::create($validated);

        if ($bl->job_id) {
            return redirect()->to(route('jobs.show', $bl->job_id) . '#tab-bl')
                ->with('success', 'B/L ' . $bl->number . ' berhasil diterbitkan.');
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
        $jobs      = Job::with('customer')->latest('id')->limit(50)->get();
        $customers = Customer::orderBy('name')->get();
        $carriers  = Vendor::orderBy('name')->get();
        $ports     = Port::orderBy('name')->get();

        return view('bills-of-lading.edit', ['bl' => $billOfLading, 'jobs' => $jobs, 'customers' => $customers, 'carriers' => $carriers, 'ports' => $ports]);
    }

    public function update(Request $request, BillOfLading $billOfLading)
    {
        $validated = $request->validate([
            'number'              => 'required|string|max:60|unique:bills_of_lading,number,' . $billOfLading->id,
            'bl_date'             => 'required|date',
            'job_id'              => 'nullable|exists:jobs,id',
            'customer_id'         => 'nullable|exists:customers,id',
            'bl_type'             => 'required|string|in:original,telex,seaway',
            'carrier'             => 'nullable|string|max:160',
            'carrier_bl_number'   => 'nullable|string|max:100',
            'vessel_voyage'       => 'nullable|string|max:120',
            'etd'                 => 'nullable|date',
            'eta'                 => 'nullable|date',
            'pol'                 => 'nullable|string|max:120',
            'pod'                 => 'nullable|string|max:120',
            'place_of_delivery'   => 'nullable|string|max:120',
            'shipper_name'        => 'nullable|string|max:160',
            'consignee_name'      => 'nullable|string|max:160',
            'notify_party'        => 'nullable|string',
            'marks_numbers'       => 'nullable|string',
            'cargo_description'   => 'nullable|string',
            'gross_weight'        => 'nullable|numeric|min:0',
            'net_weight'          => 'nullable|numeric|min:0',
            'measurement'         => 'nullable|numeric|min:0',
            'freight_term'        => 'required|string|in:PREPAID,COLLECT',
            'remarks'             => 'nullable|string',
            'status'              => 'required|string|in:draft,issued,released,completed,cancelled',
        ]);

        $billOfLading->update($validated);

        if ($billOfLading->job_id) {
            return redirect()->to(route('jobs.show', $billOfLading->job_id) . '#tab-bl')
                ->with('success', 'B/L ' . $billOfLading->number . ' berhasil diperbarui.');
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
}
