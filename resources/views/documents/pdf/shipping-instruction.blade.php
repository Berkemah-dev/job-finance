<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shipping Instruction {{ $si->number }}</title>
    <style>
        @page {
            margin: 20pt 25pt 15pt 25pt;
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
        .top-header {
            margin-bottom: 12pt;
        }
        .logo-img {
            height: 28pt;
            width: auto;
            display: block;
        }
        table.si-main-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000000;
            table-layout: fixed;
        }
        table.si-main-table td {
            border: 1px solid #000000;
            vertical-align: top;
            padding: 0;
        }
        .cell-head {
            background-color: #dfdfdf;
            font-weight: bold;
            font-size: 8.5pt;
            padding: 2.5pt 5pt;
            border-bottom: 1px solid #000000;
        }
        .cell-body {
            padding: 4pt 6pt;
            font-size: 8pt;
            line-height: 1.25;
        }
        .si-title-center {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            text-decoration: underline;
            margin-top: 10pt;
            margin-bottom: 3pt;
        }
        .si-no-center {
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 16pt;
        }
        .si-meta-row {
            padding: 0 10pt;
            font-size: 8pt;
            line-height: 1.35;
        }
        .si-notice-text {
            padding: 24pt 10pt 10pt 10pt;
            font-size: 8pt;
            line-height: 1.3;
        }
        .cargo-head {
            background-color: #dfdfdf;
            font-weight: bold;
            font-size: 8pt;
            text-align: center;
            padding: 3pt 4pt;
            border-bottom: 1px solid #000000;
        }
        .cargo-cell {
            padding: 6pt;
            font-size: 8pt;
            line-height: 1.25;
            vertical-align: top;
        }
        .page-footer {
            margin-top: 8pt;
            font-size: 8pt;
            color: #000000;
        }
    </style>
</head>
<body>

@php
    $logoPath = file_exists(public_path('images/rdx-header-logo.jpg'))
        ? public_path('images/rdx-header-logo.jpg')
        : (file_exists(public_path('images/rdx-logistics-doc-logo.png')) ? public_path('images/rdx-logistics-doc-logo.png') : public_path('images/logo.png'));

    $shipperCustomer = $si->customer ?? $si->job?->customer;
    $shipperName = $si->shipper_name ?: ($shipperCustomer?->name ?? '—');
    $shipperAddress = $si->shipper_address ?: ($shipperCustomer?->address ?? '');

    $consigneeName = $si->consignee_name ?: ($si->job?->consignee_name ?: ($shipperCustomer?->consignees?->first()?->name ?? '—'));
    $consigneeAddress = $si->consignee_address ?: ($si->job?->consignee_address ?: ($shipperCustomer?->consignees?->first()?->address ?? ''));

    $notifyName = $si->notify_party ?: ($si->job?->notify_party ?: 'SAME AS CONSIGNEE');

    $carrierName = $si->carrier ?: ($si->carrier_name ?: ($si->job?->carrier ?? '—'));
    $carrierAttn = $si->carrier_contact ?: 'EXPORT DEPT';
    $carrierPhone = $si->carrier_phone ?: '—';

    $vesselName = $si->vessel_voyage ?: ($si->job?->vessel_voyage ?: ($si->vessel_name ?: '—'));
    $etdStr = $si->etd ? $si->etd->format('d-M-Y') : ($si->job?->etd ? $si->job->etd->format('d-M-Y') : '—');
    $etaStr = $si->eta ? $si->eta->format('d-M-Y') : ($si->job?->eta ? $si->job->eta->format('d-M-Y') : '—');

    $shipmentTerm = $si->shipment_term ?: ($si->freight_term ?: 'CY/CY');
    $connectingVessel = $si->connecting_vessel ?: '—';
    $loadingPort = $si->pol ?: ($si->job?->pol ?? 'JAKARTA, INDONESIA');
    $dischargePort = $si->pod ?: ($si->job?->pod ?? '—');

    $marksNumbers = $si->marks_numbers ?: ($si->container_number ? $si->container_number . ($si->seal_number ? ' / ' . $si->seal_number : '') : 'N/M');
    $description = $si->cargo_description ?: ($si->job?->cargo_description ?? 'SAID TO CONTAIN :');
    if ($si->package_count) {
        $description = $si->package_count . ' ' . ($si->package_unit ?: 'PACKAGES') . "\n" . $description;
    }
    if ($si->container_type) {
        $description .= "\n1x " . strtoupper($si->container_type);
    }

    $gwMeas = '';
    if ($si->gross_weight) {
        $gwMeas .= 'G.W : ' . \App\Support\Money::format($si->gross_weight) . " KGS\n";
    } elseif ($si->job?->gross_weight) {
        $gwMeas .= 'G.W : ' . \App\Support\Money::format($si->job->gross_weight) . " KGS\n";
    }
    if ($si->measurement) {
        $gwMeas .= 'MEAS: ' . \App\Support\Money::format($si->measurement) . " CBM";
    } elseif ($si->job?->volume) {
        $gwMeas .= 'MEAS: ' . \App\Support\Money::format($si->job->volume) . " CBM";
    }
    if ($gwMeas === '') {
        $gwMeas = "G.W : —\nMEAS: —";
    }

    $remarksContent = $si->remarks ?: ($si->notes ?: "FREIGHT " . ($si->freight_term ?: 'PREPAID'));
@endphp

{{-- LOGO ATAS KIRI --}}
<div class="top-header">
    <img class="logo-img" src="{{ $logoPath }}" alt="RDX LOGISTICS">
</div>

{{-- MAIN SI TABLE --}}
<table class="si-main-table">
    {{-- BARIS 1: SHIPPER, CONSIGNEE, NOTIFY vs HEADER SI --}}
    <tr>
        <td style="width: 50%;">
            <div class="cell-head">SHIPPER</div>
            <div class="cell-body" style="min-height: 55pt;">
                <strong>{{ $shipperName }}</strong>
                @if($shipperAddress)
                    <div>{!! nl2br(e($shipperAddress)) !!}</div>
                @endif
            </div>

            <div class="cell-head" style="border-top: 1px solid #000;">CONSIGNEE</div>
            <div class="cell-body" style="min-height: 55pt;">
                <strong>{{ $consigneeName }}</strong>
                @if($consigneeAddress)
                    <div>{!! nl2br(e($consigneeAddress)) !!}</div>
                @endif
            </div>

            <div class="cell-head" style="border-top: 1px solid #000;">NOTIFY PARTY</div>
            <div class="cell-body" style="min-height: 48pt;">
                {!! nl2br(e($notifyName)) !!}
            </div>
        </td>
        <td style="width: 50%;">
            <div class="si-title-center">SHIPPING INSTRUCTION</div>
            <div class="si-no-center">{{ $si->number }}</div>

            <div class="si-meta-row">
                <table style="width: 100%; border-collapse: collapse; font-size: inherit;">
                    <tr>
                        <td style="border: none; width: 28%; padding: 1pt 0;">To</td>
                        <td style="border: none; width: 4%; padding: 1pt 0;">:</td>
                        <td style="border: none; width: 68%; padding: 1pt 0;">{{ $carrierName }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 1pt 0;">Attn</td>
                        <td style="border: none; padding: 1pt 0;">:</td>
                        <td style="border: none; padding: 1pt 0;">{{ $carrierAttn }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 1pt 0;">Telp/Fax</td>
                        <td style="border: none; padding: 1pt 0;">:</td>
                        <td style="border: none; padding: 1pt 0;">{{ $carrierPhone }}</td>
                    </tr>
                </table>
            </div>

            <div class="si-notice-text">
                Please kindly arrange space for our booking<br>
                as according to below mention
            </div>
        </td>
    </tr>

    {{-- BARIS 2: VESSEL / SCHEDULE & SHIPMENT TERM --}}
    <tr>
        <td style="padding: 4pt 6pt;">
            <table style="width: 100%; border-collapse: collapse; font-size: 8pt;">
                <tr>
                    <td style="border: none; width: 28%;">Vessel Name</td>
                    <td style="border: none; width: 4%;">:</td>
                    <td style="border: none; width: 38%;">{{ $vesselName }}</td>
                    <td style="border: none; width: 12%;">ETD</td>
                    <td style="border: none; width: 4%;">:</td>
                    <td style="border: none; width: 14%;">{{ $etdStr }}</td>
                </tr>
                <tr>
                    <td style="border: none;"></td>
                    <td style="border: none;"></td>
                    <td style="border: none;"></td>
                    <td style="border: none;">ETA</td>
                    <td style="border: none;">:</td>
                    <td style="border: none;">{{ $etaStr }}</td>
                </tr>
            </table>
        </td>
        <td style="padding: 4pt 6pt;">
            <table style="width: 100%; border-collapse: collapse; font-size: 8pt;">
                <tr>
                    <td style="border: none; width: 34%;">Shipment Term</td>
                    <td style="border: none; width: 4%;">:</td>
                    <td style="border: none; width: 62%;">{{ $shipmentTerm }}</td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- BARIS 3: CONNECTING VESSEL & LOADING / DISCHARGE --}}
    <tr>
        <td style="padding: 4pt 6pt; vertical-align: middle;">
            <table style="width: 100%; border-collapse: collapse; font-size: 8pt;">
                <tr>
                    <td style="border: none; width: 38%;">Connecting Vessel</td>
                    <td style="border: none; width: 4%;">:</td>
                    <td style="border: none; width: 58%;">{{ $connectingVessel }}</td>
                </tr>
            </table>
        </td>
        <td style="padding: 0;">
            <table style="width: 100%; border-collapse: collapse; font-size: 8pt;">
                <tr>
                    <td style="border: none; border-bottom: 1px solid #000; width: 34%; font-weight: bold; padding: 3pt 6pt;">LOADING</td>
                    <td style="border: none; border-bottom: 1px solid #000; padding: 3pt 6pt;">{{ $loadingPort }}</td>
                </tr>
                <tr>
                    <td style="border: none; width: 34%; font-weight: bold; padding: 3pt 6pt;">DISCHARGE</td>
                    <td style="border: none; padding: 3pt 6pt;">{{ $dischargePort }}</td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- BARIS 4: CARGO HEADERS --}}
    <tr>
        <td colspan="2" style="padding: 0;">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                <tr>
                    <td class="cargo-head" style="width: 28%; border: none; border-right: 1px solid #000;">MARKS AND NUMBER</td>
                    <td class="cargo-head" style="width: 44%; border: none; border-right: 1px solid #000;">DESCRIPTION</td>
                    <td class="cargo-head" style="width: 28%; border: none;">GW/MEASUREMENT</td>
                </tr>
                <tr>
                    <td class="cargo-cell" style="width: 28%; min-height: 240pt; height: 240pt; border: none; border-right: 1px solid #000;">
                        {!! nl2br(e($marksNumbers)) !!}
                    </td>
                    <td class="cargo-cell" style="width: 44%; min-height: 240pt; height: 240pt; border: none; border-right: 1px solid #000;">
                        {!! nl2br(e($description)) !!}
                    </td>
                    <td class="cargo-cell" style="width: 28%; min-height: 240pt; height: 240pt; border: none;">
                        {!! nl2br(e($gwMeas)) !!}
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- BARIS 5: REMARKS --}}
    <tr>
        <td colspan="2" style="padding: 0;">
            <div class="cell-head">REMARKS</div>
            <div class="cell-body" style="min-height: 50pt;">
                {!! nl2br(e($remarksContent)) !!}
            </div>
        </td>
    </tr>
</table>

{{-- FOOTER KIRI BAWAH --}}
<div class="page-footer">
    Page 1/1
</div>

</body>
</html>
