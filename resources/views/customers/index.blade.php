@extends('layouts.app')
@section('title','Customer')
@section('content')
<x-menu-banner
    tag="SALES & CUSTOMER"
    title="Master Data Customer"
    description="Kelola identitas pelanggan, NPWP, batas kredit, kontak PIC, dan riwayat dokumen."
    :action-url="route('customers.create')"
    action-label="+ Tambah Customer"
    action-icon="plus"
    icon="users"
    art-title="Data pelanggan,"
    art-subtitle="terkelola rapi."
/>
<section class="panel">
<form class="filter-bar" method="GET"><input name="search" value="{{ $search }}" placeholder="Cari nama, kode, email, NPWP, atau telepon" aria-label="Cari customer"><select name="status" aria-label="Status customer"><option value="active" @selected($status==='active')>Customer aktif</option><option value="inactive" @selected($status==='inactive')>Nonaktif / diarsipkan</option></select><button class="button button-primary">Cari</button><a class="text-link" href="{{ route('customers.index') }}">Reset</a></form>
<div class="table-scroll"><table><thead><tr><th>Kode</th><th>Customer</th><th>Kontak</th><th>Email / Telepon</th><th>NPWP</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($customers as $customer)<tr><td>{{ $customer->code }}</td><td><strong>{{ $customer->name }}</strong></td><td>{{ $customer->contact_name ?? '—' }}</td><td>{{ $customer->email ?? '—' }}<br><small>{{ $customer->phone }}</small></td><td>{{ $customer->tax_number ?? '—' }}</td><td>@if($customer->trashed())<span class="status-badge status-inactive">Nonaktif</span>@else<span class="status-badge status-active">Aktif</span>@endif</td><td>@if(!$customer->trashed())<a class="text-link" href="{{ route('customers.show',$customer) }}">Detail</a> · <a class="text-link" href="{{ route('customers.edit',$customer) }}">Edit</a>@else<form method="POST" action="{{ route('customers.restore',$customer) }}" data-confirm="Aktifkan kembali customer ini?">@csrf<input type="hidden" name="lock_version" value="{{ $customer->lock_version }}"><button class="text-link">Aktifkan</button></form>@endif</td></tr>@empty<tr><td colspan="7"><div class="empty-state"><x-icon name="users"/><h3>Belum ada customer yang sesuai</h3><p>Tambahkan customer atau sesuaikan pencarian.</p></div></td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $customers->links() }}</div></section>
@endsection