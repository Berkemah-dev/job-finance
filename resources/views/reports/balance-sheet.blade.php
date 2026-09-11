@extends('layouts.app')
@section('title','Neraca')
@section('content')
<x-menu-banner
    tag="LAPORAN KEUANGAN"
    title="Laporan Neraca (Balance Sheet)"
    description="Posisi kesehatan finansial perusahaan mencakup total aset, liabilitas, dan ekuitas modal."
    icon="chart"
    art-title="Posisi keuangan,"
    art-subtitle="kokoh & sehat."
/>
<form class="filter-bar" method="GET"><div class="date-filter-group"><x-icon name="calendar"/><span style="font-size: 11px; color: #64748b; font-weight: 500;">Per tanggal:</span><input type="date" name="to" value="{{ $to }}" aria-label="Per tanggal" title="Per tanggal"></div><button class="button button-secondary">Terapkan</button></form><div class="report-grid"><section class="panel report-card"><h2>Aset</h2><strong>Rp {{ \App\Support\Money::format($assets) }}</strong></section><section class="panel report-card"><h2>Liabilitas</h2><strong>Rp {{ \App\Support\Money::format($liabilities) }}</strong><div class="summary-row"><span>Ekuitas</span><b>Rp {{ \App\Support\Money::format($equity) }}</b></div><div class="summary-row"><span>Laba berjalan</span><b>Rp {{ \App\Support\Money::format($earnings) }}</b></div><div class="summary-row summary-total"><span>Liabilitas + Ekuitas</span><b>Rp {{ \App\Support\Money::format($liabilities_equity) }}</b></div></section></div>@endsection
