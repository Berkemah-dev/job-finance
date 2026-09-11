@extends('layouts.app')
@section('title',$quotation->exists?'Edit Quotation':'Buat Quotation')
@section('content')
<div class="page-heading"><div><p class="eyebrow">OPERASIONAL</p><h1>{{ $quotation->exists?'Edit quotation':'Buat quotation' }}</h1><p>Simpan penawaran sebagai Draft sebelum diajukan.</p></div><a class="text-link" href="{{ $quotation->exists?route('quotations.show',$quotation):route('quotations.index') }}">Kembali</a></div>
@if($customers->isEmpty())<div class="info-note">Belum ada customer aktif. <a class="text-link" href="{{ route('customers.create') }}">Tambahkan customer terlebih dahulu.</a></div>@endif
<section class="panel"><form class="data-form" data-quotation-form data-pricing-endpoint="{{ route('pricing.suggest-trucking') }}" data-payment-terms-map="{{ json_encode($customers->pluck('default_payment_terms', 'id')) }}" method="POST" action="{{ $quotation->exists?route('quotations.update',$quotation):route('quotations.store') }}">@csrf @if($quotation->exists) @method('PUT') @endif<input type="hidden" name="lock_version" value="{{ old('lock_version',$quotation->lock_version ?? 0) }}">
@if($quotation->exists && $quotation->status->value === 'revision')<div class="info-note"><strong>Quotation sedang direvisi.</strong> Perbaiki sesuai catatan revisi lalu ajukan ulang. @if($quotation->revision_reason)Catatan: {{ $quotation->revision_reason }}.@endif</div>@endif
<div class="form-grid"><div class="field"><label for="customer_id">Customer <span class="required">*</span></label><select name="customer_id" id="customer_id" data-customer-select required><option value="">Pilih customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" data-payment-terms="{{ $customer->default_payment_terms }}" @selected((string)old('customer_id',$quotation->customer_id)===(string)$customer->id)>{{ $customer->code }} — {{ $customer->name }}</option>@endforeach</select></div><div class="field"><label for="subject">Judul penawaran <span class="required">*</span></label><input id="subject" name="subject" value="{{ old('subject',$quotation->subject) }}" required maxlength="255" placeholder="Contoh: Pengiriman Jakarta – Surabaya"></div>
@can('users.view')<div class="field"><label for="sales_id">Sales PIC</label><select id="sales_id" name="sales_id"><option value="">—</option>@foreach($sales as $salesUser)<option value="{{ $salesUser->id }}" @selected((string)old('sales_id',$quotation->sales_id)===(string)$salesUser->id)>{{ $salesUser->name }}</option>@endforeach</select></div>@endcan
<div class="field"><label for="quotation_date">Tanggal quotation <span class="required">*</span></label><input type="date" id="quotation_date" name="quotation_date" value="{{ old('quotation_date', $quotation->quotation_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required></div><div class="field"><label for="valid_until">Berlaku sampai <span class="required">*</span></label><input type="date" id="valid_until" name="valid_until" value="{{ old('valid_until', $quotation->valid_until?->format('Y-m-d') ?? now()->addDays(30)->format('Y-m-d')) }}" required></div>
<div class="field"><label for="service_type">Jenis layanan</label><select id="service_type" name="service_type"><option value="">—</option>@foreach(config('operations.service_types') as $key=>$label)<option value="{{ $key }}" @selected(old('service_type',$quotation->service_type)===$key)>{{ $label }}</option>@endforeach</select></div><div class="field"><label for="origin">Asal</label><input id="origin" name="origin" value="{{ old('origin',$quotation->origin) }}" maxlength="120" placeholder="cth: Jakarta"></div>
<div class="field"><label for="destination">Tujuan</label><input id="destination" name="destination" value="{{ old('destination',$quotation->destination) }}" maxlength="120" placeholder="cth: Surabaya"></div><div class="field"><label for="payment_terms">Ketentuan pembayaran</label><select id="payment_terms" name="payment_terms"><option value="">—</option>@foreach(config('operations.customer_payment_terms') as $key=>$label)<option value="{{ $key }}" @selected(old('payment_terms',$quotation->payment_terms)===$key)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="currency">Mata uang</label><select id="currency" name="currency">@foreach(config('operations.currencies') as $key=>$label)<option value="{{ $key }}" @selected(old('currency',$quotation->currency ?? 'IDR')===$key)>{{ $label }}</option>@endforeach</select></div><div class="field"><label for="exchange_rate">Kurs</label><input id="exchange_rate" name="exchange_rate" type="number" min="0.01" step="0.01" value="{{ old('exchange_rate',$quotation->exchange_rate ?? 1) }}" max="999999999.99"></div>
<div class="field"><label>Shipper (pengirim)</label><select data-shipper-picker><option value="">Isi manual atau pilih kontak</option></select></div><div class="field"><label>Consignee (penerima)</label><select data-consignee-picker><option value="">Isi manual atau pilih kontak</option></select></div>
<div class="field"><label for="shipper_name">Nama shipper</label><input id="shipper_name" name="shipper_name" value="{{ old('shipper_name',$quotation->shipper_name) }}" maxlength="160" placeholder="Nama pengirim" data-shipper-name></div><div class="field"><label for="consignee_name">Nama consignee</label><input id="consignee_name" name="consignee_name" value="{{ old('consignee_name',$quotation->consignee_name) }}" maxlength="160" placeholder="Nama penerima" data-consignee-name></div>
<div class="field span-2"><label for="shipper_address">Alamat shipper</label><textarea id="shipper_address" name="shipper_address" rows="2" maxlength="5000" data-shipper-address>{{ old('shipper_address',$quotation->shipper_address) }}</textarea></div><div class="field span-2"><label for="consignee_address">Alamat consignee</label><textarea id="consignee_address" name="consignee_address" rows="2" maxlength="5000" data-consignee-address>{{ old('consignee_address',$quotation->consignee_address) }}</textarea></div>
<div class="field"><label for="tax_rate">Pajak (%)</label><input id="tax_rate" name="tax_rate" type="number" min="0" max="100" step="0.01" value="{{ old('tax_rate',$quotation->tax_rate ?? 0) }}"></div><div class="field"><label for="discount">Diskon (IDR)</label><input id="discount" name="discount" type="number" min="0" max="999999999.99" step="0.01" value="{{ old('discount',$quotation->discount ?? 0) }}"></div>
</div>
<style>
    details.lcl-panel summary::marker { display: none; content: ""; }
    details.lcl-panel summary::-webkit-details-marker { display: none; }
</style>

<details class="lcl-panel" data-lcl-panel style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; margin-top: 25px; margin-bottom: 20px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
<summary style="padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; margin: 0; cursor: pointer; list-style: none; display: flex; align-items: center;"><x-icon name="calculator" style="margin-right: 8px;"/><strong>Estimator biaya LCL</strong><span class="subtle" style="margin-left: 10px;">Hitung lalu skor sebagai item quotation.</span></summary>
<div class="lcl-body" style="padding: 16px;">
@php $lclDefaultRate = config('operations.lcl.default_rate'); @endphp
<div class="field"><label for="lcl-rate">Tarif per m³ (IDR)</label><input id="lcl-rate" type="text" inputmode="decimal" placeholder="{{ $lclDefaultRate ? 'Kosongkan = pakai default '.number_format((float) $lclDefaultRate, 0, ',', '.') : 'Kosongkan = pakai default' }}"></div>
<div id="lcl-inline-rows">
<div class="lcl-inline-row"><input class="l-qty" type="number" min="1" value="1" placeholder="Qty" aria-label="Qty"><input class="l-length" type="number" min="0" step="0.01" value="120" placeholder="P (cm)" aria-label="Panjang"><input class="l-width" type="number" min="0" step="0.01" value="100" placeholder="L (cm)" aria-label="Lebar"><input class="l-height" type="number" min="0" step="0.01" value="100" placeholder="T (cm)" aria-label="Tinggi"><input class="l-gross" type="number" min="0" step="0.01" value="120" placeholder="Kg" aria-label="Berat kotor"><button class="button button-secondary l-remove" type="button" hidden>x</button></div>
</div>
<div class="lcl-actions"><button class="button button-secondary" type="button" data-lcl-add>+ Baris</button><button class="button button-primary" type="button" data-lcl-calc>Hitung biaya</button><button class="button button-primary" type="button" data-lcl-add-item hidden>Tambahkan sebagai item</button></div>
<p class="lcl-status" data-lcl-status></p>
</div>
</details>

<details class="lcl-panel" data-vw-panel style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 30px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
<summary style="padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; margin: 0; cursor: pointer; list-style: none; display: flex; align-items: center;"><x-icon name="calculator" style="margin-right: 8px;"/><strong>Estimator Volume Weight & CBM</strong><span class="subtle" style="margin-left: 10px;">Simulasikan dimensi untuk menentukan chargeable weight atau total kubikasi.</span></summary>
<div class="item-editor" style="margin-top: 10px;">
<div id="vw-inline-rows">
<div class="vw-inline-row item-fields">
    <div class="field" style="max-width: 100px;"><label>Qty</label><input class="v-qty" type="number" min="1" value="1"></div>
    <div class="field"><label>P (cm)</label><input class="v-length" type="number" min="0" step="0.01" value="100"></div>
    <div class="field"><label>L (cm)</label><input class="v-width" type="number" min="0" step="0.01" value="80"></div>
    <div class="field"><label>T (cm)</label><input class="v-height" type="number" min="0" step="0.01" value="60"></div>
    <div class="field"><label>Berat Kotor (Kg)</label><input class="v-gross" type="number" min="0" step="0.01" value="40"></div>
    <div class="field"><label>&nbsp;</label><button class="button button-danger v-remove" type="button" hidden>Hapus</button></div>
</div>
</div>
<div class="item-fields" style="align-items: flex-end;">
    <div class="field">
        <button class="button button-secondary" type="button" data-vw-add>+ Tambah dimensi</button>
        <button class="button button-primary" type="button" data-vw-calc>Hitung Volume / CBM</button>
    </div>
</div>
<div data-vw-status hidden style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color); display: flex; gap: 30px;">
    <div><span class="subtle" style="display: block; margin-bottom: 5px;">Volume Weight</span><strong style="font-size: 1.1rem;"><span data-v-vw></span></strong></div>
    <div><span class="subtle" style="display: block; margin-bottom: 5px;">Total CBM</span><strong style="font-size: 1.1rem;"><span data-v-cbm></span></strong></div>
    <div><span class="subtle" style="display: block; margin-bottom: 5px;">Chargeable Weight</span><strong style="font-size: 1.1rem;"><span data-v-cw></span></strong></div>
</div>
</div>
</details>
<div class="section-heading"><h2>Detail penawaran</h2><span class="subtle">Maksimal 100 item · nilai dalam IDR · kurs memakai weekly pricing</span></div>
<div data-items>@php $rows=old('items',$quotation->exists?$quotation->items->toArray():[[]]); @endphp @foreach($rows as $index=>$item) @include('quotations.item',compact('index','item')) @endforeach</div>
<template data-item-template>@include('quotations.item',['index'=>'__INDEX__','item'=>[]])</template>
<button class="button button-secondary" type="button" data-add-item>+ Tambah item</button>
<div class="summary-box" aria-live="polite"><div class="summary-row"><span>Total sementara</span><strong data-preview-total>Rp 0,00</strong></div><div class="summary-row"><span>Estimasi profit</span><strong data-preview-profit>Rp 0,00</strong></div><p class="form-help">Pajak, diskon, dan grand total dihitung saat disimpan.</p></div>
<div class="field"><label for="notes">Catatan / ketentuan penawaran</label><textarea name="notes" id="notes" rows="3" maxlength="5000">{{ old('notes',$quotation->notes) }}</textarea></div><div class="form-actions"><a class="button button-secondary" href="{{ route('quotations.index') }}">Batal</a><button class="button button-primary" @disabled($customers->isEmpty())>Simpan draft</button></div></form></section>
<script>
(function () {
    const panel = document.querySelector('[data-lcl-panel]');
    if (!panel) return;
    const rows = panel.querySelector('#lcl-inline-rows');
    const status = panel.querySelector('[data-lcl-status]');
    const addItemBtn = panel.querySelector('[data-lcl-add-item]');
    const form = document.querySelector('[data-quotation-form]');
    let lastCost = null;
    const refreshRemoves = () => {
        const n = rows.querySelectorAll('.lcl-inline-row').length;
        rows.querySelectorAll('.lcl-inline-row').forEach(row => row.querySelector('.l-remove').hidden = n === 1);
    };
    const params = () => {
        const q = new URLSearchParams();
        const rate = panel.querySelector('#lcl-rate').value.trim();
        if (rate) q.set('rate_per_cbm', rate);
        rows.querySelectorAll('.lcl-inline-row').forEach((row, i) => {
            q.set('packages[' + i + '][qty]', row.querySelector('.l-qty').value);
            q.set('packages[' + i + '][length]', row.querySelector('.l-length').value);
            q.set('packages[' + i + '][width]', row.querySelector('.l-width').value);
            q.set('packages[' + i + '][height]', row.querySelector('.l-height').value);
            q.set('packages[' + i + '][gross_weight]', row.querySelector('.l-gross').value);
        });
        return q;
    };
    panel.querySelector('[data-lcl-add]').addEventListener('click', () => {
        const clone = rows.querySelector('.lcl-inline-row').cloneNode(true);
        rows.appendChild(clone);
        refreshRemoves();
    });
    rows.addEventListener('click', e => {
        if (!e.target.classList.contains('l-remove')) return;
        e.target.closest('.lcl-inline-row').remove();
        refreshRemoves();
    });
    panel.querySelector('[data-lcl-calc]').addEventListener('click', async () => {
        status.textContent = 'Menghitung…';
        const response = await fetch('/api/calculators/lcl?' + params(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
        const data = await response.json();
        if (!response.ok) { status.textContent = 'Periksa kembali input.'; status.classList.add('is-error'); return; }
        lastCost = data.total_cost;
        status.classList.remove('is-error');
        status.textContent = 'Estimasi biaya Rp ' + Number(data.total_cost).toLocaleString('id-ID') + ' (basis ' + data.chargeable_basis.toLocaleString('id-ID') + ' m³, ' + data.basis_note + ')';
        addItemBtn.hidden = false;
    });
    addItemBtn.addEventListener('click', () => {
        if (lastCost === null) return;
        form.querySelector('[data-add-item]').click();
        const row = form.querySelector('[data-items]').lastElementChild;
        const desc = row.querySelector('input[name$="[description]"]');
        if (desc) desc.value = 'Estimasi biaya LCL (W/M)';
        const unit = row.querySelector('input[name$="[unit]"]');
        if (unit) unit.value = 'Shipment';
        const qty = row.querySelector('input[name$="[quantity]"]');
        if (qty) qty.value = '1';
        const price = row.querySelector('input[name$="[unit_price]"]');
        if (price) price.value = lastCost;
        row.dispatchEvent(new Event('input', { bubbles: true }));
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        panel.open = false;
    });
    refreshRemoves();
})();

(function () {
    const vwPanel = document.querySelector('[data-vw-panel]');
    if (!vwPanel) return;
    const rows = vwPanel.querySelector('#vw-inline-rows');
    const status = vwPanel.querySelector('[data-vw-status]');
    
    const refreshRemoves = () => {
        const n = rows.querySelectorAll('.vw-inline-row').length;
        rows.querySelectorAll('.vw-inline-row').forEach(row => row.querySelector('.v-remove').hidden = n === 1);
    };

    const params = () => {
        const q = new URLSearchParams();
        rows.querySelectorAll('.vw-inline-row').forEach((row, i) => {
            q.set('packages[' + i + '][qty]', row.querySelector('.v-qty').value);
            q.set('packages[' + i + '][length]', row.querySelector('.v-length').value);
            q.set('packages[' + i + '][width]', row.querySelector('.v-width').value);
            q.set('packages[' + i + '][height]', row.querySelector('.v-height').value);
            q.set('packages[' + i + '][gross_weight]', row.querySelector('.v-gross').value);
        });
        return q;
    };

    vwPanel.querySelector('[data-vw-add]').addEventListener('click', () => {
        const clone = rows.querySelector('.vw-inline-row').cloneNode(true);
        // Also clear out the input values on clone except Qty
        clone.querySelectorAll('input').forEach(inp => {
            if(!inp.classList.contains('v-qty')) inp.value = '';
        });
        rows.appendChild(clone);
        refreshRemoves();
    });

    rows.addEventListener('click', e => {
        if (!e.target.classList.contains('v-remove')) return;
        e.target.closest('.vw-inline-row').remove();
        refreshRemoves();
    });

    vwPanel.querySelector('[data-vw-calc]').addEventListener('click', async () => {
        const response = await fetch('/api/calculators/packages?' + params(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
        const data = await response.json();
        
        status.hidden = false;
        
        if (!response.ok) { 
            status.querySelector('[data-v-vw]').textContent = 'Error input';
            status.querySelector('[data-v-cbm]').textContent = 'Error input';
            status.querySelector('[data-v-cw]').textContent = 'Error input';
            return; 
        }

        status.querySelector('[data-v-vw]').textContent = Number(data.total_volume_weight).toLocaleString('id-ID') + ' kg';
        status.querySelector('[data-v-cbm]').textContent = Number(data.total_cbm).toLocaleString('id-ID', { maximumFractionDigits: 4 }) + ' m³';
        status.querySelector('[data-v-cw]').textContent = Number(data.total_chargeable_weight).toLocaleString('id-ID') + ' kg';
    });

    refreshRemoves();
})();
</script>
@endsection