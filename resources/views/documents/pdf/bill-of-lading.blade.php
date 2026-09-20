<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bill of Lading {{ $bl->number }}</title>
    <style>
        @page {
            margin: 15pt 20pt 15pt 20pt;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000000;
            font-size: 7.5pt;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }
        table.bl-grid {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000000;
            table-layout: fixed;
        }
        table.bl-grid td {
            border: 1px solid #000000;
            vertical-align: top;
            padding: 2pt 4pt;
        }
        .cell-lbl {
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2pt;
            display: block;
        }
        .cell-val {
            font-size: 7.5pt;
            line-height: 1.25;
        }
        .logo-center {
            text-align: center;
            vertical-align: middle;
        }
        .logo-center img {
            height: 22pt;
            width: auto;
            display: inline-block;
        }
        .title-bl-main {
            font-size: 13pt;
            font-weight: bold;
            text-align: center;
            letter-spacing: 0.5px;
            margin-top: 1pt;
            margin-bottom: 1pt;
        }
        .title-bl-sub {
            color: #c00000;
            font-weight: bold;
            font-size: 7.5pt;
            text-align: center;
            letter-spacing: 0.5px;
            margin-bottom: 2pt;
        }
        table.cargo-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.cargo-table th {
            border: none;
            border-bottom: 1px solid #000000;
            font-size: 7pt;
            font-weight: bold;
            text-align: center;
            padding: 3pt 2pt;
        }
        table.cargo-table td {
            border: none;
            vertical-align: top;
            padding: 4pt 3pt;
            font-size: 7.5pt;
            line-height: 1.25;
        }
        .legal-clause {
            font-size: 5.5pt;
            line-height: 1.2;
            text-align: justify;
            margin-bottom: 3pt;
        }
        .page-footer {
            margin-top: 6pt;
            text-align: right;
            font-size: 7.5pt;
            color: #888888;
        }
    </style>
</head>
<body>

@php
    $logoPath = public_path('images/logo.png');

    $copyType = strtoupper($type ?? request('type', 'draft'));
    if ($copyType === 'ORIGINAL') {
        $watermarkText = '*** ORIGINAL ***';
    } elseif ($copyType === 'COPY') {
        $watermarkText = '*** COPY ***';
    } else {
        $watermarkText = '*** DRAFT COPY ***';
    }

    $shipperCustomer = $bl->customer ?? $bl->job?->customer;
    $shipperName = $bl->shipper_name ?: ($shipperCustomer?->name ?? '—');
    $shipperAddress = $bl->shipper_address ?: ($shipperCustomer?->address ?? '');

    $consigneeName = $bl->consignee_name ?: ($bl->job?->consignee_name ?: ($shipperCustomer?->consignees?->first()?->name ?? 'TO ORDER'));
    $consigneeAddress = $bl->consignee_address ?: ($bl->job?->consignee_address ?: ($shipperCustomer?->consignees?->first()?->address ?? ''));

    $notifyParty = $bl->notify_party ?: ($bl->job?->notify_party ?: 'SAME AS CONSIGNEE');

    $bookingNo = $bl->booking_reference ?: ($bl->job?->booking_reference ?: ($bl->carrier_bl_number ?: '—'));
    $references = $bl->customer_ref_number ?: ($bl->job?->number ?: '—');
    $forwardingAgent = $bl->agent_name ?: "PT. RADIX INTERNATIONAL LOGISTICS\nJL. TEH NO 3C, JAKARTA BARAT, INDONESIA";
    $surrenderTo = $bl->surrender_to ?: ($bl->agent_name ?: "PT. RADIX INTERNATIONAL LOGISTICS OR AGENT");

    $preCarriage = $bl->pre_carriage ?: '—';
    $placeOfReceipt = $bl->place_of_receipt ?: ($bl->pol ?: 'JAKARTA, INDONESIA');
    $vesselVoyage = $bl->vessel_voyage ?: ($bl->job?->vessel_voyage ?: '—');
    $pol = $bl->pol ?: ($bl->job?->pol ?? 'JAKARTA, INDONESIA');
    $pod = $bl->pod ?: ($bl->job?->pod ?? '—');
    $placeOfDelivery = $bl->place_of_delivery ?: ($bl->pod ?: '—');
    $service = $bl->party ?: ($bl->service_term ?: 'CY/CY');
    $finalDest = $bl->final_destination ?: ($bl->place_of_delivery ?: ($bl->pod ?: '—'));

    $marksNumbers = $bl->marks_numbers ?: ($bl->container_number ? $bl->container_number . ($bl->seal_number ? ' / ' . $bl->seal_number : '') : 'N/M');
    $pkgCount = $bl->package_count ? $bl->package_count . ' ' . ($bl->package_unit ?: 'PKGS') : ($bl->job?->package_count ? $bl->job->package_count . ' ' . ($bl->job->package_unit ?: 'PKGS') : '—');
    
    $desc = $bl->cargo_description ?: ($bl->job?->cargo_description ?? 'SAID TO CONTAIN :');
    if ($bl->container_number) {
        $desc = "CONTAINER & SEAL NO.:\n" . $bl->container_number . ($bl->seal_number ? ' / ' . $bl->seal_number : '') . "\n\n" . $desc;
    }

    $gw = $bl->gross_weight ? number_format((float)$bl->gross_weight, 2, '.', ',') . ' KGS' : ($bl->job?->gross_weight ? number_format((float)$bl->job->gross_weight, 2, '.', ',') . ' KGS' : '—');
    $meas = $bl->measurement ? number_format((float)$bl->measurement, 3, '.', ',') . ' CBM' : ($bl->job?->volume ? number_format((float)$bl->job->volume, 3, '.', ',') . ' CBM' : '—');

    $freightTerm = strtoupper($bl->freight_term ?: 'FREIGHT PREPAID');
    $incoterms = $bl->incoterms ?: ($bl->job?->incoterm ?: 'FOB');
    $origCount = $bl->original_bl_count ? $bl->original_bl_count . ' (' . ($bl->original_bl_count == 3 ? 'THREE' : (string)$bl->original_bl_count) . ')' : '3 (THREE)';
    $shippedDate = $bl->shipped_on_board_date ? $bl->shipped_on_board_date->format('d-M-Y') : ($bl->bl_date ? $bl->bl_date->format('d-M-Y') : '—');
    $placeDateIssue = ($bl->place_of_issue ?: 'JAKARTA') . ', ' . ($bl->date_of_issue ? $bl->date_of_issue->format('d-M-Y') : ($bl->bl_date ? $bl->bl_date->format('d-M-Y') : now()->format('d-M-Y')));
    $containerSay = $bl->total_containers_say ?: ($bl->container_number ? 'SAY ONE CONTAINER ONLY' : '—');
@endphp

<table class="bl-grid">
    {{-- TOP ROW: SHIPPER (LEFT 50%) vs LOGO & BL NO (RIGHT 50%) --}}
    <tr>
        <td rowspan="2" style="width: 50%; height: 60pt;">
            <span class="cell-lbl">SHIPPER</span>
            <div class="cell-val">
                <strong>{{ $shipperName }}</strong><br>
                {!! nl2br(e($shipperAddress)) !!}
            </div>
        </td>
        <td style="width: 25%; height: 26pt;" class="logo-center">
            <img src="{{ $logoPath }}" alt="RDX">
        </td>
        <td style="width: 25%; height: 26pt;">
            <span class="cell-lbl">BILL OF LADING NO.</span>
            <div class="cell-val" style="font-weight: bold; font-size: 8pt;">{{ $bl->number }}</div>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="height: 34pt; vertical-align: middle;">
            <div class="title-bl-main">BILL OF LADING</div>
            <div class="title-bl-sub">{{ $watermarkText }}</div>
        </td>
    </tr>

    {{-- CONSIGNEE vs BOOKING NO & REFERENCES --}}
    <tr>
        <td rowspan="2" style="height: 60pt;">
            <span class="cell-lbl">CONSIGNEE</span>
            <div class="cell-val">
                <strong>{{ $consigneeName }}</strong><br>
                {!! nl2br(e($consigneeAddress)) !!}
            </div>
        </td>
        <td style="height: 25pt;">
            <span class="cell-lbl">BOOKING NO.</span>
            <div class="cell-val">{{ $bookingNo }}</div>
        </td>
        <td style="height: 25pt;">
            <span class="cell-lbl">REFERENCES</span>
            <div class="cell-val">{{ $references }}</div>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="height: 35pt;">
            <span class="cell-lbl">FORWARDING AGENT</span>
            <div class="cell-val">{!! nl2br(e($forwardingAgent)) !!}</div>
        </td>
    </tr>

    {{-- NOTIFY PARTY vs SURRENDERED TO --}}
    <tr>
        <td style="height: 52pt;">
            <span class="cell-lbl">NOTIFY PARTY</span>
            <div class="cell-val">{!! nl2br(e($notifyParty)) !!}</div>
        </td>
        <td colspan="2" style="height: 52pt;">
            <span class="cell-lbl">BILL OF LADING MUST SURRENDERED TO :</span>
            <div class="cell-val">{!! nl2br(e($surrenderTo)) !!}</div>
        </td>
    </tr>

    {{-- 4 COLUMNS: PRE-CARRIAGE, PLACE OF RECEIPT, VESSEL/VOYAGE, POL --}}
    <tr>
        <td style="padding: 0;" colspan="3">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                <tr>
                    <td style="border: none; border-right: 1px solid #000; width: 25%; padding: 2pt 4pt;">
                        <span class="cell-lbl">PRE-CARRIAGE BY</span>
                        <div class="cell-val">{{ $preCarriage }}</div>
                    </td>
                    <td style="border: none; border-right: 1px solid #000; width: 25%; padding: 2pt 4pt;">
                        <span class="cell-lbl">PLACE OF RECEIPT</span>
                        <div class="cell-val">{{ $placeOfReceipt }}</div>
                    </td>
                    <td style="border: none; border-right: 1px solid #000; width: 25%; padding: 2pt 4pt;">
                        <span class="cell-lbl">VESSEL/VOYAGE NO.</span>
                        <div class="cell-val">{{ $vesselVoyage }}</div>
                    </td>
                    <td style="border: none; width: 25%; padding: 2pt 4pt;">
                        <span class="cell-lbl">PORT OF LOADING</span>
                        <div class="cell-val">{{ $pol }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- 4 COLUMNS: PORT OF DISCHARGE, PLACE OF DELIVERY, SERVICE, FINAL DESTINATION --}}
    <tr>
        <td style="padding: 0;" colspan="3">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                <tr>
                    <td style="border: none; border-right: 1px solid #000; width: 25%; padding: 2pt 4pt;">
                        <span class="cell-lbl">PORT OF DISCHARGE</span>
                        <div class="cell-val">{{ $pod }}</div>
                    </td>
                    <td style="border: none; border-right: 1px solid #000; width: 25%; padding: 2pt 4pt;">
                        <span class="cell-lbl">PLACE OF DELIVERY</span>
                        <div class="cell-val">{{ $placeOfDelivery }}</div>
                    </td>
                    <td style="border: none; border-right: 1px solid #000; width: 25%; padding: 2pt 4pt;">
                        <span class="cell-lbl">SERVICE</span>
                        <div class="cell-val">{{ $service }}</div>
                    </td>
                    <td style="border: none; width: 25%; padding: 2pt 4pt;">
                        <span class="cell-lbl">FINAL DESTINATION</span>
                        <div class="cell-val">{{ $finalDest }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- CARGO TABLE --}}
    <tr>
        <td colspan="3" style="padding: 0;">
            <table class="cargo-table">
                <thead>
                    <tr>
                        <th style="width: 20%; border-right: 1px solid #000;">MARKS &amp; NUMBERS</th>
                        <th style="width: 12%; border-right: 1px solid #000;">NO. OF PKGS</th>
                        <th style="width: 36%; border-right: 1px solid #000;">DESCRIPTION OF PACKAGES AND GOODS</th>
                        <th style="width: 16%; border-right: 1px solid #000;">GROSS WEIGHT</th>
                        <th style="width: 16%;">MEASUREMENT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="height: 180pt; border-right: 1px solid #000;">
                            {!! nl2br(e($marksNumbers)) !!}
                        </td>
                        <td style="height: 180pt; border-right: 1px solid #000; text-align: center;">
                            {{ $pkgCount }}
                        </td>
                        <td style="height: 180pt; border-right: 1px solid #000;">
                            {!! nl2br(e($desc)) !!}
                        </td>
                        <td style="height: 180pt; border-right: 1px solid #000; text-align: right;">
                            {{ $gw }}
                        </td>
                        <td style="height: 180pt; text-align: right;">
                            {{ $meas }}
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top: 1px solid #000; border-right: 1px solid #000;"></td>
                        <td style="border-top: 1px solid #000; border-right: 1px solid #000; text-align: center; font-weight: bold;">
                            TOTAL:
                        </td>
                        <td style="border-top: 1px solid #000; border-right: 1px solid #000;"></td>
                        <td style="border-top: 1px solid #000; border-right: 1px solid #000;"></td>
                        <td style="border-top: 1px solid #000;"></td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>

    {{-- FREIGHT, INCOTERMS, ORIGINAL COUNT, SHIPPED ON BOARD --}}
    <tr>
        <td style="padding: 0;" colspan="3">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                <tr>
                    <td style="border: none; border-right: 1px solid #000; width: 25%; padding: 2pt 4pt;">
                        <span class="cell-lbl">FREIGHT &amp; CHARGES</span>
                        <div class="cell-val">{{ $freightTerm }}</div>
                    </td>
                    <td style="border: none; border-right: 1px solid #000; width: 25%; padding: 2pt 4pt;">
                        <span class="cell-lbl">INCOTERMS</span>
                        <div class="cell-val">{{ $incoterms }}</div>
                    </td>
                    <td style="border: none; border-right: 1px solid #000; width: 25%; padding: 2pt 4pt;">
                        <span class="cell-lbl">NUMBER OF ORIGINAL B(S)/L</span>
                        <div class="cell-val">{{ $origCount }}</div>
                    </td>
                    <td style="border: none; width: 25%; padding: 2pt 4pt;">
                        <span class="cell-lbl">SHIPPED ON BOARD</span>
                        <div class="cell-val">{{ $shippedDate }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- LEGAL CLAUSE vs TOTAL CONTAINERS --}}
    <tr>
        <td colspan="2" style="width: 60%; padding: 3pt 4pt;">
            <div class="legal-clause">
                RECEIVED by the Carrier in apparent good order and condition, unless otherwise indicated herein, the goods or packages specified above, to be transported subject to all the terms and conditions printed on the reverse side hereof or otherwise incorporated herein, to which the Shipper agrees by accepting this Bill of Lading.
            </div>
            <div class="legal-clause" style="margin-bottom: 0;">
                IN WITNESS WHEREOF, the Carrier, Master or Agent of the said vessel has signed 3 (THREE) Original Bills of Lading, all of this tenor and date, one of which being accomplished, the others to stand void.
            </div>
        </td>
        <td style="width: 40%; padding: 3pt 4pt;">
            <span class="cell-lbl">TOTAL NUMBER OF CONTAINERS SAY :</span>
            <div class="cell-val" style="margin-top: 4pt; font-weight: bold;">{{ $containerSay }}</div>
        </td>
    </tr>

    {{-- PLACE & DATE OF ISSUE vs AS AGENT FOR CARRIER --}}
    <tr>
        <td colspan="2" style="width: 60%; height: 38pt;">
            <span class="cell-lbl">PLACE AND DATE OF ISSUE</span>
            <div class="cell-val" style="margin-top: 2pt;">{{ $placeDateIssue }}</div>
        </td>
        <td style="width: 40%; height: 38pt;">
            <span class="cell-lbl">AS AGENT FOR THE CARRIER</span>
            <div class="cell-val" style="margin-top: 18pt; text-align: center;">
                PT. RADIX INTERNATIONAL LOGISTICS
            </div>
        </td>
    </tr>
</table>

<div class="page-footer">
    Page 1 of 1
</div>

</body>
</html>
