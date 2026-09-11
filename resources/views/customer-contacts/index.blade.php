@extends('layouts.app')
@section('title','Consignee & Shipper')
@section('content')
<div class="page-heading"><div><p class="eyebrow">SALES & CUSTOMER</p><h1>Consignee &amp; Shipper</h1><p>Master kontak tujuan dan pengirim untuk quotation &amp; dokumen.</p></div><span class="date-chip"><x-icon name="calendar"/>{{ now()->locale('id')->translatedFormat('d F Y') }}</span></div>
<x-menu-banner
    tag="SALES & CUSTOMER"
    title="Kontak Shipper & Consignee"
    description="Master kontak pengirim dan penerima barang untuk quotation, surat jalan, dan dokumen operasional."
    :action-url="route('customer-contacts.create')"
    action-label="+ Tambah Kontak"
    action-icon="plus"
    icon="users"
    art-title="Kontak logistik,"
    art-subtitle="siap digunakan."
/>
<section class="panel">
<form class="filter-bar" method="GET"><input name="search" value="{{ $search }}" placeholder="Cari nama, perusahaan, atau email" aria-label="Cari kontak"><select name="type" aria-label="Tipe kontak"><option value="">Semua tipe</option><option value="shipper" @selected($type==='shipper')>Shipper</option><option value="consignee" @selected($type==='consignee')>Consignee</option></select><select name="status" aria-label="Status kontak"><option value="active" @selected($status==='active')>Aktif</option><option value="inactive" @selected($status==='inactive')>Nonaktif</option></select><button class="button button-primary">Cari</button><a class="text-link" href="{{ route('customer-contacts.index') }}">Reset</a></form>
<div class="table-scroll"><table><thead><tr><th>Tipe</th><th>Nama / Perusahaan</th><th>Email / Telepon</th><th>Negara</th><th>Customer</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($contacts as $contact)<tr><td><span class="status-badge status-{{ $contact->type }}">{{ \Illuminate\Support\Str::ucfirst($contact->type) }}</span></td><td><strong>{{ $contact->name }}</strong>@if($contact->company)<br><small>{{ $contact->company }}</small>@endif</td><td>{{ $contact->email ?? '—' }}<br><small>{{ $contact->phone ?? '' }}</small></td><td>{{ $contact->country ?? '—' }}</td><td>{{ $contact->customer?->name ?? '—' }}</td><td>@if($contact->is_active)<span class="status-badge status-active">Aktif</span>@else<span class="status-badge status-inactive">Nonaktif</span>@endif</td><td><a class="text-link" href="{{ route('customer-contacts.show',$contact) }}">Detail</a> · <a class="text-link" href="{{ route('customer-contacts.edit',$contact) }}">Edit</a></td></tr>@empty<tr><td colspan="7"><div class="empty-state"><x-icon name="users"/><h3>Belum ada kontak</h3><p>Tambahkan shipper/consignee dari sini atau melalui form customer.</p></div></td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $contacts->links() }}</div></section>
@endsection