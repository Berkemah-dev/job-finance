@extends('layouts.app')
@section('title','Reimbursement '.$reimbursement->number)
@section('content')
@php $statusLabels=['pending'=>'Menunggu','approved'=>'Disetujui','paid'=>'Dibayar','rejected'=>'Ditolak']; $journal=$reimbursement->journals->first(); @endphp
<div class="page-heading"><div><p class="eyebrow">REIMBURSEMENT</p><h1>{{ $reimbursement->number }} <span class="status-badge status-{{ $reimbursement->status }}">{{ $statusLabels[$reimbursement->status] ?? $reimbursement->status }}</span></h1><p>{{ $reimbursement->description }}</p></div><a class="text-link" href="{{ route('reimbursements.index') }}">Kembali ke daftar</a></div>
<section class="panel"><div class="cost-summary-body"><div class="stats-grid"><div class="stat-card"><p>Jumlah</p><strong>{{ $reimbursement->currency }} {{ \App\Support\Money::format($reimbursement->amount) }}</strong></div><div class="stat-card"><p>Kategori</p><strong>{{ config('jobfinance.reimbursement_categories.'.$reimbursement->category.'.label', $reimbursement->category) }}</strong></div><div class="stat-card"><p>Karyawan</p><strong>{{ $reimbursement->employee->name }}</strong></div><div class="stat-card"><p>Tanggal transaksi</p><strong>{{ $reimbursement->reimbursement_date->format('d/m/Y') }}</strong></div></div></div>
<div class="cost-summary-body"><div class="form-grid">
<div class="field"><label>Dibuat oleh</label><div class="form-help">{{ $reimbursement->createdBy->name }} · {{ $reimbursement->created_at->format('d/m/Y H:i') }}</div></div>
@if($reimbursement->job)<div class="field"><label>Job terkait</label><div class="form-help"><a class="text-link" href="{{ route('jobs.show',$reimbursement->job) }}">{{ $reimbursement->job->number }}</a> · {{ $reimbursement->job->subject }}</div></div>@endif
@if($reimbursement->vendor)<div class="field"><label>Vendor terkait</label><div class="form-help">{{ $reimbursement->vendor->name }}</div></div>@endif
@if($reimbursement->currency!=='IDR')<div class="field"><label>Nilai Rupiah</label><div class="form-help">Rp {{ \App\Support\Money::format(\App\Support\Money::decimal($reimbursement->amount)->multipliedBy(\App\Support\Money::decimal($reimbursement->exchange_rate))) }} @ kurs {{ $reimbursement->exchange_rate }}</div></div>@endif
@if($reimbursement->attachment_path)<div class="field"><label>Lampiran</label><div class="form-help"><a class="text-link" href="{{ route('reimbursements.attachment',$reimbursement) }}">Unduh {{ $reimbursement->attachment_name }}</a></div></div>@endif
@if($reimbursement->reviewed_at)<div class="field"><label>Diproses oleh</label><div class="form-help">{{ $reimbursement->reviewedBy?->name }} · {{ $reimbursement->reviewed_at->format('d/m/Y H:i') }} {{ $reimbursement->status==='approved' ? 'disetujui' : 'ditolak' }}</div></div>@endif
@if($reimbursement->paid_at)<div class="field"><label>Dibayar</label><div class="form-help">{{ $reimbursement->paid_date->format('d/m/Y') }} · {{ $reimbursement->fundingAccount?->name }} @if($reimbursement->payment_reference) · Ref {{ $reimbursement->payment_reference }} @endif</div></div>@endif
@if($journal)<div class="field"><label>Jurnal</label><div class="form-help"><a class="text-link" href="{{ route('journals.show',$journal) }}">{{ $journal->number }}</a> · {{ $journal->journal_date->format('d/m/Y') }}</div></div>@endif
</div>
@if($reimbursement->notes)<div class="cost-progress"><span>Riwayat catatan</span><p style="margin:0">{{ $reimbursement->notes }}</p></div>@endif
</div>
@if($reimbursement->status==='pending')
<div class="cost-summary-body"><p class="panel-note">Reimbursement ini menunggu persetujuan finance.</p>
<form class="transition-form" method="POST" action="{{ route('reimbursements.approve',$reimbursement) }}">@csrf<input type="hidden" name="lock_version" value="{{ $reimbursement->lock_version }}"><div class="field"><label for="approve-notes">Catatan persetujuan</label><textarea id="approve-notes" name="notes" rows="2" maxlength="1000" placeholder="Opsional"></textarea></div><div class="action-group"><button class="button button-primary">Setujui</button></div></form>
<form class="transition-form" method="POST" action="{{ route('reimbursements.reject',$reimbursement) }}">@csrf<input type="hidden" name="lock_version" value="{{ $reimbursement->lock_version }}"><div class="field"><label for="reject-notes">Alasan penolakan</label><textarea id="reject-notes" name="notes" rows="2" maxlength="1000" placeholder="Wajib diisi sesuai kebijakan"></textarea></div><div class="action-group"><button class="button button-danger">Tolak</button></div></form></div>
@elseif($reimbursement->status==='approved')
<div class="cost-summary-body"><p class="panel-note">Reimbursement disetujui. Pencatatan pembayaran akan membuat jurnal beban yang seimbang.</p>
<form class="transition-form" method="POST" action="{{ route('reimbursements.pay',$reimbursement) }}">@csrf<input type="hidden" name="lock_version" value="{{ $reimbursement->lock_version }}"><div class="form-grid">
<div class="field"><label for="paid_date">Tanggal bayar <span class="required">*</span></label><input id="paid_date" name="paid_date" type="date" value="{{ old('paid_date',today()->toDateString()) }}" required></div>
<div class="field"><label for="funding_account">Sumber dana <span class="required">*</span></label><select id="funding_account" name="funding_account" required><option value="bank">Bank</option><option value="cash">Kas</option></select></div>
<div class="field"><label for="reference">Referensi pembayaran</label><input id="reference" name="reference" maxlength="100" placeholder="cth: KMK-001"></div>
<div class="field"><label for="pay-notes">Catatan pembayaran</label><textarea id="pay-notes" name="notes" rows="2" maxlength="1000"></textarea></div>
</div><div class="action-group"><button class="button button-primary">Catat pembayaran</button></div></form></div>
@elseif($reimbursement->status==='rejected')
<div class="cost-summary-body"><p class="panel-note">Reimbursement ditolak. Jika sesuai prosedur, buat reimbursement baru dengan koreksi.</p><a class="text-link" href="{{ route('reimbursements.create') }}">Buat reimbursement baru</a></div>
@endif
</section>
@endsection