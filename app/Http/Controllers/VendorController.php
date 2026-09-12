<?php

namespace App\Http\Controllers;

use App\Http\Requests\VendorRequest;
use App\Http\Requests\VersionRequest;
use App\Models\Vendor;
use App\Services\MasterDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $status = $request->boolean('archived') ? 'archived' : (string) $request->input('status', '');
        $category = (string) $request->input('category', '');
        $vendors = Vendor::query()
            ->with('categories')
            ->when($status === 'archived', fn ($q) => $q->onlyTrashed())
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')->orWhere('country', 'like', '%'.$search.'%')))
            ->when($category !== '' && array_key_exists($category, config('operations.vendor_types')), fn ($q) => $q->where('type', $category))
            ->orderBy('name')->paginate(10)->withQueryString();

        return view('vendors.index', compact('vendors', 'search', 'status', 'category'));
    }

    public function create()
    {
        return view('vendors.form', ['vendor' => new Vendor]);
    }

    public function store(VendorRequest $request, MasterDataService $service)
    {
        $vendor = DB::transaction(function () use ($request, $service) {
            $validated = $request->validated();
            $vendor = $service->save(new Vendor, $validated, $request->user());
            if ($request->has('categories')) {
                $vendor->categories()->delete();
                foreach ($request->input('categories', []) as $cat) {
                    $vendor->categories()->create(['category' => $cat]);
                }
            }
            return $vendor;
        }, 3);

        return redirect()->route('vendors.show', $vendor)->with('success', 'Vendor berhasil ditambahkan.');
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['truckingPrices', 'categories']);

        return view('vendors.show', compact('vendor'));
    }

    public function edit(Vendor $vendor)
    {
        $vendor->load('categories');
        return view('vendors.form', compact('vendor'));
    }

    public function update(VendorRequest $request, Vendor $vendor, MasterDataService $service)
    {
        DB::transaction(function () use ($request, $vendor, $service) {
            $validated = $request->validated();
            $service->save($vendor, $validated, $request->user());
            if ($request->has('categories')) {
                $vendor->categories()->delete();
                foreach ($request->input('categories', []) as $cat) {
                    $vendor->categories()->create(['category' => $cat]);
                }
            }
        }, 3);

        return redirect()->route('vendors.show', $vendor)->with('success', 'Vendor berhasil diperbarui.');
    }

    public function toggle(VersionRequest $request, Vendor $vendor, MasterDataService $service)
    {
        $service->toggleActive($vendor, $request->validated(), $request->user());

        return redirect()->route('vendors.show', $vendor)->with('success', 'Status vendor diperbarui.');
    }

    public function destroy(VersionRequest $request, Vendor $vendor, MasterDataService $service)
    {
        $service->archive($vendor, $request->validated(), $request->user());

        return redirect()->route('vendors.index')->with('success', 'Vendor diarsipkan. Histori tetap tersimpan.');
    }

    public function restore(VersionRequest $request, int $id, MasterDataService $service)
    {
        $vendor = Vendor::withTrashed()->findOrFail($id);
        $service->restore($vendor, $request->validated(), $request->user());

        return redirect()->route('vendors.show', $vendor)->with('success', 'Vendor diaktifkan kembali.');
    }

}
