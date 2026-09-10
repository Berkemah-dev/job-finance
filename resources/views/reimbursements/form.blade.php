@extends('layouts.app')
@section('title','Buat Reimbursement')
@section('content')
<div class="page-heading"><div><p class="eyebrow">REIMBURSEMENT</p><h1>Buat reimbursement</h1><p>Catat pengeluaran karyawan yang belum diganti. Status awal: Menunggu persetujuan.</p></div><a class="text-link" href="{{ route('reimbursements.index') }}">Kembali</a></div>
<section class="panel"><form class="data-form" method="POST" action="{{ route('reimbursements.store') }}" enctype="multipart/form-data">@csrf
@if($errors->any())<div class="info-note">{{ $errors->first() }}</div>@endif
<div class="form-grid">
<div class="field"><label for="employee_id">Karyawan <span class="required">*</span></label><select id="employee_id" name="employee_id" required><option value="">Pilih karyawan</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string)old('employee_id')===(string)$employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
<div class="field"><label for="category">Kategori <span class="required">*</span></label><select id="category" name="category" required><option value="">Pilih kategori</option>@foreach(config('jobfinance.reimbursement_categories') as $key=>$settings)<option value="{{ $key }}" @selected(old('category')===$key)>{{ $settings['label'] }}</option>@endforeach</select></div>
<div class="field"><label for="job_id">Job terkait</label><select id="job_id" name="job_id"><option value="">Tidak ada</option>@foreach($jobs as $job)<option value="{{ $job->id }}" @selected((string)old('job_id')===(string)$job->id)>{{ $job->number }} · {{ \Illuminate\Support\Str::limit($job->subject, 45) }}</option>@endforeach</select></div>
<div class="field"><label for="vendor_id">Vendor terkait</label><select id="vendor_id" name="vendor_id"><option value="">Tidak ada</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected((string)old('vendor_id')===(string)$vendor->id)>{{ $vendor->name }}</option>@endforeach</select></div>
<div class="field"><label for="reimbursement_date">Tanggal transaksi <span class="required">*</span></label><input id="reimbursement_date" name="reimbursement_date" type="date" value="{{ old('reimbursement_date',today()->toDateString()) }}" required></div>
<div class="field"><label for="currency">Mata uang <span class="required">*</span></label><select id="currency" name="currency"><option value="IDR">IDR (Rupiah Indonesia)</option><option value="USD" @selected(old('currency')==='USD')>USD (US Dollar)</option></select></div>
<div class="field"><label for="amount">Jumlah <span class="required">*</span></label><input id="amount" name="amount" type="number" min="0.01" max="999999999999.99" step="0.01" value="{{ old('amount') }}" required placeholder="cth: 150000"></div>
<div class="field" id="rate-field" @if(old('currency')==='USD')@else style="display:none"@endif><label for="exchange_rate">Kurs ke IDR</label><input id="exchange_rate" name="exchange_rate" type="number" min="0.01" step="0.01" value="{{ old('exchange_rate') }}" placeholder="Kosongkan = kurs mingguan aktif"></div>
<div class="field span-2"><label for="description">Keterangan <span class="required">*</span></label><input id="description" name="description" value="{{ old('description') }}" maxlength="255" required placeholder="cth: Bensin kunjungan vendor ke Tanjung Priok"></div>
<div class="field span-2"><label for="attachment">Lampiran bukti (PDF/JPG/PNG maks 4 MB)</label><input id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp"></div>
<div class="field span-2"><label for="notes">Catatan tambahan</label><textarea id="notes" name="notes" rows="3" maxlength="1000">{{ old('notes') }}</textarea></div>
</div>
<div class="form-actions"><a class="button button-secondary" href="{{ route('reimbursements.index') }}">Batal</a><button class="button button-primary">Simpan</button></div>
</form></section>
<script>const currencySelect=document.getElementById('currency');const rateField=document.getElementById('rate-field');const rateInput=document.getElementById('exchange_rate');currencySelect.addEventListener('change',function(){if(this.value==='USD'){rateField.style.display='';}else{rateField.style.display='none';rateInput.value='';}});</script>
@endsection