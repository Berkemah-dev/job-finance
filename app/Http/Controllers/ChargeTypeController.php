<?php

namespace App\Http\Controllers;

use App\Models\ChargeType;
use Illuminate\Http\Request;

class ChargeTypeController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 80);
        $status = $request->get('status', 'all');

        $charges = ChargeType::when($search, fn($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->when($status === 'active', fn($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('master.charge-types.index', compact('charges', 'search', 'status'));
    }

    public function toggle(ChargeType $chargeType)
    {
        $chargeType->update(['is_active' => !$chargeType->is_active]);
        $label = $chargeType->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Jenis biaya {$chargeType->name} berhasil {$label}.");
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:150|unique:charge_types,name']);
        $data['name'] = strtoupper(trim($data['name']));
        ChargeType::create(['name' => $data['name'], 'is_active' => true]);
        return back()->with('success', 'Jenis biaya berhasil ditambahkan.');
    }

    public function destroy(ChargeType $chargeType)
    {
        $chargeType->delete();
        return back()->with('success', 'Jenis biaya berhasil dihapus.');
    }

    public function edit(\App\Models\ChargeType $chargeType) {
        return view('accounts.edit-charge', compact('chargeType'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\ChargeType $chargeType) {
        $data = $request->validate(['name' => 'required|string|max:150|unique:charge_types,name,'.$chargeType->id]);
        $chargeType->update(['name' => strtoupper(trim($data['name']))]);
        return redirect()->route('charge-types.index')->with('success', 'Jenis biaya diperbarui.');
    }
}
