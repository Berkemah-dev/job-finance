@extends('layouts.app')
@section('title',$customer->exists?'Edit Customer':'Tambah Customer')
@section('content')
<div class="page-heading"><div><p class="eyebrow">MASTER DATA</p><h1>{{ $customer->exists?'Edit customer':'Tambah customer' }}</h1><p>Identitas customer digunakan pada quotation dan pekerjaan.</p></div><a class="text-link" href="{{ route('customers.index') }}">Kembali ke daftar</a></div>
<section class="panel form-panel"><form class="data-form" method="POST" action="{{ $customer->exists?route('customers.update',$customer):route('customers.store') }}">@csrf @if($customer->exists) @method('PUT') @endif
<input type="hidden" name="lock_version" value="{{ old('lock_version',$customer->lock_version ?? 0) }}">
<div class="form-grid">
@foreach(['code'=>['Kode customer','CUS-001'],'name'=>['Nama customer','PT Nama Perusahaan'],'contact_name'=>['Nama kontak','Nama penanggung jawab'],'email'=>['Email','nama@perusahaan.com'],'phone'=>['Telepon','0812...'],'tax_number'=>['NPWP','Nomor pokok wajib pajak']] as $field=>[$label,$placeholder])
<div class="field"><label for="{{ $field }}">{{ $label }} @if(in_array($field,['code','name']))<span class="required">*</span>@endif</label><input id="{{ $field }}" name="{{ $field }}" type="{{ $field==='email'?'email':'text' }}" value="{{ old($field,$customer->$field) }}" placeholder="{{ $placeholder }}" maxlength="{{ $field==='code'?30:(in_array($field,['phone','tax_number'])?40:255) }}" @required(in_array($field,['code','name']))></div>
@endforeach
<div class="field span-2"><label for="address">Alamat</label><textarea id="address" name="address" rows="3" maxlength="2000">{{ old('address',$customer->address) }}</textarea></div></div>
<div class="form-actions"><a class="button button-secondary" href="{{ route('customers.index') }}">Batal</a><button class="button button-primary">Simpan customer</button></div></form></section>
@if($customer->exists)<section class="archive-panel"><div><h3>Arsipkan customer</h3><p>Customer tidak bisa dipilih untuk transaksi baru. Histori tetap tersimpan.</p></div><form method="POST" action="{{ route('customers.destroy',$customer) }}" data-confirm="Arsipkan customer ini? Histori transaksi tetap tersimpan.">@csrf @method('DELETE')<input type="hidden" name="lock_version" value="{{ $customer->lock_version }}"><button class="button button-danger">Arsipkan</button></form></section>@endif
@endsection
