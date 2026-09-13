<?php

namespace App\Http\Controllers;

use App\Http\Requests\VersionRequest;
use App\Http\Requests\WeeklyPricingRequest;
use App\Models\ServiceType;
use App\Models\WeeklyPricing;
use App\Services\PricingService;
use Illuminate\Http\Request;

class WeeklyPricingController extends Controller
{
    public function index(Request $request)
    {
        $query = WeeklyPricing::query();
        $search = mb_substr($request->string('search')->toString(), 0, 80);
        if ($search !== '') {
            $query->where(fn ($q) => $q
                ->where('week', 'like', '%'.$search.'%')
                ->orWhere('currency', 'like', '%'.$search.'%')
                ->orWhere('service', 'like', '%'.$search.'%')
                ->orWhere('notes', 'like', '%'.$search.'%'));
        }
        if ($request->filled('currency')) {
            $query->where('currency', $request->string('currency')->toString());
        }
        if ($request->filled('week')) {
            $query->where('week', 'like', '%'.mb_substr($request->string('week')->toString(), 0, 20).'%');
        }
        if ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }
        $items = $query->orderByDesc('effective_date')->orderByDesc('id')->paginate(10)->withQueryString();

        return view('pricing.weekly.index', compact('items', 'search'));
    }

    public function create()
    {
        return view('pricing.weekly.form', ['weeklyPricing' => new WeeklyPricing, 'serviceTypes' => ServiceType::options()]);
    }

    public function store(WeeklyPricingRequest $request, PricingService $service)
    {
        $service->save(new WeeklyPricing, $request->validated(), $request->user());

        return redirect()->route('pricing.weekly.index')->with('success', 'Weekly pricing berhasil ditambahkan.');
    }

    public function edit(WeeklyPricing $weeklyPricing)
    {
        return view('pricing.weekly.form', ['weeklyPricing' => $weeklyPricing, 'serviceTypes' => ServiceType::options()]);
    }

    public function update(WeeklyPricingRequest $request, WeeklyPricing $weeklyPricing, PricingService $service)
    {
        $service->save($weeklyPricing, $request->validated(), $request->user());

        return redirect()->route('pricing.weekly.index')->with('success', 'Weekly pricing berhasil diperbarui.');
    }

    public function toggle(VersionRequest $request, WeeklyPricing $weeklyPricing, PricingService $service)
    {
        $service->toggle($weeklyPricing, $request->validated(), $request->user());

        return back()->with('success', 'Status weekly pricing diperbarui.');
    }

    public function destroy(VersionRequest $request, WeeklyPricing $weeklyPricing, PricingService $service)
    {
        $service->archive($weeklyPricing, $request->validated(), $request->user());

        return redirect()->route('pricing.weekly.index')->with('success', 'Weekly pricing dihapus.');
    }
}
