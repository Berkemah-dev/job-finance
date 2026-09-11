@extends('layouts.app')
@section('title', 'Dashboard Finance Manager')
@section('content')
<x-menu-banner
    tag="WORKSPACE FINANCE MANAGER"
    title="Profit terlihat jelas."
    description="Bandingkan pendapatan dan profit dari job yang sudah closing."
    action-url="{{ route('reports.profit-per-job') }}"
    action-label="Lihat Analisis"
    action-icon="arrow"
    icon="chart"
    art-title="Profitabilitas bisnis,"
    art-subtitle="terpantau presisi."
    :show-date="true"
/>

@include('dashboard.partials.charts')

<div class="stats-grid">
    <article class="stat-card"><span>Profit Job</span><strong class="stat-number"><small>Rp</small> {{ \App\Support\Money::format($profitBalance) }}</strong><p>Total profit dari job closing.</p></article>
    <article class="stat-card"><span>Pendapatan</span><strong class="stat-number"><small>Rp</small> {{ \App\Support\Money::format($revenueBalance) }}</strong><p>Total pendapatan dari closing.</p></article>
    <article class="stat-card"><span>HPP Job</span><strong class="stat-number"><small>Rp</small> {{ \App\Support\Money::format($cogsBalance) }}</strong><p>Total modal provision.</p></article>
    <article class="stat-card"><span>Kurs Mingguan</span><strong class="stat-number">{{ $weeklyPricing?->currency ?? 'USD' }}</strong><p>{{ $weeklyPricing ? 'Rp ' . \App\Support\Money::format($weeklyPricing->exchange_rate) : 'Belum tersedia' }}</p></article>
</div>

<div class="finance-strip" style="margin-bottom:20px;margin-top:20px">
    <div><span>Job Closed</span><strong>{{ $closedJobs }} Job</strong></div>
    <div><span>Job Open</span><strong>{{ $openJobs }} Job</strong></div>
    <div><span>Piutang Customer</span><strong>Rp {{ \App\Support\Money::format($receivableBalance) }}</strong></div>
    <div><span>Temporary</span><strong>Rp {{ \App\Support\Money::format($temporaryBalance) }}</strong></div>
</div>

@if(isset($widgets['topJobs']) && $widgets['topJobs']->isNotEmpty())
<section class="panel" style="margin-bottom:20px">
    <div class="panel-heading"><div><h2>Job paling menguntungkan</h2><p>Berdasarkan profit closing.</p></div><a class="text-link" href="{{ route('reports.profit-per-job') }}">Profit per Job</a></div>
    <div class="table-scroll"><table><thead><tr><th>Job</th><th>Subject</th><th>Closing</th><th class="money">Profit</th></tr></thead><tbody>@foreach($widgets['topJobs'] as $job)<tr><td><a class="text-link" href="{{ route('jobs.show',$job) }}"><strong>{{ $job->number }}</strong></a></td><td>{{ Str::limit($job->subject,35) }}</td><td>{{ $job->closingSnapshot->closing_date->format('d/m/Y') }}</td><td class="money">Rp {{ \App\Support\Money::format((string) $job->closingSnapshot->profit) }}</td></tr>@endforeach</tbody></table></div>
</section>
@endif

<section class="panel" style="margin-bottom:20px">
    <div class="panel-heading"><div><h2>Pendapatan & profit</h2><p>Performa enam bulan terakhir.</p></div><span class="status-chip">6 bulan</span></div>
    <div class="mini-chart">
        @php $maxRevenue=max(1,$monthlyPerformance->max(fn($row)=>(float)$row['revenue'])); @endphp
        @foreach($monthlyPerformance as $month)
        <div class="bar-item">
            <div class="bar" style="height:{{ max(7,((float)$month['revenue']/$maxRevenue)*100) }}%" title="Pendapatan Rp {{ \App\Support\Money::format($month['revenue']) }} · Profit Rp {{ \App\Support\Money::format($month['profit']) }}"></div>
            <small>{{ $month['label'] }}</small>
        </div>
        @endforeach
    </div>
</section>

<section class="panel">
    <div class="panel-heading"><div><h2>Akses laporan keuangan</h2><p>Gunakan laporan ini untuk analisis keuangan komprehensif.</p></div></div>
    <div class="form-actions" style="padding:18px 23px;display:flex;gap:12px;flex-wrap:wrap">
        <a class="button button-primary" href="{{ route('reports.profit-per-job') }}">Profit per Job</a>
        <a class="button button-secondary" href="{{ route('reports.profit-monthly') }}">Profit Bulanan</a>
        <a class="button button-secondary" href="{{ route('reports.balance-sheet') }}">Neraca</a>
        <a class="button button-secondary" href="{{ route('reports.income-statement') }}">Laba Rugi</a>
    </div>
</section>
@endsection
