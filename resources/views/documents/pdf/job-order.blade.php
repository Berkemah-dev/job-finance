<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Job Order {{ $job->number }}</title>
    <style>
        @page {
            margin: 22pt 26pt 20pt 26pt;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            color: #000000;
            font-size: 8.5pt;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            margin-bottom: 14pt;
            border-collapse: collapse;
        }
        .header-logo {
            width: 58%;
            vertical-align: top;
        }
        .header-logo img {
            width: 140pt;
            height: auto;
        }
        .header-company {
            font-size: 7.5pt;
            line-height: 1.2;
            color: #000;
            margin-top: 4pt;
        }
        .header-company .company-name {
            font-weight: bold;
            font-size: 8.5pt;
            margin-bottom: 2pt;
        }
        .header-meta {
            width: 42%;
            vertical-align: top;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            font-size: 8pt;
        }
        .meta-table td {
            padding: 2pt 5pt;
            border: 1px solid #000;
            vertical-align: middle;
        }
        .meta-table td.lbl {
            width: 36%;
            font-weight: bold;
        }
        .meta-table td.val {
            width: 64%;
            font-weight: normal;
        }

        /* DATA JOB ORDER TABLE */
        table.job-data {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 12pt;
            font-size: 8.5pt;
        }
        table.job-data th {
            background-color: #dfdfdf;
            color: #000;
            font-weight: bold;
            text-align: left;
            padding: 3pt 6pt;
            border: 1px solid #000;
            font-size: 8.5pt;
            letter-spacing: 0.5px;
        }
        table.job-data td {
            border: 1px solid #000;
            padding: 2.5pt 6pt;
            vertical-align: middle;
        }
        table.job-data td.lbl {
            width: 25%;
            font-weight: bold;
        }
        table.job-data td.val {
            width: 75%;
            font-weight: normal;
        }

        /* NOTE BOX */
        .note-title {
            font-size: 8.5pt;
            font-weight: bold;
            color: #000;
            margin-bottom: 4pt;
        }
        .note-box {
            border: 1px solid #000;
            min-height: 48pt;
            padding: 6pt 8pt;
            font-size: 8.5pt;
            line-height: 1.35;
            white-space: pre-wrap;
            color: #000;
        }
        .footer {
            position: fixed;
            bottom: -10px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #777;
        }
    </style>
</head>
<body>

@php
    $customerName = $job->customer?->name ?? $job->quotation_snapshot['customer']['name'] ?? '—';
    $marketingName = $job->sales?->name ?? $quotation?->sales?->name ?? '—';
    $serviceType = strtoupper(\App\Models\ServiceType::label($job->service_type));
    $loadingPort = strtoupper($job->pol ?? $job->origin ?? $quotation?->origin ?? '—');
    $dischargePort = strtoupper($job->pod ?? $job->destination ?? $quotation?->destination ?? '—');
    $etdDate = $job->etd ? $job->etd->format('d-m-Y') : '—';
    $etaDate = $job->eta ? $job->eta->format('d-m-Y') : '—';
    $noAju = $job->booking_reference ?? '—';
    $noHbl = $job->hbl_number ?? $job->hawb_number ?? '—';
    $noMbl = $job->bl_number ?? $job->awb_number ?? '—';
    $vesselName = $job->vessel_voyage ?? $job->flight_number ?? '—';
    $quantityStr = $job->package_count ? $job->package_count . ' Box' : ($job->container_type ? '1x ' . strtoupper($job->container_type) : ($quotation?->cargo_qty ?? '—'));
    $grossWeightStr = $job->gross_weight ? \App\Support\Money::format($job->gross_weight) . ' KGS' : ($quotation?->weight_meas ?? '—');
    $quotationVolume = $quotation?->items?->sum(fn($i) => (float)($i->volume ?? 0));
    $volumeStr = $job->volume ? $job->volume . ' M3' : ($quotationVolume > 0 ? $quotationVolume . ' M3' : '—');
    $commodityStr = $job->cargo_description ?? $quotation?->commodity ?? 'General Cargo';
    $noteContent = $quotation?->internal_notes ?: ($job->operational_notes ?? '—');
    $logoFile = public_path('images/logo.png');
    $logoBase64 = file_exists($logoFile) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile)) : '';
@endphp

{{-- HEADER --}}
<table class="header-table">
    <tr>
        <td class="header-logo">
            <img src="{{ $logoBase64 ?: public_path('images/logo.png') }}" alt="RDX Logistics">
            <div class="header-company">
                <div class="company-name">PT.RADIX INTERNATIONAL LOGISTICS</div>
                Jl.Teh No.3C Jakarta Barat 11110 Indonesia<br>
                Telp : 021-38873060
            </div>
        </td>
        <td class="header-meta">
            <table class="meta-table">
                <tr>
                    <td colspan="2" style="text-align: center; font-weight: bold; font-size: 9.5pt; padding: 3pt; background: #dfdfdf; letter-spacing: 0.5px;">JOB ORDER</td>
                </tr>
                <tr>
                    <td class="lbl">JO. No</td>
                    <td class="val">{{ $job->number }}</td>
                </tr>
                <tr>
                    <td class="lbl">Date</td>
                    <td class="val">{{ $job->job_date?->format('d-m-Y') }}</td>
                </tr>
                <tr>
                    <td class="lbl">Type</td>
                    <td class="val">{{ $serviceType }}</td>
                </tr>
                <tr>
                    <td class="lbl">Marketing</td>
                    <td class="val">{{ strtoupper($marketingName) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- DATA JOB ORDER TABLE --}}
<table class="job-data">
    <thead>
        <tr>
            <th colspan="2">DATA JOB ORDER</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="lbl">Customer</td>
            <td class="val">{{ $customerName }}</td>
        </tr>
        <tr>
            <td class="lbl">Shipper</td>
            <td class="val">{{ $job->shipper_name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Consignee</td>
            <td class="val">{{ $job->consignee_name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Loading</td>
            <td class="val">{{ $loadingPort }}</td>
        </tr>
        <tr>
            <td class="lbl">Discharge</td>
            <td class="val">{{ $dischargePort }}</td>
        </tr>
        <tr>
            <td class="lbl">ETD</td>
            <td class="val">{{ $etdDate }}</td>
        </tr>
        <tr>
            <td class="lbl">ETA</td>
            <td class="val">{{ $etaDate }}</td>
        </tr>
        <tr>
            <td class="lbl">No. AJU</td>
            <td class="val">{{ $noAju }}</td>
        </tr>
        @if($job->nopen)
        <tr>
            <td class="lbl">Nopen (PIB)</td>
            <td class="val">{{ $job->nopen }} @if($job->nopen_date) (Tgl: {{ $job->nopen_date->format('d/m/Y') }}) @endif</td>
        </tr>
        @endif
        @if($job->peb_number)
        <tr>
            <td class="lbl">NOPEN PEB</td>
            <td class="val">{{ $job->peb_number }} @if($job->peb_date) (Tgl: {{ $job->peb_date->format('d/m/Y') }}) @endif</td>
        </tr>
        @endif
        @if($job->npe_number)
        <tr>
            <td class="lbl">No. NPE</td>
            <td class="val">{{ $job->npe_number }}</td>
        </tr>
        @endif
        <tr>
            <td class="lbl">No. HBL</td>
            <td class="val">{{ $noHbl }}</td>
        </tr>
        <tr>
            <td class="lbl">No. MBL</td>
            <td class="val">{{ $noMbl }}</td>
        </tr>
        <tr>
            <td class="lbl">Vessel</td>
            <td class="val">{{ $vesselName }}</td>
        </tr>
        <tr>
            <td class="lbl">Quantity</td>
            <td class="val">{{ $quantityStr }}</td>
        </tr>
        <tr>
            <td class="lbl">Gross Weight</td>
            <td class="val">{{ $grossWeightStr }}</td>
        </tr>
        <tr>
            <td class="lbl">Volume</td>
            <td class="val">{{ $volumeStr }}</td>
        </tr>
        <tr>
            <td class="lbl">Commodity</td>
            <td class="val">{{ $commodityStr }}</td>
        </tr>
    </tbody>
</table>

{{-- NOTE SECTION --}}
<div class="note-title">NOTE :</div>
<div class="note-box">{{ $noteContent }}</div>

<div class="footer">Page <script type="text/php">if(isset($pdf)){echo $PAGE_NUM.' of '.$PAGE_COUNT;}</script></div>

</body>
</html>
