@extends('layouts.app')
@section('title','Neraca')
@section('content')
<div class="page-heading"><div><p class="eyebrow">LAPORAN KEUANGAN</p><h1>Laporan Neraca</h1><p>Rincian posisi aset, hutang, dan modal perusahaan pada tanggal pelaporan.</p></div></div>
<section class="panel report-filter-panel"><form class="filter-bar balance-filter" method="GET"><div class="date-filter-group"><x-icon name="calendar"/><span>Periode:</span><input type="date" name="from" value="{{ $from }}" aria-label="Tanggal awal"><span>sampai</span><input type="date" name="to" value="{{ $to }}" aria-label="Tanggal akhir"></div><button class="button button-primary">Tampilkan Neraca</button></form></section>
@php
  $money = fn ($amount) => 'Rp '.\App\Support\Money::format($amount);
  $sectionTotal = fn ($rows) => $rows->reduce(fn ($total, $row) => \App\Support\Money::decimal($total)->plus($row->report_balance), \App\Support\Money::decimal(0));
@endphp
<div class="balance-layout">
  <section class="balance-column"><div class="balance-column-title"><span>1</span><h2>ASET</h2></div>
    @foreach([['Aset Lancar', $currentAssets], ['Aset Tidak Lancar', $nonCurrentAssets], ['Aset Lainnya', $otherAssets]] as [$heading, $accounts])
      @if($accounts->isNotEmpty())<div class="balance-group"><h3>{{ $heading }}</h3><div class="balance-rows">@foreach($accounts as $account)<div class="balance-row level-{{ min(4, max(1, (int) ($account->level ?? 3))) }}"><span>{{ $account->code }} · {{ $account->name }}</span><strong>{{ (float) $account->report_balance == 0.0 ? '—' : $money($account->report_balance) }}</strong></div>@endforeach</div><div class="balance-subtotal"><span>Total {{ $heading }}</span><strong>{{ $money((string) $sectionTotal($accounts)) }}</strong></div></div>@endif
    @endforeach
    <div class="balance-grand-total"><span>TOTAL ASET</span><strong>{{ $money($assets) }}</strong></div>
  </section>
  <section class="balance-column"><div class="balance-column-title"><span>2</span><h2>HUTANG</h2></div><div class="balance-rows">@foreach($liabilityRows as $account)<div class="balance-row level-{{ min(4, max(1, (int) ($account->level ?? 2))) }}"><span>{{ $account->code }} · {{ $account->name }}</span><strong>{{ (float) $account->report_balance == 0.0 ? '—' : $money($account->report_balance) }}</strong></div>@endforeach</div><div class="balance-grand-total"><span>TOTAL HUTANG</span><strong>{{ $money($liabilities) }}</strong></div>
    <div class="balance-column-title modal-title"><span>3</span><h2>MODAL</h2></div><div class="balance-rows">@foreach($equityRows as $account)<div class="balance-row level-{{ min(4, max(1, (int) ($account->level ?? 2))) }}"><span>{{ $account->code }} · {{ $account->name }}</span><strong>{{ (float) $account->report_balance == 0.0 ? '—' : $money($account->report_balance) }}</strong></div>@endforeach<div class="balance-row level-2"><span>Laba Bersih Berjalan</span><strong>{{ $money($earnings) }}</strong></div></div><div class="balance-subtotal"><span>Total Modal</span><strong>{{ $money(\App\Support\Money::decimal($equity)->plus($earnings)) }}</strong></div><div class="balance-grand-total"><span>TOTAL HUTANG DAN MODAL</span><strong>{{ $money($liabilities_equity) }}</strong></div>
  </section>
</div>
@endsection
