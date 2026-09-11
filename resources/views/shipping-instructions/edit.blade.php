@extends('layouts.app')
@section('title', 'Edit Shipping Instruction ' . $si->number)
@section('content')

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL</p>
        <h1>Edit Shipping Instruction</h1>
        <p>{{ $si->number }} · Carrier: {{ $si->to_carrier }}</p>
    </div>
    <a class="text-link" href="{{ route('shipping-instructions.show', $si) }}">← Kembali ke detail</a>
</div>

<section class="panel form-panel">
    <form class="data-form" method="POST" action="{{ route('shipping-instructions.update', $si) }}" id="siForm">
        @csrf
        @method('PUT')

        {{-- HUBUNGAN JOB ORDER & DATA DOKUMEN --}}
        <div class="form-section-heading">
            <h2>Informasi Dokumen & Job Order</h2>
            <p>Ubah nomor, tanggal, status, dan asosiasi Job Order.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="job_id">Terkait Job Order</label>
                <select id="job_id" name="job_id">
                    <option value="">Pilih Job Order (Opsional)</option>
                    @foreach($jobs as $j)
                        <option value="{{ $j->id }}" @selected(old('job_id', $si->job_id) == $j->id)>
                            {{ $j->number }} — {{ $j->customer?->name }} ({{ Str::limit($j->subject, 30) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="number">Nomor Shipping Instruction <span class="required">*</span></label>
                <input id="number" name="number" maxlength="60" value="{{ old('number', $si->number) }}" required>
            </div>

            <div class="field">
                <label for="si_date">Tanggal SI <span class="required">*</span></label>
                <input id="si_date" name="si_date" type="date" value="{{ old('si_date', $si->si_date->format('Y-m-d')) }}" required>
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="submitted" @selected(old('status', $si->status) === 'submitted')>Submitted</option>
                    <option value="draft" @selected(old('status', $si->status) === 'draft')>Draft</option>
                    <option value="completed" @selected(old('status', $si->status) === 'completed')>Completed</option>
                    <option value="cancelled" @selected(old('status', $si->status) === 'cancelled')>Cancelled</option>
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
                <input id="to_carrier" name="to_carrier" list="carrier_list" maxlength="160" value="{{ old('to_carrier', $si->to_carrier) }}" required placeholder="contoh: ONE / Maersk / CMA CGM">
                <datalist id="carrier_list">
                    @foreach($carriers as $c)
                        <option value="{{ $c->name }}">{{ $c->code ? '['.$c->code.'] ' : '' }}{{ $c->name }}</option>
                    @endforeach
                </datalist>
            </div>

            <div class="field">
                <label for="carrier_attn">Attn (Nama PIC Pelayaran)</label>
                <input id="carrier_attn" name="carrier_attn" maxlength="120" value="{{ old('carrier_attn', $si->carrier_attn) }}" placeholder="contoh: Export Booking Dept / Bpk. David">
            </div>

            <div class="field">
                <label for="carrier_contact">Telp / Fax</label>
                <input id="carrier_contact" name="carrier_contact" maxlength="120" value="{{ old('carrier_contact', $si->carrier_contact) }}" placeholder="Nomor Telepon atau Fax Pelayaran">
            </div>

            <div class="field">
                <label for="customer_id">Customer Pemilik Muatan</label>
                <select id="customer_id" name="customer_id">
                    <option value="">Pilih Customer (Opsional)</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(old('customer_id', $si->customer_id) == $c->id)>
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
                <input id="shipper_name" name="shipper_name" maxlength="160" value="{{ old('shipper_name', $si->shipper_name) }}" required placeholder="Nama Perusahaan Shipper">
            </div>

            <div class="field">
                <label for="consignee_name">Consignee (Penerima) <span class="required">*</span></label>
                <input id="consignee_name" name="consignee_name" maxlength="160" value="{{ old('consignee_name', $si->consignee_name) }}" required placeholder="Nama Perusahaan Consignee / TO ORDER">
            </div>

            <div class="field">
                <label for="shipper_address">Alamat Shipper</label>
                <textarea id="shipper_address" name="shipper_address" rows="3" placeholder="Alamat lengkap Shipper">{{ old('shipper_address', $si->shipper_address) }}</textarea>
            </div>

            <div class="field">
                <label for="consignee_address">Alamat Consignee</label>
                <textarea id="consignee_address" name="consignee_address" rows="3" placeholder="Alamat lengkap Consignee">{{ old('consignee_address', $si->consignee_address) }}</textarea>
            </div>

            <div class="field span-2">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <label for="notify_party" style="margin:0;">Notify Party</label>
                    <button type="button" class="button button-secondary" style="padding:2px 10px;font-size:12px;" onclick="document.getElementById('notify_party').value = 'SAME AS CONSIGNEE';">
                        Set "SAME AS CONSIGNEE"
                    </button>
                </div>
                <textarea id="notify_party" name="notify_party" rows="2" placeholder="Nama & alamat pihak yang dinotifikasi atau 'SAME AS CONSIGNEE'">{{ old('notify_party', $si->notify_party) }}</textarea>
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
                <input id="vessel_voyage" name="vessel_voyage" maxlength="120" value="{{ old('vessel_voyage', $si->vessel_voyage) }}" placeholder="contoh: MV. WAN HAI 312 V.E215">
            </div>

            <div class="field">
                <label for="connecting_vessel">Connecting Vessel (Kapal Penghubung / Feeder)</label>
                <input id="connecting_vessel" name="connecting_vessel" maxlength="120" value="{{ old('connecting_vessel', $si->connecting_vessel) }}" placeholder="Opsional jika ada transhipment">
            </div>

            <div class="field">
                <label for="etd">ETD (Keberangkatan)</label>
                <input id="etd" name="etd" type="date" value="{{ old('etd', $si->etd?->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="eta">ETA (Kedatangan)</label>
                <input id="eta" name="eta" type="date" value="{{ old('eta', $si->eta?->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="shipment_term">Shipment Term <span class="required">*</span></label>
                <select id="shipment_term" name="shipment_term" required>
                    <option value="PREPAID" @selected(old('shipment_term', $si->shipment_term) === 'PREPAID')>PREPAID (Ongkir Dibayar di Pelabuhan Asal)</option>
                    <option value="COLLECT" @selected(old('shipment_term', $si->shipment_term) === 'COLLECT')>COLLECT (Ongkir Dibayar di Pelabuhan Tujuan)</option>
                </select>
            </div>

            <div class="field">
                <label for="pol">LOADING (Port of Loading) <span class="required">*</span></label>
                <input id="pol" name="pol" list="port_list" maxlength="120" value="{{ old('pol', $si->pol) }}" required placeholder="Pelabuhan Muat">
            </div>

            <div class="field">
                <label for="pod">DISCHARGE (Port of Discharge) <span class="required">*</span></label>
                <input id="pod" name="pod" list="port_list" maxlength="120" value="{{ old('pod', $si->pod) }}" required placeholder="Pelabuhan Bongkar">
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
                <textarea id="marks_numbers" name="marks_numbers" rows="4" placeholder="Tanda kemasan pada peti/karton">{{ old('marks_numbers', $si->marks_numbers) }}</textarea>
            </div>

            <div class="field">
                <label for="cargo_description">Description of Goods <span class="required">*</span></label>
                <textarea id="cargo_description" name="cargo_description" rows="4" required placeholder="Uraian barang, jenis paket/kontainer">{{ old('cargo_description', $si->cargo_description) }}</textarea>
            </div>

            <div class="field">
                <label for="gross_weight">G.W (Gross Weight - KGS)</label>
                <input id="gross_weight" name="gross_weight" inputmode="decimal" value="{{ old('gross_weight', $si->gross_weight) }}" placeholder="contoh: 14500.00">
            </div>

            <div class="field">
                <label for="net_weight">N.W (Net Weight - KGS)</label>
                <input id="net_weight" name="net_weight" inputmode="decimal" value="{{ old('net_weight', $si->net_weight) }}" placeholder="contoh: 13800.00">
            </div>

            <div class="field">
                <label for="measurement">MEAS (Measurement / CBM)</label>
                <input id="measurement" name="measurement" inputmode="decimal" value="{{ old('measurement', $si->measurement) }}" placeholder="contoh: 28.50">
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
                <textarea id="remarks" name="remarks" rows="3">{{ old('remarks', $si->remarks) }}</textarea>
            </div>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ route('shipping-instructions.show', $si) }}">Batal</a>
            <button class="button button-primary">Simpan Perubahan</button>
        </div>
    </form>
</section>

@endsection
