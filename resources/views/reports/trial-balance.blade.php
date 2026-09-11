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
<form class="filter-bar" method="GET"><div class="date-filter-group"><x-icon name="calendar"/><span style="font-size: 11px; color: #64748b; font-weight: 500;">Sampai:</span><input type="date" name="to" value="{{ $to }}" aria-label="Sampai tanggal" title="Sampai tanggal"></div><button class="button button-secondary">Terapkan</button></form><section class="panel"><div class="table-scroll"><table><thead><tr><th>Kode</th><th>Nama akun</th><th>Tipe</th><th class="money">Debit</th><th class="money">Kredit</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row->code }}</td><td>{{ $row->name }}</td><td>{{ config('accounting.types.'.$row->type) }}</td><td class="money">{{ \App\Support\Money::format($row->closing_debit) }}</td><td class="money">{{ \App\Support\Money::format($row->closing_credit) }}</td></tr>@endforeach</tbody><tfoot><tr><th colspan="3">Total</th><th class="money">Rp {{ \App\Support\Money::format($rows->sum(fn($r)=>(float)$r->closing_debit)) }}</th><th class="money">Rp {{ \App\Support\Money::format($rows->sum(fn($r)=>(float)$r->closing_credit)) }}</th></tr></tfoot></table></div></section>@endsection
