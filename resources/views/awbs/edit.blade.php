@extends('layouts.app')
@section('title', 'Edit AWB ' . $awb->number)
@section('content')

@php
    $backUrl = $awb->job_id
        ? route('jobs.show', $awb->job_id) . '#tab-awb'
        : route('awbs.show', $awb);
@endphp

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL — EXPORT AIR</p>
        <h1>Edit AWB</h1>
        <p>{{ $awb->number }} · Airline: {{ $awb->airline ?: '—' }}</p>
    </div>
    <a class="button button-secondary" href="{{ $backUrl }}">← Kembali</a>
</div>

<section class="panel form-panel">
    <form class="data-form" method="POST" action="{{ route('awbs.update', $awb) }}">
        @csrf
        @method('PUT')

        <div class="form-section-heading">
            <h2>Informasi Dasar & Job Order</h2>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="job_id">Terkait Job Order</label>
                <select id="job_id" name="job_id">
                    <option value="">Pilih Job Order (Opsional)</option>
                    @foreach($jobs as $j)
                        <option value="{{ $j->id }}" @selected(old('job_id', $awb->job_id) == $j->id)>
                            {{ $j->number }} — {{ $j->customer?->name }} ({{ Str::limit($j->subject, 30) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="number">Nomor AWB <span class="required">*</span></label>
                <input id="number" name="number" maxlength="60" value="{{ old('number', $awb->number) }}" required>
            </div>

            <div class="field">
                <label for="awb_date">Tanggal AWB <span class="required">*</span></label>
                <input id="awb_date" name="awb_date" type="date" value="{{ old('awb_date', $awb->awb_date->format('Y-m-d')) }}" required>
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="draft" @selected(old('status', $awb->status) === 'draft')>Draft</option>
                    <option value="issued" @selected(old('status', $awb->status) === 'issued')>Issued</option>
                    <option value="completed" @selected(old('status', $awb->status) === 'completed')>Completed</option>
                    <option value="cancelled" @selected(old('status', $awb->status) === 'cancelled')>Cancelled</option>
                </select>
            </div>

            <div class="field">
                <label for="customer_id">Customer (Shipper)</label>
                <select id="customer_id" name="customer_id">
                    <option value="">Pilih Customer (Opsional)</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(old('customer_id', $awb->customer_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="freight_term">Freight Term <span class="required">*</span></label>
                <select id="freight_term" name="freight_term" required>
                    <option value="PREPAID" @selected(old('freight_term', $awb->freight_term) === 'PREPAID')>PREPAID</option>
                    <option value="COLLECT" @selected(old('freight_term', $awb->freight_term) === 'COLLECT')>COLLECT</option>
                </select>
            </div>
        </div>

        <div class="form-section-heading">
            <h2>Maskapai & Rute Penerbangan</h2>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="airline">Airline (Maskapai)</label>
                <input id="airline" name="airline" list="airline_list" maxlength="160"
                    value="{{ old('airline', $awb->airline) }}" placeholder="contoh: Garuda Indonesia">
                <datalist id="airline_list">
                    @foreach($airlines as $a)
                        <option value="{{ $a->name }}">{{ $a->name }}</option>
                    @endforeach
                </datalist>
            </div>

            <div class="field">
                <label for="airline_code">Kode IATA Maskapai</label>
                <input id="airline_code" name="airline_code" maxlength="20"
                    value="{{ old('airline_code', $awb->airline_code) }}" placeholder="contoh: GA / SQ">
            </div>

            <div class="field">
                <label for="flight_number">No. Penerbangan</label>
                <input id="flight_number" name="flight_number" maxlength="60"
                    value="{{ old('flight_number', $awb->flight_number) }}" placeholder="contoh: GA-724">
            </div>

            <div class="field">
                <label for="routing">Routing / Via</label>
                <input id="routing" name="routing" maxlength="255"
                    value="{{ old('routing', $awb->routing) }}" placeholder="contoh: JKT – SIN – PVG">
            </div>

            <div class="field">
                <label for="airport_of_departure">Airport of Departure</label>
                <input id="airport_of_departure" name="airport_of_departure" maxlength="120"
                    value="{{ old('airport_of_departure', $awb->airport_of_departure) }}" placeholder="contoh: JAKARTA / CGK">
            </div>

            <div class="field">
                <label for="airport_of_destination">Airport of Destination</label>
                <input id="airport_of_destination" name="airport_of_destination" maxlength="120"
                    value="{{ old('airport_of_destination', $awb->airport_of_destination) }}" placeholder="contoh: SHANGHAI / PVG">
            </div>

            <div class="field">
                <label for="etd">ETD (Keberangkatan)</label>
                <input id="etd" name="etd" type="date" value="{{ old('etd', $awb->etd?->format('Y-m-d')) }}">
            </div>

            <div class="field">
                <label for="eta">ETA (Kedatangan)</label>
                <input id="eta" name="eta" type="date" value="{{ old('eta', $awb->eta?->format('Y-m-d')) }}">
            </div>
        </div>

        <div class="form-section-heading">
            <h2>Pihak Pengapalan</h2>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="shipper_name">Shipper (Pengirim)</label>
                <input id="shipper_name" name="shipper_name" maxlength="160"
                    value="{{ old('shipper_name', $awb->shipper_name) }}" placeholder="Nama Perusahaan Shipper">
            </div>

            <div class="field">
                <label for="consignee_name">Consignee (Penerima)</label>
                <input id="consignee_name" name="consignee_name" maxlength="160"
                    value="{{ old('consignee_name', $awb->consignee_name) }}" placeholder="Nama Perusahaan Consignee">
            </div>

            <div class="field span-2">
                <label for="notify_party">Notify Party</label>
                <input id="notify_party" name="notify_party" maxlength="255"
                    value="{{ old('notify_party', $awb->notify_party) }}" placeholder="Pihak yang dinotifikasi">
            </div>

            <div class="field">
                <label for="shipper_ref">Shipper Reference</label>
                <input id="shipper_ref" name="shipper_ref" maxlength="100"
                    value="{{ old('shipper_ref', $awb->shipper_ref) }}" placeholder="Nomor referensi shipper">
            </div>
        </div>

        <div class="form-section-heading">
            <h2>Detail Kargo</h2>
        </div>

        <div class="form-grid">
            <div class="field span-2">
                <label for="commodity">Commodity / Description of Goods</label>
                <input id="commodity" name="commodity" maxlength="255"
                    value="{{ old('commodity', $awb->commodity) }}" placeholder="Deskripsi barang / komoditas">
            </div>

            <div class="field">
                <label for="pieces">Jumlah Koli (Pieces)</label>
                <input id="pieces" name="pieces" type="number" min="0"
                    value="{{ old('pieces', $awb->pieces) }}" placeholder="contoh: 10">
            </div>

            <div class="field">
                <label for="gross_weight">Gross Weight (KGS)</label>
                <input id="gross_weight" name="gross_weight" inputmode="decimal"
                    value="{{ old('gross_weight', $awb->gross_weight) }}" placeholder="contoh: 250.00">
            </div>

            <div class="field">
                <label for="chargeable_weight">Chargeable Weight (KGS)</label>
                <input id="chargeable_weight" name="chargeable_weight" inputmode="decimal"
                    value="{{ old('chargeable_weight', $awb->chargeable_weight) }}" placeholder="contoh: 300.00">
            </div>

            <div class="field">
                <label for="volume">Volume (CBM)</label>
                <input id="volume" name="volume" inputmode="decimal"
                    value="{{ old('volume', $awb->volume) }}" placeholder="contoh: 2.50">
            </div>
        </div>

        <div class="form-section-heading">
            <h2>Remarks</h2>
        </div>

        <div class="form-grid">
            <div class="field span-2">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" name="remarks" rows="3">{{ old('remarks', $awb->remarks) }}</textarea>
            </div>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ $backUrl }}">Batal</a>
            <button class="button button-primary">Simpan Perubahan</button>
        </div>
    </form>
</section>

@endsection
