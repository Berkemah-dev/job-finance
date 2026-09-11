@extends('layouts.app')
@section('title', 'Dashboard Operational')
@section('content')
<x-menu-banner
    tag="WORKSPACE OPERATIONAL"
    title="Job siap diselesaikan."
    description="Pastikan biaya setiap job sudah lengkap dan final sebelum closing."
    action-url="{{ route('jobs.index') }}"
    action-label="Buka Job Order"
    action-icon="arrow"
    icon="briefcase"
    art-title="Operasional tertib,"
    art-subtitle="eksekusi tepat waktu."
    :show-date="true"
/>

@include('dashboard.partials.charts')

<div class="metric-grid">
    <article class="metric-card"><div class="metric-label"><span>Job Open</span><x-icon name="briefcase"/></div><strong class="metric-value">{{ $costProgress['open'] }}</strong><span class="metric-caption">Sedang berjalan</span></article>
    <article class="metric-card"><div class="metric-label"><span>Belum Final</span><x-icon name="file"/></div><strong class="metric-value">{{ $costProgress['draft'] }}</strong><span class="metric-caption">Perlu dilengkapi</span></article>
    <article class="metric-card"><div class="metric-label"><span>Siap Closing</span><x-icon name="check"/></div><strong class="metric-value">{{ $costProgress['final'] }}</strong><span class="metric-caption">Biaya sudah final</span></article>
</div>

@if(isset($widgets['shipment']))
<div class="section-heading"><h2>Pengiriman berjalan</h2><span class="subtle">Status shipment aktif</span></div>
<section class="panel" style="margin-bottom:20px">
    <div class="filter-bar" style="justify-content:flex-start;border:none;padding:14px 23px;display:flex;flex-wrap:wrap;gap:8px">
        @foreach(config('operations.shipment_statuses') as $value=>$label)
        <span class="badge-pill status-{{ $value }}">{{ $label }} · {{ $widgets['shipment'][$value] ?? 0 }}</span>
        @if(!$loop->last)<span class="text-link">→</span>@endif
        @endforeach
    </div>
</section>

<div class="dashboard-grid" style="margin-bottom:20px">
    <section class="panel">
        <div class="panel-heading"><div><h2>ETD mendatang</h2><p>Estimasi keberangkatan 14 hari ke depan.</p></div><span class="count-badge">{{ $widgets['etdSoon']->count() }}</span></div>
        <div class="table-scroll">
            <table><thead><tr><th>Job</th><th>Estimasi</th><th>Subject</th></tr></thead>
            <tbody>
                @forelse($widgets['etdSoon'] as $job)
                <tr><td><a class="text-link" href="{{ route('jobs.show',$job) }}">{{ $job->number }}</a></td><td>{{ $job->etd ? $job->etd->format('d/m/Y') : '—' }}</td><td>{{ Str::limit($job->subject,35) }}</td></tr>
                @empty<tr><td colspan="3">Tidak ada ETD dalam 14 hari ke depan.</td></tr>
                @endforelse
            </tbody>
            </table>
        </div>
    </section>
    <section class="panel">
        <div class="panel-heading"><div><h2>ETA tiba</h2><p>Estimasi kedatangan 7 hari ke depan.</p></div><span class="count-badge">{{ $widgets['etaSoon']->count() }}</span></div>
        <div class="table-scroll">
            <table><thead><tr><th>Job</th><th>Estimasi</th><th>Subject</th></tr></thead>
            <tbody>
                @forelse($widgets['etaSoon'] as $job)
                <tr><td><a class="text-link" href="{{ route('jobs.show',$job) }}">{{ $job->number }}</a></td><td>{{ $job->eta ? $job->eta->format('d/m/Y') : '—' }}</td><td>{{ Str::limit($job->subject,35) }}</td></tr>
                @empty<tr><td colspan="3">Tidak ada ETA dalam 7 hari ke depan.</td></tr>
                @endforelse
            </tbody>
            </table>
        </div>
    </section>
</div>
@endif

<section class="panel">
    <div class="panel-heading"><div><h2>Job belum final</h2><p>Job yang masih memiliki biaya Draft.</p></div><span class="count-badge">{{ $unfinishedJobs->count() }}</span></div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Job</th><th>Subject</th><th>Status biaya</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($unfinishedJobs as $job)
                <tr>
                    <td><a class="text-link" href="{{ route('jobs.show',$job) }}"><strong>{{ $job->number }}</strong></a></td>
                    <td>{{ $job->subject }}</td>
                    <td><span class="status-badge status-draft">Belum final</span></td>
                    <td><a class="text-link" href="{{ route('jobs.costs.index', $job) }}">Kelola Biaya →</a></td>
                </tr>
                @empty
                <tr><td colspan="4">Semua biaya job sudah final.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
