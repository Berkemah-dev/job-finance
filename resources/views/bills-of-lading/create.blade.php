@extends('layouts.app')
@section('title', 'Buat Bill of Lading (B/L)')
@section('content')

@php
    $backUrl = $selectedJob
        ? route('jobs.show', $selectedJob) . '#tab-bl'
        : route('bills-of-lading.index');

    $firstSi = $selectedJob?->shippingInstructions?->first();
    $firstBc = $selectedJob?->bookingConfirmations?->first();
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
    <form class="data-form" method="POST" action="{{ route('bills-of-lading.store') }}" id="blForm">
        @csrf
        {{-- Status default draft, dihilangkan dari tampilan form sesuai request client --}}
        <input type="hidden" name="status" value="{{ old('status', 'draft') }}">

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
                            $bc = $j->bookingConfirmations?->first();
                            $jVessel = $j->vessel_voyage ?: ($bc?->vessel_voyage ?? '');
                            $jCarrier = $si?->to_carrier ?: ($bc?->carrier_name ?? '');
                            $jPol = $j->pol ?? $j->origin;
                            $jPod = $j->pod ?? $j->destination;
                        @endphp
                        <option value="{{ $j->id }}"
                            @selected(old('job_id', $selectedJob?->id) == $j->id)
                            data-customer-id="{{ $j->customer_id }}"
                            data-shipper="{{ $j->shipper_name }}"
                            data-consignee="{{ $j->consignee_name }}"
                            data-notify="{{ $si?->notify_party ?: 'SAME AS CONSIGNEE' }}"
                            data-hbl="{{ $j->hbl_number }}"
                            data-mbl="{{ $j->bl_number }}"
                            data-carrier="{{ $jCarrier }}"
                            data-vessel="{{ $jVessel }}"
                            data-pol="{{ $jPol }}"
                            data-pod="{{ $jPod }}"
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
                <label for="number">HBL No. <span class="required">*</span></label>
                <input id="number" name="number" maxlength="60" value="{{ old('number', $selectedJob?->hbl_number ?: $defaultNumber) }}" required placeholder="Contoh: RDXL26090001">
            </div>

            <div class="field">
                <label for="bl_date">BL Date <span class="required">*</span></label>
                <input id="bl_date" name="bl_date" type="date" value="{{ old('bl_date', date('Y-m-d')) }}" required>
            </div>

            <div class="field">
                <label for="mbl_number">MBL No.</label>
                <input id="mbl_number" name="mbl_number" maxlength="100" value="{{ old('mbl_number', $selectedJob?->bl_number) }}" placeholder="Nomor Master B/L (dari Job Order)">
                <small style="color:#64748b;font-size:12px;margin-top:2px;">Otomatis ditarik dari Master B/L Job Order.</small>
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
                <select id="agent_name" name="agent_name" data-custom-select data-allow-custom="true" aria-label="Agent">
                    <option value="">Pilih International Agent atau ketik...</option>
                    @php
                        $currentAgent = old('agent_name');
                        $agentFound = false;
                    @endphp
                    @foreach($internationalAgents as $agent)
                        @if($currentAgent === $agent->name)
                            @php $agentFound = true; @endphp
                        @endif
                        <option value="{{ $agent->name }}" @selected($currentAgent === $agent->name)>
                            {{ $agent->code ? '['.$agent->code.'] ' : '' }}{{ $agent->name }}
                        </option>
                    @endforeach
                    @if($currentAgent && !$agentFound)
                        <option value="{{ $currentAgent }}" selected data-custom-option="true">{{ $currentAgent }}</option>
                    @endif
                </select>
                <small style="color:#64748b;font-size:12px;margin-top:2px;">Vendor (International Agent)</small>
            </div>

            @php
                $initCarrier = old('carrier', $firstSi?->to_carrier ?: ($firstBc?->carrier_name ?? ''));
            @endphp
            <div class="field">
                <label for="carrier">Carrier (Pelayaran)</label>
                <select id="carrier" name="carrier" data-custom-select data-allow-custom="true" aria-label="Carrier (Pelayaran)">
                    <option value="">Pilih Shipping Line atau ketik...</option>
                    @php
                        $currentCarrier = $initCarrier;
                        $carrierFound = false;
                    @endphp
                    @foreach($shippingLines as $c)
                        @if($currentCarrier === $c->name)
                            @php $carrierFound = true; @endphp
                        @endif
                        <option value="{{ $c->name }}" @selected($currentCarrier === $c->name)>
                            {{ $c->code ? '['.$c->code.'] ' : '' }}{{ $c->name }}
                        </option>
                    @endforeach
                    @if($currentCarrier && !$carrierFound)
                        <option value="{{ $currentCarrier }}" selected data-custom-option="true">{{ $currentCarrier }}</option>
                    @endif
                </select>
                <small style="color:#64748b;font-size:12px;margin-top:2px;">Vendor (Shipping Lines)</small>
            </div>

            {{-- 5. TOMBOL SWITCH B/L (HANYA MUNCUL JIKA DICENTANG) --}}
            <div class="field span-2" style="margin-top: 4px; padding: 12px 16px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
                <label for="toggle_switch_bl" style="display: inline-flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 700; font-size: 13px; color: #0f172a; margin: 0; user-select: none;">
                    <input type="checkbox" id="toggle_switch_bl" name="is_switch_bl" value="1"
                        @checked(old('is_switch_bl', old('shipper_switch') || old('consignee_switch') ? '1' : '0') == '1')
                        style="width: 18px; height: 18px; accent-color: #2563eb; cursor: pointer;">
                    <span>Switch B/L (Ganti Shipper & Consignee)</span>
                </label>
                <div style="font-size: 12px; color: #64748b; margin-top: 4px; margin-left: 28px;">
                    Centang jika pengiriman menggunakan Switch B/L untuk memunculkan input Shipper dan Consignee pengganti.
                </div>
            </div>

            <div id="switch_bl_container" class="form-grid span-2" style="display: {{ (old('is_switch_bl', old('shipper_switch') || old('consignee_switch') ? '1' : '0') == '1') ? 'grid' : 'none' }}; grid-column: span 2; margin: 0; padding: 0;">
                <div class="field">
                    <label for="shipper_switch">Shipper (Switch)</label>
                    <input id="shipper_switch" name="shipper_switch" maxlength="160"
                        value="{{ old('shipper_switch') }}" placeholder="Shipper pengganti (Switch BL)">
                </div>

                <div class="field">
                    <label for="consignee_switch">Consignee (Switch)</label>
                    <input id="consignee_switch" name="consignee_switch" maxlength="160"
                        value="{{ old('consignee_switch') }}" placeholder="Consignee pengganti (Switch BL)">
                </div>
            </div>
        </div>

        {{-- 3. VESSEL & ROUTING (MASTER PORT) --}}
        <div class="form-section-heading">
            <h2>Vessel & Routing Information</h2>
            <p>Rute pelabuhan (Master Port) dan sarana pengangkut laut.</p>
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
                <select id="pol" name="pol" data-custom-select data-allow-custom="true" aria-label="Port of Loading">
                    <option value="">Pilih Port of Loading (POL)...</option>
                    @php
                        $currentPol = old('pol', $selectedJob?->pol ?? $selectedJob?->origin);
                        $polFound = false;
                    @endphp
                    @foreach($ports as $p)
                        @if($currentPol === $p->name)
                            @php $polFound = true; @endphp
                        @endif
                        <option value="{{ $p->name }}" @selected($currentPol === $p->name)>
                            {{ $p->code ? '['.$p->code.'] ' : '' }}{{ $p->name }}
                        </option>
                    @endforeach
                    @if($currentPol && !$polFound)
                        <option value="{{ $currentPol }}" selected data-custom-option="true">{{ $currentPol }}</option>
                    @endif
                </select>
            </div>

            <div class="field">
                <label for="place_of_receipt">Receipt</label>
                <select id="place_of_receipt" name="place_of_receipt" data-custom-select data-allow-custom="true" aria-label="Receipt">
                    <option value="">Pilih Tempat Penerimaan...</option>
                    @php
                        $currentReceipt = old('place_of_receipt');
                        $receiptFound = false;
                    @endphp
                    @foreach($ports as $p)
                        @if($currentReceipt === $p->name)
                            @php $receiptFound = true; @endphp
                        @endif
                        <option value="{{ $p->name }}" @selected($currentReceipt === $p->name)>
                            {{ $p->code ? '['.$p->code.'] ' : '' }}{{ $p->name }}
                        </option>
                    @endforeach
                    @if($currentReceipt && !$receiptFound)
                        <option value="{{ $currentReceipt }}" selected data-custom-option="true">{{ $currentReceipt }}</option>
                    @endif
                </select>
            </div>

            <div class="field">
                <label for="pod">POD (Port of Discharge)</label>
                <select id="pod" name="pod" data-custom-select data-allow-custom="true" aria-label="Port of Discharge">
                    <option value="">Pilih Port of Discharge (POD)...</option>
                    @php
                        $currentPod = old('pod', $selectedJob?->pod ?? $selectedJob?->destination);
                        $podFound = false;
                    @endphp
                    @foreach($ports as $p)
                        @if($currentPod === $p->name)
                            @php $podFound = true; @endphp
                        @endif
                        <option value="{{ $p->name }}" @selected($currentPod === $p->name)>
                            {{ $p->code ? '['.$p->code.'] ' : '' }}{{ $p->name }}
                        </option>
                    @endforeach
                    @if($currentPod && !$podFound)
                        <option value="{{ $currentPod }}" selected data-custom-option="true">{{ $currentPod }}</option>
                    @endif
                </select>
            </div>

            <div class="field">
                <label for="place_of_delivery">Delivery</label>
                <select id="place_of_delivery" name="place_of_delivery" data-custom-select data-allow-custom="true" aria-label="Delivery">
                    <option value="">Pilih Tempat Penyerahan...</option>
                    @php
                        $currentDelivery = old('place_of_delivery');
                        $deliveryFound = false;
                    @endphp
                    @foreach($ports as $p)
                        @if($currentDelivery === $p->name)
                            @php $deliveryFound = true; @endphp
                        @endif
                        <option value="{{ $p->name }}" @selected($currentDelivery === $p->name)>
                            {{ $p->code ? '['.$p->code.'] ' : '' }}{{ $p->name }}
                        </option>
                    @endforeach
                    @if($currentDelivery && !$deliveryFound)
                        <option value="{{ $currentDelivery }}" selected data-custom-option="true">{{ $currentDelivery }}</option>
                    @endif
                </select>
            </div>

            <div class="field">
                <label for="final_destination">Destination</label>
                <select id="final_destination" name="final_destination" data-custom-select data-allow-custom="true" aria-label="Destination">
                    <option value="">Pilih Tujuan Akhir...</option>
                    @php
                        $currentDest = old('final_destination');
                        $destFound = false;
                    @endphp
                    @foreach($ports as $p)
                        @if($currentDest === $p->name)
                            @php $destFound = true; @endphp
                        @endif
                        <option value="{{ $p->name }}" @selected($currentDest === $p->name)>
                            {{ $p->code ? '['.$p->code.'] ' : '' }}{{ $p->name }}
                        </option>
                    @endforeach
                    @if($currentDest && !$destFound)
                        <option value="{{ $currentDest }}" selected data-custom-option="true">{{ $currentDest }}</option>
                    @endif
                </select>
            </div>
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
                        value="{{ old('package_count', $selectedJob?->package_count) }}" placeholder="Jumlah" style="flex: 1 1 auto; width: 100%; min-width: 0;">
                    <select id="package_unit" name="package_unit" data-native-select style="flex: 0 0 115px; width: 115px; min-width: 115px; max-width: 115px; padding-left: 10px; padding-right: 26px; cursor: pointer; text-align: center; font-weight: 600;">
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
    const setCustomSelectVal = (id, val) => {
        const el = document.getElementById(id);
        if (!el || val === undefined || val === null) return;
        let found = Array.from(el.options).find(o => o.value.trim().toLowerCase() === String(val).trim().toLowerCase());
        if (!found && val) {
            found = new Option(val, val, true, true);
            found.dataset.customOption = 'true';
            el.add(found);
        }
        if (found) {
            el.value = found.value;
        } else {
            el.value = '';
        }
        el.dispatchEvent(new Event('change', { bubbles: true }));
    };

    if (opt.dataset.customerId) setVal('customer_id', opt.dataset.customerId);
    if (opt.dataset.shipper) setVal('shipper_name', opt.dataset.shipper);
    if (opt.dataset.consignee) setVal('consignee_name', opt.dataset.consignee);
    if (opt.dataset.notify) setVal('notify_party', opt.dataset.notify);
    if (opt.dataset.hbl) setVal('number', opt.dataset.hbl);
    if (opt.dataset.mbl) setVal('mbl_number', opt.dataset.mbl);
    if (opt.dataset.carrier) setCustomSelectVal('carrier', opt.dataset.carrier);
    if (opt.dataset.vessel) setVal('vessel_voyage', opt.dataset.vessel);
    if (opt.dataset.pol) setCustomSelectVal('pol', opt.dataset.pol);
    if (opt.dataset.pod) setCustomSelectVal('pod', opt.dataset.pod);
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

// Toggle Switch B/L
const toggleSwitchBl = document.getElementById('toggle_switch_bl');
const switchBlContainer = document.getElementById('switch_bl_container');
if (toggleSwitchBl && switchBlContainer) {
    toggleSwitchBl.addEventListener('change', function() {
        switchBlContainer.style.display = this.checked ? 'grid' : 'none';
        if (!this.checked) {
            const sInput = document.getElementById('shipper_switch');
            const cInput = document.getElementById('consignee_switch');
            if (sInput) sInput.value = '';
            if (cInput) cInput.value = '';
        }
    });
}
</script>

@endsection
