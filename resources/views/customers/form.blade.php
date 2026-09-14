@extends('layouts.app')
@section('title', $customer->exists ? 'Edit Customer' : 'Tambah Customer')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">SALES & CUSTOMER</p>
        <h1>{{ $customer->exists ? 'Edit customer' : 'Tambah customer' }}</h1>
        <p>Identitas customer digunakan pada quotation, invoice, dan pekerjaan.</p>
    </div>
    <a class="button button-secondary" href="{{ $customer->exists ? route('customers.show', $customer) : route('customers.index') }}">← Kembali</a>
</div>

<section class="panel form-panel">
    <form class="data-form" method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" enctype="multipart/form-data">
        @csrf
        @if($customer->exists)
            @method('PUT')
        @endif
        <input type="hidden" name="lock_version" value="{{ old('lock_version', $customer->lock_version ?? 0) }}">

        <div class="form-grid">
            @if($customer->exists)
                <div class="field">
                    <label>Kode customer</label>
                    <div style="min-height: 44px; display: flex; align-items: center; padding: 0 14px; border: 1px solid #dce4ef; border-radius: 8px; background: #f8fafc; color: #0f172a; font-weight: 600; font-size: 13px;">
                        {{ $customer->code }}
                    </div>
                </div>
            @else
                <div class="field">
                    <label>Kode customer</label>
                    <div style="min-height: 44px; display: flex; align-items: center; padding: 0 14px; border: 1px solid #dce4ef; border-radius: 8px; background: #f8fafc; color: #64748b; font-size: 12px;">
                        Otomatis saat disimpan
                    </div>
                </div>
            @endif

            <div class="field">
                <label for="name">Nama customer <span class="required">*</span></label>
                <input id="name" name="name" type="text" value="{{ old('name', $customer->name) }}" placeholder="PT Nama Perusahaan Logistik / Shipper" maxlength="255" required>
            </div>

            <div class="field">
                <label for="contact_name">Nama kontak PIC</label>
                <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name', $customer->contact_name) }}" placeholder="Nama penanggung jawab" maxlength="255">
            </div>

            <div class="field">
                <label for="email">Email resmi</label>
                <input id="email" name="email" type="email" value="{{ old('email', $customer->email) }}" placeholder="finance@perusahaan.com" maxlength="255">
            </div>

            <div class="field">
                <label for="phone">Telepon / WhatsApp</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone', $customer->phone) }}" placeholder="021-xxxx / 0812..." maxlength="40">
            </div>

            <div class="field">
                <label for="tax_number">NPWP Perusahaan</label>
                <input id="tax_number" name="tax_number" type="text" value="{{ old('tax_number', $customer->tax_number) }}" placeholder="00.000.000.0-000.000" maxlength="40" inputmode="numeric">
                <small class="form-help">15–16 digit angka.</small>
            </div>

            <div class="field span-2">
                <x-payment-term-select name="default_payment_terms" label="Syarat pembayaran default" :value="old('default_payment_terms', $customer->default_payment_terms)"></x-payment-term-select>
                <small class="form-help">Otomatis terisi pada quotation saat customer dipilih; dapat diubah per quotation.</small>
            </div>

            <div class="field span-2">
                <label for="address">Alamat Lengkap Perusahaan <span class="required">*</span></label>
                <textarea id="address" name="address" rows="3" maxlength="2000" placeholder="Alamat lengkap resmi perusahaan...">{{ old('address', $customer->address) }}</textarea>
                <small class="form-help">Akan muncul di Bill of Lading / AWB / SI / Booking Confirmation / SK / DNP</small>
            </div>

            <div class="field">
                <label for="authorizer_name">Nama Pemberi Kuasa (Pabean)</label>
                <input id="authorizer_name" name="authorizer_name" type="text" value="{{ old('authorizer_name', $customer->authorizer_name) }}" placeholder="Nama pemberi kuasa pabean" maxlength="255">
                <small class="form-help">Akan muncul di DNP / SK</small>
            </div>

            <div class="field">
                <label for="authorizer_title">Jabatan Pemberi Kuasa</label>
                <input id="authorizer_title" name="authorizer_title" type="text" value="{{ old('authorizer_title', $customer->authorizer_title) }}" placeholder="cth: Direktur / GM" maxlength="255">
                <small class="form-help">Akan muncul di DNP / SK</small>
            </div>

            <div class="field">
                <label for="npwp_file">Upload Dokumen NPWP</label>
                <input id="npwp_file" name="npwp_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp">
                <small class="form-help">PDF / gambar, maks. 5 MB.@if($customer->npwp_file) File saat ini: <strong>{{ basename($customer->npwp_file) }}</strong>@endif</small>
            </div>

            <div class="field">
                <label for="nib_file">Upload Dokumen NIB</label>
                <input id="nib_file" name="nib_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp">
                <small class="form-help">PDF / gambar, maks. 5 MB.@if($customer->nib_file) File saat ini: <strong>{{ basename($customer->nib_file) }}</strong>@endif</small>
            </div>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ $customer->exists ? route('customers.show', $customer) : route('customers.index') }}">Batal</a>
            <button class="button button-primary" type="submit">{{ $customer->exists ? 'Simpan Perubahan' : 'Simpan Customer' }}</button>
        </div>
    </form>
</section>

@if($customer->exists)
    <section class="archive-panel">
        <div>
            <h3>Arsipkan Customer</h3>
            <p>Customer tidak bisa dipilih untuk transaksi baru. Histori transaksi tetap tersimpan aman.</p>
        </div>
        <form method="POST" action="{{ route('customers.destroy', $customer) }}" data-confirm="Arsipkan customer ini? Histori transaksi tetap tersimpan.">
            @csrf
            @method('DELETE')
            <input type="hidden" name="lock_version" value="{{ $customer->lock_version }}">
            <button class="button button-danger" type="submit">Arsipkan</button>
        </form>
    </section>
@endif
@endsection
