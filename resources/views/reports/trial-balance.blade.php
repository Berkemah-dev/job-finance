@extends('layouts.app')
@section('title','Neraca Saldo')
@section('content')
<x-menu-banner
    tag="LAPORAN AKUNTANSI"
    title="Neraca Saldo (Trial Balance)"
    description="Ringkasan saldo penutupan debit dan kredit seluruh akun untuk memastikan keseimbangan pembukuan."
    icon="chart"
    art-title="Keseimbangan buku,"
    art-subtitle="terverifikasi."
/>

<section class="panel">
    <form class="filter-bar" method="GET">
        <div class="date-filter-group">
            <x-icon name="calendar"/>
            <span style="font-size: 11px; color: #64748b; font-weight: 500;">Sampai:</span>
            <input type="date" name="to" value="{{ $to }}" aria-label="Sampai tanggal" title="Sampai tanggal">
        </div>
        <button class="button button-primary">Terapkan</button>
    </form>

    <div class="table-scroll">
        @php $ledgerFrom = \Carbon\Carbon::parse($to)->startOfYear()->toDateString(); @endphp
        <table>
            <thead>
                <tr>
                    <th style="width: 100px;">Kode</th>
                    <th>Nama akun</th>
                    <th>Tipe</th>
                    <th class="money">Debit</th>
                    <th class="money">Kredit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr class="trial-balance-row" data-ledger-url="{{ route('reports.ledger', ['account_id' => $row->id, 'from' => $ledgerFrom, 'to' => $to]) }}" tabindex="0" role="link" aria-label="Buka rincian jurnal {{ $row->code }} {{ $row->name }}">
                        <td><strong>{{ $row->code }}</strong></td>
                        <td>{{ $row->name }}</td>
                        <td><span class="badge-pill">{{ config('accounting.types.'.$row->type) }}</span></td>
                        <td class="money">{{ \App\Support\Money::format($row->closing_debit) }}</td>
                        <td class="money">{{ \App\Support\Money::format($row->closing_credit) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="summary-total">
                    <th colspan="3">Total</th>
                    <th class="money">Rp {{ \App\Support\Money::format($totalDebit) }}</th>
                    <th class="money">Rp {{ \App\Support\Money::format($totalCredit) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="pagination">{{ $rows->links() }}</div>
</section>
<style>.trial-balance-row{cursor:pointer}.trial-balance-row:hover td{background:#f0f6ff!important;color:#10203d}.trial-balance-row:focus td{background:#e7f0ff!important;outline:1px solid #7da1d2;outline-offset:-1px}.theme-dark .trial-balance-row:hover td,.theme-dark .trial-balance-row:focus td{background:#243047!important;color:#fff}</style>
<script>document.querySelectorAll('.trial-balance-row').forEach((row)=>{row.addEventListener('click',()=>window.location.assign(row.dataset.ledgerUrl));row.addEventListener('keydown',(event)=>{if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location.assign(row.dataset.ledgerUrl)}})});</script>
@endsection
