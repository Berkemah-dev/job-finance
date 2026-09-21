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

    $vehicle = strtoupper(trim($job->vehicle_type ?: ($job->vendorTruck?->vehicle_type ?: '')));
    if (str_contains($vehicle, 'TRAILER')) {
        $isFcl = true;
        $isLcl = false;
    } elseif (
        str_contains($vehicle, 'FUSO') ||
        str_contains($vehicle, 'PICKUP') ||
        str_contains($vehicle, 'PICK UP') ||
        str_contains($vehicle, 'BLINDVAN') ||
        str_contains($vehicle, 'BLIND VAN') ||
        str_contains($vehicle, 'CDD') ||
        str_contains($vehicle, 'CDE')
    ) {
        $isFcl = false;
        $isLcl = true;
    } else {
        $isFcl = ($job->container_type || in_array($job->service_type, ['exp_sea', 'imp_sea', 'sea'], true)) && !str_contains(strtolower($job->service_type ?? ''), 'lcl');
        $isLcl = str_contains(strtolower($job->service_type ?? ''), 'lcl');
    }

    $bannerFile = public_path('images/rdx-banner.png');
    $bannerBase64 = file_exists($bannerFile) ? 'data:image/png;base64,' . base64_encode(file_get_contents($bannerFile)) : '';

    $signAlliyahFile = public_path('images/signature-alliyah.jpeg');
    $signAlliyahBase64 = file_exists($signAlliyahFile) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($signAlliyahFile)) : '';
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>DELIVERY ORDER - {{ $job->number }}</title>
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
            color: #111827;
            font-size: 9.5pt;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        table.header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10pt;
        }
        table.header-table td {
            vertical-align: top;
        }
        .company-name {
            font-weight: bold;
            font-size: 10.5pt;
            margin-bottom: 2pt;
            color: #0f172a;
        }
        .company-address {
            font-size: 9pt;
            line-height: 1.35;
            color: #334155;
        }
        .doc-title-container {
            text-align: center;
            margin-top: 4pt;
            margin-bottom: 14pt;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 4pt;
        }
        .doc-title-main {
            font-weight: bold;
            font-size: 15pt;
            letter-spacing: 1px;
            color: #0f172a;
        }
        table.meta-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10pt;
        }
        table.meta-container td {
            vertical-align: middle;
        }
        table.checkbox-table {
            border-collapse: collapse;
            float: right;
        }
        .checkbox-box {
            border: 1.5px solid #000000;
            width: 20pt;
            height: 16pt;
            text-align: center;
            line-height: 15pt;
            font-weight: bold;
            font-size: 11pt;
            font-family: DejaVu Sans, Arial, sans-serif;
        }
        .checkbox-label {
            padding-left: 6pt;
            font-weight: bold;
            font-size: 9.5pt;
            vertical-align: middle;
        }
        .section-header {
            font-weight: bold;
            font-size: 10pt;
            margin-top: 8pt;
            margin-bottom: 4pt;
            text-transform: uppercase;
            color: #0f172a;
            background-color: #f1f5f9;
            padding: 3pt 6pt;
            border-left: 3px solid #2563eb;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6pt;
        }
        table.data-table td {
            vertical-align: top;
            padding: 2.5pt 4pt;
            font-size: 9.5pt;
        }
        .col-label {
            width: 140pt;
            font-weight: 600;
            color: #334155;
        }
        .col-sep {
            width: 12pt;
            text-align: center;
        }
        table.items-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4pt;
            margin-bottom: 4pt;
        }
        table.items-grid th {
            border: 1px solid #94a3b8;
            padding: 4.5pt 5pt;
            font-weight: bold;
            font-size: 9pt;
            text-align: center;
            background-color: #f1f5f9;
            color: #0f172a;
        }
        table.items-grid td {
            border: 1px solid #cbd5e1;
            padding: 4.5pt 5pt;
            font-size: 9pt;
            vertical-align: middle;
        }
        .disclaimer {
            font-size: 8.5pt;
            font-weight: bold;
            font-style: italic;
            margin-top: 4pt;
            margin-bottom: 14pt;
            color: #475569;
            line-height: 1.35;
        }
        .signature-title {
            text-align: center;
            font-weight: bold;
            font-size: 9.5pt;
            margin-bottom: 6pt;
        }
        table.signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.signature-table td {
            text-align: center;
            vertical-align: top;
            font-size: 9.5pt;
        }
        .signature-img-space {
            height: 48pt;
            text-align: center;
            vertical-align: middle;
        }
        .signature-img {
            max-height: 46pt;
            max-width: 140pt;
            display: inline-block;
        }
    </style>
</head>
<body>

    <!-- TOP HEADER -->
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <img src="{{ $bannerBase64 ?: public_path('images/rdx-banner.png') }}" style="max-height: 42pt; max-width: 210pt;" alt="RDX LOGISTICS">
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
    </div>

    <!-- META & CHECKBOX TABLE -->
    <table class="meta-container">
        <tr>
            <td style="width: 65%;">
                <table style="border-collapse: collapse;">
                    <tr>
                        <td style="width: 135pt; font-weight: bold;">Nomor Delivery Order</td>
                        <td style="width: 12pt; text-align: center; font-weight: bold;">:</td>
                        <td style="font-weight: bold; color: #0f172a;">DO-RDX/{{ $job->job_date?->format('Y') ?? now()->format('Y') }}/{{ str_pad($job->id, 4, '0', STR_PAD_LEFT) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; padding-top: 3pt;">Tanggal Delivery Order</td>
                        <td style="text-align: center; font-weight: bold; padding-top: 3pt;">:</td>
                        <td style="padding-top: 3pt;">{{ $job->job_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 35%; text-align: right;">
                <table class="checkbox-table">
                    <tr>
                        <td class="checkbox-box">{{ $isFcl ? '✓' : '' }}</td>
                        <td class="checkbox-label">FCL</td>
                        <td style="width: 16pt;"></td>
                        <td class="checkbox-box">{{ $isLcl ? '✓' : '' }}</td>
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
            <td style="font-weight: 600;">{{ $reference }}</td>
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
            <td>{{ $job->vendorTruck?->driver_name ?: ($job->driver_name ?: '—') }}</td>
        </tr>
        <tr>
            <td class="col-label">Nomor Telepon Supir</td>
            <td class="col-sep">:</td>
            <td>{{ $job->vendorTruck?->driver_phone ?: ($job->driver_phone ?: '—') }}</td>
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
            <td style="font-weight: bold; color: #0f172a;">{{ $consigneeName }}</td>
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
                <th style="width: 32pt;">NO.</th>
                <th>NAMA BARANG</th>
                <th style="width: 50pt;">QTY</th>
                <th style="width: 65pt;">SATUAN</th>
                <th style="width: 80pt;">BERAT (KG)</th>
                <th style="width: 120pt;">KETERANGAN</th>
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
                    <td style="height: 16pt; text-align: center;">{{ $i + 1 }}</td>
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
        ** Mohon periksa kembali kesesuaian dan kondisi barang diterima dengan baik sebelum menandatangani delivery order **
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
                <img src="{{ $signAlliyahBase64 ?: public_path('images/signature-alliyah.jpeg') }}" class="signature-img" alt="TTD Pengirim">
            </td>
            <td class="signature-img-space"></td>
            <td class="signature-img-space"></td>
        </tr>
        <tr>
            <td style="font-weight: 600;">( Alliyah )</td>
            <td>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</td>
            <td>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</td>
        </tr>
    </table>

</body>
</html>
