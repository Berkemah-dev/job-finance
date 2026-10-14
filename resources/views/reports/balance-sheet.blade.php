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
          @include('reports.partials.balance-party-account', ['levelClass' => 'level-3'])
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
          @include('reports.partials.balance-party-account', ['levelClass' => 'level-2'])
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
<style>
.balance-row-toggle{width:100%;border:0;background:transparent;text-align:left;cursor:pointer;font:inherit}.balance-row-toggle:hover{background:#edf4ff}.balance-party-account{border-bottom:1px solid #edf1f6}.balance-party-list{margin:4px 0 8px 18px;border:1px solid #dce7f5;border-radius:7px;background:#f8fbff;overflow:hidden}.balance-party-row{width:100%;display:flex;justify-content:space-between;gap:12px;align-items:center;padding:8px 10px;border:0;border-bottom:1px solid #e3eaf3;background:transparent;color:#425673;text-align:left;cursor:pointer;font-size:10px}.balance-party-row:hover{background:#edf4ff}.balance-party-row small{display:block;color:#71819a;font-size:9px}.balance-party-row strong{display:block;color:#2e4f78}.balance-party-row>strong{text-align:right;white-space:nowrap}.balance-party-transactions{padding:0 10px 8px;overflow:auto}.balance-party-transactions table{width:100%;min-width:580px;border-collapse:collapse;font-size:9px}.balance-party-transactions th{padding:6px;text-align:left;background:#eef4fb;color:#60748f;font-size:8px}.balance-party-transactions td{padding:6px;border-bottom:1px solid #e7edf5;color:#4b617d}.balance-ledger-link{display:block;padding:8px 10px;color:#0f4d8c;font-size:10px;font-weight:700;text-decoration:none}.theme-dark .balance-row-toggle,.theme-dark .balance-party-row{color:#cbd5e1}.theme-dark .balance-party-list{background:#172033;border-color:#334155}.theme-dark .balance-party-transactions th{background:#1e293b;color:#cbd5e1}.theme-dark .balance-party-transactions td,.theme-dark .balance-party-row{border-color:#334155;color:#cbd5e1}@media(max-width:850px){.balance-party-list{margin-left:7px}.balance-party-row{align-items:flex-start;flex-direction:column}.balance-party-row>strong{white-space:normal;text-align:left}}
</style>
<script>
document.querySelectorAll('[data-balance-toggle]').forEach((button) => {
  button.addEventListener('click', () => {
    const panel = document.getElementById(button.dataset.balanceTarget);
    if (!panel) return;
    const open = panel.hidden;
    panel.hidden = !open;
    button.setAttribute('aria-expanded', String(open));
    const icon = button.querySelector('[data-balance-icon]');
    if (icon) icon.textContent = open ? '🔼' : '🔽';
  });
});
</script>
@endsection
