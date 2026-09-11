<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Http\Requests\MappingRequest;
use App\Http\Requests\VersionRequest;
use App\Models\AccountMapping;
use App\Models\ChartOfAccount;
use App\Services\MasterDataService;
use App\Models\Port;
use App\Models\ChargeType;
use App\Models\ContainerUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->route('tab') ?? $request->query('tab', 'coa');
        $search = mb_substr($request->string('search')->toString(), 0, 100);

        if ($tab === 'port') {
            $status = $request->get('status', 'all');
            $ports = \App\Models\Port::when($search, fn($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%'))
                ->when($status === 'active', fn($q) => $q->where('is_active', true))
                ->when($status === 'inactive', fn($q) => $q->where('is_active', false))
                ->orderBy('name')->paginate(25)->withQueryString();
            return view('accounts.index', compact('tab', 'ports', 'search', 'status'));
        }

        if ($tab === 'charge') {
            $status = $request->get('status', 'all');
            $charges = \App\Models\ChargeType::when($search, fn($q) => $q->where('name', 'like', '%'.$search.'%'))
                ->when($status === 'active', fn($q) => $q->where('is_active', true))
                ->when($status === 'inactive', fn($q) => $q->where('is_active', false))
                ->orderBy('name')->paginate(25)->withQueryString();
            return view('accounts.index', compact('tab', 'charges', 'search', 'status'));
        }

        if ($tab === 'unit') {
            $units = \App\Models\ContainerUnit::when($search, fn($q) => $q->where('name', 'like', '%'.$search.'%'))
                ->orderBy('name')->paginate(25)->withQueryString();
            return view('accounts.index', compact('tab', 'units', 'search'));
        }

        // Default COA
        $type = $request->input('type');
        $archived = $request->input('archived') === '1';

        $query = ChartOfAccount::query()
            ->withCount('children')
            ->when($archived, fn ($q) => $q->onlyTrashed())
            ->when(array_key_exists((string) $type, config('accounting.types')), fn ($q) => $q->where('type', $type))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%')));

        if ($search || $type || $archived) {
            $accounts = $query->orderBy('code')->paginate(50)->withQueryString();
            $isTree = false;
        } else {
            $allAccounts = ChartOfAccount::withCount('children')->orderBy('code')->get();
            $accounts = $this->buildTreeList($allAccounts);
            $isTree = true;
        }

        return view('accounts.index', compact('tab', 'accounts', 'search', 'isTree'));
    }

    private function buildTreeList(Collection $all): Collection
    {
        $grouped = $all->groupBy(fn ($a) => (string) ($a->parent_id ?? 'root'));
        $result = collect();

        $traverse = function ($parentId, $currentLevel) use (&$traverse, $grouped, &$result) {
            $children = $grouped->get((string) $parentId, collect());
            foreach ($children as $child) {
                $child->tree_level = $currentLevel;
                $result->push($child);
                $traverse($child->id, $currentLevel + 1);
            }
        };

        // Root accounts have parent_id null
        $traverse('root', 1);

        // Append any accounts whose parent wasn't found in tree
        $pushedIds = $result->pluck('id')->all();
        $orphans = $all->reject(fn ($a) => in_array($a->id, $pushedIds));
        foreach ($orphans as $orphan) {
            $orphan->tree_level = $orphan->level ?? 1;
            $result->push($orphan);
        }

        return $result;
    }

    public function create(Request $request)
    {
        $account = new ChartOfAccount;
        $parent = null;

        if ($request->filled('parent_id')) {
            $parent = ChartOfAccount::find($request->input('parent_id'));
            if ($parent) {
                $account->parent_id = $parent->id;
                $account->type = $parent->type;
            }
        }

        $parents = ChartOfAccount::orderBy('code')->get();

        return view('accounts.form', compact('account', 'parent', 'parents'));
    }

    public function store(AccountRequest $request, MasterDataService $service)
    {
        $service->save(new ChartOfAccount, $request->validated(), $request->user());

        return redirect()->route('accounts.index')->with('success', 'Akun berhasil ditambahkan.');
    }

    public function edit(ChartOfAccount $account)
    {
        $parent = $account->parent;
        $parents = ChartOfAccount::where('id', '!=', $account->id)->orderBy('code')->get();

        return view('accounts.form', compact('account', 'parent', 'parents'));
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
        return view('accounts.mappings', [
            'accounts' => ChartOfAccount::orderBy('code')->get(),
            'mappings' => AccountMapping::pluck('chart_of_account_id', 'key'),
        ]);
    }

    public function updateMappings(MappingRequest $request, MasterDataService $service)
    {
        $service->mappings($request->validated('mappings'), $request->user());

        return redirect()->route('accounts.mappings')->with('success', 'Mapping akun berhasil disimpan.');
    }
}
