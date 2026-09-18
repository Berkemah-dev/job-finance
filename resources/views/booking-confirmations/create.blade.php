@extends('layouts.app')
@section('title', 'Buat Booking Confirmation')
@section('content')

@php
    $defaultNotes = "1. This booking confirmation validates your space booking with us according to all details stated in the Shipping Instruction, we reserve right to shut out cargo for any discrepancy that disables us from doing groupage consol.\n" .
        "2. This booking confirmation does not function as a guarantee of departure, we reserve right to shut out cargo in case of failures in export procedures and/or handicaps in export documentations.\n" .
        "3. This booking confirmation is automatically invalid upon your booking cancellation, kindly inform us immediately for any cancellation.\n" .
        "4. This booking confirmation is automatically invalid upon any cases of cargo/documentations problems, including failure of export declaration.\n" .
        "5. This booking confirmation is automatically invalid for following undeclared cargo: LONG LENGTH/OVERWEIGHT/FOODS & BEVERAGES, DUTIABLE/DANGEROUS GOODS/LAW FORBIDDEN/LIVE ANIMALS.\n" .
        "6. This booking confirmation is not valid for any claim.";
    $backUrl = $selectedJob 
        ? route('jobs.show', $selectedJob) . '#tab-booking' 
        : (request()->filled('job_id') 
            ? route('jobs.show', request('job_id')) . '#tab-booking' 
            : route('booking-confirmations.index'));
@endphp

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL</p>
        <h1>Buat Booking Confirmation</h1>
        <p>Terbitkan konfirmasi alokasi ruang kapal/pesawat untuk shipper dan customer.</p>
    </div>
    <a class="button button-secondary" href="{{ $backUrl }}">← Kembali</a>
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
                        @php
                            $hasBc = $j->bookingConfirmations?->isNotEmpty();
                        @endphp
                        <option value="{{ $j->id }}"
                            @selected(old('job_id', $selectedJob?->id) == $j->id)
                            @disabled($hasBc && old('job_id', $selectedJob?->id) != $j->id)
                            data-customer-id="{{ $j->customer_id }}"
                            data-shipper="{{ $j->shipper_name ?: $j->customer?->name }}"
                            data-consignee="{{ $j->consignee_name }}"
                            data-vessel="{{ $j->vessel_voyage }}"
                            data-pol="{{ $j->pol ?? $j->origin }}"
                            data-pod="{{ $j->pod ?? $j->destination }}"
                            data-etd="{{ $j->etd?->format('Y-m-d') }}"
                            data-eta="{{ $j->eta?->format('Y-m-d') }}"
                            data-quantity="{{ $j->package_count }}"
                            data-unit="{{ $j->container_type }}"
                            data-commodity="{{ $j->cargo_description }}"
                            data-gross-weight="{{ $j->gross_weight }}"
                            data-volume="{{ $j->volume }}"
                            data-booking-ref="{{ $j->booking_reference }}"
                        >
                            {{ $j->number }} — {{ $j->customer?->name }} ({{ Str::limit($j->subject, 30) }}){{ $hasBc ? ' [Sudah Ada BC]' : '' }}
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

        {{-- PIHAK TERKAIT (SHIPPER & CONSIGNEE) --}}
        <div class="form-section-heading">
            <h2>Pengirim (Shipper) & Penerima (Consignee)</h2>
            <p>Data Pengirim dan Penerima barang secara otomatis mengikuti Job Order awal.</p>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="shipper_name">Shipper (Pengirim) <span class="required">*</span></label>
                <input id="shipper_name" name="shipper_name" maxlength="160" value="{{ old('shipper_name', $selectedJob?->shipper_name ?? $selectedJob?->customer?->name) }}" required placeholder="Nama Shipper (Mengikuti Job Order)">
            </div>

            <div class="field">
                <label for="consignee_name">Consignee (Penerima) <span class="required">*</span></label>
                <input id="consignee_name" name="consignee_name" maxlength="160" value="{{ old('consignee_name', $selectedJob?->consignee_name) }}" required placeholder="Nama Consignee (Mengikuti Job Order)">
            </div>

            <div class="field span-2">
                <label for="customer_ref">Customer Ref (PO / Booking Ref)</label>
                <input id="customer_ref" name="customer_ref" maxlength="100" value="{{ old('customer_ref') }}" placeholder="Nomor PO / Ref dari Customer (Opsional)">
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
                <select id="carrier_name" name="carrier_name" data-custom-select data-allow-custom="true" aria-label="Carrier Booking (Shipping Lines / Maskapai)">
                    <option value="">Pilih atau ketik nama shipping line / maskapai...</option>
                    @php
                        $currentCarrier = old('carrier_name');
                        $carrierFound = false;
                    @endphp
                    @foreach($carriers as $carrier)
                        @if($currentCarrier === $carrier->name)
                            @php $carrierFound = true; @endphp
                        @endif
                        <option value="{{ $carrier->name }}" @selected($currentCarrier === $carrier->name)>
                            {{ $carrier->code ? '['.$carrier->code.'] ' : '' }}{{ $carrier->name }}
                        </option>
                    @endforeach
                    @if($currentCarrier && !$carrierFound)
                        <option value="{{ $currentCarrier }}" selected data-custom-option="true">{{ $currentCarrier }}</option>
                    @endif
                </select>
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
                <select id="pol" name="pol" data-custom-select data-allow-custom="true" aria-label="Port of Loading (POL)">
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
                <label for="pod">Port of Discharge (POD)</label>
                <select id="pod" name="pod" data-custom-select data-allow-custom="true" aria-label="Port of Discharge (POD)">
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
                <input id="quantity" name="quantity" maxlength="100" value="{{ old('quantity', $selectedJob?->package_count) }}" placeholder="contoh: 150">
            </div>

            <div class="field">
                <label for="package_unit">Satuan (Kemasan / Unit)</label>
                <select id="package_unit" name="package_unit" data-custom-select data-allow-custom="true" aria-label="Satuan (Kemasan / Unit)">
                    <option value="">Pilih atau ketik satuan kemasan...</option>
                    @php
                        $currentUnit = old('package_unit', $selectedJob?->container_type);
                        $unitFound = false;
                        $standardUnits = ['Box', 'Carton', 'Pallet', 'Pcs', 'Package', 'Drum', 'Bags', 'Rolls', 'Crates', 'Unit', '20GP', '40GP', '40HQ', 'LCL'];
                    @endphp
                    @foreach($standardUnits as $u)
                        @if(strcasecmp($currentUnit ?? '', $u) === 0)
                            @php $unitFound = true; @endphp
                        @endif
                        <option value="{{ $u }}" @selected(strcasecmp($currentUnit ?? '', $u) === 0)>{{ $u }}</option>
                    @endforeach
                    @if($currentUnit && !$unitFound)
                        <option value="{{ $currentUnit }}" selected data-custom-option="true">{{ $currentUnit }}</option>
                    @endif
                </select>
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

        {{-- SYARAT & KETENTUAN (STANDARD TERMS & CONDITIONS) --}}
        <div class="form-section-heading">
            <h2>Syarat & Ketentuan Standar (Standard Terms & Conditions)</h2>
            <p>Klausul baku pengapalan yang akan tercantum pada dokumen Booking Confirmation resmi.</p>
        </div>

        <div class="form-grid">
            <div class="field span-2">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label for="notes" style="margin-bottom: 0; font-weight: 600;">Klausul & Catatan Booking (Important Notes)</label>
                    <span style="font-size: 11.5px; color: #475569; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; border: 1px solid #e2e8f0; font-weight: 500;">Standar Klausul Ekspedisi (Dapat Disesuaikan)</span>
                </div>
                <textarea id="notes" name="notes" rows="8" style="min-height: 180px; line-height: 1.65; font-size: 13px; font-family: inherit; padding: 12px 14px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff;">{{ old('notes', $defaultNotes) }}</textarea>
                <small class="form-help">Poin ketentuan di atas adalah klausul standar operasional. Anda dapat mengedit atau menambahkan catatan khusus pengapalan jika diperlukan.</small>
            </div>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ $backUrl }}">Batal</a>
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
        if (!el || val === undefined) return;
        if (el.tagName === 'SELECT' && val) {
            let match = Array.from(el.options).find(o => o.value === val);
            if (!match && el.dataset.allowCustom === 'true') {
                const newOpt = document.createElement('option');
                newOpt.value = val;
                newOpt.textContent = val;
                newOpt.dataset.customOption = 'true';
                el.appendChild(newOpt);
            }
        }
        el.value = val;
        el.dispatchEvent(new Event('change', { bubbles: true }));
    };

    if (opt.dataset.shipper) setVal('shipper_name', opt.dataset.shipper);
    if (opt.dataset.consignee) setVal('consignee_name', opt.dataset.consignee);
    if (opt.dataset.vessel) setVal('vessel_voyage', opt.dataset.vessel);
    if (opt.dataset.pol) setVal('pol', opt.dataset.pol);
    if (opt.dataset.pod) setVal('pod', opt.dataset.pod);
    if (opt.dataset.etd) setVal('etd', opt.dataset.etd);
    if (opt.dataset.eta) setVal('eta', opt.dataset.eta);
    if (opt.dataset.quantity) setVal('quantity', opt.dataset.quantity);
    if (opt.dataset.unit) setVal('package_unit', opt.dataset.unit);
    if (opt.dataset.commodity) setVal('cargo_description', opt.dataset.commodity);
    if (opt.dataset.grossWeight) setVal('gross_weight', opt.dataset.grossWeight);
    if (opt.dataset.volume) setVal('volume', opt.dataset.volume);
    if (opt.dataset.bookingRef) setVal('carrier_booking_no', opt.dataset.bookingRef);
});
</script>

@endsection
