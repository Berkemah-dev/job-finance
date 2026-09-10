@extends('layouts.app')
@section('title',$customer->exists?'Edit Customer':'Tambah Customer')
@section('content')
<div class="page-heading"><div><p class="eyebrow">SALES & CUSTOMER</p><h1>{{ $customer->exists?'Edit customer':'Tambah customer' }}</h1><p>Identitas customer digunakan pada quotation, invoice, dan pekerjaan.</p></div><a class="text-link" href="{{ $customer->exists?route('customers.show',$customer):route('customers.index') }}">Kembali</a></div>
<section class="panel form-panel"><form class="data-form" method="POST" action="{{ $customer->exists?route('customers.update',$customer):route('customers.store') }}" enctype="multipart/form-data">@csrf @if($customer->exists) @method('PUT') @endif
<input type="hidden" name="lock_version" value="{{ old('lock_version',$customer->lock_version ?? 0) }}">
<div class="form-grid">
<div class="field"><label for="code">Kode customer</label><input id="code" name="code" type="text" value="{{ $customer->exists?$customer->code:'' }}" placeholder="Otomatis dari sistem" maxlength="30" readonly tabindex="-1"><small class="form-help">Kode dibuat otomatis oleh sistem dan tidak dapat diubah.</small></div>
<div class="field"><label for="name">Nama customer <span class="required">*</span></label><input id="name" name="name" type="text" value="{{ old('name',$customer->name) }}" placeholder="PT Nama Perusahaan" maxlength="255" required></div>
<div class="field"><label for="contact_name">Nama kontak</label><input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name',$customer->contact_name) }}" placeholder="Nama penanggung jawab" maxlength="255"></div>
<div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email',$customer->email) }}" placeholder="nama@perusahaan.com" maxlength="255"></div>
<div class="field"><label for="phone">Telepon</label><input id="phone" name="phone" type="text" value="{{ old('phone',$customer->phone) }}" placeholder="0812..." maxlength="40"></div>
<div class="field"><label for="tax_number">NPWP</label><input id="tax_number" name="tax_number" type="text" value="{{ old('tax_number',$customer->tax_number) }}" placeholder="00.000.000.0-000.000 (15-16 digit)" maxlength="40" inputmode="numeric"><small class="form-help">15–16 digit angka tanpa simbol.</small></div>
<div class="field"><x-payment-term-select name="default_payment_terms" label="Syarat pembayaran default" :value="old('default_payment_terms',$customer->default_payment_terms)"></x-payment-term-select><small class="form-help">Otomatis terisi pada quotation saat customer dipilih; dapat diubah per quotation.</small></div>
<div class="field span-2"><label for="address">Alamat</label><textarea id="address" name="address" rows="3" maxlength="2000">{{ old('address',$customer->address) }}</textarea></div>
<div class="field"><label for="npwp_file">Dokumen NPWP</label><input id="npwp_file" name="npwp_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp"><small class="form-help">PDF / gambar, maks. 5 MB.@if($customer->npwp_file) Saat ini: <strong>{{ basename($customer->npwp_file) }}</strong>.@endif</small></div>
<div class="field"><label for="nib_file">Dokumen NIB</label><input id="nib_file" name="nib_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp"><small class="form-help">PDF / gambar, maks. 5 MB.@if($customer->nib_file) Saat ini: <strong>{{ basename($customer->nib_file) }}</strong>.@endif</small></div>
</div>
<div class="form-section-heading"><h2>Shipper / Consignee</h2><p>Kelola kontak shipper/consignee disini, atau gunakan master <a class="text-link" href="{{ route('customer-contacts.index') }}">Consignee / Shipper</a>.</p></div>
<div class="contacts-editor" data-customer-contacts data-prototype='@include("customers.contact-row",["contact"=>null,"i"=>"__index__"])'>
<div class="contacts-rows" data-contacts-rows>
@if(old('contacts'))
    @foreach(old('contacts') as $i=>$contact)<div class="contact-row" data-contact-row>@include("customers.contact-row",["contact"=>$contact,"i"=>$i])</div>@endforeach
@elseif($customer->exists && $customer->contacts()->count())
    @foreach($customer->contacts as $i=>$contact)<div class="contact-row" data-contact-row>@include("customers.contact-row",["contact"=>$contact,"i"=>$i])</div>@endforeach
@endif
</div>
<button type="button" class="button button-secondary" data-contacts-add>+ Tambah shipper / consignee</button>
</div>
<div class="form-actions"><a class="button button-secondary" href="{{ $customer->exists?route('customers.show',$customer):route('customers.index') }}">Batal</a><button class="button button-primary">Simpan customer</button></div></form></section>
@if($customer->exists)<section class="archive-panel"><div><h3>Arsipkan customer</h3><p>Customer tidak bisa dipilih untuk transaksi baru. Histori tetap tersimpan.</p></div><form method="POST" action="{{ route('customers.destroy',$customer) }}" data-confirm="Arsipkan customer ini? Histori transaksi tetap tersimpan.">@csrf @method('DELETE')<input type="hidden" name="lock_version" value="{{ $customer->lock_version }}"><button class="button button-danger">Arsipkan</button></form></section>@endif
@endsection