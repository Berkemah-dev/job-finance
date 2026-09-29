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
        $actor = $request->user();
        $isSalesOnly = $actor?->hasRole('sales') && ! $actor->hasRole(['sales-manager', 'super-admin', 'admin']);

        $query = TruckingPrice::query();
        if (! $isSalesOnly) {
            $query->with('vendor');
        }

        $search = mb_substr($request->string('search')->toString(), 0, 100);
        if ($search !== '') {
            $query->where(function ($q) use ($search, $isSalesOnly) {
                $q->where('port_origin', 'like', '%'.$search.'%')
                    ->orWhere('destination', 'like', '%'.$search.'%')
                    ->orWhere('currency', 'like', '%'.$search.'%');
                if (! $isSalesOnly) {
                    $q->orWhereHas('vendor', fn ($vendor) => $vendor->where('name', 'like', '%'.$search.'%'));
                }
            });
        }
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
        if (! $isSalesOnly && $request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->string('vendor_id')->toString());
        }
        if ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $rawItems = $query->orderByDesc('effective_date')->orderByDesc('id')->get();

        // Kelompokkan per Rute (Pelabuhan Asal & Tujuan) agar 1 rute memiliki 1 harga jual dan daftar modal vendor
        $grouped = $rawItems->groupBy(function ($item) {
            return mb_strtoupper(trim($item->port_origin)) . '|||' . mb_strtoupper(trim($item->destination));
        })->map(function ($rates) {
            $first = $rates->first();
            // Harga jual tunggal rute untuk semua tipe kontainer (20GP / 40FT / 40HQ)
            $sellingPrice = $rates->whereNotNull('selling_price')->first()?->selling_price ?? $first->selling_price;
            $vendorNames = $rates->pluck('vendor.name')->filter()->unique()->values()->all();
            $containerTypes = $rates->pluck('container_type')->unique()->values()->all();

            return (object) [
                'id' => $first->id,
                'representative' => $first,
                'port_origin' => $first->port_origin,
                'destination' => $first->destination,
                'selling_price' => $sellingPrice,
                'currency' => $first->currency ?? 'IDR',
                'effective_date' => $rates->max('effective_date'),
                'effective_until' => $rates->whereNotNull('effective_until')->max('effective_until'),
                'is_active' => $rates->contains('is_active', true),
                'vendors_count' => count($vendorNames),
                'vendor_names' => $vendorNames,
                'container_types' => $containerTypes,
                'total_entries' => $rates->count(),
                'lock_version' => $first->lock_version,
            ];
        })->values();

        $page = (int) $request->input('page', 1);
        $perPage = 10;
        $items = new \Illuminate\Pagination\LengthAwarePaginator(
            $grouped->forPage($page, $perPage)->values(),
            $grouped->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $vendors = $isSalesOnly ? collect() : $this->truckingVendors();
        $containerUnits = ContainerUnit::options();

        return view('pricing.trucking.index', compact('items', 'vendors', 'containerUnits', 'search'));
    }

    public function show(TruckingPrice $truckingPrice)
    {
        $truckingPrice->load('vendor');

        // Ambil seluruh entri tarif & modal untuk rute ini (port_origin & destination sama)
        $allRouteRates = TruckingPrice::with('vendor')
            ->where('port_origin', $truckingPrice->port_origin)
            ->where('destination', $truckingPrice->destination)
            ->orderBy('vendor_id')
            ->orderBy('container_type')
            ->orderBy('overweight')
            ->get();

        // 1 Harga Jual Tunggal Rute untuk semua type (contoh 20GP/40FT/40HQ)
        $routeSellingPrice = $allRouteRates->whereNotNull('selling_price')->first()?->selling_price
            ?? $truckingPrice->selling_price;

        // Kelompokkan per vendor sehingga "1 rute dibuka langsung ada list modal beberapa vendor didalamnya"
        $vendorList = $allRouteRates->groupBy('vendor_id')->map(function ($rates, $vendorId) {
            $vendor = $rates->first()->vendor;
            return [
                'vendor_id' => $vendorId,
                'vendor' => $vendor,
                'vendor_name' => $vendor?->name ?? 'Tarif Umum / Standar (Tanpa Vendor)',
                'rates' => $rates,
                'cost_20gp' => $rates->first(fn ($r) => in_array($r->container_type, ['20gp', '20ft']) && ! $r->overweight)?->price,
                'cost_20gp_ow' => $rates->first(fn ($r) => in_array($r->container_type, ['20gp', '20ft']) && $r->overweight)?->price,
                'cost_40ft' => $rates->first(fn ($r) => in_array($r->container_type, ['40ft', '40ft_40hq']) && ! $r->overweight)?->price,
                'cost_40ft_ow' => $rates->first(fn ($r) => in_array($r->container_type, ['40ft', '40ft_40hq']) && $r->overweight)?->price,
                'cost_40hq' => $rates->first(fn ($r) => in_array($r->container_type, ['40hc', '40hq', '40ft_40hq']) && ! $r->overweight)?->price,
                'cost_40hq_ow' => $rates->first(fn ($r) => in_array($r->container_type, ['40hc', '40hq', '40ft_40hq']) && $r->overweight)?->price,
                'currency' => $rates->first()->currency ?? 'IDR',
                'is_active' => $rates->contains('is_active', true),
            ];
        })->values();

        // Helper to find specific rate in the group (for test compatibility)
        $getRate = function (array $types, bool $overweight) use ($allRouteRates) {
            return $allRouteRates->first(function ($r) use ($types, $overweight) {
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

        $routeRates = $allRouteRates;
        $vendors = $this->truckingVendors();

        return view('pricing.trucking.show', compact('truckingPrice', 'routeRates', 'matrix', 'routeSellingPrice', 'vendorList', 'vendors'));
    }

    public function saveVendorCost(Request $request)
    {
        $actor = $request->user();
        \Illuminate\Support\Facades\Gate::forUser($actor)->authorize('pricing.manage');

        $validated = $request->validate([
            'port_origin' => ['required', 'string', 'max:120'],
            'destination' => ['required', 'string', 'max:120'],
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'cost_20gp' => ['nullable', 'numeric', 'min:0'],
            'cost_40ft' => ['nullable', 'numeric', 'min:0'],
            'cost_40hq' => ['nullable', 'numeric', 'min:0'],
            'cost_20gp_ow' => ['nullable', 'numeric', 'min:0'],
            'cost_40ft_ow' => ['nullable', 'numeric', 'min:0'],
            'cost_40hq_ow' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'effective_date' => ['nullable', 'date'],
        ]);

        $origin = $validated['port_origin'];
        $dest = $validated['destination'];
        $vendorId = (int) $validated['vendor_id'];
        $currency = ! empty($validated['currency']) ? $validated['currency'] : 'IDR';
        $effDate = ! empty($validated['effective_date']) ? $validated['effective_date'] : today()->toDateString();
        $sellingPrice = $validated['selling_price'] ?? null;

        // Jika selling_price diisi, perbarui semua entri rute ini agar tetap 1 harga jual
        if ($sellingPrice !== null && $sellingPrice !== '') {
            TruckingPrice::where('port_origin', $origin)
                ->where('destination', $dest)
                ->update(['selling_price' => $sellingPrice, 'updated_by' => $actor->id]);
        } else {
            $sellingPrice = TruckingPrice::where('port_origin', $origin)
                ->where('destination', $dest)
                ->whereNotNull('selling_price')
                ->value('selling_price');
        }

        $saveType = function (string $type, bool $overweight, $cost) use ($origin, $dest, $vendorId, $currency, $effDate, $sellingPrice, $actor) {
            if ($cost === null || $cost === '') {
                return;
            }
            TruckingPrice::updateOrCreate(
                [
                    'port_origin' => $origin,
                    'destination' => $dest,
                    'container_type' => $type,
                    'overweight' => $overweight,
                    'vendor_id' => $vendorId,
                ],
                [
                    'price' => $cost,
                    'selling_price' => $sellingPrice,
                    'currency' => $currency,
                    'effective_date' => $effDate,
                    'is_active' => true,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]
            );
        };

        $saveType('20gp', false, $validated['cost_20gp'] ?? null);
        $saveType('20gp', true, $validated['cost_20gp_ow'] ?? null);
        $saveType('40ft', false, $validated['cost_40ft'] ?? null);
        $saveType('40ft', true, $validated['cost_40ft_ow'] ?? null);
        $saveType('40hq', false, $validated['cost_40hq'] ?? null);
        $saveType('40hq', true, $validated['cost_40hq_ow'] ?? null);

        $rep = TruckingPrice::where('port_origin', $origin)->where('destination', $dest)->first();

        return redirect()->route('pricing.trucking.show', $rep)
            ->with('success', 'Modal vendor untuk rute ' . $origin . ' → ' . $dest . ' berhasil disimpan.');
    }

    public function deleteVendorCost(Request $request)
    {
        $actor = $request->user();
        \Illuminate\Support\Facades\Gate::forUser($actor)->authorize('pricing.manage');

        $validated = $request->validate([
            'port_origin' => ['required', 'string', 'max:120'],
            'destination' => ['required', 'string', 'max:120'],
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
        ]);

        TruckingPrice::where('port_origin', $validated['port_origin'])
            ->where('destination', $validated['destination'])
            ->where('vendor_id', $validated['vendor_id'])
            ->delete();

        $rep = TruckingPrice::where('port_origin', $validated['port_origin'])->where('destination', $validated['destination'])->first();
        if ($rep) {
            return redirect()->route('pricing.trucking.show', $rep)->with('success', 'Modal vendor pada rute ini berhasil dihapus.');
        }

        return redirect()->route('pricing.trucking.index')->with('success', 'Modal vendor pada rute ini berhasil dihapus.');
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
