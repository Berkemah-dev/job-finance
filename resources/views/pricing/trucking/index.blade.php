@extends('layouts.app')
@section('title','Trucking Price List')
@section('content')
<x-menu-banner
    tag="PRICING & LOGISTIK"
    title="Trucking Price List"
    description="Tarif trucking standar berdasarkan rute asal, tujuan, tipe kontainer, dan vendor armada."
    :action-url="auth()->user()->can('pricing.manage') ? route('pricing.trucking.create') : null"
    action-label="+ Tambah Tarif Trucking"
    action-icon="plus"
    icon="briefcase"
    art-title="Rute & armada,"
    art-subtitle="harga transparan."
/>
<section class="panel">
<form class="filter-bar" method="GET">
    <input name="port_origin" value="{{ request('port_origin') }}" placeholder="Pelabuhan asal" aria-label="Pelabuhan asal">
    <input name="destination" value="{{ request('destination') }}" placeholder="Tujuan" aria-label="Tujuan">
    <select name="vendor_id" aria-label="Vendor">
        <option value="">Semua vendor</option>
        @foreach($vendors ?? [] as $vendor)
            <option value="{{ $vendor->id }}" @selected((int) request('vendor_id')===$vendor->id)>{{ $vendor->name }}</option>
        @endforeach
    </select>
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
        <th>Vendor Trucking</th>
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
            <br><small class="muted-cell">{{ config('operations.container_types.'.$item->container_type, $item->container_type) }} · {{ $item->overweight ? 'Overweight' : 'Normal' }}</small>
        </td>
        <td>{{ $item->vendor?->name ?? '—' }}</td>
        <td>
            {{ $item->effective_date?->format('d/m/Y') }}
            @if($item->effective_until)
                <br><small class="muted-cell">s/d {{ $item->effective_until->format('d/m/Y') }}</small>
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
                <a class="btn-action btn-action-primary" href="{{ route('pricing.trucking.show', $item) }}" title="Lihat Detail Tarif (20GP / 40FT / 40HQ)" data-tooltip="Lihat Detail" aria-label="Lihat Detail"><x-icon name="eye"/></a>
                @can('pricing.manage')
                    <a class="btn-action" href="{{ route('pricing.trucking.edit', $item) }}" title="Edit Trucking Price" data-tooltip="Edit" aria-label="Edit Trucking Price"><x-icon name="edit"/></a>
                    <form method="POST" action="{{ route('pricing.trucking.toggle', $item) }}" data-confirm="{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }} trucking price ini?">
                        @csrf
                        <input type="hidden" name="lock_version" value="{{ $item->lock_version }}">
                        <button class="btn-action {{ $item->is_active ? 'btn-action-warning' : 'btn-action-success' }}" title="{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" data-tooltip="{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                            <x-icon name="{{ $item->is_active ? 'power' : 'check' }}"/>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('pricing.trucking.destroy', $item) }}" data-confirm="Hapus trucking price ini?">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="lock_version" value="{{ $item->lock_version }}">
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
        <td colspan="5">
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
