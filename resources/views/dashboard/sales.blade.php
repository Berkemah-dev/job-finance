@extends('layouts.app')
@section('title', 'Dashboard Sales')
@section('content')
<x-menu-banner
    tag="WORKSPACE SALES"
    title="Quotation lebih cepat."
    description="Buat penawaran, pantau status quote, dan gunakan kurs terbaru."
    action-url="{{ route('quotations.create') }}"
    action-label="Buat Quotation"
    action-icon="arrow"
    icon="file"
    art-title="Peluang bisnis,"
    art-subtitle="jadi transaksi."
    :show-date="true"
/>

@include('dashboard.partials.charts')

<div class="stats-grid">
    <article class="stat-card"><span>Quotation Draft</span><strong class="stat-number">{{ $widgets['quotes']['draft'] ?? 0 }}</strong><p>Belum diajukan.</p></article>
    <article class="stat-card"><span>Menunggu Persetujuan</span><strong class="stat-number">{{ ($widgets['quotes']['submitted'] ?? 0) + ($widgets['quotes']['revision'] ?? 0) }}</strong><p>Perlu ditindaklanjuti.</p></article>
    <article class="stat-card"><span>Quotation Disetujui</span><strong class="stat-number">{{ $widgets['quotes']['approved'] ?? 0 }}</strong><p>Siap dikonversi ke Job.</p></article>
    <article class="stat-card"><span>Kurs Mingguan</span><strong class="stat-number">{{ $weeklyPricing?->currency ?? 'USD' }}</strong><p>{{ $weeklyPricing ? 'Rp ' . \App\Support\Money::format($weeklyPricing->exchange_rate) : 'Belum tersedia' }}</p></article>
</div>

<section class="panel" style="margin-bottom:20px;margin-top:20px">
    <div class="panel-heading"><div><h2>Pipeline quotation</h2><p>Posisi quotation yang sedang berjalan ({{ $widgets['quotes30d'] ?? 0 }} penawaran 30 hari terakhir).</p></div></div>
    <div style="padding:0 22px 20px"><div class="progress-track"><i style="width:{{ min(100, (($widgets['quotes']['approved'] ?? 0) + ($widgets['quotes']['converted'] ?? 0))*12) }}%"></i></div></div>
</section>

<section class="panel" style="margin-bottom:20px">
    <div class="panel-heading"><div><h2>Funnel quotation</h2><p>Pergerakan quotation dari draft sampai menjadi job.</p></div></div>
    <div class="mini-chart">
        <div class="bar-item"><div class="bar" style="height:{{ max(7,($widgets['quotes']['draft'] ?? 0)*15) }}%"></div><small>Draft<br>{{ $widgets['quotes']['draft'] ?? 0 }}</small></div>
        <div class="bar-item"><div class="bar" style="height:{{ max(7,($widgets['quotes']['submitted'] ?? 0)*15) }}%"></div><small>Diajukan<br>{{ $widgets['quotes']['submitted'] ?? 0 }}</small></div>
        <div class="bar-item"><div class="bar" style="height:{{ max(7,($widgets['quotes']['approved'] ?? 0)*15) }}%"></div><small>Disetujui<br>{{ $widgets['quotes']['approved'] ?? 0 }}</small></div>
        <div class="bar-item"><div class="bar" style="height:{{ max(7,($widgets['quotes']['converted'] ?? 0)*15) }}%"></div><small>Job<br>{{ $widgets['quotes']['converted'] ?? 0 }}</small></div>
    </div>
</section>

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
@endif

<section class="panel">
    <div class="panel-heading"><h2>Quotation saya</h2><a class="text-link" href="{{ route('quotations.index') }}">Lihat semua</a></div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Nomor</th><th>Subject</th><th>Customer</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($widgets['myQuotes'] ?? [] as $quote)
                <tr>
                    <td><a class="text-link" href="{{ route('quotations.show', $quote) }}">{{ $quote->number }}</a></td>
                    <td>{{ $quote->subject }}</td>
                    <td>{{ $quote->customer_snapshot['name'] ?? '—' }}</td>
                    <td><span class="status-badge status-{{ $quote->status->value }}">{{ $quote->status->label() }}</span></td>
                </tr>
                @empty
                <tr><td colspan="4">Belum ada quotation.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
