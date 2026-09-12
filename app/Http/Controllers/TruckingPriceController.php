<?php

namespace App\Http\Controllers;

use App\Http\Requests\TruckingPriceRequest;
use App\Http\Requests\VersionRequest;
use App\Models\TruckingPrice;
use App\Models\ContainerUnit;
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
        $items = $query->orderByDesc('effective_date')->orderByDesc('id')->paginate(10)->withQueryString();
        $vendors = $this->truckingVendors();

        $containerUnits = ContainerUnit::options();

        return view('pricing.trucking.index', compact('items', 'vendors', 'containerUnits'));
    }

    public function show(TruckingPrice $truckingPrice)
    {
        $truckingPrice->load('vendor');

        // Fetch all rates for the same route & vendor
        $routeRates = TruckingPrice::query()
            ->where('port_origin', $truckingPrice->port_origin)
            ->where('destination', $truckingPrice->destination)
            ->where(function ($q) use ($truckingPrice) {
                if ($truckingPrice->vendor_id) {
                    $q->where('vendor_id', $truckingPrice->vendor_id);
                } else {
                    $q->whereNull('vendor_id');
                }
            })
            ->orderBy('container_type')
            ->orderBy('overweight')
            ->get();

        // Helper to find specific rate in the group
        $getRate = function (array $types, bool $overweight) use ($routeRates) {
            return $routeRates->first(function ($r) use ($types, $overweight) {
                return in_array($r->container_type, $types, true) && (bool) $r->overweight === $overweight;
            });
        };

        $matrix = [
            '20gp' => [
                'label' => '20 GP / 20 FT Trailer',
                'normal' => $getRate(['20gp', '20ft'], false),
                'overweight' => $getRate(['20gp', '20ft'], true),
            ],
            '40ft' => [
                'label' => '40 FT Trailer',
                'normal' => $getRate(['40ft', '40ft_40hq'], false),
                'overweight' => $getRate(['40ft', '40ft_40hq'], true),
            ],
            '40hq' => [
                'label' => '40 HQ / 40 HC Trailer',
                'normal' => $getRate(['40hc', '40hq', '40ft_40hq'], false),
                'overweight' => $getRate(['40hc', '40hq', '40ft_40hq'], true),
            ],
        ];

        return view('pricing.trucking.show', compact('truckingPrice', 'routeRates', 'matrix'));
    }

    public function create()
    {
        return view('pricing.trucking.form', ['truckingPrice' => new TruckingPrice, 'vendors' => $this->truckingVendors(), 'containerUnits' => ContainerUnit::options()]);
    }

    public function store(TruckingPriceRequest $request, PricingService $service)
    {
        $service->save(new TruckingPrice, $request->validated(), $request->user());

        return redirect()->route('pricing.trucking.index')->with('success', 'Trucking price berhasil ditambahkan.');
    }

    public function edit(TruckingPrice $truckingPrice)
    {
        return view('pricing.trucking.form', ['truckingPrice' => $truckingPrice, 'vendors' => $this->truckingVendors(), 'containerUnits' => ContainerUnit::options()]);
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

    private function truckingVendors()
    {
        return Vendor::query()
            ->where('type', 'trucking')
            ->orderBy('name')
            ->get();
    }
}
