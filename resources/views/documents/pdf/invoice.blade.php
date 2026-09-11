<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page{margin:28px 34px}body{font-family:DejaVu Sans,sans-serif;color:#26364f;font-size:11px;line-height:1.55}.header{border-bottom:2px solid #0f1f3d;padding-bottom:14px;margin-bottom:24px}.company{font-size:19px;font-weight:700;color:#163f8f}.muted{color:#6f7f96}.title{font-size:24px;letter-spacing:2px;text-align:right;color:#1f3e72;margin-top:-44px}.grid{width:100%;margin-bottom:18px}.col{float:left;width:48%;margin-right:2%;vertical-align:top}.clear{clear:both}.box{border:1px solid #dce5f2;padding:12px}table{width:100%;border-collapse:collapse;margin-top:14px}th{background:#f1f5fb;color:#50647f;text-align:left;font-size:9px;padding:8px;border:1px solid #dce5f2}td{padding:8px;border:1px solid #e6edf6}.section{margin-top:20px}.total-row td{font-weight:bold;background:#f7f9fc}.footer{position:fixed;bottom:-8px;left:0;right:0;text-align:center;font-size:9px;color:#8a97aa}
    </style>
</head>
<body>
<div class="header"><div class="company">JobFinance Demo Company</div><div class="muted">Jakarta, Indonesia<br>operations@jobfinance.test · +62 21 0000 0000</div><div class="title">INVOICE</div></div>
<div class="grid">
    <div class="col">
        <div class="box">
            <strong>Kepada Yth.</strong><br>
            {{ $invoice->customer_snapshot['name'] ?? '-' }}<br>
            {{ $invoice->customer_snapshot['address'] ?? '-' }}<br>
            @if($invoice->customer_snapshot['tax_number'] ?? null)NPWP: {{ $invoice->customer_snapshot['tax_number'] }}@endif
        </div>
    </div>
    <div class="col">
        <div class="box">
            <strong>No. Invoice</strong>: {{ $invoice->number }}<br>
            <strong>Tanggal</strong>: {{ $invoice->issued_at ? $invoice->issued_at->format('d/m/Y') : now()->format('d/m/Y') }}<br>
            <strong>No. Job</strong>: {{ $invoice->job->number ?? '-' }}<br>
            <strong>Jatuh Tempo</strong>: {{ $invoice->due_at ? $invoice->due_at->format('d/m/Y') : '-' }}<br>
            <strong>Terms</strong>: {{ $invoice->payment_terms ?? '-' }}
        </div>
    </div>
    <div class="clear"></div>
</div>
<table>
    <thead><tr><th>No</th><th>Keterangan</th><th>Qty</th><th>Satuan</th><th style="text-align:right">Harga Satuan</th><th style="text-align:right">Jumlah</th></tr></thead>
    <tbody>
        @foreach($invoice->items as $item)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $item->description }}</td>
            <td style="text-align:right">{{ number_format($item->quantity, 2) }}</td>
            <td>{{ $item->unit ?? '' }}</td>
            <td style="text-align:right">{{ number_format($item->unit_price, 2) }}</td>
            <td style="text-align:right">{{ number_format($item->total_price, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr><td colspan="5" style="text-align:right">Subtotal</td><td style="text-align:right">{{ number_format($invoice->subtotal, 2) }}</td></tr>
        @if($invoice->discount > 0)<tr><td colspan="5" style="text-align:right">Diskon</td><td style="text-align:right">({{ number_format($invoice->discount, 2) }})</td></tr>@endif
        @if($invoice->tax > 0)<tr><td colspan="5" style="text-align:right">PPN ({{ $invoice->tax_rate ?? 11 }}%)</td><td style="text-align:right">{{ number_format($invoice->tax, 2) }}</td></tr>@endif
        <tr class="total-row"><td colspan="5" style="text-align:right">TOTAL ({{ $invoice->currency ?? 'IDR' }})</td><td style="text-align:right">{{ number_format($invoice->total, 2) }}</td></tr>
        @if(($invoice->paid ?? 0) > 0)<tr><td colspan="5" style="text-align:right">Sudah Dibayar</td><td style="text-align:right">({{ number_format($invoice->paid, 2) }})</td></tr><tr class="total-row"><td colspan="5" style="text-align:right">Sisa Tagihan</td><td style="text-align:right">{{ number_format($invoice->total - $invoice->paid, 2) }}</td></tr>@endif
    </tfoot>
</table>
<div class="section">
    <p>Catatan: {{ $invoice->notes ?? 'Harap melakukan pembayaran sebelum jatuh tempo.' }}</p>
</div>
<div style="width:100%;margin-top:50px">
    <div style="float:right;width:200px;text-align:center">
        Jakarta, {{ now()->format('d F Y') }}<br><br><br><br>
        <div style="border-top:1px solid #aeb9c8;padding-top:8px">Hormat Kami,<br><strong>JobFinance Demo Company</strong></div>
    </div>
    <div class="clear"></div>
</div>
<div class="footer">Page <script type="text/php">if(isset($pdf)){echo $PAGE_NUM.' of '.$PAGE_COUNT;}</script></div>
</body>
</html>
