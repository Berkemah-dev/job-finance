@extends('layouts.app')
@section('title','Laba Rugi')
@section('content')
<x-menu-banner
    tag="LAPORAN KEUANGAN"
    title="Laporan Laba Rugi (Income Statement)"
    description="Performa pendapatan jasa, beban pokok penjualan (HPP), biaya operasional, dan laba bersih."
    icon="chart"
    art-title="Kinerja laba,"
    art-subtitle="tumbuh positif."
/>

<section class="panel report-filter-panel">
    <form class="filter-bar" method="GET">
        <div class="date-filter-group">
            <x-icon name="calendar"/>
            <input type="date" name="from" value="{{ $from }}" aria-label="Dari tanggal" title="Dari tanggal">
            <span class="date-sep">→</span>
            <input type="date" name="to" value="{{ $to }}" aria-label="Sampai tanggal" title="Sampai tanggal">
        </div>
        <button class="button button-primary">Terapkan</button>
    </form>
</section>

@php
    $revenueValue = abs((float) $revenue);
    $cogsValue = abs((float) $cogs);
    $expenseValue = abs((float) $expense);
    $grossValue = abs((float) $gross);
    $netValue = abs((float) $net);
    $maxValue = max(1, $revenueValue, $cogsValue, $expenseValue, $grossValue, $netValue);
@endphp

<div class="report-grid report-grid-4">
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon green"><x-icon name="wallet"/></span><div><h2>Pendapatan Jasa</h2><small>Total omzet periode</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($revenue) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon amber"><x-icon name="briefcase"/></span><div><h2>HPP Job</h2><small>Modal pekerjaan</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($cogs) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon blue"><x-icon name="chart"/></span><div><h2>Laba Kotor</h2><small>Sebelum beban operasional</small></div></div></div><strong class="report-value {{ (float)$gross >= 0 ? 'positive' : 'negative' }}">Rp {{ \App\Support\Money::format($gross) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon purple"><x-icon name="check"/></span><div><h2>Laba Bersih</h2><small>Hasil akhir periode</small></div></div></div><strong class="report-value {{ (float)$net >= 0 ? 'positive' : 'negative' }}">Rp {{ \App\Support\Money::format($net) }}</strong></section>
</div>

<section class="panel report-chart-card report-section">
    <div class="report-chart-head"><div><h2>Ringkasan Laba Rugi</h2><p>Komposisi pendapatan, modal, beban, dan laba bersih.</p></div></div>
    <div class="report-bars">
        <div class="report-bar-row"><span>Pendapatan jasa</span><div class="report-bar-track"><span class="report-bar-fill green" style="--bar: {{ round(($revenueValue / $maxValue) * 100, 2) }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($revenue) }}</strong></div>
        <div class="report-bar-row"><span>HPP job</span><div class="report-bar-track"><span class="report-bar-fill amber" style="--bar: {{ round(($cogsValue / $maxValue) * 100, 2) }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($cogs) }}</strong></div>
        <div class="report-bar-row"><span>Beban operasional</span><div class="report-bar-track"><span class="report-bar-fill" style="--bar: {{ round(($expenseValue / $maxValue) * 100, 2) }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($expense) }}</strong></div>
        <div class="report-bar-row"><span>Laba bersih</span><div class="report-bar-track"><span class="report-bar-fill blue" style="--bar: {{ round(($netValue / $maxValue) * 100, 2) }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($net) }}</strong></div>
    </div>
</section>
@endsection
