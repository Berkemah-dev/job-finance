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
    <span class="date-chip"><x-icon name="calendar"/>{{ now()->locale('id')->translatedFormat('d F Y') }}</span>
</div>

<x-menu-banner
    tag="AKUNTANSI"
    title="Chart of Accounts (Bagan Akun)"
    description="Struktur akun buku besar standar akuntansi: Aset, Liabilitas, Ekuitas, Pendapatan, dan Beban."
    icon="file"
    art-title="Struktur akun,"
    art-subtitle="standar PSAK."
>
    <a class="button button-white" href="{{ route('accounts.mappings') }}" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.25);">
        <x-icon name="file"/> Mapping Akun
    </a>
    <a class="button button-white" href="{{ route('accounts.create') }}">
        <x-icon name="plus"/> Tambah Akun
    </a>
</x-menu-banner>

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

    @if($isTree)
        <div class="coa-filter-strip">
            <div class="coa-tree-info">
                <span class="coa-count-text">Menampilkan <strong>{{ count($accounts) }}</strong> akun dalam struktur hirarki</span>
            </div>
            <div class="coa-quick-actions">
                <button type="button" class="button button-secondary button-sm" id="coa-expand-all" title="Buka seluruh sub akun">
                    <x-icon name="chevron-down"/> Buka Semua
                </button>
                <button type="button" class="button button-secondary button-sm" id="coa-collapse-all" title="Tutup seluruh sub akun">
                    <x-icon name="chevron-right"/> Tutup Semua
                </button>
            </div>
        </div>
    @endif

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
                    <tr class="{{ $isRoot ? 'coa-row-root' : '' }} {{ $hasChildren ? 'coa-row-has-children' : '' }} coa-row"
                        data-coa-id="{{ $account->id }}"
                        data-coa-parent-id="{{ $account->parent_id ?? '' }}"
                        data-coa-level="{{ $level }}"
                        data-coa-has-children="{{ $hasChildren ? '1' : '0' }}">
                        <td class="coa-cell-name">
                            <div class="coa-node {{ $hasChildren ? 'coa-node-has-children' : '' }}">
                                @if($level > 1)
                                    <span class="coa-indent-guide" aria-hidden="true">
                                        @for($i = 2; $i < $level; $i++)
                                            <span class="coa-line-spacer"></span>
                                        @endfor
                                        <span class="coa-line-connector"></span>
                                    </span>
                                @endif

                                @if($hasChildren)
                                    <button type="button"
                                            class="coa-toggle-btn"
                                            data-coa-toggle
                                            aria-expanded="true"
                                            aria-label="Buka atau tutup sub akun {{ $account->code }}"
                                            title="Buka / Tutup sub akun">
                                        <x-icon name="chevron-down" class="coa-chevron-icon"/>
                                    </button>
                                @else
                                    <span class="coa-toggle-spacer" aria-hidden="true"></span>
                                @endif

                                <span class="coa-folder-icon" aria-hidden="true">
                                    <x-icon name="folder" class="coa-icon-folder"/>
                                    <x-icon name="folder-open" class="coa-icon-folder-open"/>
                                </span>

                                <span class="coa-label {{ $hasChildren ? 'coa-label-interactive' : '' }} {{ $isRoot ? 'coa-root-label' : 'coa-level-'.$level.'-label' }}"
                                      title="{{ $hasChildren ? 'Klik untuk buka/tutup sub-akun' : '' }}">
                                    @if($isRoot)
                                        <strong>{{ $account->code }} - {{ $account->name }}</strong>
                                    @else
                                        {{ $account->code }} - {{ $account->name }}
                                    @endif
                                </span>

                                @if($hasChildren)
                                    <span class="coa-children-badge" title="{{ $account->children_count }} sub akun">{{ $account->children_count }} sub</span>
                                @endif

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
