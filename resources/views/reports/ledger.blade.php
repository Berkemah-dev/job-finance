@extends('layouts.app')
@section('title','Buku Besar')
@section('content')
<div class="page-heading"><div><p class="eyebrow">LAPORAN AKUNTANSI</p><h1>Buku Besar</h1><p>Mutasi dan saldo berjalan per akun.</p></div><span class="date-chip"><x-icon name="calendar"/>{{ now()->locale('id')->translatedFormat('d F Y') }}</span></div>
<x-menu-banner
    tag="LAPORAN AKUNTANSI"
    title="Buku Besar (General Ledger)"
    description="Rincian mutasi transaksi debit-kredit dan riwayat saldo berjalan untuk setiap akun terpilih."
    icon="chart"
    art-title="Mutasi akun,"
    art-subtitle="terinci jelas."
/>
<form class="filter-bar" method="GET"><select name="account_id">@foreach($accounts as $item)<option value="{{ $item->id }}" @selected($account->id===$item->id)>{{ $item->code }} — {{ $item->name }}</option>@endforeach</select><input type="date" name="from" value="{{ $from }}"><input type="date" name="to" value="{{ $to }}"><button class="button button-secondary">Terapkan</button></form><section class="panel"><div class="panel-heading"><div><h2>{{ $account->code }} — {{ $account->name }}</h2><p>Saldo awal Rp {{ \App\Support\Money::format($opening) }}</p></div><strong>Saldo akhir Rp {{ \App\Support\Money::format($closing) }}</strong></div><div class="table-scroll"><table><thead><tr><th>Tanggal</th><th>Jurnal</th><th>Uraian</th><th class="money">Debit</th><th class="money">Kredit</th><th class="money">Saldo</th></tr></thead><tbody>@forelse($entries as $entry)<tr><td>{{ $entry->journal->journal_date->format('d/m/Y') }}</td><td>@can('journals.manage')<a class="text-link" href="{{ route('journals.show',$entry->journal) }}">{{ $entry->journal->number }}</a>@else{{ $entry->journal->number }}@endcan</td><td>{{ $entry->description }}</td><td class="money">{{ \App\Support\Money::format($entry->debit) }}</td><td class="money">{{ \App\Support\Money::format($entry->credit) }}</td><td class="money">{{ \App\Support\Money::format($entry->running_balance) }}</td></tr>@empty<tr><td colspan="6">Tidak ada mutasi pada periode ini.</td></tr>@endforelse</tbody></table></div></section>@endsection
