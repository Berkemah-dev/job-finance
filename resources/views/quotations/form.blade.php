@extends('layouts.app')
@section('title',$quotation->exists?'Edit Quotation':'Buat Quotation')
@section('content')
<div class="page-heading"><div><p class="eyebrow">OPERASIONAL</p><h1>{{ $quotation->exists?'Edit quotation':'Buat quotation' }}</h1><p>Simpan penawaran sebagai Draft sebelum diajukan.</p></div><a class="button button-secondary" href="{{ $quotation->exists?route('quotations.show',$quotation):route('quotations.index') }}">← Kembali</a></div>
@if($customers->isEmpty())<div class="info-note">Belum ada customer aktif. <a class="text-link" href="{{ route('customers.create') }}">Tambahkan customer terlebih dahulu.</a></div>@endif
<section class="panel"><form class="data-form" data-quotation-form data-pricing-endpoint="{{ route('pricing.suggest-trucking') }}" data-payment-terms-map="{{ json_encode($customers->pluck('default_payment_terms', 'id')) }}" method="POST" action="{{ $quotation->exists?route('quotations.update',$quotation):route('quotations.store') }}">@csrf @if($quotation->exists) @method('PUT') @endif<input type="hidden" name="lock_version" value="{{ old('lock_version',$quotation->lock_version ?? 0) }}">
@if($quotation->exists && $quotation->status->value === 'revision')<div class="info-note"><strong>Quotation sedang direvisi.</strong> Perbaiki sesuai catatan revisi lalu ajukan ulang. @if($quotation->revision_reason)Catatan: {{ $quotation->revision_reason }}.@endif</div>@endif
@php
    $currentUser = auth()->user();
    $isSalesOnly = $currentUser && $currentUser->hasRole('sales') && ! $currentUser->hasRole(['sales-manager', 'super-admin', 'admin']);
    $canManageCost = $currentUser && ($currentUser->hasRole(['sales-manager', 'super-admin', 'admin']) || $currentUser->hasPermission('financial.view') || $currentUser->hasPermission('quotations.approve'));
@endphp

<div class="form-grid">
<div class="field"><label for="customer_id">Customer <span class="required">*</span></label><select name="customer_id" id="customer_id" data-customer-select required><option value="">Pilih customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" data-payment-terms="{{ $customer->default_payment_terms }}" @selected((string)old('customer_id',$quotation->customer_id)===(string)$customer->id)>{{ $customer->code }} — {{ $customer->name }}</option>@endforeach</select></div>
<div class="field"><label for="subject">Judul penawaran <span class="required">*</span></label><input id="subject" name="subject" value="{{ old('subject',$quotation->subject) }}" required maxlength="255" placeholder="Contoh: Pengiriman Mesin Jakarta – Surabaya"></div>

@if($isSalesOnly)
<div class="field">
    <label>Sales PIC</label>
    <div style="padding: 10px 14px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 600; color: #1e293b; min-height: 44px; display: flex; align-items: center;">
        👤 {{ $currentUser->name }} <span class="subtle" style="margin-left: 8px; font-size: 12px;">(Akun Pembuat)</span>
    </div>
    <input type="hidden" name="sales_id" value="{{ $currentUser->id }}">
</div>
@else
@can('users.view')
<div class="field"><label for="sales_id">Sales PIC</label><select id="sales_id" name="sales_id"><option value="">— Pilih Sales —</option>@foreach($sales as $salesUser)<option value="{{ $salesUser->id }}" @selected((string)old('sales_id',$quotation->sales_id ?? $currentUser->id)===(string)$salesUser->id)>{{ $salesUser->name }}</option>@endforeach</select></div>
@endcan
@endif

<div class="field"><label for="quotation_date">Tanggal quotation <span class="required">*</span></label><input type="date" id="quotation_date" name="quotation_date" value="{{ old('quotation_date', $quotation->quotation_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required></div>
<div class="field"><label for="valid_until">Berlaku sampai <span class="required">*</span></label><input type="date" id="valid_until" name="valid_until" value="{{ old('valid_until', $quotation->valid_until?->format('Y-m-d') ?? now()->addDays(30)->format('Y-m-d')) }}" required></div>

<div class="field"><label for="service_type">Services (Layanan) <span class="required">*</span></label><select id="service_type" name="service_type" required><option value="">Pilih Service</option>@foreach($serviceTypes ?? \App\Models\ServiceType::options() as $key=>$label)<option value="{{ $key }}" @selected(old('service_type',$quotation->service_type)===$key)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="terms_of_delivery">Terms of Delivery (Incoterms)</label><select id="terms_of_delivery" name="terms_of_delivery"><option value="">Pilih Terms of Delivery</option>@foreach(config('operations.terms_of_delivery') as $key=>$label)<option value="{{ $key }}" @selected(old('terms_of_delivery',$quotation->terms_of_delivery)===$key)>{{ $label }}</option>@endforeach</select></div>

<div class="field"><label for="cargo_qty">Quantity (Shipment)</label><input id="cargo_qty" name="cargo_qty" value="{{ old('cargo_qty',$quotation->cargo_qty) }}" maxlength="100" placeholder="cth: 1 x 20' GP / 50 Cartons / 2 Pallets"></div>
<div class="field"><label for="weight_meas">Weight/Meas</label><input id="weight_meas" name="weight_meas" value="{{ old('weight_meas',$quotation->weight_meas) }}" maxlength="100" placeholder="cth: 1,500 KGS / 3 CBM"></div>

<div class="field"><label for="commodity">Commodity</label><input id="commodity" name="commodity" value="{{ old('commodity',$quotation->commodity) }}" maxlength="160" placeholder="cth: General Cargo / Spare Parts"></div>

<div class="field">
    <label for="origin">Port of Loading (POL)</label>
    <select id="origin" name="origin" data-custom-select aria-label="Port of Loading (POL)"><option value="">Pilih Port of Loading (POL)</option>@foreach($ports ?? [] as $port)<option value="{{ $port->name }}" @selected(old('origin',$quotation->origin)===$port->name)>{{ $port->code }} - {{ $port->name }}</option>@endforeach</select>
</div>
<div class="field">
    <label for="destination">Port of Discharge (POD)</label>
    <select id="destination" name="destination" data-custom-select aria-label="Port of Discharge (POD)"><option value="">Pilih Port of Discharge (POD)</option>@foreach($ports ?? [] as $port)<option value="{{ $port->name }}" @selected(old('destination',$quotation->destination)===$port->name)>{{ $port->code }} - {{ $port->name }}</option>@endforeach</select>
</div>

<style>
.port-autocomplete-field input,
.port-autocomplete-field input:focus { border-color: #dbe3ef; box-shadow: none; }
</style>

<div class="field" style="grid-column: 1 / -1;"><label for="notes">Catatan / Remarks Khusus (Opsional)</label><textarea id="notes" name="notes" rows="2" placeholder="Masukkan catatan tambahan jika ada (akan dicetak di bagian REMARKS)...">{{ old('notes', $quotation->notes) }}</textarea><small class="subtle" style="font-size:12px;color:#64748b;">Jika dikosongkan, bagian REMARKS tidak akan dicetak pada dokumen penawaran.</small></div>

</div>


{{-- KOTAK INPUT SATU ITEM BIAYA --}}
<div class="panel" id="single-item-input-panel" data-can-manage-cost="{{ $canManageCost ? '1' : '0' }}" style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 18px 20px; margin-top: 25px; margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
        <div>
            <h3 style="margin: 0; font-size: 13.5px; font-weight: 700; color: #1e3a8a;">➕ Input Item Biaya Penawaran</h3>
            <span class="subtle" style="font-size: 11.5px;">Isi detail biaya & mata uang di bawah, lalu klik <strong>"+ Tambah Item ke Daftar"</strong>.</span>
        </div>
        @if(! $canManageCost)
            <span class="status-badge" style="background:#e0f2fe;color:#0369a1;font-size:11px;">🔒 Modal akan dimasukkan oleh Sales Manager saat approval</span>
        @endif
    </div>

    {{-- BARIS 1: URAIAN BIAYA & SATUAN & QTY --}}
    <div class="form-grid" style="grid-template-columns: 2fr 1fr 1fr 1.2fr; gap: 12px; align-items: flex-start; margin-bottom: 12px;">
        <div class="field">
            <label for="input_item_desc">Uraian Biaya <span class="required">*</span></label>
            <select id="input_item_desc" data-custom-select aria-label="Uraian Biaya"><option value="">Pilih Uraian Biaya</option>@foreach($charges ?? [] as $charge)<option value="{{ $charge->name }}">{{ $charge->name }}</option>@endforeach</select>
            <div style="margin-top: 6px; display: flex; gap: 4px; flex-wrap: wrap; align-items: center;">
                <span style="font-size: 10px; color: #64748b;">Cepat:</span>
                @foreach(($charges ?? collect())->take(5) as $charge)
                    <button type="button" class="btn-quick-charge" data-charge="{{ $charge->name }}" style="background:#e2e8f0; border:none; border-radius:4px; padding:2px 6px; font-size:10px; cursor:pointer; color:#1e293b;">{{ $charge->name }}</button>
                @endforeach
            </div>
        </div>
        <div class="field">
            <label for="input_item_qty">Jumlah (Qty) <span class="required">*</span></label>
            <input id="input_item_qty" type="text" inputmode="decimal" value="1" autocomplete="off">
        </div>
        <div class="field">
            <label for="input_item_unit">Satuan <span class="required">*</span></label>
            <select id="input_item_unit" data-custom-select aria-label="Satuan">
                <option value="">Pilih Satuan</option>
                @foreach($units ?? [] as $unit)<option value="{{ $unit->name }}" @selected($unit->name === 'Shipment')>{{ $unit->name }}</option>@endforeach
            </select>
        </div>
        <div class="field">
            <label for="input_item_note">Remark / Catatan</label>
            <input id="input_item_note" type="text" placeholder="cth: Free Time 14 hari" maxlength="255">
        </div>
    </div>

    {{-- BARIS 2: MULTI CURRENCY, KURS, MODAL (JIKA ADA AKSES), HARGA JUAL --}}
    <div class="form-grid" style="grid-template-columns: 1fr 1.2fr {{ $canManageCost ? '1.5fr' : '' }} 1.5fr; gap: 12px; align-items: flex-start;">
        <div class="field">
            <label for="input_item_currency">Mata Uang <span class="required">*</span></label>
            <select id="input_item_currency">
                @foreach(config('operations.currencies') as $cKey => $cLabel)
                    <option value="{{ $cKey }}" @selected($cKey === 'IDR')>{{ $cKey }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="input_item_exchange_rate">Kurs ke IDR <span class="required">*</span></label>
            <input id="input_item_exchange_rate" type="text" inputmode="decimal" value="1" autocomplete="off">
            <small class="subtle" style="font-size: 10px;" id="item_rate_hint">Kurs 1.00 untuk IDR</small>
        </div>

        @if($canManageCost)
            <div class="field">
                <label for="input_item_cost">Modal / Unit <span class="subtle" id="label_cost_currency">(IDR)</span></label>
                <input id="input_item_cost" type="text" inputmode="decimal" value="0" autocomplete="off">
            </div>
        @else
            <input type="hidden" id="input_item_cost" value="0">
        @endif

        <div class="field">
            <label for="input_item_price">Harga Jual / Unit <span class="subtle" id="label_price_currency">(IDR)</span> <span class="required">*</span></label>
            <input id="input_item_price" type="text" inputmode="decimal" value="0" autocomplete="off">
        </div>
    </div>

    <div id="trucking-pricing-fields" hidden style="margin-top: 14px; padding: 14px; border: 1px solid #bfdbfe; border-radius: 8px; background: #eff6ff;">
        <div style="font-size: 12px; font-weight: 700; color: #1e3a8a; margin-bottom: 10px;">Tarif Trucking dari Master Harga</div>
        <div style="display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; align-items:end;">
            <div class="field"><label for="input_trucking_origin">Asal / POL <span class="required">*</span></label><input id="input_trucking_origin" type="text" placeholder="Contoh: PRIOK" autocomplete="off"></div>
            <div class="field"><label for="input_trucking_destination">Tujuan / POD <span class="required">*</span></label><input id="input_trucking_destination" type="text" placeholder="Contoh: SURABAYA" autocomplete="off"></div>
            <div class="field"><label for="input_trucking_container_type">Tipe Armada <span class="required">*</span></label><select id="input_trucking_container_type">@foreach($containerUnits ?? \App\Models\ContainerUnit::options() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="input_trucking_overweight">Kategori <span class="required">*</span></label><select id="input_trucking_overweight"><option value="0">Normal</option><option value="1">Overweight</option></select></div>
        </div>
        <div style="display:flex; align-items:center; gap:10px; margin-top:10px;">
            <button type="button" class="button button-secondary" id="btn_fetch_trucking" style="padding: 7px 14px;">Ambil harga trucking</button>
            <span id="trucking_pricing_status" style="font-size:12px; color:#64748b;">Isi asal, tujuan, dan tipe armada. Harga akan dicari otomatis.</span>
        </div>
    </div>

    <div style="margin-top: 14px; text-align: right;">
        <button type="button" class="button button-primary" id="btn_submit_single_item" style="padding: 8px 24px; font-weight: 600;">
            ➕ Tambah Item ke Daftar
        </button>
    </div>
</div>

<style>
#single-item-input-panel .form-grid > .field { min-width: 0; }
#single-item-input-panel .form-grid > .field > label { display: block; min-height: 20px; line-height: 1.35; }
#single-item-input-panel .form-grid > .field > input,
#single-item-input-panel .form-grid > .field > select { width: 100%; min-height: 44px; height: 44px; box-sizing: border-box; }
#trucking-pricing-fields input, #trucking-pricing-fields select { width:100%; min-height:44px; height:44px; box-sizing:border-box; }
@media (max-width: 900px) { #trucking-pricing-fields > div:nth-child(2) { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; } }
@media (max-width: 560px) { #trucking-pricing-fields > div:nth-child(2) { grid-template-columns: 1fr !important; } }
</style>

{{-- DAFTAR ITEM PENAWARAN (TABEL SUBMITTED ITEMS) --}}
<div class="section-heading" style="margin-top: 25px; margin-bottom: 10px;">
    <h2>📋 Daftar Item Penawaran</h2>
    <span class="subtle" id="items_count_display">0 item</span>
</div>

<div class="table-scroll" style="margin-bottom: 20px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8fafc; border-bottom: 1.5px solid #e2e8f0;">
                <th style="width: 35px; text-align: center; padding: 10px 8px;">#</th>
                <th style="padding: 10px 12px;">Uraian Biaya & Catatan</th>
                <th style="width: 110px; padding: 10px 8px;">Mata Uang / Kurs</th>
                <th style="width: 90px; padding: 10px 8px;">Satuan</th>
                <th style="width: 70px; text-align: right; padding: 10px 8px;">Qty</th>
                @if($canManageCost)
                    <th style="width: 120px; text-align: right; padding: 10px 8px;">Modal / Unit</th>
                @endif
                <th style="width: 120px; text-align: right; padding: 10px 8px;">Jual / Unit</th>
                <th style="width: 140px; text-align: right; padding: 10px 12px;">Subtotal (IDR Eqv)</th>
                <th style="width: 100px; text-align: center; padding: 10px 8px;">Aksi</th>
            </tr>
        </thead>
        <tbody id="quotation_items_tbody">
            <!-- Dynamic item rows will be rendered here by JS -->
        </tbody>
    </table>
</div>

<div class="summary-box" aria-live="polite">
    <div class="summary-row"><span>Total Penawaran (Jual)</span><strong data-preview-total>Rp 0,00</strong></div>
    <div class="summary-row"><span>Estimasi Profit</span><strong data-preview-profit style="color:#16a34a;">Rp 0,00</strong></div>
    <p class="form-help">Pajak, diskon, dan grand total dihitung saat disimpan.</p>
</div>

<div class="field"><label for="notes">Catatan / ketentuan penawaran</label><textarea name="notes" id="notes" rows="3" maxlength="5000">{{ old('notes',$quotation->notes) }}</textarea></div>
<div class="form-actions"><a class="button button-secondary" href="{{ route('quotations.index') }}">Batal</a><button class="button button-primary" id="btn_save_quotation" @disabled($customers->isEmpty())>Simpan draft</button></div>
</form></section>

@php
    $initialItems = old('items');
    if ($initialItems === null && $quotation->exists) {
        $initialItems = $quotation->items->map(fn($item) => [
            'description' => $item->description,
            'note' => $item->note ?? '',
            'unit' => $item->unit,
            'quantity' => $item->quantity,
            'unit_cost' => $item->unit_cost ?? 0,
            'unit_price' => $item->unit_price,
            'type' => $item->type->value ?? 'provision',
            'pricing_source' => $item->pricing_source ?? 'manual',
            'pricing_id' => $item->pricing_id ?? '',
            'currency' => $item->currency ?? 'IDR',
            'exchange_rate' => $item->exchange_rate ?? '1.00',
            'pricing_snapshot' => $item->pricing_snapshot ?? null,
        ])->toArray();
    }
@endphp
<script id="initial-items-data" type="application/json">
{!! json_encode($initialItems ?? []) !!}
</script>


@endsection
