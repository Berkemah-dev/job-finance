<?php

namespace App\Http\Controllers;

use App\Models\ContainerUnit;
use Illuminate\Http\Request;

class ContainerUnitController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 80);

        $units = ContainerUnit::when($search, fn($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('master.container-units.index', compact('units', 'search'));
    }

    public function toggle(ContainerUnit $containerUnit)
    {
        $containerUnit->update(['is_active' => !$containerUnit->is_active]);
        $label = $containerUnit->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Unit {$containerUnit->name} berhasil {$label}.");
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:50|unique:container_units,name']);
        ContainerUnit::create(['name' => trim($data['name']), 'is_active' => true]);
        return back()->with('success', 'Unit berhasil ditambahkan.');
    }

    public function destroy(ContainerUnit $containerUnit)
    {
        $containerUnit->delete();
        return back()->with('success', 'Unit berhasil dihapus.');
    }

    public function edit(\App\Models\ContainerUnit $containerUnit) {
        return view('accounts.edit-unit', compact('containerUnit'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\ContainerUnit $containerUnit) {
        $data = $request->validate(['name' => 'required|string|max:50|unique:container_units,name,'.$containerUnit->id]);
        $containerUnit->update(['name' => trim($data['name'])]);
        return redirect()->route('accounts.index', ['tab' => 'unit'])->with('success', 'Satuan diperbarui.');
    }
}