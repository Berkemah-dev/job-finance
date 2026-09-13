@extends('layouts.app')
@section('title','Profit per Job')
@section('content')
<x-menu-banner
    tag="ANALISIS PROFITABILITAS"
    title="Analisis Profit per Job"
    description="Evaluasi margin keuntungan, modal aktual, dan nilai jual setiap pekerjaan yang telah closed."
    icon="briefcase"
    art-title="Margin tiap job,"
    art-subtitle="maksimal."
/>

@php
    $totalJobs = $rows->count();
    $totalTemporary = $rows->sum(fn ($row) => (float) $row->total_temporary);
    $totalCost = $rows->sum(fn ($row) => (float) $row->total_provision_cost);
    $totalRevenue = $rows->sum(fn ($row) => (float) $row->total_provision_sell);
    $totalProfit = $rows->sum(fn ($row) => (float) $row->profit);
    $totalMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
@endphp

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

<div class="report-grid report-grid-4">
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon blue"><x-icon name="briefcase"/></span><div><h2>Job Closed</h2><small>Periode terpilih</small></div></div></div><strong class="report-value">{{ $totalJobs }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon amber"><x-icon name="wallet"/></span><div><h2>Total Modal</h2><small>Provision cost</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($totalCost) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon green"><x-icon name="chart"/></span><div><h2>Nilai Jual</h2><small>Revenue job</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($totalRevenue) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon purple"><x-icon name="check"/></span><div><h2>Total Profit</h2><small>Margin {{ \App\Support\Money::format($totalMargin) }}%</small></div></div></div><strong class="report-value {{ $totalProfit >= 0 ? 'positive' : 'negative' }}">Rp {{ \App\Support\Money::format($totalProfit) }}</strong></section>
</div>

<section class="panel report-table-panel">
    <div class="table-scroll">
        <table>
            <thead>
                <tr><th>Job</th><th>Customer</th><th class="money">Temporary</th><th class="money">Modal</th><th class="money">Nilai jual</th><th class="money">Profit</th><th class="money">Margin</th></tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><strong>{{ $row->job->number }}</strong></td>
                        <td>{{ $row->customer_snapshot['name'] }}</td>
                        <td class="money">{{ \App\Support\Money::format($row->total_temporary) }}</td>
                        <td class="money">{{ \App\Support\Money::format($row->total_provision_cost) }}</td>
                        <td class="money">{{ \App\Support\Money::format($row->total_provision_sell) }}</td>
                        <td class="money" style="font-weight: 600; color: {{ (float)$row->profit >= 0 ? '#16a34a' : '#dc2626' }};">{{ \App\Support\Money::format($row->profit) }}</td>
                        <td class="money">{{ \App\Support\Money::format($row->margin) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state"><x-icon name="briefcase"/><h3>Belum ada data</h3><p>Belum ada job closed pada periode ini.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
