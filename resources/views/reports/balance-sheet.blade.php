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

<section class="panel report-filter-panel">
    <form class="filter-bar" method="GET">
        <div class="date-filter-group">
            <x-icon name="calendar"/>
            <span style="font-size: 11px; color: #64748b; font-weight: 500;">Per tanggal:</span>
            <input type="date" name="to" value="{{ $to }}" aria-label="Per tanggal" title="Per tanggal">
        </div>
        <button class="button button-primary">Terapkan</button>
    </form>
</section>

@php
    $assetsValue = abs((float) $assets);
    $liabilityValue = abs((float) $liabilities);
    $equityValue = abs((float) $equity);
    $earningValue = abs((float) $earnings);
    $rightTotal = max(1, $liabilityValue + $equityValue + $earningValue);
@endphp

<div class="report-grid report-grid-3">
    <section class="report-card">
        <div class="report-card-head"><div class="report-card-title"><span class="report-icon blue"><x-icon name="wallet"/></span><div><h2>Total Aset</h2><small>Semua aset perusahaan</small></div></div></div>
        <strong class="report-value">Rp {{ \App\Support\Money::format($assets) }}</strong>
    </section>
    <section class="report-card">
        <div class="report-card-head"><div class="report-card-title"><span class="report-icon amber"><x-icon name="file"/></span><div><h2>Total Liabilitas</h2><small>Kewajiban berjalan</small></div></div></div>
        <strong class="report-value">Rp {{ \App\Support\Money::format($liabilities) }}</strong>
    </section>
    <section class="report-card">
        <div class="report-card-head"><div class="report-card-title"><span class="report-icon green"><x-icon name="chart"/></span><div><h2>Ekuitas & Laba</h2><small>Modal ditambah laba berjalan</small></div></div></div>
        <strong class="report-value">Rp {{ \App\Support\Money::format(\App\Support\Money::decimal($equity)->plus($earnings)) }}</strong>
    </section>
</div>

<section class="panel report-chart-card report-section">
    <div class="report-chart-head">
        <div><h2>Komposisi Liabilitas & Ekuitas</h2><p>Total sisi kanan neraca: Rp {{ \App\Support\Money::format($liabilities_equity) }}</p></div>
    </div>
    <div class="report-bars">
        <div class="report-bar-row"><span>Liabilitas</span><div class="report-bar-track"><span class="report-bar-fill amber" style="--bar: {{ round(($liabilityValue / $rightTotal) * 100, 2) }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($liabilities) }}</strong></div>
        <div class="report-bar-row"><span>Ekuitas Modal</span><div class="report-bar-track"><span class="report-bar-fill blue" style="--bar: {{ round(($equityValue / $rightTotal) * 100, 2) }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($equity) }}</strong></div>
        <div class="report-bar-row"><span>Laba Berjalan</span><div class="report-bar-track"><span class="report-bar-fill green" style="--bar: {{ round(($earningValue / $rightTotal) * 100, 2) }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($earnings) }}</strong></div>
    </div>
</section>
@endsection
