<?php

namespace App\Http\Controllers;

use App\Models\ServiceType;
use Illuminate\Http\Request;

class ServiceTypeController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 80);
        $status = $request->get('status', 'all');

        $services = ServiceType::when($search, fn ($q) => $q->where(fn ($q) => $q->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%')))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('master.service-types.index', compact('services', 'search', 'status'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'alpha_dash', 'unique:service_types,code'],
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        ServiceType::create([
            'code' => strtolower(trim($data['code'])),
            'name' => strtoupper(trim($data['name'])),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => true,
        ]);

        return back()->with('success', 'Service berhasil ditambahkan.');
    }

    public function edit(ServiceType $serviceType)
    {
        return view('master.service-types.edit', compact('serviceType'));
    }

    public function update(Request $request, ServiceType $serviceType)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'alpha_dash', 'unique:service_types,code,'.$serviceType->id],
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $serviceType->update([
            'code' => strtolower(trim($data['code'])),
            'name' => strtoupper(trim($data['name'])),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()->route('service-types.index')->with('success', 'Service diperbarui.');
    }

    public function toggle(ServiceType $serviceType)
    {
        $serviceType->update(['is_active' => ! $serviceType->is_active]);
        $label = $serviceType->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Service {$serviceType->name} berhasil {$label}.");
    }

    public function destroy(ServiceType $serviceType)
    {
        $serviceType->delete();

        return back()->with('success', 'Service berhasil dihapus.');
    }
}
