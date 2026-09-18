@extends('layouts.app')
@section('title', 'Buat Shipping Instruction')
@section('content')

@php
    $defaultRemarks = "FREIGHT PREPAID\nPLEASE ISSUE ORIGINAL B/L 3/3\nCARGO MUST BE DISPATCHED ACCORDING TO BOOKING SCHEDULE";
    $backUrl = $selectedJob 
        ? route('jobs.show', $selectedJob) . '#tab-si' 
        : (request()->filled('job_id') 
            ? route('jobs.show', request('job_id')) . '#tab-si' 
            : route('shipping-instructions.index'));
@endphp

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL</p>
        <h1>Buat Shipping Instruction</h1>
        <p>Instruksi pengapalan muatan kepada Shipping Line / Carrier untuk penerbitan Bill of Lading (B/L).</p>
    </div>
    <a class="button button-secondary" href="{{ $backUrl }}">← Kembali</a>
</div>

<section class="panel form-panel">
    <form class="data-form" method="POST" action="{{ route('shipping-instructions.store') }}" id="siForm">
        @csrf

        {{-- HUBUNGAN JOB ORDER & DATA DOKUMEN --}}
        <div class="form-section-heading">
            <h2>Informasi Dokumen & Job Order</h2>
            <p>Pilih Job Order untuk menarik data Shipper, Consignee, Kapal, dan Muatan secara otomatis.</p>
        </div>

@php
    $selBc = $selectedJob?->bookingConfirmations?->first();
    $initVessel = old('vessel_voyage', $selectedJob?->vessel_voyage ?: ($selBc?->vessel_voyage ?? ''));
    $initCarrier = old('to_carrier', $selBc?->carrier_name ?? '');
    $initQuantity = old('quantity', $selectedJob?->package_count ?: ($selBc?->quantity ?? ''));
    $initUnit = old('package_unit', $selectedJob?->container_type ?: ($selBc?->package_unit ?? ''));
@endphp

        <div class="form-grid">
            <div class="field">
                <label for="job_id">Terkait Job Order</label>
                <select id="job_id" name="job_id">
                    <option value="">Pilih Job Order (Opsional)</option>
                    @foreach($jobs as $j)
                        @php
                            $bcFirst = $j->bookingConfirmations?->first();
                            $hasSi = $j->shippingInstructions?->isNotEmpty();
                            $jVessel = $j->vessel_voyage ?: ($bcFirst?->vessel_voyage ?? '');
                            $jCarrier = $bcFirst?->carrier_name ?? '';
                            $jQuantity = $j->package_count ?: ($bcFirst?->quantity ?? '');
                            $jUnit = $j->container_type ?: ($bcFirst?->package_unit ?? '');
                            $jGw = $j->gross_weight ?: ($bcFirst?->gross_weight ?? '');
                            $jVol = $j->volume ?: ($bcFirst?->volume ?? '');
                        @endphp
                        <option value="{{ $j->id }}"
                            @selected(old('job_id', $selectedJob?->id) == $j->id)
                            @disabled($hasSi && old('job_id', $selectedJob?->id) != $j->id)
                            data-customer-id="{{ $j->customer_id }}"
                            data-shipper="{{ $j->shipper_name }}"
                            data-consignee="{{ $j->consignee_name }}"
                            data-carrier="{{ $jCarrier }}"
                            data-vessel="{{ $jVessel }}"
                            data-pol="{{ $j->pol ?? $j->origin }}"
                            data-pod="{{ $j->pod ?? $j->destination }}"
                            data-etd="{{ $j->etd?->format('Y-m-d') }}"
                            data-eta="{{ $j->eta?->format('Y-m-d') }}"
                            data-quantity="{{ $jQuantity }}"
                            data-unit="{{ $jUnit }}"
                            data-commodity="{{ $j->cargo_description }}"
                            data-gross-weight="{{ $jGw }}"
                            data-volume="{{ $jVol }}"
                        >
                            {{ $j->number }} — {{ $j->customer?->name }} ({{ Str::limit($j->subject, 30) }}){{ $hasSi ? ' [Sudah Ada SI]' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="number">Nomor Shipping Instruction <span class="required">*</span></label>
                <input id="number" name="number" maxlength="60" value="{{ old('number', $defaultNumber) }}" required>
            </div>

            <div class="field">
                <label for="si_date">Tanggal SI <span class="required">*</span></label>
                <input id="si_date" name="si_date" type="date" value="{{ old('si_date', date('Y-m-d')) }}" required>
            </div>
        </div>

        {{-- PENERIMA SI (CARRIER / SHIPPING LINE) --}}
        {{-- Catatan: field Telp/Fax & Customer Pemilik Muatan dihapus sesuai request client --}}
        <div class="form-section-heading">
            <h2>Penerima SI (Shipping Line / Carrier / Pelayaran)</h2>
            <p>Pihak pelayaran atau agen yang menerima instruksi pengapalan ini.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="to_carrier">To (Shipping Line / Pelayaran) <span class="required">*</span></label>
                <select id="to_carrier" name="to_carrier" data-custom-select data-allow-custom="true" required aria-label="To Carrier / Shipping Line">
                    <option value="">Pilih Shipping Line atau ketik nama pelayaran...</option>
                    @php
                        $currentCarrier = $initCarrier;
                        $carrierFound = false;
                    @endphp
                    @foreach($carriers as $c)
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
            </div>

            <div class="field">
                <label for="carrier_attn">Attn (Nama PIC Pelayaran)</label>
                <input id="carrier_attn" name="carrier_attn" maxlength="120" value="{{ old('carrier_attn') }}" placeholder="contoh: Export Booking Dept / Bpk. David">
            </div>
        </div>

        {{-- PARA PIHAK B/L (SHIPPER, CONSIGNEE, NOTIFY PARTY) --}}
        {{-- Catatan: Alamat Shipper & Alamat Consignee dihapus sesuai request client, hanya Notify Party --}}
        <div class="form-section-heading">
            <h2>Pihak Kargo pada B/L (Shipper, Consignee, Notify Party)</h2>
            <p>Nama Shipper & Consignee ditarik otomatis dari Job Order. Notify Party diisi secara manual.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="shipper_name">Shipper (Pengirim) <span class="required">*</span></label>
                <input id="shipper_name" name="shipper_name" maxlength="160" value="{{ old('shipper_name', $selectedJob?->shipper_name) }}" required placeholder="Nama Perusahaan Shipper">
            </div>

            <div class="field">
                <label for="consignee_name">Consignee (Penerima) <span class="required">*</span></label>
                <input id="consignee_name" name="consignee_name" maxlength="160" value="{{ old('consignee_name', $selectedJob?->consignee_name) }}" required placeholder="Nama Perusahaan Consignee / TO ORDER">
            </div>

            <div class="field span-2">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <label for="notify_party" style="margin:0;">Notify Party</label>
                    <button type="button" class="button button-secondary" style="padding:2px 10px;font-size:12px;" onclick="document.getElementById('notify_party').value = 'SAME AS CONSIGNEE';">
                        Set "SAME AS CONSIGNEE"
                    </button>
                </div>
                <textarea id="notify_party" name="notify_party" rows="2" placeholder="Nama & alamat pihak yang dinotifikasi atau 'SAME AS CONSIGNEE'">{{ old('notify_party', 'SAME AS CONSIGNEE') }}</textarea>
            </div>
        </div>

        {{-- RINCIAN KAPAL & RUTE --}}
        <div class="form-section-heading">
            <h2>Sarana Pengangkut & Rute Pelayaran</h2>
            <p>Kapal utama, jadwal, serta pelabuhan muat dan bongkar. Aktifkan Transhipment jika muatan melalui pelabuhan transit.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="vessel_voyage">Vessel Name & Voyage</label>
                <input id="vessel_voyage" name="vessel_voyage" maxlength="120" value="{{ $initVessel }}" placeholder="contoh: MV. WAN HAI 312 V.E215">
                <small class="form-help" style="color:#64748b;font-size:12px;">Ditarik otomatis dari Job Order / Booking. Dapat diedit jika ada perubahan kapal / nomor voyage.</small>
            </div>

            <div class="field" style="display:flex;align-items:center;gap:10px;padding-top:22px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;margin:0;">
                    <input type="checkbox" id="is_transhipment" name="is_transhipment" value="1"
                        @checked(old('is_transhipment'))
                        onchange="toggleTranshipment(this.checked)"
                        style="width:18px;height:18px;cursor:pointer;">
                    Transhipment
                </label>
                <small style="color:#64748b;">Centang jika muatan melalui pelabuhan transit</small>
            </div>

            {{-- TRANSHIPMENT FIELDS — toggle tampil jika is_transhipment dicentang --}}
            <div id="transhipment_fields" class="field span-2" style="{{ old('is_transhipment') ? '' : 'display:none;' }}">
                <div class="form-grid" style="margin:0;">
                    <div class="field">
                        <label for="transit_port">Transit Port (Pelabuhan Transit) <span class="required">*</span></label>
                        <select id="transit_port" name="transit_port" data-custom-select data-allow-custom="true" aria-label="Transit Port">
                            <option value="">Pilih Port Transit atau ketik...</option>
                            @php
                                $currentTransit = old('transit_port');
                                $transitFound = false;
                            @endphp
                            @foreach($ports as $p)
                                @if($currentTransit === $p->name)
                                    @php $transitFound = true; @endphp
                                @endif
                                <option value="{{ $p->name }}" @selected($currentTransit === $p->name)>
                                    {{ $p->code ? '['.$p->code.'] ' : '' }}{{ $p->name }}
                                </option>
                            @endforeach
                            @if($currentTransit && !$transitFound)
                                <option value="{{ $currentTransit }}" selected data-custom-option="true">{{ $currentTransit }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="field">
                        <label for="connecting_vessel">Connecting Vessel (Kapal Penghubung / Feeder)</label>
                        <input id="connecting_vessel" name="connecting_vessel" maxlength="120"
                            value="{{ old('connecting_vessel') }}"
                            placeholder="Nama kapal feeder / penghubung">
                    </div>
                    <div class="field">
                        <label for="transit_etd">ETD Transit (Keberangkatan dari Transit Port)</label>
                        <input id="transit_etd" name="transit_etd" type="date" value="{{ old('transit_etd') }}">
                    </div>
                    <div class="field">
                        <label for="transit_eta">ETA Transit (Tiba di Transit Port)</label>
                        <input id="transit_eta" name="transit_eta" type="date" value="{{ old('transit_eta') }}">
                    </div>
                </div>
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
                <label for="pol">LOADING (Port of Loading) <span class="required">*</span></label>
                <select id="pol" name="pol" data-custom-select data-allow-custom="true" required aria-label="Port of Loading">
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
                <label for="pod">DISCHARGE (Port of Discharge) <span class="required">*</span></label>
                <select id="pod" name="pod" data-custom-select data-allow-custom="true" required aria-label="Port of Discharge">
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
        </div>

        {{-- TABEL KARGO (FORMAT 3 KOLOM B/L) --}}
        <div class="form-section-heading">
            <h2>Rincian Kargo pada B/L (Marks & Numbers, Description, GW/MEAS)</h2>
            <p>Data barang yang akan dicetak pada badan Bill of Lading.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="quantity">Quantity (Kuantitas)</label>
                <input id="quantity" name="quantity" maxlength="100" value="{{ $initQuantity }}" placeholder="contoh: 150">
            </div>

            <div class="field">
                <label for="package_unit">Satuan (Kemasan / Unit)</label>
                <select id="package_unit" name="package_unit" data-custom-select data-allow-custom="true" aria-label="Satuan (Kemasan / Unit)">
                    <option value="">Pilih atau ketik satuan kemasan...</option>
                    @php
                        $unitFound = false;
                        $standardUnits = ['Box', 'Carton', 'Pallet', 'Pcs', 'Package', 'Drum', 'Bags', 'Rolls', 'Crates', 'Unit', '20GP', '40GP', '40HQ', 'LCL'];
                    @endphp
                    @foreach($standardUnits as $u)
                        @if(strcasecmp($initUnit ?? '', $u) === 0)
                            @php $unitFound = true; @endphp
                        @endif
                        <option value="{{ $u }}" @selected(strcasecmp($initUnit ?? '', $u) === 0)>{{ $u }}</option>
                    @endforeach
                    @if($initUnit && !$unitFound)
                        <option value="{{ $initUnit }}" selected data-custom-option="true">{{ $initUnit }}</option>
                    @endif
                </select>
            </div>

            <div class="field">
                <label for="marks_numbers">Marks and Number</label>
                <textarea id="marks_numbers" name="marks_numbers" rows="3" placeholder="Tanda kemasan pada peti/karton, misal:&#10;PT. ABC LOGISTICS&#10;JAKARTA - INDONESIA&#10;C/NO. 1-100">{{ old('marks_numbers') }}</textarea>
            </div>

            <div class="field">
                <label for="cargo_description">Description of Goods <span class="required">*</span></label>
                <textarea id="cargo_description" name="cargo_description" rows="3" required placeholder="Uraian barang, jenis paket/kontainer, misal:&#10;1X20'GP CONTAINER S.T.C:&#10;150 PACKAGES OF ELECTRONIC PARTS">{{ old('cargo_description', $selectedJob?->cargo_description) }}</textarea>
            </div>

            <div class="field">
                <label for="gross_weight">G.W (Gross Weight - KGS)</label>
                <input id="gross_weight" name="gross_weight" inputmode="decimal" value="{{ old('gross_weight', $selectedJob?->gross_weight ?: ($selBc?->gross_weight ?? '')) }}" placeholder="contoh: 14500.00">
            </div>

            <div class="field">
                <label for="net_weight">N.W (Net Weight - KGS)</label>
                <input id="net_weight" name="net_weight" inputmode="decimal" value="{{ old('net_weight') }}" placeholder="contoh: 13800.00">
            </div>

            <div class="field span-2">
                <label for="measurement">MEAS (Measurement / CBM)</label>
                <input id="measurement" name="measurement" inputmode="decimal" value="{{ old('measurement', $selectedJob?->volume ?: ($selBc?->volume ?? '')) }}" placeholder="contoh: 28.50">
            </div>
        </div>

        {{-- REMARKS --}}
        <div class="form-section-heading">
            <h2>Remarks / Catatan B/L</h2>
            <p>Instruksi khusus kepada Shipping Line (tempat penerbitan B/L, ketentuan pembayaran, dll).</p>
        </div>

        <div class="form-grid">
            <div class="field span-2">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" name="remarks" rows="3">{{ old('remarks', $defaultRemarks) }}</textarea>
            </div>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ $backUrl }}">Batal</a>
            <button class="button button-primary">Simpan Shipping Instruction</button>
        </div>
    </form>
</section>

<script>
// Toggle transhipment fields
function toggleTranshipment(isChecked) {
    const fields = document.getElementById('transhipment_fields');
    if (fields) {
        fields.style.display = isChecked ? '' : 'none';
        if (!isChecked) {
            ['connecting_vessel', 'transit_etd', 'transit_eta'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            const tp = document.getElementById('transit_port');
            if (tp) {
                tp.value = '';
                tp.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }
}

document.getElementById('job_id')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (!opt || !opt.value) return;

    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (!el || val === undefined || val === null) return;
        if (el.tagName === 'SELECT') {
            let found = Array.from(el.options).some(o => o.value === val);
            if (!found && val) {
                const customOpt = document.createElement('option');
                customOpt.value = val;
                customOpt.textContent = val;
                customOpt.selected = true;
                el.appendChild(customOpt);
            }
            el.value = val;
            el.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            el.value = val;
        }
    };

    if (opt.dataset.shipper) setVal('shipper_name', opt.dataset.shipper);
    if (opt.dataset.consignee) setVal('consignee_name', opt.dataset.consignee);
    if (opt.dataset.carrier) setVal('to_carrier', opt.dataset.carrier);
    if (opt.dataset.vessel) setVal('vessel_voyage', opt.dataset.vessel);
    if (opt.dataset.pol) setVal('pol', opt.dataset.pol);
    if (opt.dataset.pod) setVal('pod', opt.dataset.pod);
    if (opt.dataset.etd) setVal('etd', opt.dataset.etd);
    if (opt.dataset.eta) setVal('eta', opt.dataset.eta);
    if (opt.dataset.quantity) setVal('quantity', opt.dataset.quantity);
    if (opt.dataset.unit) setVal('package_unit', opt.dataset.unit);
    if (opt.dataset.commodity) setVal('cargo_description', opt.dataset.commodity);
    if (opt.dataset.grossWeight) {
        setVal('gross_weight', opt.dataset.grossWeight);
        const nw = parseFloat(opt.dataset.grossWeight) * 0.95;
        if (!isNaN(nw)) setVal('net_weight', nw.toFixed(2));
    }
    if (opt.dataset.volume) setVal('measurement', opt.dataset.volume);
});
</script>

@endsection
