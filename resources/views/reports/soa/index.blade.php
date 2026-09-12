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
<section class="panel soa-panel"><div class="cost-summary-body soa-summary"><div class="stats-grid soa-stats">
@php $g = $result['grand']; @endphp
<div class="stat-card"><p>Total tagihan</p><strong>Rp {{ \App\Support\Money::format($g['total']) }}</strong></div>
<div class="stat-card"><p>Sudah dibayar</p><strong>Rp {{ \App\Support\Money::format($g['paid']) }}</strong></div>
<div class="stat-card"><p>Saldo outstanding</p><strong>Rp {{ \App\Support\Money::format($g['balance']) }}</strong></div>
<div class="stat-card"><p>Piutang jatuh tempo</p><strong>Rp {{ \App\Support\Money::format(\App\Support\Money::decimal($g['aging_1_30'])->plus($g['aging_31_60'])->plus($g['aging_61_90'])->plus($g['aging_90_plus'])) }}</strong></div>
</div></div>
<form class="filter-bar soa-filter" method="GET"><input name="search" value="{{ $search }}" placeholder="Cari kode atau nama customer" aria-label="Cari customer"><label class="filter-check soa-filter-check"><input type="checkbox" name="unpaid" value="1" @checked($unpaid)> <span>Hanya belum lunas</span></label><button class="button button-primary">Terapkan</button><a class="button button-secondary" href="{{ route('reports.soa') }}">Reset</a></form>
<div class="table-scroll"><table><thead><tr><th>Customer</th><th class="money">Total tagihan</th><th class="money">Dibayar</th><th class="money">Saldo</th><th>Umur piutang</th><th>Aksi</th></tr></thead><tbody>@forelse($result['customers'] as $row)<tr><td><strong>{{ $row['customer']->code }}</strong> {{ $row['customer']->name }}@if($row['customer']->contact_name)<br><small>{{ $row['customer']->contact_name }}</small>@endif<br><small>{{ $row['invoices'] }} invoice</small></td><td class="money">Rp {{ \App\Support\Money::format($row['total']) }}</td><td class="money">Rp {{ \App\Support\Money::format($row['paid']) }}</td><td class="money"><strong>Rp {{ \App\Support\Money::format($row['balance']) }}</strong></td><td>@if(\App\Support\Money::decimal($row['balance'])->isZero())<span class="badge-pill">Lunas</span>@else @foreach(['current'=>'Saat ini','aging_1_30'=>'1-30 hari','aging_31_60'=>'31-60 hari','aging_61_90'=>'61-90 hari','aging_90_plus'=>'> 90 hari'] as $key=>$label)@if(! \App\Support\Money::decimal($row[$key])->isZero())<span class="badge-pill">{{ $label }}: Rp {{ \App\Support\Money::format($row[$key]) }}</span> @endif @endforeach @endif</td><td><div class="table-actions"><a class="btn-action btn-action-primary" href="{{ route('reports.soa.customer',$row['customer']) }}" title="Lihat SOA Customer" data-tooltip="Lihat SOA" aria-label="Lihat SOA Customer"><x-icon name="eye"/></a></div></td></tr>@empty<tr><td colspan="6"><div class="empty-state"><x-icon name="wallet"/><h3>Belum ada customer dengan tagihan</h3><p>Statement tampil setelah ada invoice yang diterbitkan melalui Closing Job.</p></div></td></tr>@endforelse</tbody></table></div></section>
@endsection
