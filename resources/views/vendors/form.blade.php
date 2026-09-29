@extends('layouts.app')
@section('title',$vendor->exists?'Edit Vendor':'Tambah Vendor')
@section('content')
<div class="page-heading"><div><p class="eyebrow">MASTER DATA</p><h1>{{ $vendor->exists?'Edit vendor':'Tambah vendor' }}</h1><p>Data vendor digunakan pada trucking pricing dan biaya operasional.</p></div><a class="button button-secondary" href="{{ $vendor->exists?route('vendors.show',$vendor):route('vendors.index') }}">← Kembali</a></div>
<section class="panel form-panel"><form class="data-form" method="POST" action="{{ $vendor->exists?route('vendors.update',$vendor):route('vendors.store') }}">@csrf @if($vendor->exists) @method('PUT') @endif
<input type="hidden" name="lock_version" value="{{ old('lock_version',$vendor->lock_version ?? 0) }}">
<div class="form-grid">
@if($vendor->exists)
<div class="field"><label for="code">Kode vendor <span class="required">*</span></label><input id="code" name="code" type="text" value="{{ old('code',$vendor->code) }}" placeholder="VND-2026-00001" maxlength="30" required></div>
@else
<div class="field"><label>Kode vendor</label><div style="min-height:44px;display:flex;align-items:center;padding:0 14px;border:1px solid #dbe3ef;border-radius:10px;background:#f8fafc;color:#64748b;font-size:12px;">Otomatis saat disimpan</div></div>
@endif
<div class="field"><label for="name">Nama vendor <span class="required">*</span></label><input id="name" name="name" type="text" value="{{ old('name',$vendor->name) }}" placeholder="PT Mitra Logistik" maxlength="255" required></div>
<div class="field"><label for="type">Kategori vendor <span class="required">*</span></label><select id="type" name="type" required>@foreach(config('operations.vendor_types') as $value=>$label)<option value="{{ $value }}" @selected(old('type',$vendor->type)===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="pic">PIC</label><input id="pic" name="pic" value="{{ old('pic',$vendor->pic) }}" placeholder="Nama penanggung jawab"></div>
<div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email',$vendor->email) }}" placeholder="nama@vendor.com"></div>
<div class="field"><label for="phone">Telepon</label><input id="phone" name="phone" value="{{ old('phone',$vendor->phone) }}" placeholder="0812..."></div>
<div class="field"><label for="tax_number">NPWP / TAX ID</label><input id="tax_number" name="tax_number" value="{{ old('tax_number',$vendor->tax_number) }}" placeholder="Nomor pokok wajib pajak / Tax ID"></div>
<div class="field"><label for="country">Negara</label><input id="country" name="country" value="{{ old('country',$vendor->country) }}" maxlength="120" placeholder="cth: Indonesia"></div>
<div class="field"><label for="bank_name">Nama Bank</label><input id="bank_name" name="bank_name" value="{{ old('bank_name',$vendor->bank_name) }}" placeholder="cth: BCA / Mandiri / BRI / BNI" maxlength="100"></div>
<div class="field"><label for="bank_account_number">No. Rekening</label><input id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number',$vendor->bank_account_number) }}" placeholder="Nomor rekening bank vendor" maxlength="100"></div>
<div class="field span-2"><label for="bank_account_name">Nama Rekening (Atas Nama)</label><input id="bank_account_name" name="bank_account_name" value="{{ old('bank_account_name',$vendor->bank_account_name) }}" placeholder="Nama pemilik rekening bank" maxlength="255"></div>
<div class="field span-2"><label for="address">Alamat</label><textarea id="address" name="address" rows="3" maxlength="2000">{{ old('address',$vendor->address) }}</textarea></div>
<div class="field"><label for="is_active">Status</label><select id="is_active" name="is_active"><option value="1" @selected(!$vendor->exists || $vendor->is_active)>Aktif</option><option value="0" @selected($vendor->exists && !$vendor->is_active)>Nonaktif</option></select></div>
<div class="field span-2"><label for="notes">Catatan</label><textarea id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes',$vendor->notes) }}</textarea></div>

@unless($vendor->exists)
<div id="trucking_fleet_fields" class="span-2" style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; background: #f8fafc; margin-top: 8px; {{ old('type', $vendor->type) === 'trucking' ? '' : 'display: none;' }}">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
        <div style="width: 32px; height: 32px; border-radius: 8px; background: #eff6ff; color: #2563eb; display: grid; place-items: center; font-size: 16px;">🚚</div>
        <div>
            <h3 style="margin: 0; font-size: 14px; font-weight: 700; color: #0f172a;">Armada & No. Supir (Khusus Vendor Trucking)</h3>
            <p style="margin: 2px 0 0; font-size: 11.5px; color: #64748b;">Dapat diisi langsung sekarang atau ditambahkan lebih banyak di rincian vendor.</p>
        </div>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
        <div class="field">
            <label for="initial_plate_number" style="font-size: 12px; font-weight: 600;">Plat Nomor Truk</label>
            <input id="initial_plate_number" name="initial_plate_number" type="text" value="{{ old('initial_plate_number') }}" placeholder="contoh: B 9123 UE" maxlength="30" style="text-transform: uppercase; font-weight: 600;">
        </div>
        <div class="field">
            <label for="initial_vehicle_type" style="font-size: 12px; font-weight: 600;">Jenis Kendaraan</label>
            <input id="initial_vehicle_type" name="initial_vehicle_type" type="text" value="{{ old('initial_vehicle_type') }}" placeholder="contoh: Trailer 40ft / CDD / Wingbox" maxlength="60">
        </div>
        <div class="field">
            <label for="initial_driver_name" style="font-size: 12px; font-weight: 600;">Nama Supir</label>
            <input id="initial_driver_name" name="initial_driver_name" type="text" value="{{ old('initial_driver_name') }}" placeholder="contoh: Bambang Supriyadi" maxlength="160">
        </div>
        <div class="field">
            <label for="initial_driver_phone" style="font-size: 12px; font-weight: 600;">No. Supir (Telepon / HP)</label>
            <input id="initial_driver_phone" name="initial_driver_phone" type="text" value="{{ old('initial_driver_phone') }}" placeholder="contoh: 0812-3456-7890" maxlength="50">
        </div>
    </div>
</div>
@endunless

</div>
<div class="form-actions"><a class="button button-secondary" href="{{ $vendor->exists?route('vendors.show',$vendor):route('vendors.index') }}">Batal</a><button class="button button-primary">Simpan vendor</button></div></form></section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('type');
        const fleetSection = document.getElementById('trucking_fleet_fields');
        if (typeSelect && fleetSection) {
            function checkType() {
                if (typeSelect.value === 'trucking') {
                    fleetSection.style.display = 'block';
                } else {
                    fleetSection.style.display = 'none';
                }
            }
            typeSelect.addEventListener('change', checkType);
            checkType();
        }
    });
</script>
@if($vendor->exists)<section class="archive-panel"><div><h3>Arsipkan vendor</h3><p>Vendor tidak bisa dipilih untuk transaksi baru. Histori tetap tersimpan.</p></div><form method="POST" action="{{ route('vendors.destroy',$vendor) }}" data-confirm="Arsipkan vendor ini? Histori transaksi tetap tersimpan.">@csrf @method('DELETE')<input type="hidden" name="lock_version" value="{{ $vendor->lock_version }}"><button class="button button-danger">Arsipkan</button></form></section>@endif
@endsection
