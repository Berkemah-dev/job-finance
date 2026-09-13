@extends('layouts.app')
@section('title','Arus Kas')
@section('content')
<x-menu-banner
    tag="LAPORAN KEUANGAN"
    title="Laporan Arus Kas (Cash Flow)"
    description="Pergerakan arus kas masuk dari customer dan arus kas keluar untuk biaya operasional & investasi."
    icon="wallet"
    art-title="Likuiditas kas,"
    art-subtitle="terjaga optimal."
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
    $paymentValue = abs((float) $customer_payment);
    $costValue = abs((float) $job_cost_capitalization);
    $adjustmentValue = abs((float) $adjustment);
    $otherValue = abs((float) $other);
    $netValue = abs((float) $net);
    $maxValue = max(1, $paymentValue, $costValue, $adjustmentValue, $otherValue, $netValue);
@endphp

<div class="report-grid report-grid-4">
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon green"><x-icon name="wallet"/></span><div><h2>Penerimaan Customer</h2><small>Kas masuk</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($customer_payment) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon amber"><x-icon name="briefcase"/></span><div><h2>Biaya Job</h2><small>Temporary & provision</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($job_cost_capitalization) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon blue"><x-icon name="file"/></span><div><h2>Penyesuaian</h2><small>Kas / bank</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($adjustment) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon purple"><x-icon name="chart"/></span><div><h2>Arus Kas Bersih</h2><small>Net movement</small></div></div></div><strong class="report-value {{ (float)$net >= 0 ? 'positive' : 'negative' }}">Rp {{ \App\Support\Money::format($net) }}</strong></section>
</div>

<section class="panel report-chart-card report-section">
    <div class="report-chart-head"><div><h2>Pergerakan Kas</h2><p>Grafik ringkas kas masuk dan kas keluar periode ini.</p></div></div>
    <div class="report-bars">
        <div class="report-bar-row"><span>Penerimaan customer</span><div class="report-bar-track"><span class="report-bar-fill green" style="--bar: {{ round(($paymentValue / $maxValue) * 100, 2) }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($customer_payment) }}</strong></div>
        <div class="report-bar-row"><span>Biaya job</span><div class="report-bar-track"><span class="report-bar-fill amber" style="--bar: {{ round(($costValue / $maxValue) * 100, 2) }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($job_cost_capitalization) }}</strong></div>
        <div class="report-bar-row"><span>Penyesuaian</span><div class="report-bar-track"><span class="report-bar-fill blue" style="--bar: {{ round(($adjustmentValue / $maxValue) * 100, 2) }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($adjustment) }}</strong></div>
        <div class="report-bar-row"><span>Transaksi lain</span><div class="report-bar-track"><span class="report-bar-fill" style="--bar: {{ round(($otherValue / $maxValue) * 100, 2) }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($other) }}</strong></div>
    </div>
</section>
@endsection
