@extends('layouts.app')
@section('title','HPP per Jenis Biaya')
@section('content')
<x-menu-banner tag="LAPORAN KEUANGAN" title="HPP per Jenis Biaya" description="Rincian HPP Trucking, Freight, dan biaya lain yang telah diakui saat Closing Job." icon="briefcase" art-title="Modal pekerjaan," art-subtitle="terukur per jenis."/>

<section class="panel report-filter-panel">
    <form class="filter-bar" method="GET">
        <label>Dari <input type="date" name="from" value="{{ $from }}" max="{{ $to }}"></label>
        <label>Sampai <input type="date" name="to" value="{{ $to }}" min="{{ $from }}" max="{{ today()->toDateString() }}"></label>
        <button class="button button-primary">Terapkan</button>
    </form>
</section>

<div class="report-grid report-grid-4">
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon amber"><x-icon name="briefcase"/></span><div><h2>Total HPP</h2><small>Modal final</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($totalCost) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon blue"><x-icon name="file"/></span><div><h2>Jenis Biaya</h2><small>Uraian yang digunakan</small></div></div></div><strong class="report-value">{{ $rows->count() }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon purple"><x-icon name="wallet"/></span><div><h2>PPh 23 Dipotong</h2><small>Rekonsiliasi pajak vendor</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($totalPph23) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon green"><x-icon name="briefcase"/></span><div><h2>Job Tercakup</h2><small>{{ $totalTransactions }} transaksi HPP</small></div></div></div><strong class="report-value">{{ $totalJobs }}</strong></section>
</div>

<section class="panel report-table-panel">
    <div class="panel-heading"><div><h2>Rincian HPP</h2><p>{{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} sampai {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}.</p></div></div>
    <div class="table-scroll"><table><thead><tr><th>Jenis biaya</th><th class="money">Transaksi</th><th class="money">Job</th><th class="money">Total modal / HPP</th><th class="money">PPh 23</th><th class="money">Nilai jual</th><th class="money">Margin estimasi</th></tr></thead><tbody>
    @forelse($rows as $row)
        @php $margin = (float) $row->total_price > 0 ? (((float) $row->total_price - (float) $row->total_cost) / (float) $row->total_price) * 100 : 0; @endphp
        <tr><td><strong>{{ $row->description }}</strong></td><td class="money">{{ $row->transaction_count }}</td><td class="money">{{ $row->job_count }}</td><td class="money"><strong>Rp {{ \App\Support\Money::format($row->total_cost) }}</strong></td><td class="money">Rp {{ \App\Support\Money::format($row->total_pph23) }}</td><td class="money">Rp {{ \App\Support\Money::format($row->total_price) }}</td><td class="money">{{ \App\Support\Money::format($margin) }}%</td></tr>
    @empty
        <tr><td colspan="7"><div class="empty-state"><x-icon name="briefcase"/><h3>Belum ada HPP Closing</h3><p>Laporan muncul setelah Job berhasil di-closing.</p></div></td></tr>
    @endforelse
    </tbody><tfoot><tr class="summary-total"><td>Total</td><td class="money">{{ $totalTransactions }}</td><td class="money">{{ $totalJobs }}</td><td class="money">Rp {{ \App\Support\Money::format($totalCost) }}</td><td class="money">Rp {{ \App\Support\Money::format($totalPph23) }}</td><td class="money">Rp {{ \App\Support\Money::format($totalSales) }}</td><td></td></tr></tfoot></table></div>
</section>
@endsection
