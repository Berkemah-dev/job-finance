<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Job Order {{ $job->number }}</title>
    <style>
        @page {
            margin: 28px 36px 28px 36px;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #000;
            font-size: 10px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 20px;
            width: 100%;
        }
        .header-logo {
            float: left;
            width: 48%;
        }
        .header-logo img {
            max-width: 200px;
            max-height: 70px;
        }
        .header-meta {
            float: right;
            width: 44%;
        }
        .clear {
            clear: both;
        }
        .meta-box {
            border: 1px solid #000;
            width: 100%;
            border-collapse: collapse;
        }
        .meta-box-title {
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            padding: 4px;
            letter-spacing: 1px;
            border-bottom: 1px solid #000;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        .meta-table td {
            padding: 3px 6px;
            border: 1px solid #000;
        }
        .meta-table td.lbl {
            width: 35%;
            font-weight: 600;
        }
        .meta-table td.val {
            width: 65%;
            font-weight: 700;
        }

        /* DATA JOB ORDER TABLE */
        table.job-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 10px;
        }
        table.job-data th {
            background-color: #e5e5e5;
            color: #000;
            font-weight: 700;
            text-align: left;
            padding: 5px 8px;
            border: 1px solid #000;
            font-size: 10.5px;
            letter-spacing: 0.5px;
        }
        table.job-data td {
            border: 1px solid #000;
            padding: 4px 8px;
            vertical-align: middle;
        }
        table.job-data td.lbl {
            width: 25%;
            font-weight: 600;
        }
        table.job-data td.val {
            width: 75%;
            font-weight: 700;
        }

        /* NOTE BOX */
        .note-title {
            font-size: 10.5px;
            font-weight: 700;
            text-decoration: underline;
            color: #000;
            margin-bottom: 6px;
        }
        .note-box {
            border: 1px solid #000;
            min-height: 180px;
            padding: 10px;
            font-size: 10px;
            line-height: 1.5;
            white-space: pre-wrap;
        }
        .footer {
            position: fixed;
            bottom: -10px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8.5px;
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
    $noMbl = $job->bl_number ?? $job->mawb_number ?? '—';
    $vesselName = $job->vessel_voyage ?? $job->flight_number ?? '—';
    $quantityStr = $job->package_count ? $job->package_count . ' Box' : ($job->container_type ? '1x ' . strtoupper($job->container_type) : ($quotation?->cargo_qty ?? '—'));
    $grossWeightStr = $job->gross_weight ? \App\Support\Money::format($job->gross_weight) . ' KGS' : ($quotation?->weight_meas ?? '—');
    $quotationVolume = $quotation?->items?->sum(fn($i) => (float)($i->volume ?? 0));
    $volumeStr = $job->volume ? $job->volume . ' M3' : ($quotationVolume > 0 ? $quotationVolume . ' M3' : '—');
    $commodityStr = $job->cargo_description ?? $quotation?->commodity ?? 'General Cargo';
    $noteContent = $job->operational_notes ?? $quotation?->notes ?? '';
@endphp

{{-- HEADER --}}
<div class="header">
    <div class="header-logo">
        <img src="{{ public_path('images/logo.png') }}" alt="RDX Logistics">
    </div>
    <div class="header-meta">
        <div class="meta-box">
            <div class="meta-box-title">JOB ORDER</div>
            <table class="meta-table">
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
        </div>
    </div>
    <div class="clear"></div>
</div>

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
