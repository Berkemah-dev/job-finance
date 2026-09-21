<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shipping Instruction {{ $si->number }}</title>
    <style>
        @page {
            margin: 18pt 25pt 15pt 25pt;
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
            margin-bottom: 18pt;
        }
        .logo-img {
            height: 24pt;
            width: auto;
            display: block;
        }
        table.si-main-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000000;
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
            margin-top: 8pt;
            margin-bottom: 2pt;
        }
        .si-no-center {
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 14pt;
        }
        .si-meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            padding: 0 8pt;
        }
        .si-meta-table td {
            border: none !important;
            padding: 1.5pt 0;
        }
        .si-notice-text {
            text-align: center;
            margin-top: 75pt;
            margin-bottom: 8pt;
            padding: 0 8pt;
            font-size: 8pt;
            line-height: 1.35;
        }
        .cargo-head {
            background-color: #dfdfdf;
            font-weight: bold;
            font-size: 8.5pt;
            text-align: center;
            padding: 3pt 4pt;
            border-bottom: 1px solid #000000;
        }
        .cargo-cell {
            padding: 6pt;
            font-size: 8pt;
            line-height: 1.3;
            vertical-align: top;
        }
        .page-footer {
            margin-top: 16pt;
            font-size: 8pt;
            color: #000000;
        }
    </style>
</head>
<body>

@php
    $cleanLogoFile = public_path('images/rdx-logo-clean.png');
    $headerLogoFile = public_path('images/rdx-header-logo.jpg');
    $altLogoFile = public_path('images/rdx-logistics-doc-logo.png');
    $pngLogoFile = public_path('images/logo.png');

    if (file_exists($cleanLogoFile)) {
        $logoPath = 'data:image/png;base64,' . base64_encode(file_get_contents($cleanLogoFile));
    } elseif (file_exists($headerLogoFile)) {
        $logoPath = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($headerLogoFile));
    } elseif (file_exists($altLogoFile)) {
        $logoPath = 'data:image/png;base64,' . base64_encode(file_get_contents($altLogoFile));
    } elseif (file_exists($pngLogoFile)) {
        $logoPath = 'data:image/png;base64,' . base64_encode(file_get_contents($pngLogoFile));
    } else {
        $logoPath = '';
    }

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

{{-- LOGO ATAS KIRI (TANPA TEKS INTERNATIONAL FREIGHT FORWARDERS) --}}
<div class="top-header">
    <img class="logo-img" src="{{ $logoPath }}" alt="RDX LOGISTICS">
</div>

{{-- MAIN SI TABLE --}}
<table class="si-main-table">
    {{-- BARIS 1: SHIPPER, CONSIGNEE, NOTIFY vs HEADER SI --}}
    <tr>
        <td style="width: 50%;">
            <div class="cell-head">SHIPPER</div>
            <div class="cell-body" style="min-height: 52pt;">
                <strong>{{ $shipperName }}</strong>
                @if($shipperAddress)
                    <div>{!! nl2br(e($shipperAddress)) !!}</div>
                @endif
            </div>

            <div class="cell-head" style="border-top: 1px solid #000000;">CONSIGNEE</div>
            <div class="cell-body" style="min-height: 52pt;">
                <strong>{{ $consigneeName }}</strong>
                @if($consigneeAddress)
                    <div>{!! nl2br(e($consigneeAddress)) !!}</div>
                @endif
            </div>

            <div class="cell-head" style="border-top: 1px solid #000000;">NOTIFY PARTY</div>
            <div class="cell-body" style="min-height: 46pt;">
                {!! nl2br(e($notifyName)) !!}
            </div>
        </td>
        <td style="width: 50%; padding: 4pt 6pt;">
            <div class="si-title-center">SHIPPING INSTRUCTION</div>
            <div class="si-no-center">{{ $si->number }}</div>

            <table class="si-meta-table">
                <tr>
                    <td style="width: 25%; font-weight: bold;">To</td>
                    <td style="width: 4%;">:</td>
                    <td style="width: 71%;">{{ $carrierName }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Attn</td>
                    <td>:</td>
                    <td>{{ $carrierAttn }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Telp/Fax</td>
                    <td>:</td>
                    <td>{{ $carrierPhone }}</td>
                </tr>
            </table>

            {{-- DITENGAH DAN DIPOSISIKAN DI BAWAH SESUAI GAMBAR 2 & GAMBAR 4 --}}
            <div class="si-notice-text">
                Please kindly arrange space for our booking<br>
                as according to below mention
            </div>
        </td>
    </tr>

    {{-- BARIS 2: VESSEL / SCHEDULE & SHIPMENT TERM --}}
    <tr>
        <td style="padding: 4pt 6pt;">
            <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt;">
                <tr>
                    <td style="border: none !important; width: 28%; font-weight: bold;">Vessel Name</td>
                    <td style="border: none !important; width: 4%;">:</td>
                    <td style="border: none !important; width: 36%;">{{ $vesselName }}</td>
                    <td style="border: none !important; width: 12%; font-weight: bold;">ETD</td>
                    <td style="border: none !important; width: 4%;">:</td>
                    <td style="border: none !important; width: 16%;">{{ $etdStr }}</td>
                </tr>
                <tr>
                    <td style="border: none !important;"></td>
                    <td style="border: none !important;"></td>
                    <td style="border: none !important;"></td>
                    <td style="border: none !important; font-weight: bold;">ETA</td>
                    <td style="border: none !important;">:</td>
                    <td style="border: none !important;">{{ $etaStr }}</td>
                </tr>
            </table>
        </td>
        <td style="padding: 4pt 6pt;">
            <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt;">
                <tr>
                    <td style="border: none !important; width: 34%; font-weight: bold;">Shipment Term</td>
                    <td style="border: none !important; width: 4%;">:</td>
                    <td style="border: none !important; width: 62%;">{{ $shipmentTerm }}</td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- BARIS 3: CONNECTING VESSEL & LOADING / DISCHARGE (BG PUTIH / TIDAK SHADOW SESUAI GAMBAR 3 & 4) --}}
    <tr>
        <td style="padding: 4pt 6pt; vertical-align: middle;">
            <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt;">
                <tr>
                    <td style="border: none !important; width: 40%; font-weight: bold;">Connecting Vessel</td>
                    <td style="border: none !important; width: 4%;">:</td>
                    <td style="border: none !important; width: 56%;">{{ $connectingVessel }}</td>
                </tr>
            </table>
        </td>
        <td style="padding: 0;">
            <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt;">
                <tr>
                    <td style="border: none !important; border-bottom: 1px solid #000000 !important; border-right: 1px solid #000000 !important; width: 34%; font-weight: bold; padding: 3pt 6pt; background-color: #ffffff;">LOADING</td>
                    <td style="border: none !important; border-bottom: 1px solid #000000 !important; padding: 3pt 6pt;">{{ $loadingPort }}</td>
                </tr>
                <tr>
                    <td style="border: none !important; border-right: 1px solid #000000 !important; width: 34%; font-weight: bold; padding: 3pt 6pt; background-color: #ffffff;">DISCHARGE</td>
                    <td style="border: none !important; padding: 3pt 6pt;">{{ $dischargePort }}</td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- BARIS 4: CARGO HEADERS --}}
    <tr>
        <td colspan="2" style="padding: 0;">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                <tr>
                    <td class="cargo-head" style="width: 28%; border: none !important; border-right: 1px solid #000000 !important;">MARKS AND NUMBER</td>
                    <td class="cargo-head" style="width: 44%; border: none !important; border-right: 1px solid #000000 !important;">DESCRIPTION</td>
                    <td class="cargo-head" style="width: 28%; border: none !important;">GW/MEASUREMENT</td>
                </tr>
                <tr>
                    <td class="cargo-cell" style="width: 28%; min-height: 200pt; height: 200pt; border: none !important; border-right: 1px solid #000000 !important;">
                        {!! nl2br(e($marksNumbers)) !!}
                    </td>
                    <td class="cargo-cell" style="width: 44%; min-height: 200pt; height: 200pt; border: none !important; border-right: 1px solid #000000 !important;">
                        {!! nl2br(e($description)) !!}
                    </td>
                    <td class="cargo-cell" style="width: 28%; min-height: 200pt; height: 200pt; border: none !important;">
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
