<?php

namespace App\Http\Controllers;

use App\Models\Port;
use Illuminate\Http\Request;

class PortController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 80);
        $status = $request->get('status', 'all');

        $ports = Port::when($search, fn($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%'))
            ->when($status === 'active', fn($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('master.ports.index', compact('ports', 'search', 'status'));
    }

    public function toggle(Port $port)
    {
        $port->update(['is_active' => !$port->is_active]);
        $label = $port->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Port {$port->name} berhasil {$label}.");
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:20',
        ]);
        $data['name'] = strtoupper(trim($data['name']));
        $data['code'] = strtoupper(trim($data['code']));
        Port::firstOrCreate(['name' => $data['name']], ['code' => $data['code'], 'is_active' => true]);
        return back()->with('success', 'Port berhasil ditambahkan.');
    }

    public function destroy(Port $port)
    {
        $port->delete();
        return back()->with('success', 'Port berhasil dihapus.');
    }

    public function edit(\App\Models\Port $port) {
        return view('accounts.edit-port', compact('port'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Port $port) {
        $data = $request->validate(['name' => 'required|string|max:150', 'code' => 'required|string|max:20']);
        $port->update(['name' => strtoupper(trim($data['name'])), 'code' => strtoupper(trim($data['code']))]);
        return redirect()->route('ports.index')->with('success', 'Port diperbarui.');
    }
}
