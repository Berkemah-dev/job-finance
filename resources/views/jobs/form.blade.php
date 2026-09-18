@extends('layouts.app')
@section('title','Edit Job Order')
@section('content')
@php 
    $serviceTypeRaw = strtolower($job->service_type ?? '');
    $isAir = str_contains($serviceTypeRaw, 'air');
    $serviceCategoryTitle = match ($serviceTypeRaw) { 
        'exp_sea' => 'EXPORT SHIPMENT (SEA)', 
        'exp_air' => 'EXPORT SHIPMENT (AIR)', 
        'imp_sea' => 'IMPORT SHIPMENT (SEA)', 
        'imp_air' => 'IMPORT SHIPMENT (AIR)', 
        default => 'DOMESTIC / TRUCKING' 
    }; 
@endphp
<div class="page-heading"><div><p class="eyebrow">OPERASIONAL / JOB ORDER · {{ $serviceCategoryTitle }}</p><h1>Edit Data Pengapalan</h1><p>{{ $job->number }} · Perbarui informasi operasional pengiriman.</p></div><a class="button button-secondary" href="{{ route('jobs.show',$job) }}">← Kembali</a></div>
<section class="panel form-panel">
<form class="data-form" method="POST" action="{{ route('jobs.update',$job) }}">
@csrf
@method('PUT')
<input type="hidden" name="lock_version" value="{{ old('lock_version',$job->lock_version) }}">
<input type="hidden" name="subject" value="{{ old('subject',$job->subject) }}">
<input type="hidden" name="job_date" value="{{ old('job_date',$job->job_date?->format('Y-m-d')) }}">

<div class="info-note">Isi data operasional yang berubah. Informasi customer, service, rute, dan dokumen tetap tersimpan di Job Order.</div>

<div class="form-section-heading"><h2>Data Operasional & Routing</h2><p>Lengkapi informasi pengiriman, pelabuhan, dan muatan.</p></div>
<div class="form-grid">
    <div class="field"><label for="shipper_name">Shipper</label><input id="shipper_name" name="shipper_name" maxlength="160" value="{{ old('shipper_name',$job->shipper_name) }}"></div>
    <div class="field"><label for="consignee_name">Consignee</label><input id="consignee_name" name="consignee_name" maxlength="160" value="{{ old('consignee_name',$job->consignee_name) }}"></div>
    <div class="field"><label for="shipper_address">Alamat Shipper</label><input id="shipper_address" name="shipper_address" maxlength="5000" value="{{ old('shipper_address',$job->shipper_address) }}"></div>
    <div class="field"><label for="consignee_address">Alamat Consignee</label><input id="consignee_address" name="consignee_address" maxlength="5000" value="{{ old('consignee_address',$job->consignee_address) }}"></div>
    
    <div class="field"><label for="origin">Asal (Origin)</label><input id="origin" name="origin" maxlength="255" value="{{ old('origin',$job->origin) }}"></div>
    <div class="field"><label for="destination">Tujuan (Destination)</label><input id="destination" name="destination" maxlength="255" value="{{ old('destination',$job->destination) }}"></div>
    <div class="field"><label for="pol">Pelabuhan muat (POL)</label><input id="pol" name="pol" maxlength="120" value="{{ old('pol',$job->pol) }}"></div>
    <div class="field"><label for="pod">Pelabuhan bongkar (POD)</label><input id="pod" name="pod" maxlength="120" value="{{ old('pod',$job->pod) }}"></div>
    
    <div class="field"><label for="etd">ETD</label><input id="etd" name="etd" type="date" value="{{ old('etd',$job->etd?->format('Y-m-d')) }}"></div>
    <div class="field"><label for="eta">ETA</label><input id="eta" name="eta" type="date" value="{{ old('eta',$job->eta?->format('Y-m-d')) }}"></div>
    
    @if($isAir)
        <div class="field"><label for="flight_number">Nomor Penerbangan</label><input id="flight_number" name="flight_number" maxlength="60" value="{{ old('flight_number',$job->flight_number) }}" placeholder="e.g. GA 881"></div>
        <div class="field"></div>
        <div class="field"><label for="awb_number">Nomor AWB</label><input id="awb_number" name="awb_number" maxlength="60" value="{{ old('awb_number',$job->awb_number) }}" placeholder="Nomor Master AWB"></div>
        <div class="field"><label for="hawb_number">Nomor HAWB</label><input id="hawb_number" name="hawb_number" maxlength="60" value="{{ old('hawb_number',$job->hawb_number) }}" placeholder="Nomor House AWB"></div>
    @else
        <div class="field"><label for="vessel_voyage">Vessel & Voyage</label><input id="vessel_voyage" name="vessel_voyage" maxlength="120" value="{{ old('vessel_voyage',$job->vessel_voyage) }}" placeholder="Nama Kapal / Vessel & Voyage"></div>
        <div class="field"></div>
        <div class="field"><label for="bl_number">Nomor BL</label><input id="bl_number" name="bl_number" maxlength="60" value="{{ old('bl_number',$job->bl_number) }}" placeholder="Nomor Master BL"></div>
        <div class="field"><label for="hbl_number">Nomor HBL</label><input id="hbl_number" name="hbl_number" maxlength="60" value="{{ old('hbl_number',$job->hbl_number) }}" placeholder="Nomor House BL"></div>
    @endif

    <div class="field">
        <label for="package_count">Quantity</label>
        <input id="package_count" name="package_count" type="number" min="0" max="999999" value="{{ old('package_count',$job->package_count) }}" placeholder="Jumlah kuantitas">
    </div>
    <div class="field">
        <label for="container_type">Satuan / Tipe Kontainer</label>
        <select id="container_type" name="container_type">
            <option value="">Pilih satuan (opsional)</option>
            @foreach($containerUnits ?? \App\Models\ContainerUnit::options() as $value => $label)
                <option value="{{ $value }}" @selected(old('container_type', $job->container_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label for="gross_weight">Gross Weight (kg)</label><input id="gross_weight" name="gross_weight" inputmode="decimal" value="{{ old('gross_weight',$job->gross_weight) }}" placeholder="0.00"></div>
    <div class="field"><label for="volume">Volume (m³ / CBM)</label><input id="volume" name="volume" inputmode="decimal" value="{{ old('volume',$job->volume) }}" placeholder="0.00"></div>
    <div class="field span-2"><label for="shipment_reference">Referensi Pengiriman</label><input id="shipment_reference" name="shipment_reference" maxlength="100" value="{{ old('shipment_reference',$job->shipment_reference) }}" placeholder="No. Referensi / PO"></div>

    <div class="field span-2 form-section-heading" style="margin-top: 15px;">
        <h2>Dokumen Komersial & SK Pabean</h2>
        <p>Data invoice dan packing list untuk kelengkapan Surat Kuasa Kepabeanan.</p>
    </div>
    <div class="field"><label for="commercial_invoice_number">Nomor Invoice</label><input id="commercial_invoice_number" name="commercial_invoice_number" maxlength="60" value="{{ old('commercial_invoice_number',$job->commercial_invoice_number) }}" placeholder="Nomor Commercial Invoice"></div>
    <div class="field"><label for="commercial_invoice_date">Tanggal Invoice</label><input id="commercial_invoice_date" name="commercial_invoice_date" type="date" value="{{ old('commercial_invoice_date',$job->commercial_invoice_date?->format('Y-m-d')) }}"></div>
    <div class="field"><label for="packing_list_number">Nomor Packing List</label><input id="packing_list_number" name="packing_list_number" maxlength="60" value="{{ old('packing_list_number',$job->packing_list_number) }}" placeholder="Nomor Packing List"></div>
    <div class="field"><label for="packing_list_date">Tanggal Packing List</label><input id="packing_list_date" name="packing_list_date" type="date" value="{{ old('packing_list_date',$job->packing_list_date?->format('Y-m-d')) }}"></div>
    <div class="field span-2"><label for="invoice_issuer">Nama Penerbit Invoice</label><input id="invoice_issuer" name="invoice_issuer" maxlength="160" value="{{ old('invoice_issuer',$job->invoice_issuer) }}" placeholder="Nama perusahaan shipper/penerbit invoice"></div>
    <div class="field">
        <label for="incoterm">Incoterm</label>
        @php
            $curIncoterm = strtoupper(old('incoterm', $job->incoterm ?: ($job->quotation?->incoterm ?? 'CIF')));
        @endphp
        <select id="incoterm" name="incoterm">
            <option value="CIF" @selected($curIncoterm === 'CIF')>CIF (Cost, Insurance & Freight)</option>
            <option value="FOB" @selected($curIncoterm === 'FOB')>FOB (Free On Board)</option>
            <option value="EXW" @selected($curIncoterm === 'EXW')>EXW (Ex Works)</option>
            <option value="DDP" @selected($curIncoterm === 'DDP')>DDP (Delivered Duty Paid)</option>
            <option value="CFR" @selected($curIncoterm === 'CFR' || $curIncoterm === 'C&F')>CFR / C&F (Cost and Freight)</option>
            <option value="FCA" @selected($curIncoterm === 'FCA')>FCA (Free Carrier)</option>
            <option value="CPT" @selected($curIncoterm === 'CPT')>CPT (Carriage Paid To)</option>
            <option value="CIP" @selected($curIncoterm === 'CIP')>CIP (Carriage & Insurance Paid)</option>
            <option value="DAP" @selected($curIncoterm === 'DAP')>DAP (Delivered at Place)</option>
            <option value="DPU" @selected($curIncoterm === 'DPU')>DPU (Delivered at Place Unloaded)</option>
        </select>
    </div>
    <div class="field"><label for="invoice_amount">Nilai Invoice</label><input id="invoice_amount" name="invoice_amount" maxlength="100" value="{{ old('invoice_amount',$job->invoice_amount) }}" placeholder="contoh: USD 25,000 atau Rp 150.000.000"></div>

    <div class="field span-2"><label for="cargo_description">Commodity</label><textarea id="cargo_description" name="cargo_description" rows="3" maxlength="2000">{{ old('cargo_description',$job->cargo_description) }}</textarea></div>
    <div class="field span-2"><label for="operational_notes">Catatan Operasional</label><textarea id="operational_notes" name="operational_notes" rows="2" maxlength="5000">{{ old('operational_notes',$job->operational_notes) }}</textarea></div>
</div>
<div class="form-actions"><a class="button button-secondary" href="{{ route('jobs.show',$job) }}">Batal</a><button class="button button-primary">Simpan operasional</button></div>
</form>
</section>
@endsection
