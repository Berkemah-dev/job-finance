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
<div class="page-heading"><div><p class="eyebrow">OPERASIONAL · {{ $serviceCategoryTitle }}</p><h1>JOB ORDER</h1><p>{{ $job->number }} · Perbarui informasi operasional pengiriman.</p></div><a class="button button-secondary" href="{{ route('jobs.show',$job) }}">← Kembali</a></div>
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
    @php
        $isImport = str_contains($serviceTypeRaw, 'imp');
    @endphp
    <div class="field">
        <label for="shipper_name" style="display: flex; justify-content: space-between; align-items: center;">
            <span>Shipper @if($isImport)<span style="font-size: 11px; background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; margin-left: 4px;">Master Shipper Import</span>@endif</span>
            @can('customers.manage')
                <a href="{{ route('customer-contacts.create', ['type' => 'shipper', 'customer_id' => $job->customer_id]) }}" target="_blank" style="color: #2563eb; font-size: 11px; text-decoration: none; font-weight: 500;">+ Tambah Master Shipper</a>
            @endcan
        </label>
        <select id="shipper_name" name="shipper_name" data-custom-select data-allow-custom="true" aria-label="Shipper">
            <option value="">{{ $isImport ? 'Pilih dari Master Shipper Import / ketik...' : 'Pilih atau ketik Shipper...' }}</option>
            @foreach($masterShippers ?? [] as $sContact)
                <option value="{{ $sContact->name }}" data-address="{{ $sContact->address }}" @selected(old('shipper_name',$job->shipper_name) === $sContact->name)>{{ $sContact->name }} @if($sContact->country)({{ $sContact->country }})@endif</option>
            @endforeach
            @if($curShipper = old('shipper_name', $job->shipper_name))
                @if(!collect($masterShippers ?? [])->contains('name', $curShipper))
                    <option value="{{ $curShipper }}" selected>{{ $curShipper }}</option>
                @endif
            @endif
        </select>
        <input type="hidden" id="shipper_address" name="shipper_address" value="{{ old('shipper_address',$job->shipper_address) }}">
        <p class="form-help" id="shipper_address_display" style="font-size: 11px; color: #64748b;">Alamat: {{ \Illuminate\Support\Str::limit(old('shipper_address',$job->shipper_address) ?: 'Tersambung otomatis dari Master Shipper', 60) }}</p>
    </div>

    <div class="field">
        <label for="consignee_name" style="display: flex; justify-content: space-between; align-items: center;">
            <span>Consignee</span>
            @can('customers.manage')
                <a href="{{ route('customer-contacts.create', ['type' => 'consignee', 'customer_id' => $job->customer_id]) }}" target="_blank" style="color: #2563eb; font-size: 11px; text-decoration: none; font-weight: 500;">+ Tambah Master Consignee</a>
            @endcan
        </label>
        <select id="consignee_name" name="consignee_name" data-custom-select data-allow-custom="true" aria-label="Consignee">
            <option value="">Pilih atau ketik Consignee...</option>
            @if($job->customer)
                <option value="{{ $job->customer->name }}" data-address="{{ $job->customer->address }}" @selected(old('consignee_name',$job->consignee_name) === $job->customer->name)>{{ $job->customer->name }} (Customer)</option>
            @endif
            @foreach($masterConsignees ?? [] as $cContact)
                <option value="{{ $cContact->name }}" data-address="{{ $cContact->address }}" @selected(old('consignee_name',$job->consignee_name) === $cContact->name)>{{ $cContact->name }} @if($cContact->country)({{ $cContact->country }})@endif</option>
            @endforeach
            @if($curConsignee = old('consignee_name', $job->consignee_name))
                @if((!$job->customer || $job->customer->name !== $curConsignee) && !collect($masterConsignees ?? [])->contains('name', $curConsignee))
                    <option value="{{ $curConsignee }}" selected>{{ $curConsignee }}</option>
                @endif
            @endif
        </select>
        <input type="hidden" id="consignee_address" name="consignee_address" value="{{ old('consignee_address',$job->consignee_address) }}">
        <p class="form-help" id="consignee_address_display" style="font-size: 11px; color: #64748b;">Alamat: {{ \Illuminate\Support\Str::limit(old('consignee_address',$job->consignee_address) ?: 'Tersambung otomatis dari Master Consignee/Customer', 60) }}</p>
    </div>

    {{-- Cuman ada 1 baris dan default dari Quote: Port of Loading & Port of Discharge --}}
    @php
        $defaultPol = old('pol', $job->pol ?: ($job->origin ?: $job->quotation?->origin));
        $defaultPod = old('pod', $job->pod ?: ($job->destination ?: $job->quotation?->destination));
    @endphp
    <div class="field">
        <label for="pol">Port of Loading (POL) / Pelabuhan muat (POL)</label>
        <select id="pol" name="pol" data-custom-select data-allow-custom="true" aria-label="Port of Loading (POL)">
            <option value="">Port of Loading (POL)</option>
            @foreach($ports ?? [] as $port)
                <option value="{{ $port->name }}" @selected($defaultPol === $port->name || $defaultPol === $port->code)>{{ $port->name }}</option>
            @endforeach
            @if($defaultPol && !collect($ports ?? [])->contains('name', $defaultPol) && !collect($ports ?? [])->contains('code', $defaultPol))
                <option value="{{ $defaultPol }}" selected>{{ $defaultPol }}</option>
            @endif
        </select>
    </div>
    <div class="field">
        <label for="pod">Port of Discharge (POD) / Pelabuhan bongkar (POD)</label>
        <select id="pod" name="pod" data-custom-select data-allow-custom="true" aria-label="Port of Discharge (POD)">
            <option value="">Port of Discharge (POD)</option>
            @foreach($ports ?? [] as $port)
                <option value="{{ $port->name }}" @selected($defaultPod === $port->name || $defaultPod === $port->code)>{{ $port->name }}</option>
            @endforeach
            @if($defaultPod && !collect($ports ?? [])->contains('name', $defaultPod) && !collect($ports ?? [])->contains('code', $defaultPod))
                <option value="{{ $defaultPod }}" selected>{{ $defaultPod }}</option>
            @endif
        </select>
    </div>
    <input type="hidden" id="origin" name="origin" value="{{ old('origin', $job->origin ?: $defaultPol) }}">
    <input type="hidden" id="destination" name="destination" value="{{ old('destination', $job->destination ?: $defaultPod) }}">
    
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
    <div class="field span-2" style="margin-top: 6px;">
        <label for="cargo_description">
            <span style="font-weight: 700; color: #1e293b;">Commodity (Nama Barang)</span>
        </label>
        <textarea id="cargo_description" name="cargo_description" rows="2" maxlength="2000" placeholder="Masukkan nama barang / komoditas (contoh: Spare Parts Mesin Industri / Garmen / Tekstil)">{{ old('cargo_description', $job->cargo_description) }}</textarea>
    </div>

    <div class="field span-2">
        <label for="operational_notes">Catatan Operasional</label>
        <textarea id="operational_notes" name="operational_notes" rows="2" maxlength="5000" placeholder="Catatan operasional atau instruksi penanganan khusus...">{{ old('operational_notes', $job->operational_notes) }}</textarea>
    </div>
</div>
<div class="form-actions"><a class="button button-secondary" href="{{ route('jobs.show',$job) }}">Batal</a><button class="button button-primary">Simpan operasional</button></div>
</form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Sync POL with origin, POD with destination
    const polSelect = document.getElementById('pol');
    const podSelect = document.getElementById('pod');
    const originInput = document.getElementById('origin');
    const destinationInput = document.getElementById('destination');

    if (polSelect && originInput) {
        polSelect.addEventListener('change', () => { originInput.value = polSelect.value; });
    }
    if (podSelect && destinationInput) {
        podSelect.addEventListener('change', () => { destinationInput.value = podSelect.value; });
    }

    // 2. Auto-populate Shipper Address from Master Shipper
    const shipperSelect = document.getElementById('shipper_name');
    const shipperAddressInput = document.getElementById('shipper_address');
    const shipperAddressDisplay = document.getElementById('shipper_address_display');

    if (shipperSelect && shipperAddressInput) {
        shipperSelect.addEventListener('change', function() {
            const opt = shipperSelect.options[shipperSelect.selectedIndex];
            if (opt && opt.dataset.address) {
                shipperAddressInput.value = opt.dataset.address;
                if (shipperAddressDisplay) {
                    shipperAddressDisplay.textContent = 'Alamat: ' + opt.dataset.address.substring(0, 60) + (opt.dataset.address.length > 60 ? '...' : '');
                }
            }
        });
    }

    // 3. Auto-populate Consignee Address from Master Consignee
    const consigneeSelect = document.getElementById('consignee_name');
    const consigneeAddressInput = document.getElementById('consignee_address');
    const consigneeAddressDisplay = document.getElementById('consignee_address_display');

    if (consigneeSelect && consigneeAddressInput) {
        consigneeSelect.addEventListener('change', function() {
            const opt = consigneeSelect.options[consigneeSelect.selectedIndex];
            if (opt && opt.dataset.address) {
                consigneeAddressInput.value = opt.dataset.address;
                if (consigneeAddressDisplay) {
                    consigneeAddressDisplay.textContent = 'Alamat: ' + opt.dataset.address.substring(0, 60) + (opt.dataset.address.length > 60 ? '...' : '');
                }
            }
        });
    }
});
</script>
@endsection
