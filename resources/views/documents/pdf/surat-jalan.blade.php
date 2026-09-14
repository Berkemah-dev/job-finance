@php
    $customer = $job->customer ?? $quotation?->customer;
    $snapshot = $quotation?->customer_snapshot ?? [];

    $consigneeName = $job->consignee_name ?: ($customer?->name ?? ($snapshot['name'] ?? '—'));
    $consigneeAddress = $job->consignee_address ?: ($customer?->address ?? ($snapshot['address'] ?? '—'));
    $customerContact = $customer?->contact_name ?: ($customer?->authorizer_name ?? ($snapshot['contact_name'] ?? '—'));

    $reference = ($job->awb_number ?: $job->bl_number)
        ?: ($job->hawb_number ?: $job->hbl_number)
        ?: ($job->shipment_reference ?: '—');

    $hasPhysicalCargo = !empty($job->cargo_description) || !empty($job->package_count);
    $items = $hasPhysicalCargo ? [] : ($job->quotation_snapshot['items'] ?? []);

    $isFcl = ($job->container_type || in_array($job->service_type, ['exp_sea', 'imp_sea', 'sea'], true)) && !str_contains(strtolower($job->service_type ?? ''), 'lcl');
    $isLcl = str_contains(strtolower($job->service_type ?? ''), 'lcl');
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>DELIVERY ORDER SURAT JALAN - {{ $job->number }}</title>
    <style>
        @page {
            margin: 25px 35px 25px 35px;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', Courier, DejaVu Sans Mono, monospace;
            color: #000;
            font-size: 8.5px;
            line-height: 1.35;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-name {
            font-weight: bold;
            font-size: 9.5px;
            margin-bottom: 2px;
        }
        .company-address {
            font-size: 8.5px;
            line-height: 1.35;
        }
        .doc-title-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .doc-title-main {
            font-weight: bold;
            font-size: 13px;
            text-decoration: underline;
            letter-spacing: 0.5px;
        }
        .meta-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .meta-container td {
            vertical-align: top;
        }
        .checkbox-table {
            border-collapse: collapse;
            float: right;
        }
        .checkbox-box {
            border: 1px solid #000;
            width: 22px;
            height: 16px;
            text-align: center;
            line-height: 15px;
            font-weight: bold;
            font-size: 9.5px;
        }
        .checkbox-label {
            padding-left: 8px;
            font-weight: bold;
            font-size: 9px;
            vertical-align: middle;
        }
        .section-header {
            font-weight: bold;
            font-size: 9.5px;
            margin-bottom: 3px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .data-table td {
            vertical-align: top;
            padding: 1px 0;
            font-size: 8.5px;
        }
        .col-label {
            width: 170px;
        }
        .col-sep {
            width: 15px;
            text-align: center;
        }
        .items-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .items-grid th {
            border: 1px solid #000;
            padding: 3.5px 4px;
            font-weight: bold;
            font-size: 8px;
            text-align: center;
        }
        .items-grid td {
            border: 1px solid #000;
            padding: 3.5px 4px;
            font-size: 8px;
            vertical-align: middle;
        }
        .disclaimer {
            font-size: 8px;
            font-weight: bold;
            font-style: italic;
            margin-top: 5px;
            margin-bottom: 25px;
            line-height: 1.3;
        }
        .signature-title {
            text-align: center;
            font-weight: bold;
            font-size: 9.5px;
            margin-bottom: 8px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signature-table td {
            text-align: center;
            vertical-align: top;
        }
        .signature-img-space {
            height: 52px;
            text-align: center;
            vertical-align: middle;
        }
        .signature-img {
            max-height: 48px;
            max-width: 140px;
            display: inline-block;
        }
    </style>
</head>
<body>

    <!-- TOP HEADER -->
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <img src="{{ public_path('images/rdx-banner.png') }}" style="max-height: 44px; max-width: 230px;" alt="RDX LOGISTICS">
            </td>
            <td style="width: 50%; text-align: right;">
                <div class="company-name">PT.RADIX INTERNATIONAL LOGISTICS</div>
                <div class="company-address">Jl.Teh No.3C Jakarta Barat 11110 Indonesia</div>
                <div class="company-address">Telp : 021-38873060</div>
            </td>
        </tr>
    </table>

    <!-- DOCUMENT TITLE -->
    <div class="doc-title-container">
        <div class="doc-title-main">DELIVERY ORDER</div>
        <div class="doc-title-main" style="margin-top: 2px;">SURAT JALAN</div>
    </div>

    <!-- META & CHECKBOX TABLE -->
    <table class="meta-container">
        <tr>
            <td style="width: 70%;">
                <table style="border-collapse: collapse;">
                    <tr>
                        <td style="width: 160px; font-weight: bold;">Nomor Surat Jalan</td>
                        <td style="width: 15px; text-align: center; font-weight: bold;">:</td>
                        <td style="font-weight: bold;">SJ-RDX/{{ $job->job_date?->format('Y') ?? now()->format('Y') }}/{{ str_pad($job->id, 4, '0', STR_PAD_LEFT) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; padding-top: 3px;">Tanggal Surat Jalan</td>
                        <td style="text-align: center; font-weight: bold; padding-top: 3px;">:</td>
                        <td style="padding-top: 3px;">{{ $job->job_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 30%;">
                <table class="checkbox-table">
                    <tr>
                        <td class="checkbox-box">{{ $isFcl ? '√' : '' }}</td>
                        <td class="checkbox-label">FCL</td>
                    </tr>
                    <tr>
                        <td class="checkbox-box">{{ $isLcl ? '√' : '' }}</td>
                        <td class="checkbox-label">LCL</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- SECTION: DATA PENGIRIMAN -->
    <div class="section-header">DATA PENGIRIMAN</div>
    <table class="data-table">
        <tr>
            <td class="col-label">Nomor BL/AWB</td>
            <td class="col-sep">:</td>
            <td>{{ $reference }}</td>
        </tr>
        <tr>
            <td class="col-label">Nomor Truk</td>
            <td class="col-sep">:</td>
            <td></td>
        </tr>
        <tr>
            <td class="col-label">Nomor Container</td>
            <td class="col-sep">:</td>
            <td>{{ $job->container_type ? strtoupper($job->container_type) : '' }}</td>
        </tr>
        <tr>
            <td class="col-label">Nama Supir</td>
            <td class="col-sep">:</td>
            <td></td>
        </tr>
        <tr>
            <td class="col-label">Nomor Telepon Supir</td>
            <td class="col-sep">:</td>
            <td></td>
        </tr>
        <tr>
            <td class="col-label">Jenis Kendaraan</td>
            <td class="col-sep">:</td>
            <td></td>
        </tr>
        <tr>
            <td class="col-label">Dari Gudang</td>
            <td class="col-sep">:</td>
            <td>{{ $job->pol ?? $job->origin ?? 'Jakarta' }}</td>
        </tr>
        <tr>
            <td class="col-label">Tujuan Pengiriman</td>
            <td class="col-sep">:</td>
            <td>{{ $consigneeAddress }}</td>
        </tr>
    </table>

    <!-- SECTION: DATA PENERIMA -->
    <div class="section-header">DATA PENERIMA</div>
    <table class="data-table">
        <tr>
            <td class="col-label">Nama Penerima</td>
            <td class="col-sep">:</td>
            <td style="font-weight: bold;">{{ $consigneeName }}</td>
        </tr>
        <tr>
            <td class="col-label">Alamat Penerima</td>
            <td class="col-sep">:</td>
            <td>{{ $consigneeAddress }}</td>
        </tr>
        <tr>
            <td class="col-label">PIC</td>
            <td class="col-sep">:</td>
            <td>{{ $customerContact }}</td>
        </tr>
    </table>

    <!-- SECTION: DETAIL BARANG -->
    <div class="section-header">DETAIL BARANG</div>
    <table class="items-grid">
        <thead>
            <tr>
                <th style="width: 45px;">NO.</th>
                <th>NAMA BARANG</th>
                <th style="width: 65px;">QTY</th>
                <th style="width: 75px;">SATUAN</th>
                <th style="width: 90px;">BERAT (KG)</th>
                <th style="width: 140px;">KETERANGAN</th>
            </tr>
        </thead>
        <tbody>
            @php $rowCount = 0; @endphp
            @forelse($items as $item)
                @php $rowCount++; @endphp
                <tr>
                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                    <td>{{ $item['description'] ?? 'Barang / Dokumen' }}</td>
                    <td style="text-align: center;">{{ \App\Support\Money::format($item['quantity'] ?? 1) }}</td>
                    <td style="text-align: center;">{{ $item['unit'] ?? 'Package' }}</td>
                    <td style="text-align: center;">{{ isset($item['weight']) ? \App\Support\Money::format($item['weight']) : ($job->gross_weight ? \App\Support\Money::format($job->gross_weight) : '—') }}</td>
                    <td></td>
                </tr>
            @empty
                @php $rowCount = 1; @endphp
                <tr>
                    <td style="text-align: center;">1</td>
                    <td>{{ $job->cargo_description ?? 'Barang / Kargo Pengiriman' }}</td>
                    <td style="text-align: center;">{{ $job->package_count ?? 1 }}</td>
                    <td style="text-align: center;">{{ $job->package_unit ?? 'Package' }}</td>
                    <td style="text-align: center;">{{ $job->gross_weight ? \App\Support\Money::format($job->gross_weight) : '—' }}</td>
                    <td></td>
                </tr>
            @endforelse

            @for($i = $rowCount; $i < 3; $i++)
                <tr>
                    <td style="height: 15px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <!-- DISCLAIMER NOTE -->
    <div class="disclaimer">
        ** Mohon periksa kembali kesesuaian dan kondisi barang diterima dengan baik sebelum menandatangani surat jalan **
    </div>

    <!-- SECTION: TANDA TANGAN -->
    <div class="signature-title">TANDA TANGAN</div>
    <table class="signature-table">
        <tr>
            <td style="width: 33.33%; font-weight: bold;">Pengirim,</td>
            <td style="width: 33.33%; font-weight: bold;">Supir,</td>
            <td style="width: 33.33%; font-weight: bold;">Penerima,</td>
        </tr>
        <tr>
            <td class="signature-img-space">
                <img src="{{ public_path('images/signature-alliyah.jpeg') }}" class="signature-img" alt="TTD Pengirim">
            </td>
            <td class="signature-img-space"></td>
            <td class="signature-img-space"></td>
        </tr>
        <tr>
            <td>( Alliyah )</td>
            <td>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</td>
            <td>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</td>
        </tr>
    </table>

</body>
</html>
