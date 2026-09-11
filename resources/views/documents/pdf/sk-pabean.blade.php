<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page{margin:28px 34px}body{font-family:DejaVu Sans,sans-serif;color:#26364f;font-size:11px;line-height:1.6}.header{border-bottom:2px solid #0f1f3d;padding-bottom:14px;margin-bottom:20px;text-align:center}.company{font-size:20px;font-weight:700;color:#163f8f}.muted{color:#6f7f96;font-size:10px}.title{font-size:18px;font-weight:bold;text-align:center;text-decoration:underline;margin:16px 0}.content{margin:0 10px}table{width:100%;margin-top:8px}td{padding:4px 6px;vertical-align:top}.label{width:180px;font-weight:bold}.section{margin-top:20px;text-align:justify}.sign{width:100%;margin-top:50px}.sign-col{float:left;width:50%;text-align:center}.line{border-top:1px solid #26364f;margin:70px 30px 0;padding-top:8px;font-weight:bold}.clear{clear:both}
    </style>
</head>
<body>
<div class="header"><div class="company">JobFinance Demo Company</div><div class="muted">Jakarta, Indonesia | operations@jobfinance.test | +62 21 0000 0000</div></div>
<div class="title">SURAT KEPUTUSAN PABEAN</div>
<div class="content">
<table>
    <tr><td class="label">Nomor SK</td><td>: SKP-{{ $job->number }}/{{ now()->format('Y') }}</td></tr>
    <tr><td class="label">Nomor Job</td><td>: {{ $job->number }}</td></tr>
    <tr><td class="label">Tanggal</td><td>: {{ now()->format('d F Y') }}</td></tr>
    <tr><td class="label">Perusahaan</td><td>: <strong>{{ $quotation->customer_snapshot['name'] }}</strong></td></tr>
    <tr><td class="label">NPWP</td><td>: {{ $quotation->customer_snapshot['tax_number'] ?? '..................................' }}</td></tr>
    <tr><td class="label">Alamat</td><td>: {{ $quotation->customer_snapshot['address'] ?? '..................................' }}</td></tr>
</table>

<div class="section">
    <p>Berdasarkan dokumen kepabeanan yang telah diperiksa dan diverifikasi, dengan ini menerangkan bahwa:</p>
</div>

<table style="margin-top:12px;border-top:2px solid #0f1f3d">
    <tr><td class="label">BL / AWB Number</td><td>: <strong>{{ $job->bl_number ?: ($job->awb_number ?: '..................................') }}</strong></td></tr>
    <tr><td class="label">HBL / HAWB</td><td>: {{ $job->hbl_number ?: ($job->hawb_number ?: '..................................') }}</td></tr>
    <tr><td class="label">Booking Reference</td><td>: {{ $job->booking_reference ?? '..................................' }}</td></tr>
    <tr><td class="label">Pelabuhan Muat (POL)</td><td>: {{ $job->pol ?? '..................................' }}</td></tr>
    <tr><td class="label">Pelabuhan Bongkar (POD)</td><td>: {{ $job->pod ?? '..................................' }}</td></tr>
    <tr><td class="label">Vessel / Voyage / Flight</td><td>: {{ $job->vessel_voyage ?? $job->flight_number ?? '..................................' }}</td></tr>
    <tr><td class="label">ETD</td><td>: {{ $job->etd ? $job->etd->format('d/m/Y') : '..........' }}</td></tr>
    <tr><td class="label">ETA</td><td>: {{ $job->eta ? $job->eta->format('d/m/Y') : '..........' }}</td></tr>
    <tr><td class="label">Jenis Layanan</td><td>: {{ strtoupper($job->service_type ?? '-') }}</td></tr>
    <tr><td class="label">Uraian Barang</td><td>: {{ $job->cargo_description ?? '..................................' }}</td></tr>
    <tr><td class="label">Berat Kotor</td><td>: {{ $job->gross_weight ? number_format($job->gross_weight, 2).' Kg' : '..........' }}</td></tr>
    <tr><td class="label">Volume / CBM</td><td>: {{ $job->volume ? number_format($job->volume, 2).' CBM' : '..........' }}</td></tr>
    <tr><td class="label">Jumlah Kemasan</td><td>: {{ $job->package_count ? $job->package_count.' pcs' : '..........' }}</td></tr>
    <tr><td class="label">Tipe Kontainer</td><td>: {{ $job->container_type ?? '-' }}</td></tr>
</table>

<div class="section">
    <p>telah memenuhi persyaratan kepabeanan dan diperbolehkan untuk dilanjutkan proses importasi/eksportasi sesuai dengan ketentuan peraturan perundang-undangan yang berlaku.</p>
</div>
</div>
<div class="sign"><div class="sign-col">Jakarta, {{ now()->format('d F Y') }}<br>Hormat Kami,<div class="line">JobFinance Demo Company</div></div><div class="sign-col">Mengetahui,<div class="line">{{ $quotation->customer_snapshot['name'] }}</div></div><div class="clear"></div></div>
</body>
</html>
