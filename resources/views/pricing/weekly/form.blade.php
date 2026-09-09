@extends('layouts.app')
@section('title',$weeklyPricing->exists?'Edit Weekly Pricing':'Tambah Weekly Pricing')
@section('content')
<div class="page-heading"><div><p class="eyebrow">PRICING</p><h1>{{ $weeklyPricing->exists?'Edit weekly pricing':'Tambah weekly pricing' }}</h1><p>Kurs ini menjadi default quotation / invoice sampai ada weekly pricing baru.</p></div><a class="text-link" href="{{ route('pricing.weekly.index') }}">Kembali ke daftar</a></div>
<section class="panel form-panel"><form class="data-form" method="POST" action="{{ $weeklyPricing->exists?route('pricing.weekly.update',$weeklyPricing):route('pricing.weekly.store') }}">@csrf @if($weeklyPricing->exists) @method('PUT') @endif
<input type="hidden" name="lock_version" value="{{ old('lock_version',$weeklyPricing->lock_version ?? 0) }}">
<div class="form-grid">
<div class="field"><label for="week">Pekan <span class="required">*</span></label><input id="week" name="week" value="{{ old('week',$weeklyPricing->week) }}" placeholder="cth. W36-2026" required></div>
<div class="field"><label for="effective_date">Tanggal berlaku <span class="required">*</span></label><input id="effective_date" type="date" name="effective_date" value="{{ old('effective_date',$weeklyPricing->effective_date?->format('Y-m-d') ?? today()->format('Y-m-d')) }}" required></div>
<div class="field"><label for="currency">Mata uang <span class="required">*</span></label><select id="currency" name="currency" required>@foreach(config('operations.currencies') as $code=>$label)<option value="{{ $code }}" @selected(old('currency',$weeklyPricing->currency)===$code)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="exchange_rate">Kurs <span class="required">*</span></label><input id="exchange_rate" type="text" inputmode="decimal" name="exchange_rate" value="{{ old('exchange_rate',$weeklyPricing->exists?number_format((float) $weeklyPricing->exchange_rate, 4, '.', ''):'1') }}" required></div>
<div class="field"><label for="service">Service</label><select id="service" name="service"><option value="">Semua service</option>@foreach(config('operations.service_types') as $value=>$label)<option value="{{ $value }}" @selected(old('service',$weeklyPricing->service)===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="is_active">Status</label><select id="is_active" name="is_active"><option value="1" @selected(!$weeklyPricing->exists || $weeklyPricing->is_active)>Aktif</option><option value="0" @selected($weeklyPricing->exists && !$weeklyPricing->is_active)>Nonaktif</option></select></div>
<div class="field span-2"><label for="notes">Catatan</label><textarea id="notes" name="notes" rows="3" maxlength="1000">{{ old('notes',$weeklyPricing->notes) }}</textarea></div>
</div>
<div class="form-actions"><a class="button button-secondary" href="{{ route('pricing.weekly.index') }}">Batal</a><button class="button button-primary">Simpan weekly pricing</button></div></form></section>
@endsection