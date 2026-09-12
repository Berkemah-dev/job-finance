@extends('layouts.app')
@section('title',$contact->exists?'Edit Kontak':'Tambah Kontak')
@section('content')
<div class="page-heading"><div><p class="eyebrow">SALES & CUSTOMER</p><h1>{{ $contact->exists?'Edit kontak':'Tambah kontak' }}</h1><p>Shipper (pengirim) dan consignee (penerima) yang digunakan pada quotation &amp; dokumen.</p></div><a class="text-link" href="{{ $contact->exists?route('customer-contacts.show',$contact):route('customer-contacts.index') }}">Kembali</a></div>
<section class="panel form-panel"><form class="data-form" method="POST" action="{{ $contact->exists?route('customer-contacts.update',$contact):route('customer-contacts.store') }}">@csrf @if($contact->exists) @method('PUT') @endif
<input type="hidden" name="lock_version" value="{{ old('lock_version',$contact->lock_version ?? 0) }}">
<div class="form-grid">
<div class="field span-2"><label for="customer_id">Customer <span class="required">*</span></label><select id="customer_id" name="customer_id" required><option value="">— Pilih customer —</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected((int)old('customer_id',$contact->customer_id)===(int)$customer->id)>{{ $customer->code }} · {{ $customer->name }}</option>@endforeach</select></div>
<div class="field"><label for="type">Tipe <span class="required">*</span></label><select id="type" name="type" required><option value="" disabled @selected(!$contact->type)>—</option><option value="shipper" @selected(old('type',$contact->type)==='shipper')>Shipper (Pengirim)</option><option value="consignee" @selected(old('type',$contact->type)==='consignee')>Consignee (Penerima)</option></select></div>
<div class="field"><label for="name">Nama / Perusahaan <span class="required">*</span></label><input id="name" name="name" value="{{ old('name',$contact->name) }}" maxlength="255" required></div>
<div class="field"><label for="company">Perusahaan</label><input id="company" name="company" value="{{ old('company',$contact->company) }}" maxlength="255"></div>
<div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email',$contact->email) }}" maxlength="255"></div>
<div class="field"><label for="phone">Telepon</label><input id="phone" name="phone" value="{{ old('phone',$contact->phone) }}" maxlength="40"></div>
<div class="field"><label for="country">Negara</label><input id="country" name="country" value="{{ old('country',$contact->country) }}" maxlength="120" placeholder="cth: Indonesia"></div>
<div class="field"><label for="notes">Catatan</label><textarea id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes',$contact->notes) }}</textarea></div>
<div class="field checkbox-field" style="display:flex;align-items:center;min-height:44px;padding-top:18px;"><label for="is_active" style="display:inline-flex;align-items:center;gap:10px;margin:0;cursor:pointer;line-height:1.2;"><input id="is_active" type="checkbox" name="is_active" value="1" @checked(filter_var(old('is_active',$contact->is_active ?? 1), FILTER_VALIDATE_BOOL)) style="width:18px;height:18px;margin:0;flex:0 0 auto;"> <span>Kontak aktif</span></label></div>
</div>
<div class="form-actions"><a class="button button-secondary" href="{{ $contact->exists?route('customer-contacts.show',$contact):route('customer-contacts.index') }}">Batal</a><button class="button button-primary">Simpan kontak</button></div></form></section>
@if($contact->exists)<section class="archive-panel"><div><h3>Hapus kontak</h3><p>Kontak yang masih terpakai pada quotation lama tetap tampil sebagai snapshot.</p></div><form method="POST" action="{{ route('customer-contacts.destroy',$contact) }}" data-confirm="Hapus kontak ini?">@csrf @method('DELETE')<input type="hidden" name="lock_version" value="{{ $contact->lock_version }}"><button class="button button-danger">Hapus kontak</button></form></section>@endif
@endsection
