<?php

namespace App\Http\Controllers;

use App\Http\Requests\VendorRequest;
use App\Http\Requests\VersionRequest;
use App\Models\Vendor;
use App\Services\MasterDataService;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $vendors = Vendor::query()->when($request->input('archived') === '1', fn ($q) => $q->onlyTrashed())
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('vendors.index', compact('vendors', 'search'));
    }

    public function create()
    {
        return view('vendors.form', ['vendor' => new Vendor]);
    }

    public function store(VendorRequest $request, MasterDataService $service)
    {
        $service->save(new Vendor, $request->validated(), $request->user());

        return redirect()->route('vendors.index')->with('success', 'Vendor berhasil ditambahkan.');
    }

    public function edit(Vendor $vendor)
    {
        return view('vendors.form', compact('vendor'));
    }

    public function update(VendorRequest $request, Vendor $vendor, MasterDataService $service)
    {
        $service->save($vendor, $request->validated(), $request->user());

        return redirect()->route('vendors.index')->with('success', 'Vendor berhasil diperbarui.');
    }

    public function destroy(VersionRequest $request, Vendor $vendor, MasterDataService $service)
    {
        $service->archive($vendor, $request->validated(), $request->user());

        return redirect()->route('vendors.index')->with('success', 'Vendor diarsipkan. Histori tetap tersimpan.');
    }
}
