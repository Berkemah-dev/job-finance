<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page{margin:28px 34px}body{font-family:DejaVu Sans,sans-serif;color:#26364f;font-size:11px;line-height:1.55}.header{border-bottom:2px solid #0f1f3d;padding-bottom:14px;margin-bottom:24px}.company{font-size:19px;font-weight:700;color:#163f8f}.muted{color:#6f7f96}.title{font-size:24px;letter-spacing:2px;text-align:right;color:#1f3e72;margin-top:-44px}.grid{width:100%;margin-bottom:18px}.col{float:left;width:48%;margin-right:2%;vertical-align:top}.clear{clear:both}.box{border:1px solid #dce5f2;padding:12px}table{width:100%;border-collapse:collapse;margin-top:14px}th{background:#f1f5fb;color:#50647f;text-align:left;font-size:9px;padding:8px;border:1px solid #dce5f2}td{padding:8px;border:1px solid #e6edf6}.section{margin-top:20px}.sign{width:100%;margin-top:42px}.sign-col{float:left;width:33%;text-align:center}.line{border-top:1px solid #aeb9c8;margin:54px 20px 0;padding-top:8px}.footer{position:fixed;bottom:-8px;left:0;right:0;text-align:center;font-size:9px;color:#8a97aa}
    </style>
</head>
<body>
<div class="header"><div class="company">JobFinance Demo Company</div><div class="muted">Jakarta, Indonesia<br>operations@jobfinance.test · +62 21 0000 0000</div><div class="title">SURAT JALAN</div></div>
<div class="grid"><div class="col"><div class="box"><strong>Kepada Yth. / Penerima</strong><br>{{ $job->consignee_name ?? $quotation->customer_snapshot['name'] }}<br>{{ $job->consignee_address ?? '-' }}</div></div><div class="col"><div class="box"><strong>Nomor Job</strong>: {{ $job->number }}<br><strong>Tanggal</strong>: {{ now()->format('d/m/Y') }}<br><strong>Pengirim</strong>: {{ $job->shipper_name ?? 'JobFinance Demo Company' }}<br><strong>Kendaraan/No Polisi</strong>: ....................</div></div><div class="clear"></div></div>
<div class="section"><p>Harap diterima barang-barang di bawah ini dengan baik dan benar:</p></div>
<table><thead><tr><th>No</th><th>Nama Barang / Deskripsi</th><th>Quantity</th><th>Keterangan</th></tr></thead><tbody>@foreach($job->quotation_snapshot['items'] as $item)<tr><td>{{ $loop->iteration }}</td><td>{{ $item['description'] }}</td><td>{{ \App\Support\Money::format($item['quantity']) }} {{ $item['unit'] }}</td><td></td></tr>@endforeach</tbody></table>
<div class="section"><p>Catatan Tambahan:<br>{{ $job->operational_notes ?: '-' }}</p></div>
<div class="sign"><div class="sign-col"><div class="line">Penerima (Nama Jelas & Cap)</div></div><div class="sign-col"><div class="line">Pengemudi</div></div><div class="sign-col"><div class="line">Hormat Kami (Pengirim)</div></div><div class="clear"></div></div>
<div class="footer">Page <script type="text/php">if(isset($pdf)){echo $PAGE_NUM.' of '.$PAGE_COUNT;}</script></div>
</body>
</html>
