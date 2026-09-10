<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page{margin:28px 34px}body{font-family:DejaVu Sans,sans-serif;color:#26364f;font-size:12px;line-height:1.6}.header{border-bottom:2px solid #0f1f3d;padding-bottom:14px;margin-bottom:24px;text-align:center}.company{font-size:22px;font-weight:700;color:#163f8f}.muted{color:#6f7f96;font-size:11px}.title{font-size:20px;font-weight:bold;text-align:center;text-decoration:underline;margin-top:20px;margin-bottom:30px}.content{margin:0 20px}.section{margin-top:20px;text-align:justify}table{width:100%;margin-top:14px}td{padding:4px;vertical-align:top}.sign{width:100%;margin-top:60px}.sign-col{float:left;width:50%;text-align:center}.line{border-top:1px solid #26364f;margin:80px 40px 0;padding-top:8px;font-weight:bold}.clear{clear:both}
    </style>
</head>
<body>
<div class="header"><div class="company">JobFinance Demo Company</div><div class="muted">Jakarta, Indonesia | operations@jobfinance.test | +62 21 0000 0000</div></div>
<div class="title">SURAT KUASA PENGAMBILAN D/O</div>
<div class="content">
<p>Yang bertanda tangan di bawah ini:</p>
<table><tr><td style="width:120px">Nama Perusahaan</td><td>: <strong>{{ $quotation->customer_snapshot['name'] }}</strong></td></tr><tr><td>Alamat</td><td>: {{ $quotation->customer_snapshot['address'] ?? '..........................................................' }}</td></tr></table>
<div class="section">
    <p>Dengan ini memberikan kuasa kepada:</p>
    <table><tr><td style="width:120px">Nama Perusahaan</td><td>: <strong>JobFinance Demo Company</strong></td></tr><tr><td>Alamat</td><td>: Jakarta, Indonesia</td></tr></table>
</div>
<div class="section">
    <p>Untuk melakukan pengambilan Delivery Order (D/O) atas pengiriman barang dengan rincian dokumen sebagai berikut:</p>
    <table>
        <tr><td style="width:140px">Nomor BL / AWB</td><td>: <strong>{{ $job->bl_number ?: ($job->awb_number ?: '..............................') }}</strong></td></tr>
        <tr><td>Vessel / Voyage</td><td>: {{ $job->vessel_voyage ?? ($job->flight_number ?? '..............................') }}</td></tr>
        <tr><td>Pelabuhan Muat</td><td>: {{ $job->pol ?? '..............................' }}</td></tr>
        <tr><td>Pelabuhan Bongkar</td><td>: {{ $job->pod ?? '..............................' }}</td></tr>
        <tr><td>Jumlah / Jenis</td><td>: {{ $job->package_count ? $job->package_count.' paket' : '......' }} / {{ $job->cargo_description ?: '......' }}</td></tr>
    </table>
</div>
<div class="section"><p>Demikian Surat Kuasa ini kami buat untuk dapat dipergunakan sebagaimana mestinya.</p></div>
<div class="sign"><div class="sign-col">Penerima Kuasa,<div class="line">JobFinance Demo Company</div></div><div class="sign-col">Jakarta, {{ now()->format('d F Y') }}<br>Pemberi Kuasa,<div class="line">{{ $quotation->customer_snapshot['name'] }}</div></div><div class="clear"></div></div>
</div>
</body>
</html>
