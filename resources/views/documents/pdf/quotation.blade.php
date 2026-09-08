<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page{margin:28px 34px}body{font-family:DejaVu Sans,sans-serif;color:#26364f;font-size:11px;line-height:1.55}.header{border-bottom:2px solid #2563eb;padding-bottom:14px;margin-bottom:24px}.company{font-size:19px;font-weight:700;color:#163f8f}.muted{color:#6f7f96}.title{font-size:24px;letter-spacing:2px;text-align:right;color:#1f3e72;margin-top:-44px}.grid{display:table;width:100%;margin-bottom:18px}.col{display:table-cell;width:50%;vertical-align:top}.box{border:1px solid #dce5f2;padding:12px;border-radius:6px}table{width:100%;border-collapse:collapse;margin-top:14px}th{background:#f1f5fb;color:#50647f;text-align:left;font-size:9px;padding:8px;border:1px solid #dce5f2}td{padding:8px;border:1px solid #e6edf6}.money{text-align:right}.summary{width:42%;margin-left:auto}.sign{display:table;width:100%;margin-top:42px}.sign div{display:table-cell;width:50%;text-align:center}.line{border-top:1px solid #aeb9c8;margin:54px 45px 0;padding-top:8px}.footer{position:fixed;bottom:-8px;left:0;right:0;text-align:center;font-size:9px;color:#8a97aa}
    </style>
</head>
<body>
<div class="header"><div class="company">JobFinance Demo Company</div><div class="muted">Jakarta, Indonesia<br>finance@jobfinance.test · +62 21 0000 0000 · jobfinance.test</div><div class="title">QUOTATION</div></div>
<div class="grid"><div class="col"><div class="box"><strong>Customer</strong><br>{{ $quotation->customer_snapshot['name'] }}<br>{{ $quotation->customer_snapshot['address'] ?? '' }}<br>{{ $quotation->customer_snapshot['email'] ?? '' }}</div></div><div class="col"><div class="box"><strong>Quotation No</strong>: {{ $quotation->number }}<br><strong>Date</strong>: {{ $quotation->quotation_date->format('d/m/Y') }}<br><strong>Valid Until</strong>: {{ $quotation->valid_until->format('d/m/Y') }}<br><strong>Project</strong>: {{ $quotation->subject }}</div></div></div>
<table><thead><tr><th>No</th><th>Description</th><th>Qty</th><th>Unit</th><th class="money">Unit Price</th><th class="money">Amount</th></tr></thead><tbody>@foreach($quotation->items as $item)<tr><td>{{ $loop->iteration }}</td><td>{{ $item->description }}</td><td>{{ \App\Support\Money::format($item->quantity) }}</td><td>{{ $item->unit }}</td><td class="money">Rp {{ \App\Support\Money::format($item->unit_price) }}</td><td class="money">Rp {{ \App\Support\Money::format($item->total_price) }}</td></tr>@endforeach</tbody></table>
<table class="summary"><tr><td>Subtotal</td><td class="money">Rp {{ \App\Support\Money::format($quotation->subtotal) }}</td></tr><tr><td>Discount</td><td class="money">Rp 0,00</td></tr><tr><td>Tax</td><td class="money">Rp 0,00</td></tr><tr><td><strong>Grand Total</strong></td><td class="money"><strong>Rp {{ \App\Support\Money::format($quotation->subtotal) }}</strong></td></tr></table>
<p><strong>Terms & Conditions</strong><br>{{ $quotation->notes ?: 'Harga berlaku sesuai tanggal validitas quotation. Pajak dan biaya tambahan mengikuti kesepakatan tertulis.' }}</p>
<p><strong>Payment Terms</strong><br>Pembayaran mengikuti invoice yang diterbitkan setelah pekerjaan dikonfirmasi.</p>
<div class="sign"><div><div class="line">Prepared By<br>{{ $quotation->creator?->name ?? '-' }}</div></div><div><div class="line">Approved By<br>{{ $quotation->approver?->name ?? '-' }}</div></div></div>
<div class="footer">Page <script type="text/php">if(isset($pdf)){echo $PAGE_NUM.' of '.$PAGE_COUNT;}</script></div>
</body>
</html>
