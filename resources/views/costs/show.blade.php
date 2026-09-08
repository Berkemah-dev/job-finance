@extends('layouts.app')
@section('title','Detail Biaya')
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ $job->number }} · {{ config('operations.job_statuses.'.$job->status) }}</p><h1>{{ $cost->number }}</h1><p>{{ $cost->description }}</p></div><a class="text-link" href="{{ route('jobs.costs.index',$job) }}">Kembali ke biaya</a></div>
@if($cost->status==='final')<div class="info-note">Biaya sudah Final dan terkunci. Perubahan langsung serta penghapusan tidak diperbolehkan.</div>@endif
<section class="panel"><div class="panel-heading"><h2>Rincian biaya aktual</h2><span class="status-badge status-{{ $cost->status }}">{{ config('operations.cost_statuses.'.$cost->status) }}</span></div>
<dl class="detail-grid">
<div><dt>Jenis biaya</dt><dd>{{ ucfirst($cost->type) }}</dd></div><div><dt>Tanggal biaya</dt><dd>{{ $cost->cost_date->format('d/m/Y') }}</dd></div><div><dt>Jumlah / Satuan</dt><dd>{{ \App\Support\Money::format($cost->quantity) }} {{ $cost->unit }}</dd></div>
<div><dt>Modal per unit</dt><dd>Rp {{ \App\Support\Money::format($cost->unit_cost) }}</dd></div><div><dt>Nilai jual per unit</dt><dd>Rp {{ \App\Support\Money::format($cost->unit_price) }}</dd></div><div><dt>Penerima / vendor</dt><dd>{{ $cost->payee ?? '—' }}</dd></div>
<div><dt>Nomor bukti / referensi</dt><dd>{{ $cost->reference ?? '—' }}</dd></div><div><dt>Dibuat oleh</dt><dd>{{ $cost->creator?->name }}<br>{{ $cost->created_at->format('d/m/Y H:i') }}</dd></div><div><dt>Finalisasi</dt><dd>{{ $cost->finalizer?->name ?? 'Belum difinalisasi' }}<br>{{ $cost->finalized_at?->format('d/m/Y H:i') }}</dd></div>
</dl>
@if($cost->notes)<div class="detail-notes"><strong>Catatan</strong><br>{{ $cost->notes }}</div>@endif
<div class="summary-box"><div class="summary-row"><span>Total modal</span><strong>Rp {{ \App\Support\Money::format($cost->total_cost) }}</strong></div><div class="summary-row summary-total"><span>Total jual</span><strong>Rp {{ \App\Support\Money::format($cost->total_price) }}</strong></div></div>
</section>
<div class="quote-actions">@can('update',$cost)<a class="button button-secondary" href="{{ route('jobs.costs.edit',[$job,$cost]) }}">Edit biaya Draft</a>@endcan
@can('finalize',$cost)<form method="POST" action="{{ route('jobs.costs.finalize',[$job,$cost]) }}" data-confirm="Finalisasi biaya ini? Setelah Final, biaya tidak dapat diedit atau dihapus langsung.">@csrf<input type="hidden" name="job_version" value="{{ $job->lock_version }}"><input type="hidden" name="lock_version" value="{{ $cost->lock_version }}"><button class="button button-primary">Finalisasi biaya</button></form>@endcan
@can('delete',$cost)<form method="POST" action="{{ route('jobs.costs.destroy',[$job,$cost]) }}" data-confirm="Hapus biaya Draft ini dari perhitungan job?">@csrf @method('DELETE')<input type="hidden" name="job_version" value="{{ $job->lock_version }}"><input type="hidden" name="lock_version" value="{{ $cost->lock_version }}"><button class="button button-danger">Hapus biaya Draft</button></form>@endcan
</div>
@endsection
