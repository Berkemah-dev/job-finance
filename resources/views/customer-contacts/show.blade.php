@extends('layouts.app')
@section('title','Detail Kontak')
@section('content')
<div class="page-heading"><div><p class="eyebrow">SALES & CUSTOMER</p><h1>{{ $contact->name }}</h1><p>{{ $contact->customer?->name }}</p></div><a class="text-link" href="{{ route('customer-contacts.index') }}">Kembali</a></div>
<section class="panel"><div class="panel-heading"><h2>Detail kontak</h2>@if($contact->is_active)<span class="status-badge status-active">Aktif</span>@else<span class="status-badge status-inactive">Nonaktif</span>@endif</div>
<dl class="detail-grid"><div><dt>Tipe</dt><dd>{{ \Illuminate\Support\Str::ucfirst($contact->type) }}</dd></div><div><dt>Customer</dt><dd>{{ $contact->customer?->name ?? '—' }}</dd></div><div><dt>Email</dt><dd>{{ $contact->email ?? '—' }}</dd></div><div><dt>Telepon</dt><dd>{{ $contact->phone ?? '—' }}</dd></div><div><dt>Negara</dt><dd>{{ $contact->country ?? '—' }}</dd></div><div><dt>Alamat</dt><dd>{{ $contact->address ?? '—' }}</dd></div><div><dt>Catatan</dt><dd>{{ $contact->notes ?? '—' }}</dd></div></dl>
<div class="quote-actions"><a class="button button-secondary" href="{{ route('customer-contacts.edit',$contact) }}">Edit kontak</a></div></section>
@endsection