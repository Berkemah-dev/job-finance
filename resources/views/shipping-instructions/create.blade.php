@extends('layouts.app')
@section('title', 'Buat Shipping Instruction')
@section('content')

@php
    $defaultRemarks = "FREIGHT PREPAID\nPLEASE ISSUE ORIGINAL B/L 3/3\nCARGO MUST BE DISPATCHED ACCORDING TO BOOKING SCHEDULE";
@endphp

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL</p>
        <h1>Buat Shipping Instruction</h1>
        <p>Instruksi pengapalan muatan kepada Shipping Line / Carrier untuk penerbitan Bill of Lading (B/L).</p>
    </div>
    <a class="text-link" href="{{ route('shipping-instructions.index') }}">← Kembali ke daftar</a>
</div>

<section class="panel form-panel">
    <form class="data-form" method="POST" action="{{ route('shipping-instructions.store') }}" id="siForm">
        @csrf

        {{-- HUBUNGAN JOB ORDER & DATA DOKUMEN --}}
        <div class="form-section-heading">
            <h2>Informasi Dokumen & Job Order</h2>
            <p>Pilih Job Order untuk menarik data Shipper, Consignee, Kapal, dan Muatan secara otomatis.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="job_id">Terkait Job Order</label>
                <select id="job_id" name="job_id">
                    <option value="">Pilih Job Order (Opsional)</option>
                    @foreach($jobs as $j)
                        <option value="{{ $j->id }}"
                            @selected(old('job_id', $selectedJob?->id) == $j->id)
                            data-customer-id="{{ $j->customer_id }}"
                            data-shipper="{{ $j->shipper_name }}"
                            data-shipper-address="{{ $j->shipper_address }}"
                            data-consignee="{{ $j->consignee_name }}"
                            data-consignee-address="{{ $j->consignee_address }}"
                            data-vessel="{{ $j->vessel_voyage }}"
                            data-pol="{{ $j->pol ?? $j->origin }}"
                            data-pod="{{ $j->pod ?? $j->destination }}"
                            data-etd="{{ $j->etd?->format('Y-m-d') }}"
                            data-eta="{{ $j->eta?->format('Y-m-d') }}"
                            data-commodity="{{ $j->cargo_description }}"
                            data-gross-weight="{{ $j->gross_weight }}"
                            data-volume="{{ $j->volume }}"
                        >
                            {{ $j->number }} — {{ $j->customer?->name }} ({{ Str::limit($j->subject, 30) }})
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

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="submitted" @selected(old('status', 'submitted') === 'submitted')>Submitted</option>
                    <option value="draft" @selected(old('status') === 'draft')>Draft</option>
                    <option value="completed" @selected(old('status') === 'completed')>Completed</option>
                    <option value="cancelled" @selected(old('status') === 'cancelled')>Cancelled</option>
                </select>
            </div>
        </div>

        {{-- PENERIMA SI (CARRIER / SHIPPING LINE) --}}
        <div class="form-section-heading">
            <h2>Penerima SI (Shipping Line / Carrier / Pelayaran)</h2>
            <p>Pihak pelayaran atau agen yang menerima instruksi pengapalan ini.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="to_carrier">To (Shipping Line / Pelayaran) <span class="required">*</span></label>
                <input id="to_carrier" name="to_carrier" list="carrier_list" maxlength="160" value="{{ old('to_carrier') }}" required placeholder="contoh: ONE / Maersk / CMA CGM">
                <datalist id="carrier_list">
                    @foreach($carriers as $c)
                        <option value="{{ $c->name }}">{{ $c->code ? '['.$c->code.'] ' : '' }}{{ $c->name }}</option>
                    @endforeach
                </datalist>
            </div>

            <div class="field">
                <label for="carrier_attn">Attn (Nama PIC Pelayaran)</label>
                <input id="carrier_attn" name="carrier_attn" maxlength="120" value="{{ old('carrier_attn') }}" placeholder="contoh: Export Booking Dept / Bpk. David">
            </div>

            <div class="field">
                <label for="carrier_contact">Telp / Fax</label>
                <input id="carrier_contact" name="carrier_contact" maxlength="120" value="{{ old('carrier_contact') }}" placeholder="Nomor Telepon atau Fax Pelayaran">
            </div>

            <div class="field">
                <label for="customer_id">Customer Pemilik Muatan</label>
                <select id="customer_id" name="customer_id">
                    <option value="">Pilih Customer (Opsional)</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(old('customer_id', $selectedJob?->customer_id) == $c->id)>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- PARA PIHAK B/L (SHIPPER, CONSIGNEE, NOTIFY PARTY) --}}
        <div class="form-section-heading">
            <h2>Pihak Kargo pada B/L (Shipper, Consignee, Notify Party)</h2>
            <p>Data identitas yang akan tercetak pada Bill of Lading (B/L).</p>
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

            <div class="field">
                <label for="shipper_address">Alamat Shipper</label>
                <textarea id="shipper_address" name="shipper_address" rows="3" placeholder="Alamat lengkap Shipper">{{ old('shipper_address', $selectedJob?->shipper_address) }}</textarea>
            </div>

            <div class="field">
                <label for="consignee_address">Alamat Consignee</label>
                <textarea id="consignee_address" name="consignee_address" rows="3" placeholder="Alamat lengkap Consignee">{{ old('consignee_address', $selectedJob?->consignee_address) }}</textarea>
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
            <p>Kapal utama, kapal penghubung, jadwal, serta pelabuhan muat dan bongkar.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="vessel_voyage">Vessel Name & Voyage</label>
                <input id="vessel_voyage" name="vessel_voyage" maxlength="120" value="{{ old('vessel_voyage', $selectedJob?->vessel_voyage) }}" placeholder="contoh: MV. WAN HAI 312 V.E215">
            </div>

            <div class="field">
                <label for="connecting_vessel">Connecting Vessel (Kapal Penghubung / Feeder)</label>
                <input id="connecting_vessel" name="connecting_vessel" maxlength="120" value="{{ old('connecting_vessel') }}" placeholder="Opsional jika ada transhipment">
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
                <label for="shipment_term">Shipment Term <span class="required">*</span></label>
                <select id="shipment_term" name="shipment_term" required>
                    <option value="PREPAID" @selected(old('shipment_term', 'PREPAID') === 'PREPAID')>PREPAID (Ongkir Dibayar di Pelabuhan Asal)</option>
                    <option value="COLLECT" @selected(old('shipment_term') === 'COLLECT')>COLLECT (Ongkir Dibayar di Pelabuhan Tujuan)</option>
                </select>
            </div>

            <div class="field">
                <label for="pol">LOADING (Port of Loading) <span class="required">*</span></label>
                <input id="pol" name="pol" list="port_list" maxlength="120" value="{{ old('pol', $selectedJob?->pol ?? $selectedJob?->origin) }}" required placeholder="Pelabuhan Muat">
            </div>

            <div class="field">
                <label for="pod">DISCHARGE (Port of Discharge) <span class="required">*</span></label>
                <input id="pod" name="pod" list="port_list" maxlength="120" value="{{ old('pod', $selectedJob?->pod ?? $selectedJob?->destination) }}" required placeholder="Pelabuhan Bongkar">
            </div>

            <datalist id="port_list">
                @foreach($ports as $p)
                    <option value="{{ $p->name }}">{{ $p->code ? '['.$p->code.'] ' : '' }}{{ $p->name }}</option>
                @endforeach
            </datalist>
        </div>

        {{-- TABEL KARGO (FORMAT 3 KOLOM B/L) --}}
        <div class="form-section-heading">
            <h2>Rincian Kargo pada B/L (Marks & Numbers, Description, GW/MEAS)</h2>
            <p>Data barang yang akan dicetak pada badan Bill of Lading.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="marks_numbers">Marks and Number</label>
                <textarea id="marks_numbers" name="marks_numbers" rows="4" placeholder="Tanda kemasan pada peti/karton, misal:&#10;PT. ABC LOGISTICS&#10;JAKARTA - INDONESIA&#10;C/NO. 1-100">{{ old('marks_numbers') }}</textarea>
            </div>

            <div class="field">
                <label for="cargo_description">Description of Goods <span class="required">*</span></label>
                <textarea id="cargo_description" name="cargo_description" rows="4" required placeholder="Uraian barang, jenis paket/kontainer, misal:&#10;1X20'GP CONTAINER S.T.C:&#10;150 PACKAGES OF ELECTRONIC PARTS">{{ old('cargo_description', $selectedJob?->cargo_description) }}</textarea>
            </div>

            <div class="field">
                <label for="gross_weight">G.W (Gross Weight - KGS)</label>
                <input id="gross_weight" name="gross_weight" inputmode="decimal" value="{{ old('gross_weight', $selectedJob?->gross_weight) }}" placeholder="contoh: 14500.00">
            </div>

            <div class="field">
                <label for="net_weight">N.W (Net Weight - KGS)</label>
                <input id="net_weight" name="net_weight" inputmode="decimal" value="{{ old('net_weight') }}" placeholder="contoh: 13800.00">
            </div>

            <div class="field">
                <label for="measurement">MEAS (Measurement / CBM)</label>
                <input id="measurement" name="measurement" inputmode="decimal" value="{{ old('measurement', $selectedJob?->volume) }}" placeholder="contoh: 28.50">
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
            <a class="button button-secondary" href="{{ route('shipping-instructions.index') }}">Batal</a>
            <button class="button button-primary">Simpan Shipping Instruction</button>
        </div>
    </form>
</section>

<script>
document.getElementById('job_id')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (!opt || !opt.value) return;

    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el && val) el.value = val;
    };

    if (opt.dataset.customerId) setVal('customer_id', opt.dataset.customerId);
    if (opt.dataset.shipper) setVal('shipper_name', opt.dataset.shipper);
    if (opt.dataset.shipperAddress) setVal('shipper_address', opt.dataset.shipperAddress);
    if (opt.dataset.consignee) setVal('consignee_name', opt.dataset.consignee);
    if (opt.dataset.consigneeAddress) setVal('consignee_address', opt.dataset.consigneeAddress);
    if (opt.dataset.vessel) setVal('vessel_voyage', opt.dataset.vessel);
    if (opt.dataset.pol) setVal('pol', opt.dataset.pol);
    if (opt.dataset.pod) setVal('pod', opt.dataset.pod);
    if (opt.dataset.etd) setVal('etd', opt.dataset.etd);
    if (opt.dataset.eta) setVal('eta', opt.dataset.eta);
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
