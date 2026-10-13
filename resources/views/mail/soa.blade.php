<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Statement of Account {{ $customer->code }} · {{ $customer->name }}</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;color:#1f2a44;margin:0;padding:24px;font-size:13px}
h1{font-size:18px;margin:0 0 2px}h2{font-size:13px;margin:0 0 16px;color:#5b627a;font-weight:400}
table{width:100%;border-collapse:collapse;margin-top:8px}
th{background:#f1f4f9;text-align:left;padding:7px 8px;border:1px solid #d9e1ec;font-size:11px;text-transform:uppercase;letter-spacing:.04em}
td{padding:7px 8px;border:1px solid #d9e1ec}
.money{text-align:right;white-space:nowrap}
.meta{width:100%;margin:14px 0;border-collapse:collapse}
.meta td{border:none;padding:2px 8px 2px 0}
strong{font-weight:700}
.total{font-weight:700;background:#f8fafc}
</style></head>
<body>
<h1>Statement of Account — {{ $customer->code }} · {{ $customer->name }}</h1>
<h2>Periode {{ $periodFrom->format('d/m/Y') }} s.d. {{ $periodTo->format('d/m/Y') }}</h2>
@if($customer->address)<p style="margin:0">{{ $customer->address }}</p>@endif
@if($customer->tax_number)<p style="margin:0">NPWP: {{ $customer->tax_number }}</p>@endif

<table class="meta"><tr><td>Saldo awal</td><td class="money"><strong>Rp {{ \App\Support\Money::format($statement['opening']) }}</strong></td></tr>
<tr><td>Tagihan periode</td><td class="money">Rp {{ \App\Support\Money::format($statement['invoiced']) }}</td></tr>
<tr><td>Pembayaran periode</td><td class="money">Rp {{ \App\Support\Money::format($statement['paid']) }}</td></tr>
<tr><td>Saldo akhir</td><td class="money"><strong>Rp {{ \App\Support\Money::format($statement['closing']) }}</strong></td></tr></table>

@php $aged = $statement['aged']; @endphp
@if($aged['invoices'] > 0)
<p style="margin:12px 0 0">Umur piutang: @foreach(['current'=>'Saat ini','aging_1_30'=>'1-30 hari','aging_31_60'=>'31-60 hari','aging_61_90'=>'61-90 hari','aging_90_plus'=>'> 90 hari'] as $key=>$label)@unless(\App\Support\Money::decimal($aged[$key])->isZero()){{ $label }} Rp {{ \App\Support\Money::format($aged[$key]) }}@unless($loop->last); @endunless@endunless@endforeach</p>
@endif

<table><thead><tr><th>Tanggal</th><th>Nomor</th><th>Keterangan</th><th class="money">Debet</th><th class="money">Kredit</th><th class="money">Saldo</th></tr></thead>
<tbody>@foreach($statement['rows'] as $row)<tr><td>{{ $row['date']->format('d/m/Y') }}</td><td>{{ $row['number'] }}</td><td>{{ $row['description'] }}</td><td class="money">@if(\App\Support\Money::decimal($row['debit'])->isPositive())Rp {{ \App\Support\Money::format($row['debit']) }}@endif</td><td class="money">@if(\App\Support\Money::decimal($row['credit'])->isPositive())Rp {{ \App\Support\Money::format($row['credit']) }}@endif</td><td class="money"><strong>Rp {{ \App\Support\Money::format($row['balance']) }}</strong></td></tr>@endforeach</tbody></table>

<p style="margin-top:16px;color:#5b627a">Diterbitkan otomatis dari sistem. Jika ada pertanyaan, hubungi tim finance Anda.</p>
</body>
</html>