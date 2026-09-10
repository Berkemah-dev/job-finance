@extends('layouts.app')
@section('title', 'Data COA')
@section('content')
<div class="page-heading">
    <div>
        <div class="coa-header-title">
            <x-icon name="list"/>
            <h1>Data COA</h1>
        </div>
        <p>Bagan Akun Standar (Chart of Accounts) dengan struktur hirarki terintegrasi.</p>
    </div>
    <div class="action-group">
        <a class="button button-secondary" href="{{ route('accounts.mappings') }}">
            <x-icon name="file"/> Mapping Akun
        </a>
        <a class="button button-primary" href="{{ route('accounts.create') }}">
            <x-icon name="plus"/> Tambah Akun
        </a>
    </div>
</div>

<section class="panel coa-panel">
    <form class="filter-bar" method="GET" action="{{ route('accounts.index') }}">
        <input name="search" value="{{ $search }}" placeholder="Cari kode atau nama akun..." aria-label="Cari akun">
        <select name="type" aria-label="Tipe akun">
            <option value="">Semua tipe</option>
            @foreach(config('accounting.types') as $key => $label)
                <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="archived" aria-label="Status akun">
            <option value="0">Akun aktif</option>
            <option value="1" @selected(request('archived') === '1')>Diarsipkan</option>
        </select>
        <button class="button button-primary" type="submit">Cari</button>
        @if($search || request('type') || request('archived'))
            <a class="button button-secondary" href="{{ route('accounts.index') }}">Tampilkan Semua (Tree)</a>
        @endif
    </form>

    <div class="coa-table-wrapper">
        <table class="coa-tree-table">
            <thead>
                <tr>
                    <th scope="col">ACCOUNT NAME</th>
                    <th scope="col" class="col-actions">ADD SUB</th>
                    <th scope="col" class="col-actions">EDIT</th>
                    <th scope="col" class="col-actions">DEL</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                    @php
                        $level = $account->tree_level ?? $account->level ?? 1;
                        $isRoot = $level === 1;
                        $hasChildren = ($account->children_count ?? 0) > 0;
                    @endphp
                    <tr class="{{ $isRoot ? 'coa-row-root' : '' }}">
                        <td class="coa-cell-name">
                            <div class="coa-node">
                                @if($level > 1)
                                    <span class="coa-indent-guide" aria-hidden="true">
                                        @for($i = 2; $i < $level; $i++)
                                            <span class="coa-line-spacer"></span>
                                        @endfor
                                        <span class="coa-line-connector"></span>
                                    </span>
                                @endif

                                <span class="coa-folder-icon" aria-hidden="true">
                                    <x-icon name="folder"/>
                                </span>

                                <span class="coa-label {{ $isRoot ? 'coa-root-label' : 'coa-level-'.$level.'-label' }}">
                                    @if($isRoot)
                                        <strong>{{ $account->code }} - {{ $account->name }}</strong>
                                    @else
                                        {{ $account->code }} - {{ $account->name }}
                                    @endif
                                </span>

                                @if(!$isTree)
                                    <span class="coa-type-badge">{{ config('accounting.types.'.$account->type) }}</span>
                                @endif
                            </div>
                        </td>

                        {{-- ADD SUB COLUMN --}}
                        <td class="col-actions">
                            @if(!$account->trashed())
                                <a class="coa-action-button" href="{{ route('accounts.create', ['parent_id' => $account->id]) }}" title="Tambah sub akun di bawah {{ $account->code }} - {{ $account->name }}">
                                    <x-icon name="plus-square"/>
                                </a>
                            @endif
                        </td>

                        {{-- EDIT COLUMN --}}
                        <td class="col-actions">
                            @if(!$account->trashed())
                                <a class="coa-action-button" href="{{ route('accounts.edit', $account) }}" title="Edit {{ $account->code }} - {{ $account->name }}">
                                    <x-icon name="edit"/>
                                </a>
                            @else
                                <span class="count-badge">Diarsipkan</span>
                            @endif
                        </td>

                        {{-- DEL COLUMN --}}
                        <td class="col-actions">
                            @if(!$account->trashed() && !$isRoot && !$hasChildren)
                                <form method="POST" action="{{ route('accounts.destroy', $account) }}" data-confirm="Apakah Anda yakin ingin mengarsipkan akun {{ $account->code }} - {{ $account->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="lock_version" value="{{ $account->lock_version }}">
                                    <button class="coa-action-button coa-btn-del" type="submit" title="Arsipkan {{ $account->code }} - {{ $account->name }}">
                                        <x-icon name="x"/>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">
                            <div class="empty-icon"><x-icon name="file"/></div>
                            <h3>Tidak ada akun yang sesuai</h3>
                            <p>Coba gunakan kata kunci pencarian lain atau klik reset.</p>
                        </td>
                    </tr>
                @endforelse

                @if($isTree && !request('archived'))
                    <tr class="coa-bottom-add-row">
                        <td class="coa-cell-name">
                            <div class="coa-node">
                                <span class="coa-folder-icon"><x-icon name="folder"/></span>
                                <span style="color: #64748b; font-size: 11px;">Tambah akun baru...</span>
                            </div>
                        </td>
                        <td class="col-actions">
                            <a class="coa-action-button" href="{{ route('accounts.create') }}" title="Tambah Akun Baru">
                                <x-icon name="plus-square"/>
                            </a>
                        </td>
                        <td class="col-actions"></td>
                        <td class="col-actions"></td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if(method_exists($accounts, 'links') && $accounts->hasPages())
        <div class="pagination">{{ $accounts->links() }}</div>
    @endif
</section>
@endsection
