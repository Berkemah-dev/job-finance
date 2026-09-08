@extends('layouts.app')
@section('title','Edit Job Order')
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ $job->number }}</p><h1>Data operasional</h1><p>Lengkapi informasi pekerjaan dan pengiriman.</p></div><a class="text-link" href="{{ route('jobs.show',$job) }}">Kembali ke job</a></div>
<section class="panel form-panel"><form class="data-form" method="POST" action="{{ route('jobs.update',$job) }}">@csrf @method('PUT')<input type="hidden" name="lock_version" value="{{ old('lock_version',$job->lock_version) }}">
<div class="info-note">Customer dan quotation asal tetap mengikuti penawaran yang disetujui. Data operasional tidak mengubah snapshot penawaran.</div>
<div class="form-grid">
<div class="field span-2"><label for="subject">Nama pekerjaan <span class="required">*</span></label><input id="subject" name="subject" maxlength="255" value="{{ old('subject',$job->subject) }}" required></div>
<div class="field"><label for="job_date">Tanggal job</label><input id="job_date" name="job_date" type="date" value="{{ old('job_date',$job->job_date->format('Y-m-d')) }}" required @readonly($job->status!=='draft')>@if($job->status!=='draft')<p class="form-help">Tanggal dikunci setelah job dibuka.</p>@endif</div>
<div class="field"><label for="expected_completion_date">Target selesai</label><input id="expected_completion_date" name="expected_completion_date" type="date" value="{{ old('expected_completion_date',$job->expected_completion_date?->format('Y-m-d')) }}"></div>
<div class="field"><label for="service_type">Jenis layanan</label><select id="service_type" name="service_type"><option value="">Pilih jenis layanan</option>@foreach(config('operations.service_types') as $value=>$label)<option value="{{ $value }}" @selected(old('service_type',$job->service_type)===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="shipment_reference">Referensi pengiriman</label><input id="shipment_reference" name="shipment_reference" maxlength="100" value="{{ old('shipment_reference',$job->shipment_reference) }}" placeholder="Nomor BL, AWB, atau referensi lainnya"></div>
@foreach(['origin'=>'Asal','destination'=>'Tujuan'] as $field=>$label)<div class="field"><label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" maxlength="255" value="{{ old($field,$job->$field) }}" placeholder="Kota / pelabuhan / lokasi"></div>@endforeach
<div class="field span-2"><label for="cargo_description">Deskripsi muatan</label><textarea id="cargo_description" name="cargo_description" rows="3" maxlength="2000">{{ old('cargo_description',$job->cargo_description) }}</textarea></div>
<div class="field span-2"><label for="operational_notes">Catatan operasional</label><textarea id="operational_notes" name="operational_notes" rows="3" maxlength="5000">{{ old('operational_notes',$job->operational_notes) }}</textarea></div>
</div><div class="form-actions"><a class="button button-secondary" href="{{ route('jobs.show',$job) }}">Batal</a><button class="button button-primary">Simpan operasional</button></div></form></section>
@endsection
