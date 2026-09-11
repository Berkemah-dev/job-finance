@extends('layouts.app')
@section('title','Edit Job Order')
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ $job->number }}</p><h1>Data operasional</h1><p>Lengkapi informasi pekerjaan, pihak terkait, dan pengiriman.</p></div><a class="text-link" href="{{ route('jobs.show',$job) }}">Kembali ke job</a></div>
<section class="panel form-panel"><form class="data-form" method="POST" action="{{ route('jobs.update',$job) }}">@csrf @method('PUT')<input type="hidden" name="lock_version" value="{{ old('lock_version',$job->lock_version) }}">
<div class="info-note">Customer dan quotation asal tetap mengikuti penawaran yang disetujui. Data operasional tidak mengubah snapshot penawaran.</div>
<div class="form-grid">
<div class="field span-2"><label for="subject">Nama pekerjaan <span class="required">*</span></label><input id="subject" name="subject" maxlength="255" value="{{ old('subject',$job->subject) }}" required></div>
<div class="field"><label for="job_date">Tanggal job</label><input id="job_date" name="job_date" type="date" value="{{ old('job_date',$job->job_date->format('Y-m-d')) }}" required @readonly($job->status!=='draft')>@if($job->status!=='draft')<p class="form-help">Tanggal dikunci setelah job dibuka.</p>@endif</div>
<div class="field"><label for="expected_completion_date">Target selesai</label><input id="expected_completion_date" name="expected_completion_date" type="date" value="{{ old('expected_completion_date',$job->expected_completion_date?->format('Y-m-d')) }}"></div>
<div class="field"><label for="service_type">Jenis layanan</label><select id="service_type" name="service_type"><option value="">Pilih jenis layanan</option>@foreach(config('operations.service_types') as $value=>$label)<option value="{{ $value }}" @selected(old('service_type',$job->service_type)===$value)>{{ $label }}</option>@endforeach</select></div>
</div>
<div class="form-section-heading"><h2>Pihak terkait</h2><p>Pengirim dan penerima muatan pada job ini.</p></div>
<div class="form-grid">
<div class="field"><label for="shipper_name">Pengirim (Shipper)</label><input id="shipper_name" name="shipper_name" maxlength="160" value="{{ old('shipper_name',$job->shipper_name) }}"></div>
<div class="field"><label for="consignee_name">Penerima (Consignee)</label><input id="consignee_name" name="consignee_name" maxlength="160" value="{{ old('consignee_name',$job->consignee_name) }}"></div>
<div class="field"><label for="shipper_address">Alamat pengirim</label><textarea id="shipper_address" name="shipper_address" rows="2" maxlength="5000">{{ old('shipper_address',$job->shipper_address) }}</textarea></div>
<div class="field"><label for="consignee_address">Alamat penerima</label><textarea id="consignee_address" name="consignee_address" rows="2" maxlength="5000">{{ old('consignee_address',$job->consignee_address) }}</textarea></div>
</div>
<div class="form-section-heading"><h2>Rute & transportasi</h2><p>Informasi perjalanan dan sarana pengangkut (Loading, Discharge, ETD, ETA, Vessel).</p></div>
<div class="form-grid">
<div class="field"><label for="pol">Loading / Pelabuhan Muat (POL)</label><input id="pol" name="pol" maxlength="120" value="{{ old('pol',$job->pol) }}" placeholder="Pelabuhan keberangkatan"></div>
<div class="field"><label for="pod">Discharge / Pelabuhan Bongkar (POD)</label><input id="pod" name="pod" maxlength="120" value="{{ old('pod',$job->pod) }}" placeholder="Pelabuhan tujuan"></div>
<div class="field"><label for="etd">ETD (Perkiraan Berangkat)</label><input id="etd" name="etd" type="date" value="{{ old('etd',$job->etd?->format('Y-m-d')) }}"></div>
<div class="field"><label for="eta">ETA (Perkiraan Tiba)</label><input id="eta" name="eta" type="date" value="{{ old('eta',$job->eta?->format('Y-m-d')) }}"></div>
<div class="field"><label for="vessel_voyage">Vessel (Nama Kapal & Voyage)</label><input id="vessel_voyage" name="vessel_voyage" maxlength="120" value="{{ old('vessel_voyage',$job->vessel_voyage) }}" placeholder="contoh: KMTC JAKARTA V.2401N"></div>
<div class="field"><label for="flight_number">Nomor penerbangan</label><input id="flight_number" name="flight_number" maxlength="60" value="{{ old('flight_number',$job->flight_number) }}" placeholder="Untuk layanan udara"></div>
</div>
<div class="form-section-heading"><h2>Referensi dokumen</h2><p>Nomor dokumen pabean dan konosemen pengiriman.</p></div>
<div class="form-grid">
<div class="field"><label for="booking_reference">No. AJU (Nomor Pengajuan Bea Cukai)</label><input id="booking_reference" name="booking_reference" maxlength="60" value="{{ old('booking_reference',$job->booking_reference) }}" placeholder="contoh: 000020-012345-20260911-001234"></div>
<div class="field"><label for="hbl_number">No. HBL (House Bill of Lading)</label><input id="hbl_number" name="hbl_number" maxlength="60" value="{{ old('hbl_number',$job->hbl_number) }}" placeholder="Nomor HBL forwarder"></div>
<div class="field"><label for="bl_number">No. MBL (Master Bill of Lading)</label><input id="bl_number" name="bl_number" maxlength="60" value="{{ old('bl_number',$job->bl_number) }}" placeholder="Nomor MBL dari pelayaran"></div>
<div class="field"><label for="awb_number">Nomor AWB</label><input id="awb_number" name="awb_number" maxlength="60" value="{{ old('awb_number',$job->awb_number) }}" placeholder="Master Air Waybill"></div>
<div class="field"><label for="hawb_number">Nomor HAWB</label><input id="hawb_number" name="hawb_number" maxlength="60" value="{{ old('hawb_number',$job->hawb_number) }}" placeholder="House Air Waybill"></div>
<div class="field"><label for="shipment_reference">Referensi pengiriman</label><input id="shipment_reference" name="shipment_reference" maxlength="100" value="{{ old('shipment_reference',$job->shipment_reference) }}" placeholder="Referensi internal"></div>
</div>
<div class="form-section-heading"><h2>Muatan</h2><p>Ringkasan komoditas, jumlah paket, berat kotor, dan volume.</p></div>
<div class="form-grid">
<div class="field span-2"><label for="cargo_description">Commodity / Deskripsi Muatan</label><textarea id="cargo_description" name="cargo_description" rows="3" maxlength="2000" placeholder="Jenis komoditas / nama barang">{{ old('cargo_description',$job->cargo_description) }}</textarea></div>
<div class="field"><label for="package_count">Quantity / Jumlah paket (Box/Pcs)</label><input id="package_count" name="package_count" type="number" min="0" max="999999" value="{{ old('package_count',$job->package_count) }}" placeholder="contoh: 12"></div>
<div class="field"><label for="container_type">Jenis kontainer</label><select id="container_type" name="container_type"><option value="">Pilih kontainer</option>@foreach(config('operations.container_types') as $value=>$label)<option value="{{ $value }}" @selected(old('container_type',$job->container_type)===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="gross_weight">Gross Weight (kg)</label><input id="gross_weight" name="gross_weight" inputmode="decimal" value="{{ old('gross_weight',$job->gross_weight) }}" placeholder="contoh: 13.00"><small>Dalam satuan kilogram (kg).</small></div>
<div class="field"><label for="volume">Volume (m³ / CBM)</label><input id="volume" name="volume" inputmode="decimal" value="{{ old('volume',$job->volume) }}" placeholder="contoh: 64.00"><small>Dalam satuan cubic meter (cbm).</small></div>
</div>
<div class="form-section-heading"><h2>Penanggung jawab</h2><p>Sales dan customer service yang menangani job ini.</p></div>
<div class="form-grid">
<div class="field"><label for="sales_id">Sales</label><select id="sales_id" name="sales_id"><option value="">Pilih sales</option>@foreach($assignees as $user)<option value="{{ $user->id }}" @selected((int) old('sales_id',$job->sales_id)===$user->id)>{{ $user->name }}</option>@endforeach</select></div>
<div class="field"><label for="cs_id">Customer service</label><select id="cs_id" name="cs_id"><option value="">Pilih CS</option>@foreach($assignees as $user)<option value="{{ $user->id }}" @selected((int) old('cs_id',$job->cs_id)===$user->id)>{{ $user->name }}</option>@endforeach</select></div>
<div class="field span-2"><label for="operational_notes">Catatan operasional</label><textarea id="operational_notes" name="operational_notes" rows="3" maxlength="5000">{{ old('operational_notes',$job->operational_notes) }}</textarea></div>
</div><div class="form-actions"><a class="button button-secondary" href="{{ route('jobs.show',$job) }}">Batal</a><button class="button button-primary">Simpan operasional</button></div></form></section>
@endsection