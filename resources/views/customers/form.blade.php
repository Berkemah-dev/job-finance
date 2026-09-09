@extends('layouts.app')
@section('title',$customer->exists?'Edit Customer':'Tambah Customer')
@section('content')
<div class="page-heading"><div><p class="eyebrow">MASTER DATA</p><h1>{{ $customer->exists?'Edit customer':'Tambah customer' }}</h1><p>Identitas customer digunakan pada quotation, invoice, dan pekerjaan.</p></div><a class="text-link" href="{{ route('customers.index') }}">Kembali ke daftar</a></div>
<section class="panel form-panel"><form class="data-form" method="POST" action="{{ $customer->exists?route('customers.update',$customer):route('customers.store') }}" enctype="multipart/form-data">@csrf @if($customer->exists) @method('PUT') @endif
<input type="hidden" name="lock_version" value="{{ old('lock_version',$customer->lock_version ?? 0) }}">
<div class="form-grid">
@foreach(['code'=>['Kode customer','CUS-001','text',30],'name'=>['Nama customer','PT Nama Perusahaan','text',255],'contact_name'=>['Nama kontak','Nama penanggung jawab','text',255],'email'=>['Email','nama@perusahaan.com','email',255],'phone'=>['Telepon','0812...','text',40],'tax_number'=>['NPWP','Nomor pokok wajib pajak','text',40],'default_payment_terms'=>['Syarat pembayaran','cth. Net 30','text',60]] as $field=>[$label,$placeholder,$type,$max])
<div class="field"><label for="{{ $field }}">{{ $label }} @if(in_array($field,['code','name']))<span class="required">*</span>@endif</label><input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field,$customer->$field) }}" placeholder="{{ $placeholder }}" maxlength="{{ $max }}" @required(in_array($field,['code','name']))></div>
@endforeach
<div class="field span-2"><label for="address">Alamat</label><textarea id="address" name="address" rows="3" maxlength="2000">{{ old('address',$customer->address) }}</textarea></div>
<div class="field"><label for="npwp_file">Dokumen NPWP</label><input id="npwp_file" name="npwp_file" type="file" accept=".pdf,.jpg,.jpeg,.png"><small>PDF / gambar, maks. 2 MB.@if($customer->npwp_file) Saat ini: <strong>{{ basename($customer->npwp_file) }}</strong>.@endif</small></div>
<div class="field"><label for="nib_file">Dokumen NIB</label><input id="nib_file" name="nib_file" type="file" accept=".pdf,.jpg,.jpeg,.png"><small>PDF / gambar, maks. 2 MB.@if($customer->nib_file) Saat ini: <strong>{{ basename($customer->nib_file) }}</strong>.@endif</small></div>
</div>
<div class="form-section-heading"><h2>Shipper / Consignee</h2><p>Data ini dapat dipilih saat membuat Job Order, BL, AWB, atau dokumen shipment.</p></div>
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
<div class="form-actions"><a class="button button-secondary" href="{{ route('customers.index') }}">Batal</a><button class="button button-primary">Simpan customer</button></div></form></section>
@if($customer->exists)<section class="archive-panel"><div><h3>Arsipkan customer</h3><p>Customer tidak bisa dipilih untuk transaksi baru. Histori tetap tersimpan.</p></div><form method="POST" action="{{ route('customers.destroy',$customer) }}" data-confirm="Arsipkan customer ini? Histori transaksi tetap tersimpan.">@csrf @method('DELETE')<input type="hidden" name="lock_version" value="{{ $customer->lock_version }}"><button class="button button-danger">Arsipkan</button></form></section>@endif
@endsection