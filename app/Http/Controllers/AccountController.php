<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Http\Requests\MappingRequest;
use App\Http\Requests\VersionRequest;
use App\Models\AccountMapping;
use App\Models\ChartOfAccount;
use App\Services\MasterDataService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $accounts = ChartOfAccount::query()->when($request->input('archived') === '1', fn ($q) => $q->onlyTrashed())
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%')))
            ->when(array_key_exists((string) $request->input('type'), config('accounting.types')), fn ($q) => $q->where('type', $request->input('type')))
            ->orderBy('code')->paginate(15)->withQueryString();

        return view('accounts.index', compact('accounts', 'search'));
    }

    public function create()
    {
        return view('accounts.form', ['account' => new ChartOfAccount]);
    }

    public function store(AccountRequest $request, MasterDataService $service)
    {
        $service->save(new ChartOfAccount, $request->validated(), $request->user());

        return redirect()->route('accounts.index')->with('success', 'Akun berhasil ditambahkan.');
    }

    public function edit(ChartOfAccount $account)
    {
        return view('accounts.form', compact('account'));
    }

    public function update(AccountRequest $request, ChartOfAccount $account, MasterDataService $service)
    {
        $service->save($account, $request->validated(), $request->user());

        return redirect()->route('accounts.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(VersionRequest $request, ChartOfAccount $account, MasterDataService $service)
    {
        $service->archive($account, $request->validated(), $request->user());

        return redirect()->route('accounts.index')->with('success', 'Akun berhasil diarsipkan.');
    }

    public function mappings()
    {
        return view('accounts.mappings', ['accounts' => ChartOfAccount::orderBy('code')->get(), 'mappings' => AccountMapping::pluck('chart_of_account_id', 'key')]);
    }

    public function updateMappings(MappingRequest $request, MasterDataService $service)
    {
        $service->mappings($request->validated('mappings'), $request->user());

        return redirect()->route('accounts.mappings')->with('success', 'Mapping akun berhasil disimpan.');
    }
}
