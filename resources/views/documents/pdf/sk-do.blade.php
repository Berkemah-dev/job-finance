<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Kuasa Pengambilan DO - {{ $job->number }}</title>
    <style>
        @page {
            margin: 28px 36px 30px 36px;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 10px;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #0f1f3d;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .brand {
            font-size: 16px;
            font-weight: 800;
            color: #0f1f3d;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .brand-sub {
            color: #475569;
            font-size: 8.5px;
            margin-top: 2px;
            line-height: 1.35;
        }
        .doc-title-box {
            text-align: center;
            margin: 14px 0 16px;
        }
        .doc-title {
            font-size: 13.5px;
            font-weight: 800;
            color: #0f1f3d;
            letter-spacing: 1px;
            text-decoration: underline;
        }
        .doc-no {
            font-size: 9.5px;
            color: #475569;
            margin-top: 3px;
            font-weight: 600;
        }
        .intro-p {
            margin: 10px 0 6px;
            text-align: justify;
            font-size: 9.5px;
        }
        .party-table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0 8px 12px;
            font-size: 9.5px;
        }
        .party-table td {
            padding: 2.5px 4px;
            vertical-align: top;
        }
        .party-table .label {
            width: 135px;
            font-weight: 600;
            color: #334155;
        }
        .cargo-card {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin: 10px 0;
            background: #fff;
            border-collapse: collapse;
        }
        .cargo-card th {
            background: #f1f5f9;
            color: #0f172a;
            padding: 5px 8px;
            font-size: 9px;
            font-weight: 700;
            text-align: left;
            border-bottom: 1px solid #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .cargo-card td {
            padding: 4px 8px;
            font-size: 9px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .cargo-card .col-label {
            width: 140px;
            font-weight: 600;
            color: #475569;
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
        }
        .cargo-card .col-val {
            font-weight: 700;
            color: #0f172a;
        }
        .signature-table {
            width: 100%;
            margin-top: 24px;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            padding: 0 20px;
            vertical-align: top;
        }
        .sign-title-cell {
            vertical-align: bottom !important;
            padding-bottom: 8px !important;
        }
        .sign-title {
            font-size: 9.5px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.4;
        }
        .sign-box {
            height: 65px;
            border-bottom: 1px solid #64748b;
            margin-bottom: 8px;
            position: relative;
        }
        .meterai-box {
            display: inline-block;
            border: 1px dashed #94a3b8;
            padding: 4px 10px;
            font-size: 7.5px;
            color: #64748b;
            margin-top: 18px;
            border-radius: 3px;
        }
        .sign-name {
            font-size: 9.5px;
            font-weight: 800;
            color: #0f172a;
        }
        .sign-sub {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 2px;
        }
        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -10px;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            color: #94a3b8;
            font-size: 8px;
        }
        .footer .right {
            float: right;
        }
    </style>
</head>
<body>
@php
    $customerName = $job->customer?->name ?? $quotation?->customer_snapshot['name'] ?? $job->consignee_name ?? '—';
    $customerTax = $job->customer?->tax_number ?? $quotation?->customer_snapshot['tax_number'] ?? '—';
    $customerContact = $job->customer?->contact_name ?? 'Direktur / Penanggung Jawab';
    $customerAddress = $job->customer?->address ?? $quotation?->customer_snapshot['address'] ?? $job->consignee_address ?? '—';
    $blNumber = $job->bl_number ?: ($job->awb_number ?: ($job->hbl_number ?: ($job->hawb_number ?: '—')));
    $hblNumber = $job->hbl_number ?: ($job->hawb_number ?: '—');
    $vessel = $job->vessel_voyage ?? ($job->flight_number ?? '—');
    $pol = $job->pol ?? $job->origin ?? '—';
    $pod = $job->pod ?? $job->destination ?? '—';
    $qty = $job->package_count ? $job->package_count . ' Box / Koli' : ($job->container_type ? '1x ' . strtoupper($job->container_type) : ($quotation?->cargo_qty ?? '—'));
    $weight = $job->gross_weight ? \App\Support\Money::format($job->gross_weight) . ' KGS' : ($quotation?->weight_meas ?? '—');
    $volume = $job->volume ? $job->volume . ' M3' : '—';
    $commodity = $job->cargo_description ?? $quotation?->commodity ?? 'General Cargo';
@endphp

<div class="header">
    <div class="brand">PT. RADIX INTERNATIONAL LOGISTICS</div>
    <div class="brand-sub">JL. TEH NO 3C RT.008 RW.007, PINANGSIA, TAMAN SARI, KOTA ADM. JAKARTA BARAT, DKI JAKARTA<br>NPWP: 02.701.891.8-603.2000 · NITKU: 0270189186032000000000</div>
</div>

<div class="doc-title-box">
    <div class="doc-title">SURAT KUASA PENGAMBILAN D/O</div>
    <div class="doc-no">Nomor: SK-DO/{{ $job->number }}/{{ now()->format('Y') }}</div>
</div>

<p class="intro-p">Yang bertanda tangan di bawah ini:</p>
<table class="party-table">
    <tr>
        <td class="label">Nama Perusahaan</td>
        <td>: <strong>{{ $customerName }}</strong></td>
    </tr>
    <tr>
        <td class="label">NPWP Perusahaan</td>
        <td>: {{ $customerTax }}</td>
    </tr>
    <tr>
        <td class="label">Alamat Lengkap</td>
        <td>: {{ $customerAddress }}</td>
    </tr>
</table>

<p class="intro-p" style="margin-top: 8px;">Dengan ini memberikan kuasa penuh kepada:</p>
<table class="party-table">
    <tr>
        <td class="label">Nama Perusahaan</td>
        <td>: <strong>PT. RADIX INTERNATIONAL LOGISTICS</strong></td>
    </tr>
    <tr>
        <td class="label">NPWP / NITKU</td>
        <td>: 02.701.891.8-603.2000 / 0270189186032000000000</td>
    </tr>
    <tr>
        <td class="label">Alamat</td>
        <td>: JL. TEH NO 3C RT.008 RW.007, PINANGSIA, TAMAN SARI, KOTA ADM. JAKARTA BARAT</td>
    </tr>
</table>

<p class="intro-p" style="margin-top: 8px;">Untuk melakukan pengurusan, penebusan, penandatanganan berkas, serta pengambilan <strong>Original Delivery Order (D/O)</strong> kepada pihak Pelayaran / Penerbangan / Agen Pengangkut atas kedatangan kargo impor dengan rincian sebagai berikut:</p>

<table class="cargo-card">
    <thead>
        <tr>
            <th colspan="2">RINCIAN PENGIRIMAN & DOKUMEN MUATAN</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="col-label">No. BL / AWB (Master)</td>
            <td class="col-val">{{ $blNumber }}</td>
        </tr>
        <tr>
            <td class="col-label">No. HBL / HAWB (Host)</td>
            <td class="col-val">{{ $hblNumber }}</td>
        </tr>
        <tr>
            <td class="col-label">Sarana Pengangkut (Vessel)</td>
            <td class="col-val">{{ $vessel }}</td>
        </tr>
        <tr>
            <td class="col-label">Pelabuhan Muat / Bongkar</td>
            <td class="col-val">{{ $pol }} &rarr; {{ $pod }}</td>
        </tr>
        <tr>
            <td class="col-label">Jumlah Kemasan / Qty</td>
            <td class="col-val">{{ $qty }}</td>
        </tr>
        <tr>
            <td class="col-label">Berat Kotor / Volume</td>
            <td class="col-val">{{ $weight }} / {{ $volume }}</td>
        </tr>
        <tr>
            <td class="col-label">Uraian / Commodity</td>
            <td class="col-val">{{ $commodity }}</td>
        </tr>
    </tbody>
</table>

<p class="intro-p" style="margin-top: 10px;">Segala biaya dan akibat hukum yang timbul terkait pelaksanaan kuasa ini menjadi tanggung jawab kami sepenuhnya. Demikian Surat Kuasa ini dibuat dengan sebenar-benarnya untuk dapat dipergunakan sebagaimana mestinya.</p>

<table class="signature-table">
    <tr>
        <td class="sign-title-cell">
            <div class="sign-title">Penerima Kuasa,</div>
        </td>
        <td class="sign-title-cell">
            <div class="sign-title">Jakarta, {{ now()->format('d F Y') }}<br>Pemberi Kuasa,</div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="sign-box"></div>
        </td>
        <td>
            <div class="sign-box">
                <span class="meterai-box">Materai Rp 10.000</span>
            </div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="sign-name">PT. RADIX INTERNATIONAL LOGISTICS</div>
            <div class="sign-sub">Tanda Tangan & Cap PPJK / Freight Forwarder</div>
        </td>
        <td>
            <div class="sign-name">{{ $customerName }}</div>
            <div class="sign-sub">Tanda Tangan, Nama Jelas & Cap Perusahaan</div>
        </td>
    </tr>
</table>

<div class="footer">
    {{ $job->number }} · Surat Kuasa Pengambilan D/O <span class="right">Dicetak: {{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>
