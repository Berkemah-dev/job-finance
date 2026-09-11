<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page{margin:28px 34px}body{font-family:DejaVu Sans,sans-serif;color:#26364f;font-size:10px;line-height:1.5}.header{border-bottom:2px solid #0f1f3d;padding-bottom:12px;margin-bottom:16px}.company{font-size:18px;font-weight:700;color:#163f8f}.muted{color:#6f7f96}.title{font-size:20px;letter-spacing:1px;text-align:right;color:#1f3e72;margin-top:-40px}.grid{width:100%;margin-bottom:14px}.col{float:left;width:48%;margin-right:2%}.clear{clear:both}.box{border:1px solid #dce5f2;padding:10px}table{width:100%;border-collapse:collapse;margin-top:10px}th{background:#f1f5fb;color:#50647f;text-align:left;font-size:9px;padding:7px;border:1px solid #dce5f2}td{padding:7px;border:1px solid #e6edf6}.text-right{text-align:right}.text-bold{font-weight:bold}.bg-opening{background:#fefce8}.bg-total{background:#f0f9f0;font-weight:bold}.bg-overdue{background:#fef2f2}.footer{position:fixed;bottom:-8px;left:0;right:0;text-align:center;font-size:8px;color:#8a97aa}
    </style>
</head>
<body>
<div class="header">
    <div class="company">JobFinance Demo Company</div>
    <div class="muted">Jakarta, Indonesia | operations@jobfinance.test</div>
    <div class="title">STATEMENT OF ACCOUNT</div>
</div>
<div class="grid">
    <div class="col">
        <div class="box">
            <strong>Kepada Yth.</strong><br>
            {{ $customer->name }}<br>
            {{ $customer->address ?? '-' }}<br>
            @if($customer->tax_number)NPWP: {{ $customer->tax_number }}@endif
        </div>
    </div>
    <div class="col">
        <div class="box">
            <strong>Kode Customer</strong>: {{ $customer->code }}<br>
            <strong>Periode</strong>: {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}<br>
            <strong>Tanggal Cetak</strong>: {{ now()->format('d/m/Y H:i') }}<br>
            <strong>Saldo Opening</strong>: {{ number_format($statement['opening'], 2) }}
        </div>
    </div>
    <div class="clear"></div>
</div>

<table>
    <thead>
        <tr>
            <th>No</th>
            <th>No Invoice</th>
            <th>No Job</th>
            <th>Tgl Invoice</th>
            <th>Jatuh Tempo</th>
            <th>Status</th>
            <th class="text-right">Total</th>
            <th class="text-right">Dibayar</th>
            <th class="text-right">Saldo</th>
        </tr>
    </thead>
    <tbody>
        @if($statement['opening'] != 0)
        <tr class="bg-opening">
            <td colspan="6"><em>Saldo Awal (sebelum {{ $from->format('d/m/Y') }})</em></td>
            <td class="text-right"></td>
            <td class="text-right"></td>
            <td class="text-right"><strong>{{ number_format($statement['opening'], 2) }}</strong></td>
        </tr>
        @endif
        @foreach($statement['lines'] as $line)
        <tr @if($line['is_overdue'])class="bg-overdue"@endif>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $line['invoice']->number }}</td>
            <td>{{ $line['invoice']->job->number ?? '-' }}</td>
            <td>{{ $line['invoice']->invoice_date ? \Carbon\Carbon::parse($line['invoice']->invoice_date)->format('d/m/Y') : '-' }}</td>
            <td>{{ $line['invoice']->due_date ? \Carbon\Carbon::parse($line['invoice']->due_date)->format('d/m/Y') : '-' }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', $line['invoice']->status)) }}</td>
            <td class="text-right">{{ number_format($line['debit'], 2) }}</td>
            <td class="text-right">{{ number_format($line['credit'], 2) }}</td>
            <td class="text-right">{{ number_format($line['running'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="bg-total">
            <td colspan="6" class="text-right">TOTAL SALDO</td>
            <td class="text-right">{{ number_format($statement['total_debit'], 2) }}</td>
            <td class="text-right">{{ number_format($statement['total_credit'], 2) }}</td>
            <td class="text-right">{{ number_format($statement['closing'], 2) }}</td>
        </tr>
    </tfoot>
</table>

<div style="margin-top:30px;font-size:9px;color:#6f7f96">
    <em>Dokumen ini dicetak secara otomatis oleh sistem JobFinance. Mohon segera konfirmasi jika terdapat perbedaan.</em>
</div>
<div class="footer">Page <script type="text/php">if(isset($pdf)){echo $PAGE_NUM.' of '.$PAGE_COUNT;}</script></div>
</body>
</html>
