@extends('layouts.app')
@section('title','Neraca')
@section('content')
<div class="page-heading">
  <div>
    <p class="eyebrow">PT RADIX INTERNATIONAL LOGISTICS</p>
    <h1>N E R A C A (Balance Sheet)</h1>
    <p>Per {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}</p>
  </div>
</div>

<section class="panel report-filter-panel">
  <form class="filter-bar balance-filter" method="GET">
    <div class="date-filter-group">
      <x-icon name="calendar"/>
      <span>Periode:</span>
      <input type="date" name="from" value="{{ $from }}" aria-label="Tanggal awal">
      <span>sampai</span>
      <input type="date" name="to" value="{{ $to }}" aria-label="Tanggal akhir">
    </div>
    <button class="button button-primary">Tampilkan Neraca</button>
  </form>
</section>

@php
  $money = fn ($amount) => 'Rp '.\App\Support\Money::format($amount);
  $ledgerUrl = fn ($account) => !empty($account->id) ? route('reports.ledger', ['account_id' => $account->id, 'from' => $from, 'to' => $to]) : '#';
@endphp

<p class="balance-detail-hint">
  <x-icon name="chart"/> Klik baris akun untuk melihat mutasi jurnal dan saldo berjalan pada Buku Besar.
</p>

<div class="balance-layout">
  {{-- SISI KIRI: AKTIVA --}}
  <section class="balance-column">
    <div class="balance-column-title">
      <span>1</span>
      <h2>A K T I V A</h2>
    </div>

    <div class="balance-group">
      <h3>Aktiva Lancar</h3>
      <div class="balance-rows">
        @foreach($radixCurrentAssets ?? [] as $account)
          <a class="balance-row balance-row-link level-3" href="{{ $ledgerUrl($account) }}" title="Lihat rincian {{ $account->acc_no }} — {{ $account->name }}">
            <span><strong>{{ $account->acc_no }}</strong> &nbsp; {{ $account->name }}</span>
            <strong>{{ $money($account->report_balance) }} <x-icon name="arrow"/></strong>
          </a>
        @endforeach
      </div>
      <div class="balance-subtotal">
        <span>Total Aktiva Lancar</span>
        <strong>{{ $money($totalCurrentAssets ?? 0) }}</strong>
      </div>
    </div>

    <div class="balance-group">
      <h3>Aktiva Tetap</h3>
      <div class="balance-rows">
        @foreach($radixFixedAssets ?? [] as $account)
          <a class="balance-row balance-row-link level-3" href="{{ $ledgerUrl($account) }}" title="Lihat rincian {{ $account->acc_no }} — {{ $account->name }}">
            <span><strong>{{ $account->acc_no }}</strong> &nbsp; {{ $account->name }}</span>
            <strong>{{ $money($account->report_balance) }} <x-icon name="arrow"/></strong>
          </a>
        @endforeach
      </div>
      <div class="balance-subtotal">
        <span>Total Aktiva Tetap</span>
        <strong>{{ $money($totalFixedAssets ?? 0) }}</strong>
      </div>
    </div>

    <div class="balance-grand-total">
      <span>TOTAL AKTIVA</span>
      <strong>{{ $money($assets) }}</strong>
    </div>
  </section>

  {{-- SISI KANAN: KEWAJIBAN DAN MODAL --}}
  <section class="balance-column">
    <div class="balance-column-title">
      <span>2</span>
      <h2>KEWAJIBAN DAN MODAL</h2>
    </div>

    <div class="balance-group">
      <h3>Kewajiban Lancar</h3>
      <div class="balance-rows">
        @foreach($radixCurrentLiabilities ?? [] as $account)
          <a class="balance-row balance-row-link level-2" href="{{ $ledgerUrl($account) }}" title="Lihat rincian {{ $account->acc_no }} — {{ $account->name }}">
            <span><strong>{{ $account->acc_no }}</strong> &nbsp; {{ $account->name }}</span>
            <strong>{{ $money($account->report_balance) }} <x-icon name="arrow"/></strong>
          </a>
        @endforeach
      </div>
      <div class="balance-subtotal">
        <span>Total Kewajiban Lancar</span>
        <strong>{{ $money($totalCurrentLiabilities ?? $liabilities) }}</strong>
      </div>
    </div>

    <div class="balance-column-title modal-title">
      <span>3</span>
      <h2>Modal</h2>
    </div>
    <div class="balance-rows">
      @foreach($radixEquity ?? [] as $account)
        @if(!empty($account->id) && $account->acc_no !== '32')
          <a class="balance-row balance-row-link level-2" href="{{ $ledgerUrl($account) }}" title="Lihat rincian {{ $account->acc_no }} — {{ $account->name }}">
            <span><strong>{{ $account->acc_no }}</strong> &nbsp; {{ $account->name }}</span>
            <strong>{{ $money($account->report_balance) }} <x-icon name="arrow"/></strong>
          </a>
        @else
          <div class="balance-row level-2">
            <span><strong>{{ $account->acc_no }}</strong> &nbsp; {{ $account->name }}</span>
            <strong>{{ $money($account->report_balance) }}</strong>
          </div>
        @endif
      @endforeach
    </div>
    <div class="balance-subtotal">
      <span>Total Modal</span>
      <strong>{{ $money($totalModal ?? \App\Support\Money::decimal($equity)->plus($earnings)) }}</strong>
    </div>

    <div class="balance-grand-total">
      <span>TOTAL KEWAJIBAN DAN MODAL</span>
      <strong>{{ $money($liabilities_equity) }}</strong>
    </div>
  </section>
</div>
@endsection
