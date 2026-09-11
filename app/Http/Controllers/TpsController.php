<?php

namespace App\Http\Controllers;

use App\Models\Tps;
<<<<<<< HEAD
=======
use App\Services\MasterDataService;
>>>>>>> 367a9ee93734e3a97628530a24dddb5e9c488978
use Illuminate\Http\Request;

class TpsController extends Controller
{
    public function index(Request $request)
    {
<<<<<<< HEAD
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
=======
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $mode = (string) $request->input('mode', '');
        $status = (string) $request->input('status', 'active');

        $tpsList = Tps::query()
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('code', 'like', '%'.$search.'%')
                ->orWhere('city', 'like', '%'.$search.'%')))
            ->when(in_array($mode, ['air', 'sea'], true), fn ($q) => $q->where('mode', $mode))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false), fn ($q) => $q->where('is_active', true))
            ->orderBy('mode')
            ->orderBy('city')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('tps.index', compact('tpsList', 'search', 'mode', 'status'));
>>>>>>> 367a9ee93734e3a97628530a24dddb5e9c488978
    }

    public function create()
    {
<<<<<<< HEAD
        return view('master.tps.form', ['tps' => new Tps]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        Tps::create($data);
=======
        return view('tps.form', ['tps' => new Tps]);
    }

    public function store(Request $request, MasterDataService $service)
    {
        $validated = $this->validated($request);
        $tps = Tps::create($validated);
        $service->log($request->user(), 'tps.created', 'Menambahkan TPS '.$tps->code.' — '.$tps->name, ['module' => 'tps', 'record_id' => $tps->id]);
>>>>>>> 367a9ee93734e3a97628530a24dddb5e9c488978

        return redirect()->route('tps.index')->with('success', 'TPS berhasil ditambahkan.');
    }

    public function edit(Tps $tps)
    {
<<<<<<< HEAD
        return view('master.tps.form', compact('tps'));
    }

    public function update(Request $request, Tps $tps)
    {
        $data = $this->validated($request, $tps->id);
        $tps->update($data);
=======
        return view('tps.form', compact('tps'));
    }

    public function update(Request $request, Tps $tps, MasterDataService $service)
    {
        $validated = $this->validated($request, $tps);
        $before = $tps->only(['city', 'name', 'code', 'mode', 'is_active']);
        $tps->update($validated);
        $service->log($request->user(), 'tps.updated', 'Memperbarui TPS '.$tps->code.' — '.$tps->name, ['module' => 'tps', 'record_id' => $tps->id, 'before' => $before, 'after' => $tps->only(['city', 'name', 'code', 'mode', 'is_active'])]);
>>>>>>> 367a9ee93734e3a97628530a24dddb5e9c488978

        return redirect()->route('tps.index')->with('success', 'TPS berhasil diperbarui.');
    }

<<<<<<< HEAD
    public function destroy(Tps $tps)
    {
=======
    public function toggle(Request $request, Tps $tps, MasterDataService $service)
    {
        $tps->update(['is_active' => ! $tps->is_active]);
        $service->log($request->user(), 'tps.toggled', ($tps->is_active ? 'Mengaktifkan' : 'Menonaktifkan').' TPS '.$tps->code, ['module' => 'tps', 'record_id' => $tps->id]);

        return back()->with('success', 'Status TPS berhasil diubah.');
    }

    public function destroy(Request $request, Tps $tps, MasterDataService $service)
    {
        $service->log($request->user(), 'tps.deleted', 'Menghapus TPS '.$tps->code.' — '.$tps->name, ['module' => 'tps', 'record_id' => $tps->id]);
>>>>>>> 367a9ee93734e3a97628530a24dddb5e9c488978
        $tps->delete();

        return redirect()->route('tps.index')->with('success', 'TPS berhasil dihapus.');
    }

<<<<<<< HEAD
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
=======
    private function validated(Request $request, ?Tps $tps = null): array
    {
        $codeRule = 'required|string|max:30|unique:tps,code'.($tps ? ','.$tps->id : '');

        return $request->validate([
            'city'      => ['required', 'string', 'max:120'],
            'name'      => ['required', 'string', 'max:200'],
            'code'      => [$codeRule],
            'mode'      => ['required', 'in:air,sea'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
>>>>>>> 367a9ee93734e3a97628530a24dddb5e9c488978
