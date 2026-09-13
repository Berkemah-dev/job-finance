@extends('layouts.app')
@section('title',$customer->exists?'Edit Customer':'Tambah Customer')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">SALES & CUSTOMER</p>
        <h1>{{ $customer->exists?'Edit customer':'Tambah customer' }}</h1>
        <p>Identitas customer digunakan pada quotation, invoice, dan pekerjaan.</p>
    </div>
    <a class="button button-secondary" href="{{ $customer->exists?route('customers.show',$customer):route('customers.index') }}">← Kembali</a>
</div>

<form class="data-form-wrapper" method="POST" action="{{ $customer->exists?route('customers.update',$customer):route('customers.store') }}" enctype="multipart/form-data">
    @csrf
    @if($customer->exists)
        @method('PUT')
    @endif
    <input type="hidden" name="lock_version" value="{{ old('lock_version',$customer->lock_version ?? 0) }}">

    <div class="form-layout">
        <!-- Main Form Column -->
        <div class="form-main">
            {{-- Card 1: Informasi Utama --}}
            <section class="panel" style="margin-bottom: 22px;">
                <div class="panel-heading" style="padding: 16px 24px; border-bottom: 1px solid #edf1f7;">
                    <h2 style="font-size: 14px; font-weight: 700; color: #0f1f3d;">
                        <x-icon name="user" style="width:16px;height:16px;margin-right:6px;display:inline-block;vertical-align:middle;color:#0f1f3d;"/> Informasi Utama
                    </h2>
                    <span class="subtle" style="font-size: 11px;">Data identitas & kontak customer</span>
                </div>
                <div class="form-grid" style="padding: 24px;">
                    <div class="field">
                        <label for="code">Kode customer</label>
                        <input id="code" @if(!$customer->exists) name="code" @endif type="text" value="{{ $customer->exists ? $customer->code : '' }}" placeholder="Otomatis dari sistem" maxlength="30" readonly tabindex="-1">
                        <small class="form-help">Kode dibuat otomatis oleh sistem dan tidak dapat diubah.</small>
                    </div>
                    <div class="field">
                        <label for="name">Nama customer <span class="required">*</span></label>
                        <input id="name" name="name" type="text" value="{{ old('name',$customer->name) }}" placeholder="PT Nama Perusahaan Logistik / Shipper" maxlength="255" required>
                    </div>
                    <div class="field">
                        <label for="contact_name">Nama kontak PIC</label>
                        <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name',$customer->contact_name) }}" placeholder="Nama penanggung jawab" maxlength="255">
                    </div>
                    <div class="field">
                        <label for="email">Email resmi</label>
                        <input id="email" name="email" type="email" value="{{ old('email',$customer->email) }}" placeholder="finance@perusahaan.com" maxlength="255">
                    </div>
                    <div class="field">
                        <label for="phone">Telepon / WhatsApp</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone',$customer->phone) }}" placeholder="021-xxxx / 0812..." maxlength="40">
                    </div>
                    <div class="field">
                        <label for="tax_number">NPWP Perusahaan</label>
                        <input id="tax_number" name="tax_number" type="text" value="{{ old('tax_number',$customer->tax_number) }}" placeholder="00.000.000.0-000.000 (15-16 digit)" maxlength="40" inputmode="numeric">
                        <small class="form-help">15–16 digit angka tanpa simbol.</small>
                    </div>
                    <div class="field span-2">
                        <x-payment-term-select name="default_payment_terms" label="Syarat pembayaran default" :value="old('default_payment_terms',$customer->default_payment_terms)"></x-payment-term-select>
                        <small class="form-help">Otomatis terisi pada quotation saat customer dipilih; dapat diubah per quotation.</small>
                    </div>
                </div>
            </section>

            {{-- Card 2: Document Details --}}
            <section class="panel" style="margin-bottom: 22px;">
                <div class="panel-heading" style="padding: 16px 24px; border-bottom: 1px solid #edf1f7;">
                    <h2 style="font-size: 14px; font-weight: 700; color: #0f1f3d;">
                        <x-icon name="file" style="width:16px;height:16px;margin-right:6px;display:inline-block;vertical-align:middle;color:#0f1f3d;"/> Document Details
                    </h2>
                    <span class="subtle" style="font-size: 11px;">Penerbitan B/L, AWB, SI, DNP & SK</span>
                </div>
                <div class="form-grid" style="padding: 24px;">
                    <div class="field span-2">
                        <label for="doc_customer_name">Nama Customer Dokumen</label>
                        <input id="doc_customer_name" type="text" value="{{ old('name',$customer->name) }}" placeholder="Otomatis mengikuti nama customer di atas" readonly style="background: #f8fafc; color: #475569; font-weight: 600;">
                        <small class="form-help">Sesuai nama customer yang diinput di atas.</small>
                    </div>
                    <div class="field span-2">
                        <label for="address">Alamat Lengkap Perusahaan <span class="required">*</span></label>
                        <textarea id="address" name="address" rows="3" maxlength="2000" placeholder="Alamat lengkap resmi perusahaan...">{{ old('address',$customer->address) }}</textarea>
                        <small class="form-help">Akan muncul di Bill of Lading / AWB / SI / Booking Confirmation / SK / DNP</small>
                    </div>
                    <div class="field">
                        <label for="authorizer_name">Nama Pemberi Kuasa</label>
                        <input id="authorizer_name" name="authorizer_name" type="text" value="{{ old('authorizer_name',$customer->authorizer_name) }}" placeholder="Nama pemberi kuasa pabean" maxlength="255">
                        <small class="form-help">Akan muncul di DNP / SK</small>
                    </div>
                    <div class="field">
                        <label for="authorizer_title">Jabatan Pemberi Kuasa</label>
                        <input id="authorizer_title" name="authorizer_title" type="text" value="{{ old('authorizer_title',$customer->authorizer_title) }}" placeholder="cth: Direktur / GM" maxlength="255">
                        <small class="form-help">Akan muncul di DNP / SK</small>
                    </div>
                </div>
            </section>

            {{-- Card 3: Dokumen Legalitas --}}
            <section class="panel" style="margin-bottom: 22px;">
                <div class="panel-heading" style="padding: 16px 24px; border-bottom: 1px solid #edf1f7;">
                    <h2 style="font-size: 14px; font-weight: 700; color: #0f1f3d;">
                        <x-icon name="clip" style="width:16px;height:16px;margin-right:6px;display:inline-block;vertical-align:middle;color:#0f1f3d;"/> Dokumen Legalitas
                    </h2>
                    <span class="subtle" style="font-size: 11px;">Berkas pendukung NPWP & NIB</span>
                </div>
                <div class="form-grid" style="padding: 24px;">
                    <div class="field">
                        <label for="npwp_file">Upload Dokumen NPWP</label>
                        <input id="npwp_file" name="npwp_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        <small class="form-help">PDF / gambar, maks. 5 MB.@if($customer->npwp_file) Currently: <strong>{{ basename($customer->npwp_file) }}</strong>.@endif</small>
                    </div>
                    <div class="field">
                        <label for="nib_file">Upload Dokumen NIB</label>
                        <input id="nib_file" name="nib_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        <small class="form-help">PDF / gambar, maks. 5 MB.@if($customer->nib_file) Currently: <strong>{{ basename($customer->nib_file) }}</strong>.@endif</small>
                    </div>
                </div>
            </section>
        </div>

        <!-- Sidebar Column (Actions & Help) -->
        <div class="form-sidebar">
            <div class="form-sidebar-card">
                <div class="sidebar-header">
                    <span class="status-chip-badge {{ $customer->exists ? 'blue' : 'green' }}">
                        {{ $customer->exists ? 'EDIT MODE' : 'TAMBAH BARU' }}
                    </span>
                    <h3>Simpan Customer</h3>
                    <p>Periksa kembali data identitas & alamat perusahaan sebelum menyimpan.</p>
                </div>

                <div class="sidebar-actions">
                    <button class="button button-primary btn-block" type="submit">
                        {{ $customer->exists ? 'Simpan Perubahan' : 'Simpan Customer' }}
                    </button>
                    <a class="button button-secondary btn-block" href="{{ $customer->exists ? route('customers.show',$customer) : route('customers.index') }}">
                        ← Batal & Kembali
                    </a>
                </div>

                <div class="sidebar-divider"></div>

                <div class="sidebar-guide">
                    <h4>Informasi Pengisian</h4>
                    <ul>
                        <li>Data customer ini akan otomatis terhubung pada modul <strong>Quotation</strong>, <strong>Invoice</strong>, dan <strong>Job Order</strong>.</li>
                        <li><strong>NPWP & Alamat</strong> digunakan langsung untuk cetakan dokumen operasional & kepabeanan.</li>
                        <li>Customer baru berstatus <em>Pending Approval</em> hingga disetujui Finance Manager.</li>
                    </ul>
                </div>
            </div>

            @if($customer->exists)
                <div class="archive-sidebar-card">
                    <h4>Arsipkan Customer</h4>
                    <p>Customer tidak bisa dipilih untuk transaksi baru. Histori transaksi tetap tersimpan aman.</p>
                    <form method="POST" action="{{ route('customers.destroy',$customer) }}" data-confirm="Arsipkan customer ini? Histori transaksi tetap tersimpan.">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="lock_version" value="{{ $customer->lock_version }}">
                        <button class="button button-danger btn-block">Hapus / Arsipkan</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
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
@endsection
