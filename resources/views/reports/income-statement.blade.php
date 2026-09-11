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
<form class="filter-bar" method="GET"><div class="date-filter-group"><x-icon name="calendar"/><input type="date" name="from" value="{{ $from }}" aria-label="Dari tanggal" title="Dari tanggal"><span class="date-sep">→</span><input type="date" name="to" value="{{ $to }}" aria-label="Sampai tanggal" title="Sampai tanggal"></div><button class="button button-secondary">Terapkan</button></form><section class="panel report-list"><div><span>Pendapatan jasa</span><strong>Rp {{ \App\Support\Money::format($revenue) }}</strong></div><div><span>HPP job</span><strong>(Rp {{ \App\Support\Money::format($cogs) }})</strong></div><div class="report-subtotal"><span>Laba kotor</span><strong>Rp {{ \App\Support\Money::format($gross) }}</strong></div><div><span>Beban operasional</span><strong>(Rp {{ \App\Support\Money::format($expense) }})</strong></div><div class="report-total"><span>Laba bersih</span><strong>Rp {{ \App\Support\Money::format($net) }}</strong></div></section>@endsection
