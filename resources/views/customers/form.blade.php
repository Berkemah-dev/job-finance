@extends('layouts.app')
@section('title',$customer->exists?'Edit Customer':'Tambah Customer')
@section('content')
<div class="page-heading"><div><p class="eyebrow">SALES & CUSTOMER</p><h1>{{ $customer->exists?'Edit customer':'Tambah customer' }}</h1><p>Identitas customer digunakan pada quotation, invoice, dan pekerjaan.</p></div><a class="button button-secondary" href="{{ $customer->exists?route('customers.show',$customer):route('customers.index') }}">← Kembali</a></div>

<form class="data-form" method="POST" action="{{ $customer->exists?route('customers.update',$customer):route('customers.store') }}" enctype="multipart/form-data" style="max-width: 1100px;">@csrf @if($customer->exists) @method('PUT') @endif
<input type="hidden" name="lock_version" value="{{ old('lock_version',$customer->lock_version ?? 0) }}">

{{-- Card 1: Informasi Utama --}}
<section class="panel" style="margin-bottom: 20px;">
    <div class="panel-heading" style="padding: 16px 24px; border-bottom: 1px solid #edf1f7;">
        <h2 style="font-size: 13px; font-weight: 600; color: #1e3a8a;">👤 Informasi Utama</h2>
        <span class="subtle" style="font-size: 11px;">Data identitas customer</span>
    </div>
    <div class="form-grid" style="padding: 24px;">
        <div class="field"><label for="code">Kode customer</label><input id="code" @if(!$customer->exists) name="code" @endif type="text" value="{{ $customer->exists ? $customer->code : '' }}" placeholder="Otomatis dari sistem" maxlength="30" readonly tabindex="-1"><small class="form-help">Kode dibuat otomatis oleh sistem dan tidak dapat diubah.</small></div>
        <div class="field"><label for="name">Nama customer <span class="required">*</span></label><input id="name" name="name" type="text" value="{{ old('name',$customer->name) }}" placeholder="PT Nama Perusahaan" maxlength="255" required></div>
        <div class="field"><label for="contact_name">Nama kontak</label><input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name',$customer->contact_name) }}" placeholder="Nama penanggung jawab" maxlength="255"></div>
        <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email',$customer->email) }}" placeholder="nama@perusahaan.com" maxlength="255"></div>
        <div class="field"><label for="phone">Telepon</label><input id="phone" name="phone" type="text" value="{{ old('phone',$customer->phone) }}" placeholder="0812..." maxlength="40"></div>
        <div class="field"><label for="tax_number">NPWP</label><input id="tax_number" name="tax_number" type="text" value="{{ old('tax_number',$customer->tax_number) }}" placeholder="00.000.000.0-000.000 (15-16 digit)" maxlength="40" inputmode="numeric"><small class="form-help">15–16 digit angka tanpa simbol.</small></div>
        <div class="field"><x-payment-term-select name="default_payment_terms" label="Syarat pembayaran default" :value="old('default_payment_terms',$customer->default_payment_terms)"></x-payment-term-select><small class="form-help">Otomatis terisi pada quotation saat customer dipilih; dapat diubah per quotation.</small></div>
    </div>
</section>

{{-- Card 2: Dokumen Legalitas --}}
<section class="panel" style="margin-bottom: 20px;">
    <div class="panel-heading" style="padding: 16px 24px; border-bottom: 1px solid #edf1f7;">
        <h2 style="font-size: 13px; font-weight: 600; color: #1e3a8a;">📎 Dokumen Legalitas</h2>
        <span class="subtle" style="font-size: 11px;">Upload NPWP dan NIB customer</span>
    </div>
    <div class="form-grid" style="padding: 24px;">
        <div class="field"><label for="npwp_file">Dokumen NPWP</label><input id="npwp_file" name="npwp_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp"><small class="form-help">PDF / gambar, maks. 5 MB.@if($customer->npwp_file) Saat ini: <strong>{{ basename($customer->npwp_file) }}</strong>.@endif</small></div>
        <div class="field"><label for="nib_file">Dokumen NIB</label><input id="nib_file" name="nib_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp"><small class="form-help">PDF / gambar, maks. 5 MB.@if($customer->nib_file) Saat ini: <strong>{{ basename($customer->nib_file) }}</strong>.@endif</small></div>
    </div>
</section>

{{-- Card 3: Document Details --}}
<section class="panel" style="margin-bottom: 20px;">
    <div class="panel-heading" style="padding: 16px 24px; border-bottom: 1px solid #edf1f7;">
        <h2 style="font-size: 13px; font-weight: 600; color: #1e3a8a;">📄 DOCUMENT DETAILS</h2>
        <span class="subtle" style="font-size: 11px;">Data resmi untuk penerbitan dokumen operasional & kepabeanan</span>
    </div>
    <div class="form-grid" style="padding: 24px;">
        <div class="field span-2">
            <label for="doc_customer_name">Nama Customer</label>
            <input id="doc_customer_name" type="text" value="{{ old('name',$customer->name) }}" placeholder="Otomatis mengikuti nama customer di atas" readonly style="background: #f8fafc; color: #475569; font-weight: 600;">
            <small class="form-help">Sesuai nama customer yang diinput di atas.</small>
        </div>
        <div class="field span-2">
            <label for="address">ALAMAT PERUSAHAAN <span class="required">*</span></label>
            <textarea id="address" name="address" rows="3" maxlength="2000" placeholder="Alamat lengkap resmi perusahaan...">{{ old('address',$customer->address) }}</textarea>
            <small class="form-help">Akan muncul di Bill of Lading / AWB / SI / Booking Confirmation / SK / DNP</small>
        </div>
        <div class="field">
            <label for="authorizer_name">NAMA PEMBERI KUASA</label>
            <input id="authorizer_name" name="authorizer_name" type="text" value="{{ old('authorizer_name',$customer->authorizer_name) }}" placeholder="Nama pemberi kuasa pabean" maxlength="255">
            <small class="form-help">Akan muncul di DNP / SK</small>
        </div>
        <div class="field">
            <label for="authorizer_title">JABATAN</label>
            <input id="authorizer_title" name="authorizer_title" type="text" value="{{ old('authorizer_title',$customer->authorizer_title) }}" placeholder="cth: Direktur / General Manager" maxlength="255">
            <small class="form-help">Akan muncul di DNP / SK</small>
        </div>
    </div>
</section>

<div class="form-actions"><a class="button button-secondary" href="{{ $customer->exists?route('customers.show',$customer):route('customers.index') }}">Batal</a><button class="button button-primary">Simpan customer</button></div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const nameInput = document.getElementById('name');
    const docNameInput = document.getElementById('doc_customer_name');
    if (nameInput && docNameInput) {
        nameInput.addEventListener('input', function () {
            docNameInput.value = this.value;
        });
    }
});
</script>

@if($customer->exists)<section class="archive-panel" style="max-width: 1100px;"><div><h3>Arsipkan customer</h3><p>Customer tidak bisa dipilih untuk transaksi baru. Histori tetap tersimpan.</p></div><form method="POST" action="{{ route('customers.destroy',$customer) }}" data-confirm="Arsipkan customer ini? Histori transaksi tetap tersimpan.">@csrf @method('DELETE')<input type="hidden" name="lock_version" value="{{ $customer->lock_version }}"><button class="button button-danger">Arsipkan</button></form></section>@endif
@endsection