@extends('layouts.app')
@section('title', 'Edit B/L ' . $bl->number)
@section('content')

@php
    $backUrl = $bl->job_id ? route('jobs.show', $bl->job_id) . '#tab-bl' : route('bills-of-lading.show', $bl);
@endphp

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL — EXPORT SEA</p>
        <h1>Edit Bill of Lading</h1>
        <p>{{ $bl->number }} · Carrier: {{ $bl->carrier ?: '—' }}</p>
    </div>
    <a class="button button-secondary" href="{{ $backUrl }}">← Kembali</a>
</div>

<section class="panel form-panel">
    <form class="data-form" method="POST" action="{{ route('bills-of-lading.update', $bl) }}">
        @csrf
        @method('PUT')

        <div class="form-section-heading"><h2>Informasi Dasar & Job Order</h2></div>
        <div class="form-grid">
            <div class="field">
                <label for="job_id">Terkait Job Order</label>
                <select id="job_id" name="job_id">
                    <option value="">Pilih Job Order (Opsional)</option>
                    @foreach($jobs as $j)
                        <option value="{{ $j->id }}" @selected(old('job_id', $bl->job_id) == $j->id)>
                            {{ $j->number }} — {{ $j->customer?->name }} ({{ Str::limit($j->subject, 30) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="number">Nomor B/L <span class="required">*</span></label>
                <input id="number" name="number" maxlength="60" value="{{ old('number', $bl->number) }}" required>
            </div>
            <div class="field">
                <label for="bl_date">Tanggal B/L <span class="required">*</span></label>
                <input id="bl_date" name="bl_date" type="date" value="{{ old('bl_date', $bl->bl_date->format('Y-m-d')) }}" required>
            </div>
            <div class="field">
                <label for="bl_type">Tipe B/L <span class="required">*</span></label>
                <select id="bl_type" name="bl_type" required>
                    <option value="original" @selected(old('bl_type', $bl->bl_type) === 'original')>Original B/L (3 set)</option>
                    <option value="telex" @selected(old('bl_type', $bl->bl_type) === 'telex')>Telex Release</option>
                    <option value="seaway" @selected(old('bl_type', $bl->bl_type) === 'seaway')>Sea Waybill</option>
                </select>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="draft" @selected(old('status', $bl->status) === 'draft')>Draft</option>
                    <option value="issued" @selected(old('status', $bl->status) === 'issued')>Issued</option>
                    <option value="released" @selected(old('status', $bl->status) === 'released')>Released</option>
                    <option value="completed" @selected(old('status', $bl->status) === 'completed')>Completed</option>
                    <option value="cancelled" @selected(old('status', $bl->status) === 'cancelled')>Cancelled</option>
                </select>
            </div>
            <div class="field">
                <label for="customer_id">Customer (Pemilik Muatan)</label>
                <select id="customer_id" name="customer_id">
                    <option value="">Pilih Customer (Opsional)</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(old('customer_id', $bl->customer_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="freight_term">Freight Term <span class="required">*</span></label>
                <select id="freight_term" name="freight_term" required>
                    <option value="PREPAID" @selected(old('freight_term', $bl->freight_term) === 'PREPAID')>PREPAID</option>
                    <option value="COLLECT" @selected(old('freight_term', $bl->freight_term) === 'COLLECT')>COLLECT</option>
                </select>
            </div>
        </div>

        <div class="form-section-heading"><h2>Carrier & Informasi Kapal</h2></div>
        <div class="form-grid">
            <div class="field">
                <label for="carrier">Carrier (Shipping Line / Pelayaran)</label>
                <input id="carrier" name="carrier" list="carrier_list" maxlength="160"
                    value="{{ old('carrier', $bl->carrier) }}" placeholder="contoh: ONE / Maersk / CMA CGM">
                <datalist id="carrier_list">
                    @foreach($carriers as $c)
                        <option value="{{ $c->name }}">{{ $c->name }}</option>
                    @endforeach
                </datalist>
            </div>
            <div class="field">
                <label for="carrier_bl_number">No. B/L dari Pelayaran</label>
                <input id="carrier_bl_number" name="carrier_bl_number" maxlength="100"
                    value="{{ old('carrier_bl_number', $bl->carrier_bl_number) }}" placeholder="Nomor B/L pelayaran">
            </div>
            <div class="field">
                <label for="vessel_voyage">Vessel Name & Voyage</label>
                <input id="vessel_voyage" name="vessel_voyage" maxlength="120"
                    value="{{ old('vessel_voyage', $bl->vessel_voyage) }}" placeholder="contoh: MV. WAN HAI 312 V.E215">
            </div>
            <div class="field">
                <label for="place_of_delivery">Place of Delivery</label>
                <input id="place_of_delivery" name="place_of_delivery" maxlength="120"
                    value="{{ old('place_of_delivery', $bl->place_of_delivery) }}" placeholder="Tempat penyerahan akhir">
            </div>
            <div class="field">
                <label for="pol">Port of Loading (POL)</label>
                <input id="pol" name="pol" list="port_list" maxlength="120"
                    value="{{ old('pol', $bl->pol) }}" placeholder="Pelabuhan Muat">
            </div>
            <div class="field">
                <label for="pod">Port of Discharge (POD)</label>
                <input id="pod" name="pod" list="port_list" maxlength="120"
                    value="{{ old('pod', $bl->pod) }}" placeholder="Pelabuhan Bongkar">
            </div>
            <datalist id="port_list">
                @foreach($ports as $p)
                    <option value="{{ $p->name }}">{{ $p->code ? '['.$p->code.'] ' : '' }}{{ $p->name }}</option>
                @endforeach
            </datalist>
            <div class="field">
                <label for="etd">ETD (Keberangkatan)</label>
                <input id="etd" name="etd" type="date" value="{{ old('etd', $bl->etd?->format('Y-m-d')) }}">
            </div>
            <div class="field">
                <label for="eta">ETA (Kedatangan)</label>
                <input id="eta" name="eta" type="date" value="{{ old('eta', $bl->eta?->format('Y-m-d')) }}">
            </div>
        </div>

        <div class="form-section-heading"><h2>Pihak Kargo pada B/L</h2></div>
        <div class="form-grid">
            <div class="field">
                <label for="shipper_name">Shipper (Pengirim)</label>
                <input id="shipper_name" name="shipper_name" maxlength="160"
                    value="{{ old('shipper_name', $bl->shipper_name) }}" placeholder="Nama Perusahaan Shipper">
            </div>
            <div class="field">
                <label for="consignee_name">Consignee (Penerima)</label>
                <input id="consignee_name" name="consignee_name" maxlength="160"
                    value="{{ old('consignee_name', $bl->consignee_name) }}" placeholder="Nama Perusahaan Consignee / TO ORDER">
            </div>
            <div class="field span-2">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <label for="notify_party" style="margin:0;">Notify Party</label>
                    <button type="button" class="button button-secondary" style="padding:2px 10px;font-size:12px;"
                        onclick="document.getElementById('notify_party').value='SAME AS CONSIGNEE';">
                        Set "SAME AS CONSIGNEE"
                    </button>
                </div>
                <textarea id="notify_party" name="notify_party" rows="2">{{ old('notify_party', $bl->notify_party) }}</textarea>
            </div>
        </div>

        <div class="form-section-heading"><h2>Rincian Kargo pada B/L</h2></div>
        <div class="form-grid">
            <div class="field">
                <label for="marks_numbers">Marks and Number</label>
                <textarea id="marks_numbers" name="marks_numbers" rows="4" placeholder="Tanda kemasan">{{ old('marks_numbers', $bl->marks_numbers) }}</textarea>
            </div>
            <div class="field">
                <label for="cargo_description">Description of Goods</label>
                <textarea id="cargo_description" name="cargo_description" rows="4" placeholder="Uraian barang">{{ old('cargo_description', $bl->cargo_description) }}</textarea>
            </div>
            <div class="field">
                <label for="gross_weight">G.W (Gross Weight - KGS)</label>
                <input id="gross_weight" name="gross_weight" inputmode="decimal"
                    value="{{ old('gross_weight', $bl->gross_weight) }}" placeholder="contoh: 14500.00">
            </div>
            <div class="field">
                <label for="net_weight">N.W (Net Weight - KGS)</label>
                <input id="net_weight" name="net_weight" inputmode="decimal"
                    value="{{ old('net_weight', $bl->net_weight) }}" placeholder="contoh: 13800.00">
            </div>
            <div class="field">
                <label for="measurement">MEAS (Measurement / CBM)</label>
                <input id="measurement" name="measurement" inputmode="decimal"
                    value="{{ old('measurement', $bl->measurement) }}" placeholder="contoh: 28.50">
            </div>
        </div>

        <div class="form-section-heading"><h2>Remarks</h2></div>
        <div class="form-grid">
            <div class="field span-2">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" name="remarks" rows="3">{{ old('remarks', $bl->remarks) }}</textarea>
            </div>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ $backUrl }}">Batal</a>
            <button class="button button-primary">Simpan Perubahan</button>
        </div>
    </form>
</section>

@endsection
