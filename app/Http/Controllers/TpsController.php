<?php

namespace App\Http\Controllers;

use App\Models\Tps;
use Illuminate\Http\Request;

class TpsController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 80);
        $mode = (string) $request->input('mode', '');
        $tps = Tps::query()
            ->when($search, fn ($q) => $q->where('name', 'like', '%'.$search.'%')
                ->orWhere('code', 'like', '%'.$search.'%')
                ->orWhere('city', 'like', '%'.$search.'%'))
            ->when(in_array($mode, ['air', 'sea'], true), fn ($q) => $q->where('mode', $mode))
            ->orderBy('city')->orderBy('name')
            ->paginate(20)->withQueryString();

        return view('master.tps.index', compact('tps', 'search', 'mode'));
    }

    public function create()
    {
        return view('master.tps.form', ['tps' => new Tps]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        Tps::create($data);

        return redirect()->route('tps.index')->with('success', 'TPS berhasil ditambahkan.');
    }

    public function edit(Tps $tps)
    {
        return view('master.tps.form', compact('tps'));
    }

    public function update(Request $request, Tps $tps)
    {
        $data = $this->validated($request, $tps->id);
        $tps->update($data);

        return redirect()->route('tps.index')->with('success', 'TPS berhasil diperbarui.');
    }

    public function destroy(Tps $tps)
    {
        $tps->delete();

        return redirect()->route('tps.index')->with('success', 'TPS berhasil dihapus.');
    }

    private function validated(Request $request, ?int $ignoreId): array
    {
        $request->merge(['code' => strtoupper((string) $request->input('code'))]);
        $data = $request->validate([
            'code'      => 'required|string|max:20|unique:tps,code'.($ignoreId ? ','.$ignoreId : ''),
            'name'      => 'required|string|max:150',
            'city'      => 'required|string|max:100',
            'mode'      => 'required|in:air,sea',
            'address'   => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}