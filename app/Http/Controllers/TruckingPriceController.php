<?php

namespace App\Http\Controllers;

use App\Http\Requests\TruckingPriceRequest;
use App\Http\Requests\VersionRequest;
use App\Models\TruckingPrice;
use App\Models\Vendor;
use App\Services\PricingService;
use Illuminate\Http\Request;

class TruckingPriceController extends Controller
{
    public function index(Request $request)
    {
        $query = TruckingPrice::with('vendor');
        if ($request->filled('port_origin')) {
            $query->where('port_origin', 'like', '%'.mb_substr($request->string('port_origin')->toString(), 0, 120).'%');
        }
        if ($request->filled('destination')) {
            $query->where('destination', 'like', '%'.mb_substr($request->string('destination')->toString(), 0, 120).'%');
        }
        if ($request->filled('container_type')) {
            $query->where('container_type', $request->string('container_type')->toString());
        }
        if ($request->filled('overweight')) {
            $query->where('overweight', $request->boolean('overweight'));
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->string('vendor_id')->toString());
        }
        if ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }
        $items = $query->orderByDesc('effective_date')->orderByDesc('id')->paginate(15)->withQueryString();
        $vendors = Vendor::orderBy('name')->get();

        return view('pricing.trucking.index', compact('items', 'vendors'));
    }

    public function create()
    {
        return view('pricing.trucking.form', ['truckingPrice' => new TruckingPrice, 'vendors' => Vendor::orderBy('name')->get()]);
    }

    public function store(TruckingPriceRequest $request, PricingService $service)
    {
        $service->save(new TruckingPrice, $request->validated(), $request->user());

        return redirect()->route('pricing.trucking.index')->with('success', 'Trucking price berhasil ditambahkan.');
    }

    public function edit(TruckingPrice $truckingPrice)
    {
        return view('pricing.trucking.form', ['truckingPrice' => $truckingPrice, 'vendors' => Vendor::orderBy('name')->get()]);
    }

    public function update(TruckingPriceRequest $request, TruckingPrice $truckingPrice, PricingService $service)
    {
        $service->save($truckingPrice, $request->validated(), $request->user());

        return redirect()->route('pricing.trucking.index')->with('success', 'Trucking price berhasil diperbarui.');
    }

    public function toggle(VersionRequest $request, TruckingPrice $truckingPrice, PricingService $service)
    {
        $service->toggle($truckingPrice, $request->validated(), $request->user());

        return back()->with('success', 'Status trucking price diperbarui.');
    }

    public function destroy(VersionRequest $request, TruckingPrice $truckingPrice, PricingService $service)
    {
        $service->archive($truckingPrice, $request->validated(), $request->user());

        return redirect()->route('pricing.trucking.index')->with('success', 'Trucking price dihapus.');
    }
}
