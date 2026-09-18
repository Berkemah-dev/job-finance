<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\VendorTruck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VendorTruckController extends Controller
{
    public function store(Request $request, Vendor $vendor): RedirectResponse
    {
        Gate::authorize('vendors.manage');

        $validated = $request->validate([
            'driver_name'  => 'required|string|max:160',
            'driver_phone' => 'nullable|string|max:50',
            'plate_number' => 'required|string|max:30',
            'vehicle_type' => 'nullable|string|max:60',
            'notes'        => 'nullable|string|max:255',
        ]);

        $vendor->trucks()->create($validated);

        return redirect()->route('vendors.show', $vendor)
            ->with('success', 'Data supir & plat nomor ' . $validated['plate_number'] . ' berhasil ditambahkan.');
    }

    public function update(Request $request, Vendor $vendor, VendorTruck $truck): RedirectResponse
    {
        Gate::authorize('vendors.manage');

        $validated = $request->validate([
            'driver_name'  => 'required|string|max:160',
            'driver_phone' => 'nullable|string|max:50',
            'plate_number' => 'required|string|max:30',
            'vehicle_type' => 'nullable|string|max:60',
            'notes'        => 'nullable|string|max:255',
            'is_active'    => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $truck->update($validated);

        return redirect()->route('vendors.show', $vendor)
            ->with('success', 'Data armada ' . $truck->plate_number . ' berhasil diperbarui.');
    }

    public function destroy(Vendor $vendor, VendorTruck $truck): RedirectResponse
    {
        Gate::authorize('vendors.manage');

        $plate = $truck->plate_number;
        $truck->delete();

        return redirect()->route('vendors.show', $vendor)
            ->with('success', 'Data armada ' . $plate . ' berhasil dihapus.');
    }

    public function apiList(Vendor $vendor): JsonResponse
    {
        $trucks = $vendor->trucks()
            ->where('is_active', true)
            ->orderBy('plate_number')
            ->get(['id', 'driver_name', 'driver_phone', 'plate_number', 'vehicle_type']);

        return response()->json($trucks);
    }
}
