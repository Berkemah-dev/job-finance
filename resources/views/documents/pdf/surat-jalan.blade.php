<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 26px 30px 30px; }
        body { font-family: DejaVu Sans, sans-serif; color:#17233b; font-size:10.5px; line-height:1.45; }
        .top { width:100%; border-bottom:3px solid #0f1f3d; padding-bottom:12px; margin-bottom:18px; }
        .brand { font-size:20px; font-weight:800; color:#0f1f3d; letter-spacing:.5px; }
        .brand-sub { color:#66748a; font-size:9.5px; margin-top:3px; }
        .doc-title { text-align:right; font-size:23px; font-weight:800; color:#0f1f3d; letter-spacing:1.8px; margin-top:-42px; }
        .doc-sub { text-align:right; color:#b91c1c; font-size:9px; font-weight:700; letter-spacing:1.2px; margin-top:2px; }
        .meta { width:100%; margin:16px 0 14px; border-collapse:separate; border-spacing:0 8px; }
        .meta td { vertical-align:top; }
        .box { border:1px solid #dbe3ef; border-radius:8px; padding:10px 12px; }
        .box-title { color:#7c8aa1; font-size:8.5px; font-weight:800; letter-spacing:1px; text-transform:uppercase; margin-bottom:5px; }
        .box strong { color:#0f1f3d; font-size:12px; }
        .info-table { width:100%; border-collapse:collapse; margin:14px 0 16px; }
        .info-table th { width:22%; text-align:left; background:#f7f9fc; color:#586a84; border:1px solid #dfe7f2; padding:7px 9px; font-size:9px; }
        .info-table td { border:1px solid #dfe7f2; padding:7px 9px; }
        .section-title { margin:18px 0 8px; color:#0f1f3d; font-size:12px; font-weight:800; }
        .items { width:100%; border-collapse:collapse; }
        .items th { background:#0f1f3d; color:#fff; padding:8px; border:1px solid #0f1f3d; font-size:9px; text-align:left; }
        .items td { padding:8px; border:1px solid #dfe7f2; vertical-align:top; }
        .items tbody tr:nth-child(even) td { background:#fafcff; }
        .note { border:1px solid #dfe7f2; background:#fafcff; border-radius:8px; padding:10px 12px; min-height:42px; }
        .signature { width:100%; margin-top:34px; border-collapse:collapse; }
        .signature td { width:33.33%; text-align:center; padding:0 12px; vertical-align:bottom; }
        .sign-space { height:58px; border-bottom:1px solid #9aa8ba; margin-bottom:7px; }
        .sign-label { font-size:9.5px; color:#4b5f7a; font-weight:700; }
        .footer { position:fixed; left:0; right:0; bottom:-12px; border-top:1px solid #dfe7f2; padding-top:6px; color:#8a97aa; font-size:8.5px; }
        .footer .right { float:right; }
    </style>
</head>
<body>
@php
    $items = $job->quotation_snapshot['items'] ?? [];
    $reference = $job->shipment_reference ?: ($job->bl_number ?: ($job->awb_number ?: ($job->hbl_number ?: ($job->hawb_number ?: '—'))));
@endphp
<div class="top">
    <div class="brand">RDX / Radix International Logistics</div>
    <div class="brand-sub">Jakarta, Indonesia · operations@radix-logistics.test · +62 21 0000 0000</div>
    <div class="doc-title">SURAT JALAN</div>
    <div class="doc-sub">DELIVERY ORDER</div>
</div>

<table class="meta">
    <tr>
        <td style="width:50%; padding-right:8px;">
            <div class="box"><div class="box-title">Penerima</div><strong>{{ $job->consignee_name ?? ($quotation->customer_snapshot['name'] ?? '—') }}</strong><br>{{ $job->consignee_address ?? '—' }}</div>
        </td>
        <td style="width:50%; padding-left:8px;">
            <div class="box"><div class="box-title">Informasi Surat Jalan</div><strong>{{ $job->number }}</strong><br>Tanggal: {{ now()->format('d/m/Y') }}<br>Referensi: {{ $reference }}</div>
        </td>
    </tr>
</table>

<table class="info-table">
    <tr><th>Pengirim</th><td>{{ $job->shipper_name ?? 'RDX / Radix International Logistics' }}</td><th>Service</th><td>{{ \App\Models\ServiceType::label($job->service_type) }}</td></tr>
    <tr><th>Rute</th><td>{{ $job->pol ?? $job->origin ?? '—' }} → {{ $job->pod ?? $job->destination ?? '—' }}</td><th>ETD / ETA</th><td>{{ $job->etd?->format('d/m/Y') ?? '—' }} / {{ $job->eta?->format('d/m/Y') ?? '—' }}</td></tr>
    <tr><th>Vessel / Flight</th><td>{{ $job->vessel_voyage ?? $job->flight_number ?? '—' }}</td><th>No. Polisi</th><td>........................................</td></tr>
</table>

<div class="section-title">Detail Barang / Dokumen</div>
<table class="items">
    <thead><tr><th style="width:34px;">No</th><th>Nama Barang / Deskripsi</th><th style="width:110px;">Jumlah</th><th style="width:150px;">Keterangan</th></tr></thead>
    <tbody>
        @forelse($items as $item)
            <tr><td>{{ $loop->iteration }}</td><td>{{ $item['description'] ?? 'Barang / Dokumen' }}</td><td>{{ \App\Support\Money::format($item['quantity'] ?? 1) }} {{ $item['unit'] ?? 'Paket' }}</td><td></td></tr>
        @empty
            <tr><td>1</td><td>{{ $job->cargo_description ?? 'Barang / Dokumen Pengiriman' }}</td><td>{{ $job->package_count ?? 1 }} Paket</td><td></td></tr>
        @endforelse
    </tbody>
</table>

<div class="section-title">Catatan</div>
<div class="note">{{ $job->operational_notes ?: 'Barang/dokumen telah diterima dalam keadaan baik sesuai data pengiriman.' }}</div>

<table class="signature">
    <tr><td><div class="sign-space"></div><div class="sign-label">Penerima<br>Nama Jelas & Cap</div></td><td><div class="sign-space"></div><div class="sign-label">Pengemudi</div></td><td><div class="sign-space"></div><div class="sign-label">Hormat Kami</div></td></tr>
</table>

<div class="footer">{{ $job->number }} · Surat Jalan <span class="right">Dicetak {{ now()->format('d/m/Y H:i') }}</span></div>
</body>
</html>
