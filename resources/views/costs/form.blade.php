@extends('layouts.app')
@section('title',$cost->exists?'Edit Biaya':'Tambah Biaya')
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ $job->number }}</p><h1>{{ $cost->exists?'Edit biaya Draft':'Tambah biaya' }}</h1><p>{{ $job->subject }}</p></div><a class="text-link" href="{{ route('jobs.costs.index',$job) }}">Kembali ke biaya</a></div>
<section class="panel form-panel"><form class="data-form" data-cost-form method="POST" action="{{ $cost->exists?route('jobs.costs.update',[$job,$cost]):route('jobs.costs.store',$job) }}">@csrf @if($cost->exists) @method('PUT') @endif
<input type="hidden" name="job_version" value="{{ old('job_version',$job->lock_version) }}"><input type="hidden" name="lock_version" value="{{ old('lock_version',$cost->lock_version ?? 0) }}">
<div class="info-note">Temporary ditagihkan kembali sebesar modal. Provision memiliki modal dan nilai jual; selisihnya menjadi estimasi profit job.</div>
<div class="form-grid">
<div class="field span-2"><label for="description">Uraian biaya <span class="required">*</span></label><input id="description" name="description" value="{{ old('description',$cost->description) }}" maxlength="255" placeholder="Contoh: Pengurusan dokumen pelabuhan" required></div>
<div class="field"><label for="type">Jenis biaya</label><select id="type" name="type" data-cost-type><option value="provision" @selected(old('type',$cost->type)==='provision')>Provision — modal dan nilai jual</option><option value="temporary" @selected(old('type',$cost->type)==='temporary')>Temporary — reimbursement</option></select></div>
<div class="field"><label for="cost_date">Tanggal biaya</label><input id="cost_date" name="cost_date" type="date" value="{{ old('cost_date',$cost->cost_date?->format('Y-m-d') ?? now()->toDateString()) }}" min="{{ $job->job_date->format('Y-m-d') }}" max="{{ now()->toDateString() }}" required></div>
<div class="field"><label for="quantity">Jumlah</label><input id="quantity" name="quantity" type="number" min="0.01" max="999999.99" step="0.01" value="{{ old('quantity',$cost->quantity ?? '1') }}" data-quantity required></div>
<div class="field"><label for="unit">Satuan</label><input id="unit" name="unit" value="{{ old('unit',$cost->unit ?? 'Layanan') }}" maxlength="30" required></div>
<div class="field"><label for="unit_cost">Modal per unit (Rp)</label><input id="unit_cost" name="unit_cost" type="number" min="0" max="999999999.99" step="0.01" value="{{ old('unit_cost',$cost->unit_cost ?? '0') }}" data-unit-cost required></div>
<div class="field"><label for="unit_price">Nilai jual per unit (Rp)</label><input id="unit_price" name="unit_price" type="number" min="0" max="999999999.99" step="0.01" value="{{ old('unit_price',$cost->unit_price ?? '0') }}" data-unit-price required><p class="form-help" data-temporary-help>Untuk Temporary, nilai jual otomatis mengikuti modal.</p></div>
<div class="field"><label for="payee">Penerima / vendor</label><input id="payee" name="payee" value="{{ old('payee',$cost->payee) }}" maxlength="255" placeholder="Nama pihak terkait biaya"></div>
<div class="field"><label for="reference">Nomor bukti / referensi</label><input id="reference" name="reference" value="{{ old('reference',$cost->reference) }}" maxlength="100" placeholder="Nomor kuitansi atau referensi"></div>
<div class="field span-2"><label for="notes">Catatan</label><textarea id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes',$cost->notes) }}</textarea></div>
</div>
<div class="summary-box" aria-live="polite"><div class="summary-row"><span>Total modal</span><strong data-preview-cost>Rp 0,00</strong></div><div class="summary-row"><span>Total jual</span><strong data-preview-total>Rp 0,00</strong></div><div class="summary-row summary-total"><span>Estimasi profit</span><strong data-preview-profit>Rp 0,00</strong></div></div>
<div class="form-actions"><a class="button button-secondary" href="{{ route('jobs.costs.index',$job) }}">Batal</a><button class="button button-primary">Simpan biaya Draft</button></div></form></section>
@endsection
