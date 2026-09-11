@extends('layouts.app')
@section('title','Neraca')
@section('content')
<div class="page-heading"><div><p class="eyebrow">LAPORAN KEUANGAN</p><h1>Neraca</h1><p>Posisi aset, liabilitas, dan ekuitas.</p></div><span class="date-chip"><x-icon name="calendar"/>{{ now()->locale('id')->translatedFormat('d F Y') }}</span></div>
<x-menu-banner
    tag="LAPORAN KEUANGAN"
    title="Laporan Neraca (Balance Sheet)"
    description="Posisi kesehatan finansial perusahaan mencakup total aset, liabilitas, dan ekuitas modal."
    icon="chart"
    art-title="Posisi keuangan,"
    art-subtitle="kokoh & sehat."
/>
<form class="filter-bar" method="GET"><label>Per tanggal</label><input type="date" name="to" value="{{ $to }}"><button class="button button-secondary">Terapkan</button></form><div class="report-grid"><section class="panel report-card"><h2>Aset</h2><strong>Rp {{ \App\Support\Money::format($assets) }}</strong></section><section class="panel report-card"><h2>Liabilitas</h2><strong>Rp {{ \App\Support\Money::format($liabilities) }}</strong><div class="summary-row"><span>Ekuitas</span><b>Rp {{ \App\Support\Money::format($equity) }}</b></div><div class="summary-row"><span>Laba berjalan</span><b>Rp {{ \App\Support\Money::format($earnings) }}</b></div><div class="summary-row summary-total"><span>Liabilitas + Ekuitas</span><b>Rp {{ \App\Support\Money::format($liabilities_equity) }}</b></div></section></div>@endsection
