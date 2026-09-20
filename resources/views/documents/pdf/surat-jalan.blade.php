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
            margin: 25pt 35pt 20pt 35pt;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000000;
            font-size: 8.5pt;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        table.header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14pt;
        }
        table.header-table td {
            vertical-align: top;
        }
        .company-name {
            font-weight: bold;
            font-size: 9.5pt;
            margin-bottom: 2pt;
        }
        .company-address {
            font-size: 8.5pt;
            line-height: 1.35;
        }
        .doc-title-container {
            text-align: center;
            margin-bottom: 14pt;
        }
        .doc-title-main {
            font-weight: bold;
            font-size: 13pt;
            letter-spacing: 0.5px;
        }
        table.meta-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10pt;
        }
        table.meta-container td {
            vertical-align: top;
        }
        table.checkbox-table {
            border-collapse: collapse;
            float: right;
        }
        .checkbox-box {
            border: 1px solid #000000;
            width: 18pt;
            height: 14pt;
            text-align: center;
            line-height: 13pt;
            font-weight: bold;
            font-size: 9pt;
        }
        .checkbox-label {
            padding-left: 6pt;
            font-weight: bold;
            font-size: 8.5pt;
            vertical-align: middle;
        }
        .section-header {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 3pt;
            text-transform: uppercase;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10pt;
        }
        table.data-table td {
            vertical-align: top;
            padding: 1.5pt 0;
            font-size: 8.5pt;
        }
        .col-label {
            width: 140pt;
        }
        .col-sep {
            width: 12pt;
            text-align: center;
        }
        table.items-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4pt;
        }
        table.items-grid th {
            border: 1px solid #000000;
            padding: 3.5pt 4pt;
            font-weight: bold;
            font-size: 8pt;
            text-align: center;
            background-color: #f2f2f2;
        }
        table.items-grid td {
            border: 1px solid #000000;
            padding: 3.5pt 4pt;
            font-size: 8pt;
            vertical-align: middle;
        }
        .disclaimer {
            font-size: 7.5pt;
            font-weight: bold;
            font-style: italic;
            margin-top: 4pt;
            margin-bottom: 18pt;
            line-height: 1.3;
        }
        .signature-title {
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 6pt;
        }
        table.signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.signature-table td {
            text-align: center;
            vertical-align: top;
        }
        .signature-img-space {
            height: 44pt;
            text-align: center;
            vertical-align: middle;
        }
        .signature-img {
            max-height: 42pt;
            max-width: 130pt;
            display: inline-block;
        }
    </style>
</head>
<body>

    <!-- TOP HEADER -->
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <img src="{{ public_path('images/rdx-banner.png') }}" style="max-height: 40pt; max-width: 200pt;" alt="RDX LOGISTICS">
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
        <div class="doc-title-main">DELIVERY ORDER SURAT JALAN</div>
    </div>

    <!-- META & CHECKBOX TABLE -->
    <table class="meta-container">
        <tr>
            <td style="width: 70%;">
                <table style="border-collapse: collapse;">
                    <tr>
                        <td style="width: 140pt; font-weight: bold;">Nomor Surat Jalan</td>
                        <td style="width: 12pt; text-align: center; font-weight: bold;">:</td>
                        <td style="font-weight: bold;">SJ-RDX/{{ $job->job_date?->format('Y') ?? now()->format('Y') }}/{{ str_pad($job->id, 4, '0', STR_PAD_LEFT) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; padding-top: 2pt;">Tanggal Surat Jalan</td>
                        <td style="text-align: center; font-weight: bold; padding-top: 2pt;">:</td>
                        <td style="padding-top: 2pt;">{{ $job->job_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 30%;">
                <table class="checkbox-table">
                    <tr>
                        <td class="checkbox-box">{{ $isFcl ? '√' : '' }}</td>
                        <td class="checkbox-label">FCL</td>
                        <td style="width: 12pt;"></td>
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
            <td>{{ $job->truck_plate_number ?: ($job->vendorTruck?->plate_number ?: '—') }}</td>
        </tr>
        <tr>
            <td class="col-label">Nomor Container</td>
            <td class="col-sep">:</td>
            <td>{{ $job->container_number ?: ($job->container_type ? strtoupper($job->container_type) : '—') }}</td>
        </tr>
        <tr>
            <td class="col-label">Nama Supir</td>
            <td class="col-sep">:</td>
            <td>{{ $job->driver_name ?: ($job->vendorTruck?->driver_name ?: '—') }}</td>
        </tr>
        <tr>
            <td class="col-label">Nomor Telepon Supir</td>
            <td class="col-sep">:</td>
            <td>{{ $job->driver_phone ?: ($job->vendorTruck?->driver_phone ?: '—') }}</td>
        </tr>
        <tr>
            <td class="col-label">Jenis Kendaraan</td>
            <td class="col-sep">:</td>
            <td>{{ $job->vehicle_type ?: ($job->vendorTruck?->vehicle_type ?: '—') }}</td>
        </tr>
        <tr>
            <td class="col-label">Dari Gudang</td>
            <td class="col-sep">:</td>
            <td>{{ $job->pol ?? $job->origin ?? 'Jakarta' }}</td>
        </tr>
        <tr>
            <td class="col-label">Tujuan Pengiriman</td>
            <td class="col-sep">:</td>
            <td>{{ $job->delivery_address ?: $consigneeAddress }}</td>
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
                <th style="width: 35pt;">NO.</th>
                <th>NAMA BARANG</th>
                <th style="width: 55pt;">QTY</th>
                <th style="width: 65pt;">SATUAN</th>
                <th style="width: 75pt;">BERAT (KG)</th>
                <th style="width: 110pt;">KETERANGAN</th>
            </tr>
        </thead>
        <tbody>
            @php $rowCount = 0; @endphp
            @if(!empty($job->cargo_description))
                @php $rowCount = 1; @endphp
                <tr>
                    <td style="text-align: center;">1</td>
                    <td style="font-weight: bold;">{{ $job->cargo_description }}</td>
                    <td style="text-align: center;">{{ $job->package_count ?? 1 }}</td>
                    <td style="text-align: center;">{{ $job->package_unit ?? ($job->container_type ? strtoupper($job->container_type) : 'Package') }}</td>
                    <td style="text-align: center;">{{ $job->gross_weight ? \App\Support\Money::format($job->gross_weight) : '—' }}</td>
                    <td>{{ $job->operational_notes ?? '' }}</td>
                </tr>
            @elseif(!empty($items))
                @foreach($items as $item)
                    @php $rowCount++; @endphp
                    <tr>
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td>{{ $item['description'] ?? 'Barang / Dokumen' }}</td>
                        <td style="text-align: center;">{{ \App\Support\Money::format($item['quantity'] ?? 1) }}</td>
                        <td style="text-align: center;">{{ $item['unit'] ?? 'Package' }}</td>
                        <td style="text-align: center;">{{ isset($item['weight']) ? \App\Support\Money::format($item['weight']) : ($job->gross_weight ? \App\Support\Money::format($job->gross_weight) : '—') }}</td>
                        <td></td>
                    </tr>
                @endforeach
            @else
                @php $rowCount = 1; @endphp
                <tr>
                    <td style="text-align: center;">1</td>
                    <td>{{ $job->subject ?: 'Barang / Kargo Pengiriman' }}</td>
                    <td style="text-align: center;">{{ $job->package_count ?? 1 }}</td>
                    <td style="text-align: center;">{{ $job->package_unit ?? ($job->container_type ? strtoupper($job->container_type) : 'Package') }}</td>
                    <td style="text-align: center;">{{ $job->gross_weight ? \App\Support\Money::format($job->gross_weight) : '—' }}</td>
                    <td>{{ $job->operational_notes ?? '' }}</td>
                </tr>
            @endif

            @for($i = $rowCount; $i < 3; $i++)
                <tr>
                    <td style="height: 14pt; text-align: center;">{{ $i + 1 }}</td>
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
