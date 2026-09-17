@extends('layouts.app')
@section('title', 'Buat Bill of Lading (B/L)')
@section('content')

@php
    $backUrl = $selectedJob
        ? route('jobs.show', $selectedJob) . '#tab-bl'
        : route('bills-of-lading.index');

    $firstSi = $selectedJob?->shippingInstructions?->first();
    $defaultNotify = $firstSi?->notify_party ?: 'SAME AS CONSIGNEE';
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
        <h1>Buat Bill of Lading (B/L)</h1>
        <p>Dokumen kepemilikan muatan laut untuk pengiriman Export Sea.</p>
    </div>
    <a class="button button-secondary" href="{{ $backUrl }}">← Kembali</a>
</div>

<section class="panel form-panel">
    <form class="data-form" method="POST" action="{{ route('bills-of-lading.store') }}" id="blForm">
        @csrf

        {{-- 1. INFORMASI JOB ORDER & IDENTITAS B/L --}}
        <div class="form-section-heading">
            <h2>Data B/L & Job Order</h2>
            <p>Pilih Job Order untuk menarik seluruh data secara otomatis.</p>
        </div>

        <div class="form-grid">
            <div class="field span-2">
                <label for="job_id">#Job No. <span class="required">*</span></label>
                <select id="job_id" name="job_id">
                    <option value="">-- Pilih Job Order --</option>
                    @foreach($jobs as $j)
                        @php
                            $si = $j->shippingInstructions?->first();
                        @endphp
                        <option value="{{ $j->id }}"
                            @selected(old('job_id', $selectedJob?->id) == $j->id)
                            data-customer-id="{{ $j->customer_id }}"
                            data-shipper="{{ $j->shipper_name }}"
                            data-consignee="{{ $j->consignee_name }}"
                            data-notify="{{ $si?->notify_party ?: 'SAME AS CONSIGNEE' }}"
                            data-hbl="{{ $j->hbl_number }}"
                            data-mbl="{{ $j->bl_number }}"
                            data-vessel="{{ $j->vessel_voyage }}"
                            data-pol="{{ $j->pol ?? $j->origin }}"
                            data-pod="{{ $j->pod ?? $j->destination }}"
                            data-etd="{{ $j->etd?->format('Y-m-d') }}"
                            data-eta="{{ $j->eta?->format('Y-m-d') }}"
                            data-commodity="{{ $j->cargo_description }}"
                            data-gross-weight="{{ $j->gross_weight }}"
                            data-volume="{{ $j->volume }}"
                            data-qty="{{ $j->package_count }}"
                        >
                            {{ $j->number }} — {{ $j->customer?->name }} ({{ Str::limit($j->subject, 35) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="number">Nomor B/L (Internal) <span class="required">*</span></label>
                <input id="number" name="number" maxlength="60" value="{{ old('number', $defaultNumber) }}" required>
            </div>

            <div class="field">
                <label for="bl_date">BL Date <span class="required">*</span></label>
                <input id="bl_date" name="bl_date" type="date" value="{{ old('bl_date', date('Y-m-d')) }}" required>
            </div>

            <div class="field">
                <label for="hbl_number">HBL No.</label>
                <input id="hbl_number" name="hbl_number" maxlength="100" value="{{ old('hbl_number', $selectedJob?->hbl_number) }}" placeholder="Nomor House B/L (dari Job Order)">
            </div>

            <div class="field">
                <label for="mbl_number">MBL No.</label>
                <input id="mbl_number" name="mbl_number" maxlength="100" value="{{ old('mbl_number', $selectedJob?->bl_number) }}" placeholder="Nomor Master B/L (dari Job Order)">
            </div>

            <div class="field">
                <label for="bl_type">Tipe B/L <span class="required">*</span></label>
                <select id="bl_type" name="bl_type" required>
                    <option value="original" @selected(old('bl_type', 'original') === 'original')>Original B/L</option>
                    <option value="telex" @selected(old('bl_type') === 'telex')>Telex Release</option>
                    <option value="seaway" @selected(old('bl_type') === 'seaway')>Sea Waybill</option>
                </select>
            </div>

            <div class="field">
                <label for="original_bl_count">Number of Original BL</label>
                <input id="original_bl_count" name="original_bl_count" type="number" min="0" value="{{ old('original_bl_count', 3) }}">
            </div>

            <div class="field">
                <label for="place_of_issue">Place of Issue</label>
                <select id="place_of_issue" name="place_of_issue">
                    <option value="JAKARTA" @selected(old('place_of_issue', 'JAKARTA') === 'JAKARTA')>JAKARTA</option>
                    <option value="SURABAYA" @selected(old('place_of_issue') === 'SURABAYA')>SURABAYA</option>
                    <option value="SEMARANG" @selected(old('place_of_issue') === 'SEMARANG')>SEMARANG</option>
                </select>
            </div>

            <div class="field">
                <label for="date_of_issue">Date of Issue</label>
                <input id="date_of_issue" name="date_of_issue" type="date" value="{{ old('date_of_issue', date('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="shipped_on_board_date">Shipping on Board</label>
                <input id="shipped_on_board_date" name="shipped_on_board_date" type="date" value="{{ old('shipped_on_board_date', $selectedJob?->etd?->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="freight_term">Freight Type <span class="required">*</span></label>
                <select id="freight_term" name="freight_term" required>
                    <option value="PREPAID" @selected(old('freight_term', 'PREPAID') === 'PREPAID')>PREPAID</option>
                    <option value="COLLECT" @selected(old('freight_term') === 'COLLECT')>COLLECT</option>
                </select>
            </div>

            <div class="field">
                <label for="freight_payable_at">Freight Payable at</label>
                <select id="freight_payable_at" name="freight_payable_at">
                    <option value="JAKARTA" @selected(old('freight_payable_at', 'JAKARTA') === 'JAKARTA')>JAKARTA</option>
                    <option value="SURABAYA" @selected(old('freight_payable_at') === 'SURABAYA')>SURABAYA</option>
                    <option value="SEMARANG" @selected(old('freight_payable_at') === 'SEMARANG')>SEMARANG</option>
                </select>
            </div>

            <div class="field">
                <label for="customer_ref_number">Customer Ref Number</label>
                <input id="customer_ref_number" name="customer_ref_number" maxlength="100" value="{{ old('customer_ref_number') }}" placeholder="No PO / Ref Customer">
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="draft" @selected(old('status', 'draft') === 'draft')>Draft</option>
                    <option value="issued" @selected(old('status') === 'issued')>Issued</option>
                    <option value="released" @selected(old('status') === 'released')>Released</option>
                    <option value="completed" @selected(old('status') === 'completed')>Completed</option>
                    <option value="cancelled" @selected(old('status') === 'cancelled')>Cancelled</option>
                </select>
            </div>

            <div class="field span-2">
                <label for="customer_id">Customer (Pemilik Muatan)</label>
                <select id="customer_id" name="customer_id">
                    <option value="">Pilih Customer (Opsional)</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(old('customer_id', $selectedJob?->customer_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- 2. PARTIES (PARA PIHAK) --}}
        <div class="form-section-heading">
            <h2>Parties (Shipper, Consignee, Notify & Agent)</h2>
            <p>Pihak-pihak yang tercetak pada dokumen B/L sesuai standar formulir.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="shipper_name">Shipper</label>
                <input id="shipper_name" name="shipper_name" maxlength="160"
                    value="{{ old('shipper_name', $selectedJob?->shipper_name) }}"
                    placeholder="Nama Pengirim (Default Job Order)">
            </div>

            <div class="field">
                <label for="consignee_name">Consignee</label>
                <input id="consignee_name" name="consignee_name" maxlength="160"
                    value="{{ old('consignee_name', $selectedJob?->consignee_name) }}"
                    placeholder="Nama Penerima (Default Job Order)">
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
                    placeholder="Nama & alamat pihak yang dinotifikasi (Default Notify SI)">{{ old('notify_party', $defaultNotify) }}</textarea>
            </div>

            <div class="field">
                <label for="agent_name">Agent</label>
                <input id="agent_name" name="agent_name" list="vendor_agent_list" maxlength="160"
                    value="{{ old('agent_name') }}" placeholder="Nama Agent di Pelabuhan Bongkar">
                <datalist id="vendor_agent_list">
                    @foreach($carriers as $agent)
                        <option value="{{ $agent->name }}">{{ $agent->name }}</option>
                    @endforeach
                </datalist>
            </div>

            <div class="field">
                <label for="carrier">Carrier (Pelayaran)</label>
                <input id="carrier" name="carrier" list="carrier_list" maxlength="160"
                    value="{{ old('carrier') }}" placeholder="contoh: ONE / Maersk / CMA CGM">
                <datalist id="carrier_list">
                    @foreach($carriers as $c)
                        <option value="{{ $c->name }}">{{ $c->name }}</option>
                    @endforeach
                </datalist>
            </div>

            <div class="field">
                <label for="shipper_switch">Shipper (Switch)</label>
                <input id="shipper_switch" name="shipper_switch" list="customer_switch_list" maxlength="160"
                    value="{{ old('shipper_switch') }}" placeholder="Shipper pengganti (Switch BL)">
                <datalist id="customer_switch_list">
                    @foreach($customers as $c)
                        <option value="{{ $c->name }}">{{ $c->name }}</option>
                    @endforeach
                </datalist>
            </div>

            <div class="field">
                <label for="consignee_switch">Consignee (Switch)</label>
                <input id="consignee_switch" name="consignee_switch" maxlength="160"
                    value="{{ old('consignee_switch') }}" placeholder="Consignee pengganti (Switch BL)">
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
                    value="{{ old('pre_carriage') }}" placeholder="Pengangkutan awal sebelum pelabuhan muat">
            </div>

            <div class="field">
                <label for="vessel_voyage">Vessel / Voyage</label>
                <input id="vessel_voyage" name="vessel_voyage" maxlength="120"
                    value="{{ old('vessel_voyage', $selectedJob?->vessel_voyage) }}"
                    placeholder="contoh: MV. WAN HAI 312 V.E215">
            </div>

            <div class="field">
                <label for="etd">ETD (Keberangkatan)</label>
                <input id="etd" name="etd" type="date" value="{{ old('etd', $selectedJob?->etd?->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="eta">ETA (Kedatangan)</label>
                <input id="eta" name="eta" type="date" value="{{ old('eta', $selectedJob?->eta?->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="pol">POL (Port of Loading)</label>
                <input id="pol" name="pol" list="port_list" maxlength="120"
                    value="{{ old('pol', $selectedJob?->pol ?? $selectedJob?->origin) }}"
                    placeholder="Pelabuhan Muat">
            </div>

            <div class="field">
                <label for="place_of_receipt">Receipt</label>
                <input id="place_of_receipt" name="place_of_receipt" list="port_list" maxlength="120"
                    value="{{ old('place_of_receipt') }}" placeholder="Tempat Penerimaan Barang">
            </div>

            <div class="field">
                <label for="pod">POD (Port of Discharge)</label>
                <input id="pod" name="pod" list="port_list" maxlength="120"
                    value="{{ old('pod', $selectedJob?->pod ?? $selectedJob?->destination) }}"
                    placeholder="Pelabuhan Bongkar">
            </div>

            <div class="field">
                <label for="place_of_delivery">Delivery</label>
                <input id="place_of_delivery" name="place_of_delivery" list="port_list" maxlength="120"
                    value="{{ old('place_of_delivery') }}" placeholder="Tempat Penyerahan Akhir">
            </div>

            <div class="field">
                <label for="final_destination">Destination</label>
                <input id="final_destination" name="final_destination" list="port_list" maxlength="120"
                    value="{{ old('final_destination') }}" placeholder="Tujuan Akhir Pengiriman">
            </div>

            <div class="field">
                <label for="carrier_bl_number">Carrier B/L No.</label>
                <input id="carrier_bl_number" name="carrier_bl_number" maxlength="100"
                    value="{{ old('carrier_bl_number') }}" placeholder="Nomor B/L dari Pelayaran">
            </div>

            <datalist id="port_list">
                @foreach($ports as $p)
                    <option value="{{ $p->name }}">{{ $p->code ? '['.$p->code.'] ' : '' }}{{ $p->name }}</option>
                @endforeach
            </datalist>
        </div>

        {{-- 4. CARGO & QUANTITY (FORMAT STANDAR B/L) --}}
        <div class="form-section-heading">
            <h2>Data Barang & Muatan (Cargo Details)</h2>
            <p>Data kuantitas, berat, dan ukuran muatan kapal.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="party">Party</label>
                <input id="party" name="party" maxlength="100" value="{{ old('party') }}" placeholder="Keterangan Party">
            </div>

            <div class="field">
                <label for="package_count">Qty</label>
                <div style="display:flex; gap:8px; align-items:center; width:100%;">
                    <input id="package_count" name="package_count" type="number" min="0"
                        value="{{ old('package_count', $selectedJob?->package_count) }}" placeholder="Jumlah" style="flex: 1 1 0%; min-width: 0;">
                    <select id="package_unit" name="package_unit" style="flex: 0 0 120px; width: 120px; max-width: 120px;">
                        <option value="Package" @selected(old('package_unit') === 'Package')>Package</option>
                        <option value="Box" @selected(old('package_unit') === 'Box')>Box</option>
                        <option value="Pallet" @selected(old('package_unit') === 'Pallet')>Pallet</option>
                        <option value="Carton" @selected(old('package_unit') === 'Carton')>Carton</option>
                        <option value="Units" @selected(old('package_unit') === 'Units')>Units</option>
                    </select>
                </div>
            </div>

            <div class="field">
                <label for="gross_weight">Gross Weight (KGS)</label>
                <input id="gross_weight" name="gross_weight" inputmode="decimal"
                    value="{{ old('gross_weight', $selectedJob?->gross_weight) }}" placeholder="contoh: 24.20">
            </div>

            <div class="field">
                <label for="measurement">Measurement (M3 / CBM)</label>
                <input id="measurement" name="measurement" inputmode="decimal"
                    value="{{ old('measurement', $selectedJob?->volume) }}" placeholder="contoh: 0.00">
            </div>

            <div class="field">
                <label for="net_weight">Net Weight (KGS)</label>
                <input id="net_weight" name="net_weight" inputmode="decimal"
                    value="{{ old('net_weight') }}" placeholder="contoh: 20.00">
            </div>

            <div class="field">
                <label for="marks_numbers">Marks and Number</label>
                <textarea id="marks_numbers" name="marks_numbers" rows="3"
                    placeholder="Tanda kemasan pada peti/karton">{{ old('marks_numbers') }}</textarea>
            </div>

            <div class="field span-2">
                <label for="cargo_description">Description of Goods</label>
                <textarea id="cargo_description" name="cargo_description" rows="3"
                    placeholder="Uraian komoditas / muatan barang">{{ old('cargo_description', $selectedJob?->cargo_description) }}</textarea>
            </div>

            <div class="field span-2">
                <label for="remarks">Remarks / Instruksi Khusus</label>
                <textarea id="remarks" name="remarks" rows="2">{{ old('remarks') }}</textarea>
            </div>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ $backUrl }}">Batal</a>
            <button class="button button-primary">Simpan Bill of Lading</button>
        </div>
    </form>
</section>

<script>
document.getElementById('job_id')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (!opt || !opt.value) return;
    const setVal = (id, val) => { const el = document.getElementById(id); if (el && val !== undefined && val !== null) el.value = val; };

    if (opt.dataset.customerId) setVal('customer_id', opt.dataset.customerId);
    if (opt.dataset.shipper) setVal('shipper_name', opt.dataset.shipper);
    if (opt.dataset.consignee) setVal('consignee_name', opt.dataset.consignee);
    if (opt.dataset.notify) setVal('notify_party', opt.dataset.notify);
    if (opt.dataset.hbl) setVal('hbl_number', opt.dataset.hbl);
    if (opt.dataset.mbl) setVal('mbl_number', opt.dataset.mbl);
    if (opt.dataset.vessel) setVal('vessel_voyage', opt.dataset.vessel);
    if (opt.dataset.pol) setVal('pol', opt.dataset.pol);
    if (opt.dataset.pod) setVal('pod', opt.dataset.pod);
    if (opt.dataset.etd) {
        setVal('etd', opt.dataset.etd);
        setVal('shipped_on_board_date', opt.dataset.etd);
    }
    if (opt.dataset.eta) setVal('eta', opt.dataset.eta);
    if (opt.dataset.commodity) setVal('cargo_description', opt.dataset.commodity);
    if (opt.dataset.grossWeight) setVal('gross_weight', opt.dataset.grossWeight);
    if (opt.dataset.volume) setVal('measurement', opt.dataset.volume);
    if (opt.dataset.qty) setVal('package_count', opt.dataset.qty);
});
</script>

@endsection
