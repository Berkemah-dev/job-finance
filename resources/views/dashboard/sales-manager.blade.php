@extends('layouts.app')
@section('title', 'Dashboard Sales Manager')
@section('content')
<x-menu-banner
    tag="WORKSPACE SALES MANAGER"
    title="Persetujuan & Performa Sales."
    description="Review draft quote dari tim sales dan pantau pergerakan penawaran."
    action-url="{{ route('quotations.create') }}"
    action-label="Buat Quotation"
    action-icon="arrow"
    icon="file"
    art-title="Target penjualan,"
    art-subtitle="tercapai maksimal."
    :show-date="true"
/>

@include('dashboard.partials.charts')

<div class="stats-grid">
    <article class="stat-card"><span>Menunggu Persetujuan</span><strong class="stat-number">{{ isset($widgets['submittedQuotes']) ? $widgets['submittedQuotes']->count() : 0 }}</strong><p>Draft quote perlu diapprove.</p></article>
    <article class="stat-card"><span>Quotation Disetujui</span><strong class="stat-number">{{ $widgets['quotes']['approved'] ?? 0 }}</strong><p>Siap dikonversi ke Job.</p></article>
    <article class="stat-card"><span>Quotation Dikonversi</span><strong class="stat-number">{{ $widgets['quotes']['converted'] ?? 0 }}</strong><p>Sudah menjadi Job Order.</p></article>
    <article class="stat-card"><span>Kurs Mingguan</span><strong class="stat-number">{{ $weeklyPricing?->currency ?? 'USD' }}</strong><p>{{ $weeklyPricing ? 'Rp ' . \App\Support\Money::format($weeklyPricing->exchange_rate) : 'Belum tersedia' }}</p></article>
</div>

<section class="panel" style="margin-bottom:20px;margin-top:20px">
    <div class="panel-heading">
        <div><h2>Draft quote menunggu persetujuan</h2><p>Quotation yang diajukan oleh Sales dan perlu diapprove.</p></div>
        <span class="count-badge">{{ isset($widgets['submittedQuotes']) ? $widgets['submittedQuotes']->count() : 0 }}</span>
    </div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Nomor</th><th>Subject</th><th>Customer</th><th>Sales</th><th>Tanggal</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($widgets['submittedQuotes'] ?? [] as $quote)
                <tr>
                    <td><a class="text-link" href="{{ route('quotations.show', $quote) }}"><strong>{{ $quote->number }}</strong></a></td>
                    <td>{{ Str::limit($quote->subject, 35) }}</td>
                    <td>{{ $quote->customer_snapshot['name'] ?? $quote->customer?->name ?? '—' }}</td>
                    <td>{{ $quote->creator?->name ?? '—' }}</td>
                    <td>{{ $quote->quotation_date?->format('d/m/Y') }}</td>
                    <td><a class="text-link" href="{{ route('quotations.show', $quote) }}">Review & Approve →</a></td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:18px">Tidak ada draft quote yang menunggu persetujuan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="panel" style="margin-bottom:20px">
    <div class="panel-heading"><div><h2>Pipeline quotation</h2><p>Posisi quotation tim sales.</p></div></div>
    <div class="mini-chart">
        <div class="bar-item"><div class="bar" style="height:{{ max(7,($widgets['quotes']['draft'] ?? 0)*15) }}%"></div><small>Draft<br>{{ $widgets['quotes']['draft'] ?? 0 }}</small></div>
        <div class="bar-item"><div class="bar" style="height:{{ max(7,($widgets['quotes']['submitted'] ?? 0)*15) }}%"></div><small>Diajukan<br>{{ $widgets['quotes']['submitted'] ?? 0 }}</small></div>
        <div class="bar-item"><div class="bar" style="height:{{ max(7,($widgets['quotes']['approved'] ?? 0)*15) }}%"></div><small>Disetujui<br>{{ $widgets['quotes']['approved'] ?? 0 }}</small></div>
        <div class="bar-item"><div class="bar" style="height:{{ max(7,($widgets['quotes']['converted'] ?? 0)*15) }}%"></div><small>Job<br>{{ $widgets['quotes']['converted'] ?? 0 }}</small></div>
    </div>
</section>
@endsection
