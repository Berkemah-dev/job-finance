@extends('layouts.app')
@section('title','Buat Reimbursement')
@section('content')
<div class="page-heading"><div><p class="eyebrow">REIMBURSEMENT</p><h1>Buat reimbursement</h1><p>Catat pengeluaran karyawan yang belum diganti. Status awal: Menunggu persetujuan.</p></div><a class="text-link" href="{{ route('reimbursements.index') }}">Kembali</a></div>
<section class="panel"><form class="data-form" method="POST" action="{{ route('reimbursements.store') }}">@csrf
@if($errors->any())<div class="info-note">{{ $errors->first() }}</div>@endif
<div class="form-grid">
<div class="field"><label for="employee_id">Karyawan <span class="required">*</span></label><select id="employee_id" name="employee_id" required><option value="">Pilih karyawan</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string)old('employee_id')===(string)$employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
<div class="field"><label for="category">Kategori <span class="required">*</span></label><select id="category" name="category" required><option value="">Pilih kategori</option>@foreach(config('jobfinance.reimbursement_categories') as $key=>$settings)<option value="{{ $key }}" @selected(old('category')===$key)>{{ $settings['label'] }}</option>@endforeach</select></div>
<div class="field"><label for="reimbursement_date">Tanggal transaksi <span class="required">*</span></label><input id="reimbursement_date" name="reimbursement_date" type="date" value="{{ old('reimbursement_date',today()->toDateString()) }}" required></div>
<div class="field"><label for="amount">Jumlah (IDR) <span class="required">*</span></label><input id="amount" name="amount" type="number" min="0.01" max="999999999999.99" step="0.01" value="{{ old('amount') }}" required placeholder="cth: 150000"></div>
<div class="field span-2"><label for="description">Keterangan <span class="required">*</span></label><input id="description" name="description" value="{{ old('description') }}" maxlength="255" required placeholder="cth: Bensin kunjungan vendor ke Tanjung Priok"></div>
<div class="field span-2"><label for="notes">Catatan tambahan</label><textarea id="notes" name="notes" rows="3" maxlength="1000">{{ old('notes') }}</textarea></div>
</div>
<div class="form-actions"><a class="button button-secondary" href="{{ route('reimbursements.index') }}">Batal</a><button class="button button-primary">Simpan</button></div>
</form></section>
@endsection