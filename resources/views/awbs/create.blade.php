@extends('layouts.app')
@section('title', 'Buat Air Waybill (AWB)')
@section('content')

@php
    $backUrl = $selectedJob
        ? route('jobs.show', $selectedJob) . '#tab-awb'
        : route('awbs.index');
@endphp

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL — EXPORT AIR</p>
        <h1>Buat Air Waybill (AWB)</h1>
        <p>Dokumen pengapalan udara untuk pengiriman export via maskapai penerbangan.</p>
    </div>
    <a class="button button-secondary" href="{{ $backUrl }}">← Kembali</a>
</div>

<section class="panel form-panel">
    <form class="data-form" method="POST" action="{{ route('awbs.store') }}" id="awbForm">
        @csrf

        {{-- INFORMASI DASAR --}}
        <div class="form-section-heading">
            <h2>Informasi Dasar & Job Order</h2>
            <p>Pilih Job Order untuk menarik data pengapalan secara otomatis.</p>
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
                            data-shipper="{{ $j->shipper_name ?? $j->customer?->name }}"
                            data-consignee="{{ $j->consignee_name }}"
                            data-vessel="{{ $j->vessel_voyage }}"
                            data-pol="{{ $j->pol ?? $j->origin }}"
                            data-pod="{{ $j->pod ?? $j->destination }}"
                            data-etd="{{ $j->etd?->format('Y-m-d') }}"
                            data-eta="{{ $j->eta?->format('Y-m-d') }}"
                            data-commodity="{{ $j->cargo_description }}"
                            data-gross-weight="{{ $j->gross_weight }}"
                        >
                            {{ $j->number }} — {{ $j->customer?->name }} ({{ Str::limit($j->subject, 30) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="number">Nomor AWB <span class="required">*</span></label>
                <input id="number" name="number" maxlength="60" value="{{ old('number', $defaultNumber) }}" required>
            </div>

            <div class="field">
                <label for="awb_date">Tanggal AWB <span class="required">*</span></label>
                <input id="awb_date" name="awb_date" type="date" value="{{ old('awb_date', date('Y-m-d')) }}" required>
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="draft" @selected(old('status', 'draft') === 'draft')>Draft</option>
                    <option value="issued" @selected(old('status') === 'issued')>Issued</option>
                    <option value="completed" @selected(old('status') === 'completed')>Completed</option>
                    <option value="cancelled" @selected(old('status') === 'cancelled')>Cancelled</option>
                </select>
            </div>

            <div class="field">
                <label for="customer_id">Customer (Shipper)</label>
                <select id="customer_id" name="customer_id">
                    <option value="">Pilih Customer (Opsional)</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(old('customer_id', $selectedJob?->customer_id) == $c->id)
                            data-name="{{ $c->name }}">
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="freight_term">Freight Term <span class="required">*</span></label>
                <select id="freight_term" name="freight_term" required>
                    <option value="PREPAID" @selected(old('freight_term', 'PREPAID') === 'PREPAID')>PREPAID</option>
                    <option value="COLLECT" @selected(old('freight_term') === 'COLLECT')>COLLECT</option>
                </select>
            </div>
        </div>

        {{-- INFORMASI MASKAPAI & PENERBANGAN --}}
        <div class="form-section-heading">
            <h2>Maskapai & Rute Penerbangan</h2>
            <p>Nama maskapai, nomor penerbangan, dan rute penerbangan.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="airline">Airline (Maskapai) <span class="required">*</span></label>
                <input id="airline" name="airline" list="airline_list" maxlength="160"
                    value="{{ old('airline') }}" placeholder="contoh: Garuda Indonesia / Singapore Airlines">
                <datalist id="airline_list">
                    @foreach($airlines as $a)
                        <option value="{{ $a->name }}">{{ $a->name }}</option>
                    @endforeach
                </datalist>
            </div>

            <div class="field">
                <label for="airline_code">Kode IATA Maskapai</label>
                <input id="airline_code" name="airline_code" maxlength="20"
                    value="{{ old('airline_code') }}" placeholder="contoh: GA / SQ / EK">
            </div>

            <div class="field">
                <label for="flight_number">No. Penerbangan (Flight Number)</label>
                <input id="flight_number" name="flight_number" maxlength="60"
                    value="{{ old('flight_number') }}" placeholder="contoh: GA-724 / SQ-957">
            </div>

            <div class="field">
                <label for="routing">Routing / Via</label>
                <input id="routing" name="routing" maxlength="255"
                    value="{{ old('routing') }}" placeholder="contoh: JKT – SIN – PVG">
            </div>

            <div class="field">
                <label for="airport_of_departure">Airport of Departure (Bandara Keberangkatan)</label>
                <input id="airport_of_departure" name="airport_of_departure" maxlength="120"
                    value="{{ old('airport_of_departure', $selectedJob ? ($selectedJob->pol ?? $selectedJob->origin) : '') }}"
                    placeholder="contoh: JAKARTA / CGK">
            </div>

            <div class="field">
                <label for="airport_of_destination">Airport of Destination (Bandara Tujuan)</label>
                <input id="airport_of_destination" name="airport_of_destination" maxlength="120"
                    value="{{ old('airport_of_destination', $selectedJob ? ($selectedJob->pod ?? $selectedJob->destination) : '') }}"
                    placeholder="contoh: SHANGHAI / PVG">
            </div>

            <div class="field">
                <label for="etd">ETD (Tanggal Keberangkatan)</label>
                <input id="etd" name="etd" type="date" value="{{ old('etd', $selectedJob?->etd?->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="eta">ETA (Tanggal Kedatangan)</label>
                <input id="eta" name="eta" type="date" value="{{ old('eta', $selectedJob?->eta?->format('Y-m-d')) }}">
            </div>
        </div>

        {{-- PIHAK PENGAPALAN --}}
        <div class="form-section-heading">
            <h2>Pihak Pengapalan (Shipper & Consignee)</h2>
            <p>Identitas pengirim dan penerima yang tercetak pada AWB.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="shipper_name">Shipper (Pengirim)</label>
                <input id="shipper_name" name="shipper_name" maxlength="160"
                    value="{{ old('shipper_name', $selectedJob?->shipper_name ?? $selectedJob?->customer?->name) }}"
                    placeholder="Nama Perusahaan Shipper">
            </div>

            <div class="field">
                <label for="consignee_name">Consignee (Penerima)</label>
                <input id="consignee_name" name="consignee_name" maxlength="160"
                    value="{{ old('consignee_name', $selectedJob?->consignee_name) }}"
                    placeholder="Nama Perusahaan Consignee">
            </div>

            <div class="field span-2">
                <label for="notify_party">Notify Party</label>
                <input id="notify_party" name="notify_party" maxlength="255"
                    value="{{ old('notify_party') }}"
                    placeholder="Pihak yang dinotifikasi (opsional)">
            </div>

            <div class="field">
                <label for="shipper_ref">Shipper Reference (PO / Booking Ref)</label>
                <input id="shipper_ref" name="shipper_ref" maxlength="100"
                    value="{{ old('shipper_ref') }}"
                    placeholder="Nomor referensi shipper">
            </div>
        </div>

        {{-- DETAIL KARGO --}}
        <div class="form-section-heading">
            <h2>Detail Kargo</h2>
            <p>Deskripsi barang, jumlah koli, berat, dan kubikasi muatan.</p>
        </div>

        <div class="form-grid">
            <div class="field span-2">
                <label for="commodity">Commodity / Description of Goods</label>
                <input id="commodity" name="commodity" maxlength="255"
                    value="{{ old('commodity', $selectedJob?->cargo_description) }}"
                    placeholder="Deskripsi barang / komoditas">
            </div>

            <div class="field">
                <label for="pieces">Jumlah Koli (Pieces)</label>
                <input id="pieces" name="pieces" type="number" min="0"
                    value="{{ old('pieces') }}" placeholder="contoh: 10">
            </div>

            <div class="field">
                <label for="gross_weight">Gross Weight (KGS)</label>
                <input id="gross_weight" name="gross_weight" inputmode="decimal"
                    value="{{ old('gross_weight', $selectedJob?->gross_weight) }}"
                    placeholder="contoh: 250.00">
            </div>

            <div class="field">
                <label for="chargeable_weight">Chargeable Weight (KGS)</label>
                <input id="chargeable_weight" name="chargeable_weight" inputmode="decimal"
                    value="{{ old('chargeable_weight') }}"
                    placeholder="contoh: 300.00">
            </div>

            <div class="field">
                <label for="volume">Volume (CBM)</label>
                <input id="volume" name="volume" inputmode="decimal"
                    value="{{ old('volume', $selectedJob?->volume) }}"
                    placeholder="contoh: 2.50">
            </div>
        </div>

        {{-- REMARKS --}}
        <div class="form-section-heading">
            <h2>Remarks / Catatan</h2>
            <p>Catatan atau instruksi khusus untuk maskapai.</p>
        </div>

        <div class="form-grid">
            <div class="field span-2">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" name="remarks" rows="3">{{ old('remarks') }}</textarea>
            </div>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ $backUrl }}">Batal</a>
            <button class="button button-primary">Simpan AWB</button>
        </div>
    </form>
</section>

<script>
document.getElementById('job_id')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (!opt || !opt.value) return;
    const setVal = (id, val) => { const el = document.getElementById(id); if (el && val) el.value = val; };
    if (opt.dataset.customerId) setVal('customer_id', opt.dataset.customerId);
    if (opt.dataset.shipper) setVal('shipper_name', opt.dataset.shipper);
    if (opt.dataset.consignee) setVal('consignee_name', opt.dataset.consignee);
    if (opt.dataset.pol) setVal('airport_of_departure', opt.dataset.pol);
    if (opt.dataset.pod) setVal('airport_of_destination', opt.dataset.pod);
    if (opt.dataset.etd) setVal('etd', opt.dataset.etd);
    if (opt.dataset.eta) setVal('eta', opt.dataset.eta);
    if (opt.dataset.commodity) setVal('commodity', opt.dataset.commodity);
    if (opt.dataset.grossWeight) setVal('gross_weight', opt.dataset.grossWeight);
});

document.getElementById('customer_id')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (!opt || !opt.value) return;
    const setVal = (id, val) => { const el = document.getElementById(id); if (el && val) el.value = val; };
    if (opt.dataset.name) setVal('shipper_name', opt.dataset.name);
});
</script>

@endsection
