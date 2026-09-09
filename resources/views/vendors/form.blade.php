@extends('layouts.app')
@section('title',$vendor->exists?'Edit Vendor':'Tambah Vendor')
@section('content')
<div class="page-heading"><div><p class="eyebrow">MASTER DATA</p><h1>{{ $vendor->exists?'Edit vendor':'Tambah vendor' }}</h1><p>Data vendor digunakan pada trucking pricing dan biaya operasional.</p></div><a class="text-link" href="{{ route('vendors.index') }}">Kembali ke daftar</a></div>
<section class="panel form-panel"><form class="data-form" method="POST" action="{{ $vendor->exists?route('vendors.update',$vendor):route('vendors.store') }}">@csrf @if($vendor->exists) @method('PUT') @endif
<input type="hidden" name="lock_version" value="{{ old('lock_version',$vendor->lock_version ?? 0) }}">
<div class="form-grid">
@foreach(['code'=>['Kode vendor','VND-001'],'name'=>['Nama vendor','PT Mitra Logistik']] as $field=>[$label,$placeholder])
<div class="field"><label for="{{ $field }}">{{ $label }} <span class="required">*</span></label><input id="{{ $field }}" name="{{ $field }}" type="text" value="{{ old($field,$vendor->$field) }}" placeholder="{{ $placeholder }}" maxlength="{{ $field==='code'?30:255 }}" required></div>
@endforeach
<div class="field"><label for="type">Kategori vendor <span class="required">*</span></label><select id="type" name="type" required>@foreach(config('operations.vendor_types') as $value=>$label)<option value="{{ $value }}" @selected(old('type',$vendor->type)===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="pic">PIC</label><input id="pic" name="pic" value="{{ old('pic',$vendor->pic) }}" placeholder="Nama penanggung jawab"></div>
<div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email',$vendor->email) }}" placeholder="nama@vendor.com"></div>
<div class="field"><label for="phone">Telepon</label><input id="phone" name="phone" value="{{ old('phone',$vendor->phone) }}" placeholder="0812..."></div>
<div class="field"><label for="tax_number">NPWP</label><input id="tax_number" name="tax_number" value="{{ old('tax_number',$vendor->tax_number) }}" placeholder="Nomor pokok wajib pajak"></div>
<div class="field span-2"><label for="address">Alamat</label><textarea id="address" name="address" rows="3" maxlength="2000">{{ old('address',$vendor->address) }}</textarea></div>
<div class="field"><label for="is_active">Status</label><select id="is_active" name="is_active"><option value="1" @selected(!$vendor->exists || $vendor->is_active)>Aktif</option><option value="0" @selected($vendor->exists && !$vendor->is_active)>Nonaktif</option></select></div>
</div>
<div class="form-actions"><a class="button button-secondary" href="{{ route('vendors.index') }}">Batal</a><button class="button button-primary">Simpan vendor</button></div></form></section>
@if($vendor->exists)<section class="archive-panel"><div><h3>Arsipkan vendor</h3><p>Vendor tidak bisa dipilih untuk transaksi baru. Histori tetap tersimpan.</p></div><form method="POST" action="{{ route('vendors.destroy',$vendor) }}" data-confirm="Arsipkan vendor ini? Histori transaksi tetap tersimpan.">@csrf @method('DELETE')<input type="hidden" name="lock_version" value="{{ $vendor->lock_version }}"><button class="button button-danger">Arsipkan</button></form></section>@endif
@endsection