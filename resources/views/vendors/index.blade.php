@extends('layouts.app')
@section('title','Vendor')
@section('content')
<x-menu-banner
    tag="SALES & CUSTOMER"
    title="Master Data Vendor"
    description="Kelola Shipping Lines, Vendor Trucking, dan agent pendukung operasional ekspedisi."
    :action-url="route('vendors.create')"
    action-label="+ Tambah Vendor"
    action-icon="plus"
    icon="users"
    art-title="Mitra vendor,"
    art-subtitle="terverifikasi."
/>
<section class="panel">
<form class="filter-bar" method="GET"><input name="search" value="{{ $search }}" placeholder="Cari nama, kode, email, atau negara" aria-label="Cari vendor"><select name="category" aria-label="Kategori vendor"><option value="">Semua kategori</option>@foreach(config('operations.vendor_types') as $key=>$label)<option value="{{ $key }}" @selected($category===$key)>{{ $label }}</option>@endforeach</select><select name="status" aria-label="Status vendor"><option value="">Semua status</option><option value="active" @selected($status==='active')>Aktif</option><option value="inactive" @selected($status==='inactive')>Nonaktif</option><option value="archived" @selected($status==='archived')>Diarsipkan</option></select><button class="button button-primary">Cari</button><a class="text-link" href="{{ route('vendors.index') }}">Reset</a></form>
<div class="table-scroll"><table><thead><tr><th>Kode</th><th>Vendor</th><th>Kategori</th><th>Kontak</th><th>Negara</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($vendors as $vendor)<tr><td>{{ $vendor->code }}</td><td><strong>{{ $vendor->name }}</strong></td><td>@foreach($vendor->categoryLabels() as $label)<span class="badge-pill">{{ $label }}</span>@endforeach</td><td>{{ $vendor->pic ?? '—' }}<br><small>{{ $vendor->email ?? $vendor->phone }}</small></td><td>{{ $vendor->country ?? '—' }}</td><td>@if($vendor->trashed())<span class="status-badge status-inactive">Diarsipkan</span>@elseif($vendor->is_active)<span class="status-badge status-active">Aktif</span>@else<span class="status-badge status-inactive">Nonaktif</span>@endif</td><td><div class="table-actions">@if(!$vendor->trashed())<a class="btn-action btn-action-primary" href="{{ route('vendors.show',$vendor) }}">Detail</a><a class="btn-action" href="{{ route('vendors.edit',$vendor) }}">Edit</a>@else<form method="POST" action="{{ route('vendors.restore',$vendor) }}" data-confirm="Aktifkan kembali vendor ini?">@csrf<input type="hidden" name="lock_version" value="{{ $vendor->lock_version }}"><button class="btn-action btn-action-success">Aktifkan</button></form>@endif</div></td></tr>@empty<tr><td colspan="7"><div class="empty-state"><x-icon name="users"/><h3>Belum ada vendor yang sesuai</h3><p>Tambahkan vendor atau sesuaikan pencarian.</p></div></td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $vendors->links() }}</div></section>
@endsection