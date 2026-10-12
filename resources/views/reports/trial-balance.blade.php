@extends('layouts.app')
@section('title','Neraca Saldo')
@section('content')
<x-menu-banner
    tag="PT RADIX INTERNATIONAL LOGISTICS"
    title="Neraca Saldo (Trial Balance)"
    description="Periode : {{ strtoupper(\Carbon\Carbon::parse($to)->translatedFormat('F Y')) }} ({{ $from ?? \Carbon\Carbon::parse($to)->startOfYear()->toDateString() }} s/d {{ $to }})"
    icon="chart"
    art-title="Keseimbangan buku,"
    art-subtitle="terverifikasi."
/>

<section class="panel">
    <form class="filter-bar" method="GET">
        <div class="date-filter-group">
            <x-icon name="calendar"/>
            <span style="font-size: 11px; color: #64748b; font-weight: 500;">Dari:</span>
            <input type="date" name="from" value="{{ $from ?? \Carbon\Carbon::parse($to)->startOfYear()->toDateString() }}" aria-label="Dari tanggal" title="Dari tanggal">
            <span style="font-size: 11px; color: #64748b; font-weight: 500;">Sampai:</span>
            <input type="date" name="to" value="{{ $to }}" aria-label="Sampai tanggal" title="Sampai tanggal">
        </div>
        <button class="button button-primary">Terapkan</button>
    </form>

    <div class="table-scroll">
        @php
            $ledgerFrom = $from ?? \Carbon\Carbon::parse($to)->startOfYear()->toDateString();
            $displayRows = $radixRows ?? $rows;
        @endphp
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">NO</th>
                    <th style="width: 90px;">ACC NO#</th>
                    <th>ACCOUNT - NAME</th>
                    <th class="money">BALANCE</th>
                    <th class="money">DEBET</th>
                    <th class="money">KREDIT</th>
                    <th class="money">MUTASI</th>
                    <th class="money">END.BALANCE</th>
                </tr>
            </thead>
            <tbody>
                @foreach($displayRows as $index => $row)
                    @php
                        $rowUrl = !empty($row->id) ? route('reports.ledger', ['account_id' => $row->id, 'from' => $ledgerFrom, 'to' => $to]) : '#';
                    @endphp
                    <tr class="trial-balance-row" data-ledger-url="{{ $rowUrl }}" tabindex="0" role="link" aria-label="Buka rincian jurnal {{ $row->acc_no ?? $row->code }} {{ $row->name }}">
                        <td>{{ $row->no ?? ($index + 1) }}</td>
                        <td><strong>{{ $row->acc_no ?? $row->code }}</strong></td>
                        <td>{{ $row->name }}</td>
                        <td class="money">{{ \App\Support\Money::format($row->opening_balance ?? 0) }}</td>
                        <td class="money">{{ \App\Support\Money::format($row->period_debit ?? $row->closing_debit ?? 0) }}</td>
                        <td class="money">{{ \App\Support\Money::format($row->period_credit ?? $row->closing_credit ?? 0) }}</td>
                        <td class="money">{{ \App\Support\Money::format($row->mutation ?? 0) }}</td>
                        <td class="money"><strong>{{ \App\Support\Money::format($row->ending_balance ?? 0) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="summary-total">
                    <th colspan="3">Total</th>
                    <th class="money">Rp {{ \App\Support\Money::format($totalOpening ?? 0) }}</th>
                    <th class="money">Rp {{ \App\Support\Money::format($totalPeriodDebit ?? $totalDebit ?? 0) }}</th>
                    <th class="money">Rp {{ \App\Support\Money::format($totalPeriodCredit ?? $totalCredit ?? 0) }}</th>
                    <th class="money">Rp {{ \App\Support\Money::format($totalMutation ?? 0) }}</th>
                    <th class="money">Rp {{ \App\Support\Money::format($totalEnding ?? 0) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
<style>.trial-balance-row{cursor:pointer}.trial-balance-row:hover td{background:#f0f6ff!important;color:#10203d}.trial-balance-row:focus td{background:#e7f0ff!important;outline:1px solid #7da1d2;outline-offset:-1px}.theme-dark .trial-balance-row:hover td,.theme-dark .trial-balance-row:focus td{background:#243047!important;color:#fff}</style>
<script>document.querySelectorAll('.trial-balance-row').forEach((row)=>{row.addEventListener('click',()=>{if(row.dataset.ledgerUrl&&row.dataset.ledgerUrl!=='#')window.location.assign(row.dataset.ledgerUrl)});row.addEventListener('keydown',(event)=>{if((event.key==='Enter'||event.key===' ')&&row.dataset.ledgerUrl&&row.dataset.ledgerUrl!=='#'){event.preventDefault();window.location.assign(row.dataset.ledgerUrl)}})});</script>
@endsection
