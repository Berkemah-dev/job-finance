@extends('layouts.app')
@section('title', 'Partner / Vendor')
@section('content')
<x-menu-banner
    tag="SALES & CUSTOMER"
    title="Master Data Partner"
    description="Kelola Mitra Partner: Shipping Lines, Vendor Trucking, Agen, dan mitra pendukung operasional ekspedisi."
    :action-url="route('vendors.create')"
    action-label="Tambah Partner"
    action-icon="plus"
    icon="users"
    art-title="Mitra partner,"
    art-subtitle="terverifikasi."
/>
<section class="panel">
<form class="filter-bar" method="GET">
    <input name="search" value="{{ $search }}" placeholder="Cari nama, kode, email, atau negara" aria-label="Cari partner">
    <select name="category" aria-label="Kategori partner">
        <option value="">Semua kategori</option>
        @foreach(config('operations.vendor_types') as $key => $label)
            <option value="{{ $key }}" @selected($category === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="status" aria-label="Status partner">
        <option value="">Semua status</option>
        <option value="active" @selected($status === 'active')>Aktif</option>
        <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
        <option value="archived" @selected($status === 'archived')>Diarsipkan</option>
    </select>
    <button class="button button-primary">Cari</button>
    <a class="text-link" href="{{ route('vendors.index') }}">Reset</a>
</form>
<div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Partner</th>
                <th>Kategori</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vendors as $vendor)
                <tr>
                    <td><strong>{{ $vendor->code }}</strong></td>
                    <td><strong>{{ $vendor->name }}</strong></td>
                    <td>
                        @foreach($vendor->categoryLabels() as $label)
                            <span class="badge-pill">{{ $label }}</span>
                        @endforeach
                    </td>
                    <td>
                        @if($vendor->trashed())
                            <span class="status-badge status-inactive">Diarsipkan</span>
                        @elseif($vendor->is_active)
                            <span class="status-badge status-active">Aktif</span>
                        @else
                            <span class="status-badge status-inactive">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <div class="table-actions">
                            @if(!$vendor->trashed())
                                <a class="btn-action btn-action-primary" href="{{ route('vendors.show', $vendor) }}" title="Detail Partner" data-tooltip="Detail" aria-label="Detail Partner"><x-icon name="eye"/></a>
                                <a class="btn-action" href="{{ route('vendors.edit', $vendor) }}" title="Edit Partner" data-tooltip="Edit" aria-label="Edit Partner"><x-icon name="edit"/></a>
                            @else
                                <form method="POST" action="{{ route('vendors.restore', $vendor) }}" data-confirm="Aktifkan kembali partner ini?">
                                    @csrf
                                    <input type="hidden" name="lock_version" value="{{ $vendor->lock_version }}">
                                    <button class="btn-action btn-action-success" title="Aktifkan Partner" data-tooltip="Aktifkan" aria-label="Aktifkan Partner"><x-icon name="check"/></button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <x-icon name="users"/>
                            <h3>Belum ada partner yang sesuai</h3>
                            <p>Tambahkan mitra partner atau sesuaikan pencarian.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="pagination">{{ $vendors->links() }}</div>
</section>
@endsection