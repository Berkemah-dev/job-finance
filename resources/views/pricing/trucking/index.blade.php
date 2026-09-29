@extends('layouts.app')
@section('title','Trucking Price List')
@section('content')
<x-menu-banner
    tag="PRICING & LOGISTIK"
    title="Trucking Price List"
    description="Tarif trucking standar berdasarkan rute asal, tujuan, tipe kontainer, dan vendor armada."
    :action-url="auth()->user()->can('pricing.manage') ? route('pricing.trucking.create') : null"
    action-label="Tambah Tarif Trucking"
    action-icon="plus"
    icon="briefcase"
    art-title="Rute & armada,"
    art-subtitle="harga transparan."
/>
@php
    $isSalesOnly = auth()->user()?->hasRole('sales') && ! auth()->user()?->hasRole(['sales-manager', 'super-admin', 'admin']);
@endphp

<section class="panel">
<form class="filter-bar" method="GET">
    <input name="search" value="{{ $search ?? request('search') }}" placeholder="{{ $isSalesOnly ? 'Cari rute atau mata uang' : 'Cari rute, vendor, atau mata uang' }}" aria-label="Cari trucking price">
    <input name="port_origin" value="{{ request('port_origin') }}" placeholder="Pelabuhan asal" aria-label="Pelabuhan asal">
    <input name="destination" value="{{ request('destination') }}" placeholder="Tujuan" aria-label="Tujuan">
    @unless($isSalesOnly)
    <select name="vendor_id" aria-label="Vendor">
        <option value="">Semua vendor</option>
        @foreach($vendors ?? [] as $vendor)
            <option value="{{ $vendor->id }}" @selected((int) request('vendor_id')===$vendor->id)>{{ $vendor->name }}</option>
        @endforeach
    </select>
    @endunless
    <select name="status" aria-label="Status">
        <option value="">Aktif & nonaktif</option>
        <option value="inactive" @selected(request('status')==='inactive')>Nonaktif saja</option>
    </select>
    <button class="button button-primary">Cari</button>
    <a class="text-link" href="{{ route('pricing.trucking.index') }}">Reset</a>
</form>

<div class="table-scroll">
<table>
<thead>
    <tr>
        <th>Rute</th>
        <th>Harga Jual (Semua Tipe)</th>
        @unless($isSalesOnly)
            <th>Modal Vendor</th>
        @endunless
        <th>Tgl Berlaku</th>
        <th>Status</th>
        <th>Aksi</th>
    </tr>
</thead>
<tbody>
@forelse($items as $item)
    <tr>
        <td>
            <strong>{{ $item->port_origin }}</strong> → {{ $item->destination }}
            <br><small class="muted-cell">20GP / 40FT / 40HQ · {{ $item->total_entries ?? 1 }} entri</small>
        </td>
        <td>
            <strong style="color: #16a34a; font-size: 13.5px;">
                {{ $item->selling_price ? $item->currency . ' ' . number_format((float) $item->selling_price, 0, ',', '.') : 'Belum diset' }}
            </strong>
            <br><small class="muted-cell">1 harga jual rute</small>
        </td>
        @unless($isSalesOnly)
            <td>
                @if(($item->vendors_count ?? 0) > 0)
                    <span class="badge-pill" style="font-weight: 600; color: #1e293b;">
                        {{ $item->vendors_count }} Vendor
                    </span>
                    <br><small class="muted-cell">{{ implode(', ', array_slice($item->vendor_names ?? [], 0, 2)) }}{{ count($item->vendor_names ?? []) > 2 ? '...' : '' }}</small>
                @else
                    <span class="badge-pill" style="color: #b45309; background: #fef3c7; font-size: 11px;">Belum ada modal</span>
                @endif
            </td>
        @endunless
        <td>
            {{ $item->effective_date ? (is_string($item->effective_date) ? \Carbon\Carbon::parse($item->effective_date)->format('d/m/Y') : $item->effective_date->format('d/m/Y')) : '—' }}
            @if($item->effective_until)
                <br><small class="muted-cell">s/d {{ is_string($item->effective_until) ? \Carbon\Carbon::parse($item->effective_until)->format('d/m/Y') : $item->effective_until->format('d/m/Y') }}</small>
            @endif
        </td>
        <td>
            @if($item->is_active)
                <span class="status-badge status-active">Aktif</span>
            @else
                <span class="status-badge status-inactive">Nonaktif</span>
            @endif
        </td>
        <td>
            <div class="table-actions">
                <a class="btn-action btn-action-primary" href="{{ route('pricing.trucking.show', $item->id) }}" title="Buka Rute & Lihat List Modal Vendor" data-tooltip="Buka Rute" aria-label="Buka Rute"><x-icon name="eye"/></a>
                @can('pricing.manage')
                    <a class="btn-action" href="{{ route('pricing.trucking.edit', $item->id) }}" title="Edit Trucking Price" data-tooltip="Edit" aria-label="Edit Trucking Price"><x-icon name="edit"/></a>
                    <form method="POST" action="{{ route('pricing.trucking.toggle', $item->id) }}" data-confirm="{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }} rute trucking ini?">
                        @csrf
                        <input type="hidden" name="lock_version" value="{{ $item->lock_version ?? 0 }}">
                        <button class="btn-action {{ $item->is_active ? 'btn-action-warning' : 'btn-action-success' }}" title="{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" data-tooltip="{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                            <x-icon name="{{ $item->is_active ? 'power' : 'check' }}"/>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('pricing.trucking.destroy', $item->id) }}" data-confirm="Hapus trucking price ini?">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="lock_version" value="{{ $item->lock_version ?? 0 }}">
                        <button class="btn-action btn-action-danger" title="Hapus Trucking Price" data-tooltip="Hapus" aria-label="Hapus Trucking Price">
                            <x-icon name="trash"/>
                        </button>
                    </form>
                @endcan
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="{{ $isSalesOnly ? 5 : 6 }}">
            <div class="empty-state">
                <x-icon name="briefcase"/>
                <h3>Belum ada trucking price</h3>
                <p>Tambahkan trucking price atau sesuaikan filter.</p>
            </div>
        </td>
    </tr>
@endforelse
</tbody>
</table>
</div>
<div class="pagination">{{ $items->links() }}</div>
</section>
@endsection
