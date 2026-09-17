@extends('layouts.app')
@section('title', 'Edit B/L ' . $bl->number)
@section('content')

@php
    $backUrl = $bl->job_id ? route('jobs.show', $bl->job_id) . '#tab-bl' : route('bills-of-lading.show', $bl);
@endphp

<style>
.form-grid .field {
    display: flex;
    flex-direction: column;
}
.form-grid .field label {
    min-height: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 11.5px;
    font-weight: 600;
    color: #334155;
    line-height: 1.3;
}
</style>

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL — EXPORT SEA</p>
        <h1>Edit Bill of Lading</h1>
        <p>{{ $bl->number }} · Carrier: {{ $bl->carrier ?: '—' }}</p>
    </div>
    <a class="button button-secondary" href="{{ $backUrl }}">← Kembali</a>
</div>

<section class="panel form-panel">
    @if($errors->any())
        <div style="margin-bottom: 20px; padding: 14px 18px; border-radius: 8px; background: #fef2f2; border: 1px solid #f87171; color: #991b1b; font-size: 13px;">
            <strong style="display: block; margin-bottom: 6px; font-size: 14px;">⚠️ Periksa kembali isian form Anda:</strong>
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form class="data-form" method="POST" action="{{ route('bills-of-lading.update', $bl) }}">
        @csrf
        @method('PUT')

        {{-- 1. INFORMASI JOB ORDER & IDENTITAS B/L --}}
        <div class="form-section-heading">
            <h2>Data B/L & Job Order</h2>
            <p>Identitas dokumen Bill of Lading dan keterkaitan dengan Job Order.</p>
        </div>

        <div class="form-grid">
            <div class="field span-2">
                <label for="job_id">#Job No.</label>
                <select id="job_id" name="job_id">
                    <option value="">-- Pilih Job Order (Opsional) --</option>
                    @foreach($jobs as $j)
                        <option value="{{ $j->id }}" @selected(old('job_id', $bl->job_id) == $j->id)>
                            {{ $j->number }} — {{ $j->customer?->name }} ({{ Str::limit($j->subject, 35) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="number">Nomor B/L (Internal) <span class="required">*</span></label>
                <input id="number" name="number" maxlength="60" value="{{ old('number', $bl->number) }}" required>
            </div>

            <div class="field">
                <label for="bl_date">BL Date <span class="required">*</span></label>
                <input id="bl_date" name="bl_date" type="date" value="{{ old('bl_date', $bl->bl_date?->format('Y-m-d')) }}" required>
            </div>

            <div class="field">
                <label for="hbl_number">HBL No.</label>
                <input id="hbl_number" name="hbl_number" maxlength="100" value="{{ old('hbl_number', $bl->hbl_number) }}" placeholder="Nomor House B/L">
            </div>

            <div class="field">
                <label for="mbl_number">MBL No.</label>
                <input id="mbl_number" name="mbl_number" maxlength="100" value="{{ old('mbl_number', $bl->mbl_number) }}" placeholder="Nomor Master B/L">
            </div>

            <div class="field">
                <label for="bl_type">Tipe B/L <span class="required">*</span></label>
                <select id="bl_type" name="bl_type" required>
                    <option value="original" @selected(old('bl_type', $bl->bl_type) === 'original')>Original B/L</option>
                    <option value="telex" @selected(old('bl_type', $bl->bl_type) === 'telex')>Telex Release</option>
                    <option value="seaway" @selected(old('bl_type', $bl->bl_type) === 'seaway')>Sea Waybill</option>
                </select>
            </div>

            <div class="field">
                <label for="original_bl_count">Number of Original BL</label>
                <input id="original_bl_count" name="original_bl_count" type="number" min="0" value="{{ old('original_bl_count', $bl->original_bl_count ?? 3) }}">
            </div>

            <div class="field">
                <label for="place_of_issue">Place of Issue</label>
                <select id="place_of_issue" name="place_of_issue">
                    <option value="JAKARTA" @selected(old('place_of_issue', $bl->place_of_issue ?? 'JAKARTA') === 'JAKARTA')>JAKARTA</option>
                    <option value="SURABAYA" @selected(old('place_of_issue', $bl->place_of_issue) === 'SURABAYA')>SURABAYA</option>
                    <option value="SEMARANG" @selected(old('place_of_issue', $bl->place_of_issue) === 'SEMARANG')>SEMARANG</option>
                </select>
            </div>

            <div class="field">
                <label for="date_of_issue">Date of Issue</label>
                <input id="date_of_issue" name="date_of_issue" type="date" value="{{ old('date_of_issue', $bl->date_of_issue?->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="shipped_on_board_date">Shipping on Board</label>
                <input id="shipped_on_board_date" name="shipped_on_board_date" type="date" value="{{ old('shipped_on_board_date', $bl->shipped_on_board_date?->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="freight_term">Freight Type <span class="required">*</span></label>
                <select id="freight_term" name="freight_term" required>
                    <option value="PREPAID" @selected(old('freight_term', $bl->freight_term) === 'PREPAID')>PREPAID</option>
                    <option value="COLLECT" @selected(old('freight_term', $bl->freight_term) === 'COLLECT')>COLLECT</option>
                </select>
            </div>

            <div class="field">
                <label for="freight_payable_at">Freight Payable at</label>
                <select id="freight_payable_at" name="freight_payable_at">
                    <option value="JAKARTA" @selected(old('freight_payable_at', $bl->freight_payable_at ?? 'JAKARTA') === 'JAKARTA')>JAKARTA</option>
                    <option value="SURABAYA" @selected(old('freight_payable_at', $bl->freight_payable_at) === 'SURABAYA')>SURABAYA</option>
                    <option value="SEMARANG" @selected(old('freight_payable_at', $bl->freight_payable_at) === 'SEMARANG')>SEMARANG</option>
                </select>
            </div>

            <div class="field">
                <label for="customer_ref_number">Customer Ref Number</label>
                <input id="customer_ref_number" name="customer_ref_number" maxlength="100" value="{{ old('customer_ref_number', $bl->customer_ref_number) }}" placeholder="No PO / Ref Customer">
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

            <div class="field span-2">
                <label for="customer_id">Customer (Pemilik Muatan)</label>
                <select id="customer_id" name="customer_id">
                    <option value="">Pilih Customer (Opsional)</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(old('customer_id', $bl->customer_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- 2. PARTIES --}}
        <div class="form-section-heading">
            <h2>Parties (Shipper, Consignee, Notify & Agent)</h2>
            <p>Pihak-pihak yang tercetak pada dokumen B/L resmi.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="shipper_name">Shipper</label>
                <input id="shipper_name" name="shipper_name" maxlength="160"
                    value="{{ old('shipper_name', $bl->shipper_name) }}" placeholder="Nama Pengirim">
            </div>

            <div class="field">
                <label for="consignee_name">Consignee</label>
                <input id="consignee_name" name="consignee_name" maxlength="160"
                    value="{{ old('consignee_name', $bl->consignee_name) }}" placeholder="Nama Penerima">
            </div>

            <div class="field span-2">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <label for="notify_party" style="margin:0;">Notify Party</label>
                    <button type="button" class="button button-secondary" style="padding:2px 10px;font-size:12px;"
                        onclick="document.getElementById('notify_party').value='SAME AS CONSIGNEE';">
                        Set "SAME AS CONSIGNEE"
                    </button>
                </div>
                <textarea id="notify_party" name="notify_party" rows="2"
                    placeholder="Nama & alamat pihak yang dinotifikasi">{{ old('notify_party', $bl->notify_party) }}</textarea>
            </div>

            <div class="field">
                <label for="agent_name">Agent</label>
                <input id="agent_name" name="agent_name" list="vendor_agent_list" maxlength="160"
                    value="{{ old('agent_name', $bl->agent_name) }}" placeholder="Nama Agent di Pelabuhan Bongkar">
                <datalist id="vendor_agent_list">
                    @foreach($carriers as $agent)
                        <option value="{{ $agent->name }}">{{ $agent->name }}</option>
                    @endforeach
                </datalist>
            </div>

            <div class="field">
                <label for="carrier">Carrier (Pelayaran)</label>
                <input id="carrier" name="carrier" list="carrier_list" maxlength="160"
                    value="{{ old('carrier', $bl->carrier) }}" placeholder="contoh: ONE / Maersk / CMA CGM">
                <datalist id="carrier_list">
                    @foreach($carriers as $c)
                        <option value="{{ $c->name }}">{{ $c->name }}</option>
                    @endforeach
                </datalist>
            </div>

            <div class="field">
                <label for="shipper_switch">Shipper (Switch)</label>
                <input id="shipper_switch" name="shipper_switch" list="customer_switch_list" maxlength="160"
                    value="{{ old('shipper_switch', $bl->shipper_switch) }}" placeholder="Shipper pengganti (Switch BL)">
                <datalist id="customer_switch_list">
                    @foreach($customers as $c)
                        <option value="{{ $c->name }}">{{ $c->name }}</option>
                    @endforeach
                </datalist>
            </div>

            <div class="field">
                <label for="consignee_switch">Consignee (Switch)</label>
                <input id="consignee_switch" name="consignee_switch" maxlength="160"
                    value="{{ old('consignee_switch', $bl->consignee_switch) }}" placeholder="Consignee pengganti (Switch BL)">
            </div>
        </div>

        {{-- 3. VESSEL & ROUTING --}}
        <div class="form-section-heading">
            <h2>Vessel & Routing Information</h2>
            <p>Rute pelabuhan dan sarana pengangkut laut.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="pre_carriage">Pre Carriage</label>
                <input id="pre_carriage" name="pre_carriage" maxlength="160"
                    value="{{ old('pre_carriage', $bl->pre_carriage) }}" placeholder="Pengangkutan awal sebelum pelabuhan muat">
            </div>

            <div class="field">
                <label for="vessel_voyage">Vessel / Voyage</label>
                <input id="vessel_voyage" name="vessel_voyage" maxlength="120"
                    value="{{ old('vessel_voyage', $bl->vessel_voyage) }}" placeholder="contoh: MV. WAN HAI 312 V.E215">
            </div>

            <div class="field">
                <label for="etd">ETD (Keberangkatan)</label>
                <input id="etd" name="etd" type="date" value="{{ old('etd', $bl->etd?->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="eta">ETA (Kedatangan)</label>
                <input id="eta" name="eta" type="date" value="{{ old('eta', $bl->eta?->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="pol">POL (Port of Loading)</label>
                <input id="pol" name="pol" list="port_list" maxlength="120"
                    value="{{ old('pol', $bl->pol) }}" placeholder="Pelabuhan Muat">
            </div>

            <div class="field">
                <label for="place_of_receipt">Receipt</label>
                <input id="place_of_receipt" name="place_of_receipt" list="port_list" maxlength="120"
                    value="{{ old('place_of_receipt', $bl->place_of_receipt) }}" placeholder="Tempat Penerimaan Barang">
            </div>

            <div class="field">
                <label for="pod">POD (Port of Discharge)</label>
                <input id="pod" name="pod" list="port_list" maxlength="120"
                    value="{{ old('pod', $bl->pod) }}" placeholder="Pelabuhan Bongkar">
            </div>

            <div class="field">
                <label for="place_of_delivery">Delivery</label>
                <input id="place_of_delivery" name="place_of_delivery" list="port_list" maxlength="120"
                    value="{{ old('place_of_delivery', $bl->place_of_delivery) }}" placeholder="Tempat Penyerahan Akhir">
            </div>

            <div class="field">
                <label for="final_destination">Destination</label>
                <input id="final_destination" name="final_destination" list="port_list" maxlength="120"
                    value="{{ old('final_destination', $bl->final_destination) }}" placeholder="Tujuan Akhir Pengiriman">
            </div>

            <div class="field">
                <label for="carrier_bl_number">Carrier B/L No.</label>
                <input id="carrier_bl_number" name="carrier_bl_number" maxlength="100"
                    value="{{ old('carrier_bl_number', $bl->carrier_bl_number) }}" placeholder="Nomor B/L dari Pelayaran">
            </div>

            <datalist id="port_list">
                @foreach($ports as $p)
                    <option value="{{ $p->name }}">{{ $p->code ? '['.$p->code.'] ' : '' }}{{ $p->name }}</option>
                @endforeach
            </datalist>
        </div>

        {{-- 4. CARGO & QUANTITY --}}
        <div class="form-section-heading">
            <h2>Data Barang & Muatan (Cargo Details)</h2>
            <p>Data kuantitas, berat, dan ukuran muatan kapal.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="party">Party</label>
                <input id="party" name="party" maxlength="100" value="{{ old('party', $bl->party) }}" placeholder="Keterangan Party">
            </div>

            <div class="field">
                <label for="package_count">Qty</label>
                <div style="display:flex; gap:8px; align-items:center; width:100%;">
                    <input id="package_count" name="package_count" type="number" min="0"
                        value="{{ old('package_count', $bl->package_count) }}" placeholder="Jumlah" style="flex: 1 1 auto; width: 100%; min-width: 0;">
                    <select id="package_unit" name="package_unit" data-native-select style="flex: 0 0 115px; width: 115px; min-width: 115px; max-width: 115px; padding-left: 10px; padding-right: 26px; cursor: pointer; text-align: center; font-weight: 600;">
                        <option value="Package" @selected(old('package_unit', $bl->package_unit) === 'Package')>Package</option>
                        <option value="Box" @selected(old('package_unit', $bl->package_unit) === 'Box')>Box</option>
                        <option value="Pallet" @selected(old('package_unit', $bl->package_unit) === 'Pallet')>Pallet</option>
                        <option value="Carton" @selected(old('package_unit', $bl->package_unit) === 'Carton')>Carton</option>
                        <option value="Units" @selected(old('package_unit', $bl->package_unit) === 'Units')>Units</option>
                    </select>
                </div>
            </div>

            <div class="field">
                <label for="gross_weight">Gross Weight (KGS)</label>
                <input id="gross_weight" name="gross_weight" inputmode="decimal"
                    value="{{ old('gross_weight', $bl->gross_weight) }}" placeholder="contoh: 24.20">
            </div>

            <div class="field">
                <label for="measurement">Measurement (M3 / CBM)</label>
                <input id="measurement" name="measurement" inputmode="decimal"
                    value="{{ old('measurement', $bl->measurement) }}" placeholder="contoh: 0.00">
            </div>

            <div class="field">
                <label for="net_weight">Net Weight (KGS)</label>
                <input id="net_weight" name="net_weight" inputmode="decimal"
                    value="{{ old('net_weight', $bl->net_weight) }}" placeholder="contoh: 20.00">
            </div>

            <div class="field">
                <label for="marks_numbers">Marks and Number</label>
                <textarea id="marks_numbers" name="marks_numbers" rows="3"
                    placeholder="Tanda kemasan pada peti/karton">{{ old('marks_numbers', $bl->marks_numbers) }}</textarea>
            </div>

            <div class="field span-2">
                <label for="cargo_description">Description of Goods</label>
                <textarea id="cargo_description" name="cargo_description" rows="3"
                    placeholder="Uraian komoditas / muatan barang">{{ old('cargo_description', $bl->cargo_description) }}</textarea>
            </div>

            <div class="field span-2">
                <label for="remarks">Remarks / Instruksi Khusus</label>
                <textarea id="remarks" name="remarks" rows="2">{{ old('remarks', $bl->remarks) }}</textarea>
            </div>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ $backUrl }}">Batal</a>
            <button class="button button-primary">Perbarui Bill of Lading</button>
        </div>
    </form>
</section>

@endsection
