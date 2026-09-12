@extends('layouts.app')
@section('title','Edit Job Order')
@section('content')
@php $serviceCategoryTitle = match (strtolower($job->service_type ?? '')) { 'exp_sea' => 'EXPORT SHIPMENT (SEA)', 'exp_air' => 'EXPORT SHIPMENT (AIR)', 'imp_sea' => 'IMPORT SHIPMENT (SEA)', 'imp_air' => 'IMPORT SHIPMENT (AIR)', default => 'DOMESTIC / TRUCKING' }; @endphp
<div class="page-heading"><div><p class="eyebrow">OPERASIONAL / JOB ORDER · {{ $serviceCategoryTitle }}</p><h1>Edit Data Pengapalan</h1><p>{{ $job->number }} · Perbarui informasi operasional pengiriman.</p></div><a class="text-link" href="{{ route('jobs.show',$job) }}">← Kembali ke Job Order</a></div>
<section class="panel form-panel"><form class="data-form" method="POST" action="{{ route('jobs.update',$job) }}">@csrf @method('PUT')<input type="hidden" name="lock_version" value="{{ old('lock_version',$job->lock_version) }}">
<div class="info-note">Isi data operasional yang berubah. Informasi customer, service, rute, dan dokumen tetap tersimpan di Job Order.</div>
<div class="form-section-heading"><h2>Data Operasional</h2><p>Lengkapi informasi pengiriman dan muatan.</p></div>
<div class="form-grid">
<div class="field"><label for="shipper_name">Shipper</label><input id="shipper_name" name="shipper_name" maxlength="160" value="{{ old('shipper_name',$job->shipper_name) }}"></div>
<div class="field"><label for="consignee_name">Consignee</label><input id="consignee_name" name="consignee_name" maxlength="160" value="{{ old('consignee_name',$job->consignee_name) }}"></div>
<div class="field"><label for="etd">ETD</label><input id="etd" name="etd" type="date" value="{{ old('etd',$job->etd?->format('Y-m-d')) }}"></div>
<div class="field"><label for="eta">ETA</label><input id="eta" name="eta" type="date" value="{{ old('eta',$job->eta?->format('Y-m-d')) }}"></div>
<div class="field"><label for="vessel_voyage">Vessel</label><input id="vessel_voyage" name="vessel_voyage" maxlength="120" value="{{ old('vessel_voyage',$job->vessel_voyage) }}"></div>
<div class="field"><label for="package_count">Quantity</label><input id="package_count" name="package_count" type="number" min="0" max="999999" value="{{ old('package_count',$job->package_count) }}"></div>
<div class="field"><label for="gross_weight">Gross Weight (kg)</label><input id="gross_weight" name="gross_weight" inputmode="decimal" value="{{ old('gross_weight',$job->gross_weight) }}"></div>
<div class="field"><label for="volume">Volume (m³ / CBM)</label><input id="volume" name="volume" inputmode="decimal" value="{{ old('volume',$job->volume) }}"></div>
<div class="field span-2"><label for="cargo_description">Commodity</label><textarea id="cargo_description" name="cargo_description" rows="3" maxlength="2000">{{ old('cargo_description',$job->cargo_description) }}</textarea></div>
</div><div class="form-actions"><a class="button button-secondary" href="{{ route('jobs.show',$job) }}">Batal</a><button class="button button-primary">Simpan operasional</button></div></form></section>
@endsection
