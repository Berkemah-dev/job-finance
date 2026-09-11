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

<section class="panel report-list">
    <form class="filter-bar" method="GET" style="padding: 16px 24px; border-bottom: 1px solid #edf1f7;">
        <div class="date-filter-group">
            <x-icon name="calendar"/>
            <input type="date" name="from" value="{{ $from }}" aria-label="Dari tanggal" title="Dari tanggal">
            <span class="date-sep">→</span>
            <input type="date" name="to" value="{{ $to }}" aria-label="Sampai tanggal" title="Sampai tanggal">
        </div>
        <button class="button button-primary">Terapkan</button>
    </form>
    <div><span>Penerimaan customer</span><strong>Rp {{ \App\Support\Money::format($customer_payment) }}</strong></div>
    <div><span>Pengeluaran temporary/provision</span><strong>Rp {{ \App\Support\Money::format($job_cost_capitalization) }}</strong></div>
    <div><span>Penyesuaian Kas/Bank</span><strong>Rp {{ \App\Support\Money::format($adjustment) }}</strong></div>
    <div><span>Transaksi lain</span><strong>Rp {{ \App\Support\Money::format($other) }}</strong></div>
    <div class="report-total"><span>Arus kas bersih</span><strong>Rp {{ \App\Support\Money::format($net) }}</strong></div>
</section>
@endsection
