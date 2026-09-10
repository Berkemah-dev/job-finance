@extends('layouts.app')
@section('title','SOA · '.$customer->name)
@section('content')
<div class="page-heading"><div><p class="eyebrow">STATEMENT OF ACCOUNT</p><h1>{{ $customer->code }} · {{ $customer->name }}</h1><p>{{ $customer->address ?? '—' }} @if($customer->tax_number)· NPWP {{ $customer->tax_number }} @endif</p></div><a class="text-link" href="{{ route('reports.soa') }}">Kembali ke daftar</a></div>
<section class="panel"><form class="filter-bar" method="GET"><input type="date" name="from" value="{{ $from->toDateString() }}" aria-label="Dari tanggal"><input type="date" name="to" value="{{ $to->toDateString() }}" aria-label="Sampai tanggal"><button class="button button-primary">Terapkan periode</button><a class="text-link" href="{{ route('reports.soa.customer',$customer) }}">Reset</a><span class="filter-count">Periode transaksi: {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</span></form>
<div class="cost-summary-body"><div class="stats-grid">
<div class="stat-card"><p>Saldo awal</p><strong>Rp {{ \App\Support\Money::format($statement['opening']) }}</strong></div>
<div class="stat-card"><p>Tagihan periode</p><strong>Rp {{ \App\Support\Money::format($statement['invoiced']) }}</strong></div>
<div class="stat-card"><p>Pembayaran periode</p><strong>Rp {{ \App\Support\Money::format($statement['paid']) }}</strong></div>
<div class="stat-card"><p>Saldo akhir</p><strong>Rp {{ \App\Support\Money::format($statement['closing']) }}</strong></div>
</div></div>
@php $aged = $statement['aged']; @endphp
@if($aged['invoices'] > 0)
<div class="cost-progress"><span>Umur piutang — <strong>@foreach(['current'=>'Saat ini','aging_1_30'=>'1-30','aging_31_60'=>'31-60','aging_61_90'=>'61-90','aging_90_plus'=>'>90'] as $key=>$label)@if(! \App\Support\Money::decimal($aged[$key])->isZero())<strong>{{ $label }} hari: Rp {{ \App\Support\Money::format($aged[$key]) }}</strong> @endif @endforeach</strong></span></div>
@endif
<div class="table-scroll"><table><thead><tr><th>Tanggal</th><th>Nomor</th><th>Keterangan</th><th class="money">Debet</th><th class="money">Kredit</th><th class="money">Saldo</th></tr></thead><tbody>@forelse($statement['rows'] as $row)<tr><td>{{ $row['date']->format('d/m/Y') }}</td><td>{{ $row['number'] }}</td><td>@if($row['type']==='payment')<span class="status-badge status-partially_paid">Pembayaran</span> @endif{{ $row['description'] }}</td><td class="money">@if(\App\Support\Money::decimal($row['debit'])->isPositive())Rp {{ \App\Support\Money::format($row['debit']) }}@endif</td><td class="money">@if(\App\Support\Money::decimal($row['credit'])->isPositive())Rp {{ \App\Support\Money::format($row['credit']) }}@endif</td><td class="money"><strong>Rp {{ \App\Support\Money::format($row['balance']) }}</strong></td></tr>@empty<tr><td colspan="6"><div class="empty-state"><h3>Tidak ada transaksi pada periode ini</h3><p>Perlebar rentang tanggal untuk melihat invoice dan pembayaran.</p></div></td></tr>@endforelse</tbody></table></div></section>
@endsection