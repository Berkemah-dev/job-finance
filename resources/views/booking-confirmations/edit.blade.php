@extends('layouts.app')
@section('title', 'Edit Booking Confirmation ' . $bc->number)
@section('content')

<x-menu-banner tag="CUSTOMER SERVICE / OPERASIONAL" title="Edit Booking Confirmation" description="{{ $bc->number }} · Customer: {{ $bc->customer?->name }}" action-url="{{ route('booking-confirmations.show', $bc) }}" action-label="← Kembali ke detail" action-icon="arrow" icon="file" art-title="Alokasi space," art-subtitle="terkonfirmasi." />

<section class="panel form-panel">
    <form class="data-form" method="POST" action="{{ route('booking-confirmations.update', $bc) }}" id="bcForm">
        @csrf
        @method('PUT')

        {{-- INFORMASI UTAMA & JOB --}}
        <div class="form-section-heading">
            <h2>Informasi Dasar & Hubungan Job Order</h2>
            <p>Ubah informasi dasar dan status konfirmasi booking.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="job_id">Terkait Job Order</label>
                <select id="job_id" name="job_id">
                    <option value="">Pilih Job Order (Opsional)</option>
                    @foreach($jobs as $j)
                        <option value="{{ $j->id }}" @selected(old('job_id', $bc->job_id) == $j->id)>
                            {{ $j->number }} — {{ $j->customer?->name }} ({{ Str::limit($j->subject, 30) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="number">Nomor Booking Confirmation <span class="required">*</span></label>
                <input id="number" name="number" maxlength="60" value="{{ old('number', $bc->number) }}" required>
            </div>

            <div class="field">
                <label for="booking_date">Tanggal Booking <span class="required">*</span></label>
                <input id="booking_date" name="booking_date" type="date" value="{{ old('booking_date', $bc->booking_date->format('Y-m-d')) }}" required>
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="confirmed" @selected(old('status', $bc->status) === 'confirmed')>Confirmed</option>
                    <option value="draft" @selected(old('status', $bc->status) === 'draft')>Draft</option>
                    <option value="cancelled" @selected(old('status', $bc->status) === 'cancelled')>Cancelled</option>
                </select>
            </div>
        </div>

        {{-- PIHAK TERKAIT (CONSIGNEE & SHIPPER) --}}
        <div class="form-section-heading">
            <h2>Penerima (Consignee) & Pengirim (Shipper)</h2>
            <p>Untuk dokumen export: Penerima adalah Consignee luar negeri, dan Pengirim (Shipper) mengambil data Master Customer.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="customer_id">Master Customer (Shipper) <span class="required">*</span></label>
                <select id="customer_id" name="customer_id" required>
                    <option value="">Pilih Customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(old('customer_id', $bc->customer_id) == $c->id)>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="shipper_name">Nama Shipper</label>
                <input id="shipper_name" name="shipper_name" maxlength="160" value="{{ old('shipper_name', $bc->shipper_name) }}" placeholder="Nama Shipper (Master Customer)">
            </div>

            <div class="field">
                <label for="consignee_name">To (Nama Consignee)</label>
                <input id="consignee_name" name="consignee_name" maxlength="160" value="{{ old('consignee_name', $bc->consignee_name) }}" placeholder="Nama Consignee / Penerima">
            </div>

            <div class="field">
                <label for="contact_person">Contact Person PIC (Consignee)</label>
                <input id="contact_person" name="contact_person" maxlength="120" value="{{ old('contact_person', $bc->contact_person) }}" placeholder="Otomatis dari master consignee">
            </div>

            <div class="field span-2">
                <label for="consignee_address">Alamat Consignee</label>
                <input id="consignee_address" name="consignee_address" value="{{ old('consignee_address', $bc->consignee_address) }}" placeholder="Alamat lengkap Consignee">
            </div>

            <div class="field">
                <label for="customer_ref">Customer Ref (PO / Booking Ref)</label>
                <input id="customer_ref" name="customer_ref" maxlength="100" value="{{ old('customer_ref', $bc->customer_ref) }}" placeholder="Nomor PO / Ref dari Customer">
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
                <input id="carrier_name" name="carrier_name" list="carrier_list" maxlength="160" value="{{ old('carrier_name', $bc->carrier_name) }}" placeholder="Pilih atau ketik nama shipping line">
                <datalist id="carrier_list">
                    @foreach($carriers as $carrier)
                        <option value="{{ $carrier->name }}">{{ $carrier->code ? '['.$carrier->code.'] ' : '' }}{{ $carrier->name }}</option>
                    @endforeach
                </datalist>
            </div>

            <div class="field">
                <label for="carrier_booking_no">No. Booking Carrier</label>
                <input id="carrier_booking_no" name="carrier_booking_no" maxlength="100" value="{{ old('carrier_booking_no', $bc->carrier_booking_no) }}" placeholder="No. Booking dari Pelayaran">
            </div>

            <div class="field">
                <label for="vessel_voyage">Vessel & Voyage</label>
                <input id="vessel_voyage" name="vessel_voyage" maxlength="120" value="{{ old('vessel_voyage', $bc->vessel_voyage) }}" placeholder="Nama Kapal & Voyage">
            </div>

            <div class="field">
                <label for="service_term">Services (Term Layanan) <span class="required">*</span></label>
                <select id="service_term" name="service_term" required>
                    <option value="CY/CY" @selected(old('service_term', $bc->service_term) === 'CY/CY')>CY/CY (Container Yard to Container Yard)</option>
                    <option value="CFS/CFS" @selected(old('service_term', $bc->service_term) === 'CFS/CFS')>CFS/CFS (Container Freight Station to Container Freight Station)</option>
                    <option value="CY/CFS" @selected(old('service_term', $bc->service_term) === 'CY/CFS')>CY/CFS</option>
                    <option value="CFS/CY" @selected(old('service_term', $bc->service_term) === 'CFS/CY')>CFS/CY</option>
                    <option value="SD/SD" @selected(old('service_term', $bc->service_term) === 'SD/SD')>SD/SD (Site Delivery / Door to Door)</option>
                </select>
            </div>

            <div class="field">
                <label for="pol">Port of Loading (POL)</label>
                <select id="pol" name="pol" data-custom-select aria-label="Port of Loading (POL)"><option value="">Pilih Port of Loading (POL)</option>@foreach($ports as $p)<option value="{{ $p->name }}" @selected(old('pol', $bc->pol)===$p->name)>{{ $p->code ? $p->code.' - ' : '' }}{{ $p->name }}</option>@endforeach</select>
            </div>

            <div class="field">
                <label for="pod">Port of Discharge (POD)</label>
                <select id="pod" name="pod" data-custom-select aria-label="Port of Discharge (POD)"><option value="">Pilih Port of Discharge (POD)</option>@foreach($ports as $p)<option value="{{ $p->name }}" @selected(old('pod', $bc->pod)===$p->name)>{{ $p->code ? $p->code.' - ' : '' }}{{ $p->name }}</option>@endforeach</select>
            </div>


            <div class="field">
                <label for="etd">ETD (Keberangkatan)</label>
                <input id="etd" name="etd" type="date" value="{{ old('etd', $bc->etd?->format('Y-m-d')) }}">
            </div>


            <div class="field">
                <label for="eta">ETA (Kedatangan)</label>
                <input id="eta" name="eta" type="date" value="{{ old('eta', $bc->eta?->format('Y-m-d')) }}">
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
                <input id="quantity" name="quantity" maxlength="100" value="{{ old('quantity', $bc->quantity) }}" placeholder="contoh: 1x 20GP atau 150 Box">
            </div>

            <div class="field">
                <label for="gross_weight">Gross Weight (KGS)</label>
                <input id="gross_weight" name="gross_weight" inputmode="decimal" value="{{ old('gross_weight', $bc->gross_weight) }}" placeholder="contoh: 12500.00">
            </div>

            <div class="field">
                <label for="volume">Volume (CBM / M3)</label>
                <input id="volume" name="volume" inputmode="decimal" value="{{ old('volume', $bc->volume) }}" placeholder="contoh: 28.50">
            </div>

            <div class="field span-2">
                <label for="cargo_description">Description (Deskripsi Barang / Commodity)</label>
                <textarea id="cargo_description" name="cargo_description" rows="2" placeholder="Uraian komoditas muatan">{{ old('cargo_description', $bc->cargo_description) }}</textarea>
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
                <input id="delivery_cargo_to" name="delivery_cargo_to" maxlength="255" value="{{ old('delivery_cargo_to', $bc->delivery_cargo_to) }}" placeholder="contoh: JICT Tanjung Priok / Depo KBN Cakung">
            </div>

            <div class="field">
                <label for="doc_cutoff_at">Doc Cut-Off (Dokumen)</label>
                <input id="doc_cutoff_at" name="doc_cutoff_at" type="datetime-local" value="{{ old('doc_cutoff_at', $bc->doc_cutoff_at?->format('Y-m-d\TH:i')) }}">
            </div>

            <div class="field">
                <label for="cy_cutoff_at">CY Cut-Off (Closing Container)</label>
                <input id="cy_cutoff_at" name="cy_cutoff_at" type="datetime-local" value="{{ old('cy_cutoff_at', $bc->cy_cutoff_at?->format('Y-m-d\TH:i')) }}">
            </div>

            <div class="field">
                <label for="delivery_cutoff_at">Delivery Cut-Off (Muatan Fisik)</label>
                <input id="delivery_cutoff_at" name="delivery_cutoff_at" type="datetime-local" value="{{ old('delivery_cutoff_at', $bc->delivery_cutoff_at?->format('Y-m-d\TH:i')) }}">
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
                <textarea id="notes" name="notes" rows="6">{{ old('notes', $bc->notes) }}</textarea>
            </div>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ route('booking-confirmations.show', $bc) }}">Batal</a>
            <button class="button button-primary">Simpan Perubahan</button>
        </div>
    </form>
</section>

@endsection
