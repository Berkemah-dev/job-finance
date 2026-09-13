@extends('layouts.app')
@section('title','Statement of Account')
@section('content')
<x-menu-banner
    tag="LAPORAN PIUTANG"
    title="Statement of Account (SOA)"
    description="Rekapitulasi tagihan, riwayat pembayaran, saldo terhutang, dan umur piutang per customer."
    icon="wallet"
    art-title="Kartu piutang,"
    art-subtitle="lengkap & rapi."
/>
@php
    $g = $result['grand'];
    $toFloat = fn ($value) => (float) (string) \App\Support\Money::decimal($value);
    $overdue = \App\Support\Money::decimal($g['aging_1_30'])->plus($g['aging_31_60'])->plus($g['aging_61_90'])->plus($g['aging_90_plus']);
    $totalValue = max(1, $toFloat($g['total']));
    $paidPercent = round(($toFloat($g['paid']) / $totalValue) * 100, 2);
    $balancePercent = round(($toFloat($g['balance']) / $totalValue) * 100, 2);
@endphp

<div class="report-grid report-grid-4">
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon blue"><x-icon name="file"/></span><div><h2>Total Tagihan</h2><small>Seluruh customer</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($g['total']) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon green"><x-icon name="check"/></span><div><h2>Sudah Dibayar</h2><small>{{ \App\Support\Money::format($paidPercent) }}% dari tagihan</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($g['paid']) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon amber"><x-icon name="wallet"/></span><div><h2>Saldo Outstanding</h2><small>{{ \App\Support\Money::format($balancePercent) }}% belum lunas</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($g['balance']) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon purple"><x-icon name="calendar"/></span><div><h2>Jatuh Tempo</h2><small>Piutang overdue</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($overdue) }}</strong></section>
</div>

<section class="panel report-chart-card report-section">
    <div class="report-chart-head"><div><h2>Komposisi Piutang</h2><p>Perbandingan nilai yang sudah dibayar dan saldo outstanding.</p></div></div>
    <div class="report-bars">
        <div class="report-bar-row"><span>Sudah dibayar</span><div class="report-bar-track"><span class="report-bar-fill green" style="--bar: {{ $paidPercent }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($g['paid']) }}</strong></div>
        <div class="report-bar-row"><span>Saldo outstanding</span><div class="report-bar-track"><span class="report-bar-fill amber" style="--bar: {{ $balancePercent }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($g['balance']) }}</strong></div>
    </div>
</section>

<section class="panel report-table-panel">
    <form class="filter-bar soa-filter" method="GET"><input name="search" value="{{ $search }}" placeholder="Cari kode atau nama customer" aria-label="Cari customer"><label class="filter-check soa-filter-check"><input type="checkbox" name="unpaid" value="1" @checked($unpaid)> <span>Hanya belum lunas</span></label><button class="button button-primary">Terapkan</button><a class="button button-secondary" href="{{ route('reports.soa') }}">Reset</a></form>
    <div class="table-scroll"><table><thead><tr><th>Customer</th><th class="money">Total tagihan</th><th class="money">Dibayar</th><th class="money">Saldo</th><th>Umur piutang</th><th>Aksi</th></tr></thead><tbody>@forelse($result['customers'] as $row)<tr><td><strong>{{ $row['customer']->code }}</strong> {{ $row['customer']->name }}@if($row['customer']->contact_name)<br><small>{{ $row['customer']->contact_name }}</small>@endif<br><small>{{ $row['invoices'] }} invoice</small></td><td class="money">Rp {{ \App\Support\Money::format($row['total']) }}</td><td class="money">Rp {{ \App\Support\Money::format($row['paid']) }}</td><td class="money"><strong>Rp {{ \App\Support\Money::format($row['balance']) }}</strong></td><td>@if(\App\Support\Money::decimal($row['balance'])->isZero())<span class="badge-pill">Lunas</span>@else @foreach(['current'=>'Saat ini','aging_1_30'=>'1-30 hari','aging_31_60'=>'31-60 hari','aging_61_90'=>'61-90 hari','aging_90_plus'=>'> 90 hari'] as $key=>$label)@if(! \App\Support\Money::decimal($row[$key])->isZero())<span class="badge-pill">{{ $label }}: Rp {{ \App\Support\Money::format($row[$key]) }}</span> @endif @endforeach @endif</td><td><div class="table-actions"><a class="btn-action btn-action-primary" href="{{ route('reports.soa.customer',$row['customer']) }}" title="Lihat SOA Customer" data-tooltip="Lihat SOA" aria-label="Lihat SOA Customer"><x-icon name="eye"/></a></div></td></tr>@empty<tr><td colspan="6"><div class="empty-state"><x-icon name="wallet"/><h3>Belum ada customer dengan tagihan</h3><p>Statement tampil setelah ada invoice yang diterbitkan melalui Closing Job.</p></div></td></tr>@endforelse</tbody></table></div>
</section>
@endsection
