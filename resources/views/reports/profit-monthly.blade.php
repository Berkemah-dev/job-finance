@extends('layouts.app')
@section('title','Profit Bulanan')
@section('content')
<x-menu-banner
    tag="ANALISIS PROFITABILITAS"
    title="Rekap Profit Bulanan"
    description="Tren perolehan profit, omzet penjualan, dan volume pekerjaan dari bulan ke bulan."
    icon="calendar"
    art-title="Pertumbuhan bulanan,"
    art-subtitle="konsisten."
/>

@php
    $months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $monthShort = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $maxProfit = max(1, collect($rows)->max(fn ($row) => abs((float) $row['profit'])) ?: 1);
    $bestMonth = collect($rows)->sortByDesc(fn ($row) => (float) $row['profit'])->first();
@endphp

<section class="panel report-filter-panel">
    <form class="filter-bar" method="GET">
        <div style="min-width: 160px;">
            <select name="year" data-custom-select aria-label="Pilih Tahun">
                @foreach(range(today()->year - 3, today()->year + 1) as $y)
                    <option value="{{ $y }}" @selected($y === $year)>Tahun {{ $y }}</option>
                @endforeach
            </select>
        </div>
        <button class="button button-primary">Terapkan</button>
    </form>
</section>

<div class="report-grid report-grid-4">
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon blue"><x-icon name="briefcase"/></span><div><h2>Total Job</h2><small>Selama {{ $year }}</small></div></div></div><strong class="report-value">{{ $totals['jobs'] }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon green"><x-icon name="wallet"/></span><div><h2>Nilai Jual</h2><small>Total revenue</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($totals['revenue']) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon amber"><x-icon name="briefcase"/></span><div><h2>Total Modal</h2><small>Cost tahunan</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($totals['cost']) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon purple"><x-icon name="chart"/></span><div><h2>Total Profit</h2><small>Margin {{ \App\Support\Money::format($totals['margin']) }}%</small></div></div></div><strong class="report-value {{ (float)$totals['profit'] >= 0 ? 'positive' : 'negative' }}">Rp {{ \App\Support\Money::format($totals['profit']) }}</strong></section>
</div>

<section class="panel report-chart-card report-section">
    <div class="report-chart-head">
        <div><h2>Grafik Profit {{ $year }}</h2><p>Profit bulanan dari Januari sampai Desember.</p></div>
        @if($bestMonth)
            <span class="badge-pill">Tertinggi: {{ $months[$bestMonth['month'] - 1] }} · Rp {{ \App\Support\Money::format($bestMonth['profit']) }}</span>
        @endif
    </div>
    <div class="monthly-chart">
        @foreach($rows as $row)
            @php $height = max(4, round((abs((float) $row['profit']) / $maxProfit) * 100, 2)); @endphp
            <div class="monthly-bar" title="{{ $months[$row['month'] - 1] }}: Rp {{ \App\Support\Money::format($row['profit']) }}">
                <span class="monthly-bar-fill {{ (float)$row['profit'] < 0 ? 'negative' : '' }}" style="--bar: {{ $height }}%"></span>
                <span class="monthly-label">{{ $monthShort[$row['month'] - 1] }}</span>
            </div>
        @endforeach
    </div>
</section>

<section class="panel report-table-panel">
    <div class="table-scroll">
        <table>
            <thead>
                <tr><th>Bulan</th><th class="money">Job</th><th class="money">Temporary</th><th class="money">Modal</th><th class="money">Nilai jual</th><th class="money">Profit</th><th class="money">Margin</th></tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td><strong>{{ $months[$row['month'] - 1] }} {{ $year }}</strong></td>
                        <td class="money">{{ $row['jobs'] }}</td>
                        <td class="money">{{ \App\Support\Money::format($row['temporary']) }}</td>
                        <td class="money">{{ \App\Support\Money::format($row['cost']) }}</td>
                        <td class="money">{{ \App\Support\Money::format($row['revenue']) }}</td>
                        <td class="money" style="font-weight: 600; color: {{ (float)$row['profit'] >= 0 ? '#16a34a' : '#dc2626' }};">{{ \App\Support\Money::format($row['profit']) }}</td>
                        <td class="money">{{ \App\Support\Money::format($row['margin']) }}%</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="summary-total">
                    <td>Total {{ $year }}</td>
                    <td class="money">{{ $totals['jobs'] }}</td>
                    <td class="money">{{ \App\Support\Money::format($totals['temporary']) }}</td>
                    <td class="money">{{ \App\Support\Money::format($totals['cost']) }}</td>
                    <td class="money">{{ \App\Support\Money::format($totals['revenue']) }}</td>
                    <td class="money" style="color: {{ (float)$totals['profit'] >= 0 ? '#16a34a' : '#dc2626' }};">{{ \App\Support\Money::format($totals['profit']) }}</td>
                    <td class="money">{{ \App\Support\Money::format($totals['margin']) }}%</td>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
@endsection
