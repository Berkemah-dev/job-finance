@extends('layouts.app')
@section('title', 'Buat Booking Confirmation')
@section('content')

@php
    $defaultNotes = "This booking confirmation validates your space booking with us according to all details stated in the Shipping Instruction, we reserve right to shut out cargo for any discrepancy that disables us from doing groupage consol.\n" .
        "This booking confirmation does not function as a guarantee of departure, we reserve right to shut out cargo in case of failures in export procedures and/or handicaps in export documentations.\n" .
        "This booking confirmation is automatically invalid upon your booking cancellation, kindly inform us immediately for any cancellation.\n" .
        "This booking confirmation is automatically invalid upon any cases of cargo/documentations problems, including failure of export declaration.\n" .
        "This booking confirmation is automatically invalid for following undeclared cargo: LONG LENGTH/OVERWEIGHT/FOODS & BEVERAGES, DUTIABLE/DANGEROUS GOODS/LAW FORBIDDEN/LIVE ANIMALS.\n" .
        "This booking confirmation is not valid for any claim.";
@endphp

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL</p>
        <h1>Buat Booking Confirmation</h1>
        <p>Terbitkan konfirmasi alokasi ruang kapal/pesawat untuk shipper dan customer.</p>
    </div>
    <a class="text-link" href="{{ route('booking-confirmations.index') }}">← Kembali ke daftar</a>
</div>

<section class="panel form-panel">
    <form class="data-form" method="POST" action="{{ route('booking-confirmations.store') }}" id="bcForm">
        @csrf

        {{-- INFORMASI UTAMA & JOB --}}
        <div class="form-section-heading">
            <h2>Informasi Dasar & Hubungan Job Order</h2>
            <p>Pilih Job Order untuk menarik data operasional pengapalan secara otomatis.</p>
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
                            data-vessel="{{ $j->vessel_voyage }}"
                            data-pol="{{ $j->pol ?? $j->origin }}"
                            data-pod="{{ $j->pod ?? $j->destination }}"
                            data-etd="{{ $j->etd?->format('Y-m-d') }}"
                            data-eta="{{ $j->eta?->format('Y-m-d') }}"
                            data-quantity="{{ $j->package_count ? $j->package_count . ' Box' : ($j->container_type ? '1x ' . strtoupper($j->container_type) : '') }}"
                            data-commodity="{{ $j->cargo_description }}"
                            data-gross-weight="{{ $j->gross_weight }}"
                            data-volume="{{ $j->volume }}"
                            data-booking-ref="{{ $j->booking_reference }}"
                        >
                            {{ $j->number }} — {{ $j->customer?->name }} ({{ Str::limit($j->subject, 30) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="number">Nomor Booking Confirmation <span class="required">*</span></label>
                <input id="number" name="number" maxlength="60" value="{{ old('number', $defaultNumber) }}" required>
            </div>

            <div class="field">
                <label for="booking_date">Tanggal Booking <span class="required">*</span></label>
                <input id="booking_date" name="booking_date" type="date" value="{{ old('booking_date', date('Y-m-d')) }}" required>
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="confirmed" @selected(old('status') === 'confirmed')>Confirmed</option>
                    <option value="draft" @selected(old('status') === 'draft')>Draft</option>
                    <option value="cancelled" @selected(old('status') === 'cancelled')>Cancelled</option>
                </select>
            </div>
        </div>

        {{-- PIHAK TERKAIT --}}
        <div class="form-section-heading">
            <h2>Penerima & Pihak Terkait</h2>
            <p>Pihak yang menerima konfirmasi booking dan rincian referensi.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="customer_id">Customer (To:) <span class="required">*</span></label>
                <select id="customer_id" name="customer_id" required>
                    <option value="">Pilih Customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(old('customer_id', $selectedJob?->customer_id) == $c->id)>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="contact_person">Contact Person (PIC)</label>
                <input id="contact_person" name="contact_person" maxlength="120" value="{{ old('contact_person') }}" placeholder="Nama PIC Customer">
            </div>

            <div class="field">
                <label for="customer_ref">Customer Ref</label>
                <input id="customer_ref" name="customer_ref" maxlength="100" value="{{ old('customer_ref') }}" placeholder="Nomor PO / Ref dari Customer">
            </div>

            <div class="field">
                <label for="shipper_name">Shipper</label>
                <input id="shipper_name" name="shipper_name" maxlength="160" value="{{ old('shipper_name', $selectedJob?->shipper_name) }}" placeholder="Nama Pengirim Muatan">
            </div>
        </div>

        {{-- DETAIL PENGAPALAN & CARRIER --}}
        <div class="form-section-heading">
            <h2>Rincian Pengapalan & Carrier</h2>
            <p>Informasi pelayaran, maskapai, dan rute perjalanan.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="carrier_name">Carrier Booking (Shipping Lines / Maskapai)</label>
                <input id="carrier_name" name="carrier_name" list="carrier_list" maxlength="160" value="{{ old('carrier_name') }}" placeholder="Pilih atau ketik nama shipping line">
                <datalist id="carrier_list">
                    @foreach($carriers as $carrier)
                        <option value="{{ $carrier->name }}">{{ $carrier->code ? '['.$carrier->code.'] ' : '' }}{{ $carrier->name }}</option>
                    @endforeach
                </datalist>
            </div>

            <div class="field">
                <label for="carrier_booking_no">No. Booking Carrier</label>
                <input id="carrier_booking_no" name="carrier_booking_no" maxlength="100" value="{{ old('carrier_booking_no', $selectedJob?->booking_reference) }}" placeholder="No. Booking dari Pelayaran">
            </div>

            <div class="field">
                <label for="vessel_voyage">Vessel & Voyage</label>
                <input id="vessel_voyage" name="vessel_voyage" maxlength="120" value="{{ old('vessel_voyage', $selectedJob?->vessel_voyage) }}" placeholder="Nama Kapal & Voyage">
            </div>

            <div class="field">
                <label for="service_term">Services (Term Layanan) <span class="required">*</span></label>
                <select id="service_term" name="service_term" required>
                    <option value="CY/CY" @selected(old('service_term', 'CY/CY') === 'CY/CY')>CY/CY (Container Yard to Container Yard)</option>
                    <option value="CFS/CFS" @selected(old('service_term') === 'CFS/CFS')>CFS/CFS (Container Freight Station to Container Freight Station)</option>
                    <option value="CY/CFS" @selected(old('service_term') === 'CY/CFS')>CY/CFS</option>
                    <option value="CFS/CY" @selected(old('service_term') === 'CFS/CY')>CFS/CY</option>
                    <option value="SD/SD" @selected(old('service_term') === 'SD/SD')>SD/SD (Site Delivery / Door to Door)</option>
                </select>
            </div>

            <div class="field">
                <label for="pol">Port of Loading (POL)</label>
                <input id="pol" name="pol" list="port_list" maxlength="120" value="{{ old('pol', $selectedJob?->pol ?? $selectedJob?->origin) }}" placeholder="Pelabuhan Muat">
            </div>

            <div class="field">
                <label for="pod">Port of Discharge (POD)</label>
                <input id="pod" name="pod" list="port_list" maxlength="120" value="{{ old('pod', $selectedJob?->pod ?? $selectedJob?->destination) }}" placeholder="Pelabuhan Bongkar">
            </div>

            <datalist id="port_list">
                @foreach($ports as $p)
                    <option value="{{ $p->name }}">{{ $p->code ? '['.$p->code.'] ' : '' }}{{ $p->name }}</option>
                @endforeach
            </datalist>

            <div class="field">
                <label for="etd">ETD (Keberangkatan)</label>
                <input id="etd" name="etd" type="date" value="{{ old('etd', $selectedJob?->etd?->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="eta">ETA (Kedatangan)</label>
                <input id="eta" name="eta" type="date" value="{{ old('eta', $selectedJob?->eta?->format('Y-m-d')) }}">
            </div>
        </div>

        {{-- RINCIAN MUATAN (CARGO DETAILS) --}}
        <div class="form-section-heading">
            <h2>Rincian Muatan (Cargo Details)</h2>
            <p>Kuantitas, deskripsi, berat, dan kubikasi barang yang disepakati.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="quantity">Quantity (Kuantitas)</label>
                <input id="quantity" name="quantity" maxlength="100" value="{{ old('quantity', $selectedJob?->package_count ? $selectedJob?->package_count . ' Box' : ($selectedJob?->container_type ? '1x ' . strtoupper($selectedJob?->container_type) : '')) }}" placeholder="contoh: 1x 20GP atau 150 Box">
            </div>

            <div class="field">
                <label for="gross_weight">Gross Weight (KGS)</label>
                <input id="gross_weight" name="gross_weight" inputmode="decimal" value="{{ old('gross_weight', $selectedJob?->gross_weight) }}" placeholder="contoh: 12500.00">
            </div>

            <div class="field">
                <label for="volume">Volume (CBM / M3)</label>
                <input id="volume" name="volume" inputmode="decimal" value="{{ old('volume', $selectedJob?->volume) }}" placeholder="contoh: 28.50">
            </div>

            <div class="field span-2">
                <label for="cargo_description">Description (Deskripsi Barang / Commodity)</label>
                <textarea id="cargo_description" name="cargo_description" rows="2" placeholder="Uraian komoditas muatan">{{ old('cargo_description', $selectedJob?->cargo_description) }}</textarea>
            </div>
        </div>

        {{-- PENYERAHAN & CUT-OFF TIME --}}
        <div class="form-section-heading">
            <h2>Penyerahan Muatan & Jadwal Cut-Off</h2>
            <p>Lokasi terminal/depo dan tenggat waktu pengumpulan dokumen serta kontainer.</p>
        </div>

        <div class="form-grid">
            <div class="field span-2">
                <label for="delivery_cargo_to">Delivery Cargo To (Lokasi Penyerahan / Terminal / Depo)</label>
                <input id="delivery_cargo_to" name="delivery_cargo_to" maxlength="255" value="{{ old('delivery_cargo_to') }}" placeholder="contoh: JICT Tanjung Priok / Depo KBN Cakung">
            </div>

            <div class="field">
                <label for="doc_cutoff_at">Doc Cut-Off (Dokumen)</label>
                <input id="doc_cutoff_at" name="doc_cutoff_at" type="datetime-local" value="{{ old('doc_cutoff_at') }}">
            </div>

            <div class="field">
                <label for="cy_cutoff_at">CY Cut-Off (Closing Container)</label>
                <input id="cy_cutoff_at" name="cy_cutoff_at" type="datetime-local" value="{{ old('cy_cutoff_at') }}">
            </div>

            <div class="field">
                <label for="delivery_cutoff_at">Delivery Cut-Off (Muatan Fisik)</label>
                <input id="delivery_cutoff_at" name="delivery_cutoff_at" type="datetime-local" value="{{ old('delivery_cutoff_at') }}">
            </div>
        </div>

        {{-- IMPORTANT NOTES --}}
        <div class="form-section-heading">
            <h2>Important Note & Ketentuan</h2>
            <p>Klausul standar perusahaan untuk validitas space booking pengapalan.</p>
        </div>

        <div class="form-grid">
            <div class="field span-2">
                <label for="notes">Important Notes</label>
                <textarea id="notes" name="notes" rows="6">{{ old('notes', $defaultNotes) }}</textarea>
            </div>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ route('booking-confirmations.index') }}">Batal</a>
            <button class="button button-primary">Simpan Booking Confirmation</button>
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
    if (opt.dataset.vessel) setVal('vessel_voyage', opt.dataset.vessel);
    if (opt.dataset.pol) setVal('pol', opt.dataset.pol);
    if (opt.dataset.pod) setVal('pod', opt.dataset.pod);
    if (opt.dataset.etd) setVal('etd', opt.dataset.etd);
    if (opt.dataset.eta) setVal('eta', opt.dataset.eta);
    if (opt.dataset.quantity) setVal('quantity', opt.dataset.quantity);
    if (opt.dataset.commodity) setVal('cargo_description', opt.dataset.commodity);
    if (opt.dataset.grossWeight) setVal('gross_weight', opt.dataset.grossWeight);
    if (opt.dataset.volume) setVal('volume', opt.dataset.volume);
    if (opt.dataset.bookingRef) setVal('carrier_booking_no', opt.dataset.bookingRef);
});
</script>

@endsection
