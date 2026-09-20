<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ strtoupper($type ?? 'HAWB') }} {{ $awb->number }}</title>
    @php
        $rawType = strtolower($type ?? request('type', 'hawb'));
        $isDraft = $rawType === 'draft';
        $isMawb = $rawType === 'mawb';

        if ($isDraft) {
            $themeColor = '#475569';
            $themeBg = '#f8fafc';
            $themeBar = '#f1f5f9';
            $footerText = '*** DRAFT COPY - NOT NEGOTIABLE ***';
        } elseif ($isMawb) {
            $themeColor = '#dc2626';
            $themeBg = '#fef2f2';
            $themeBar = '#fee2e2';
            $footerText = 'Original 2 - (For Consignee)';
        } else {
            $themeColor = '#1d4ed8';
            $themeBg = '#f8fafc';
            $themeBar = '#e0e7ff';
            $footerText = 'Original 3 - (For Shipper)';
        }

        $customer = $awb->customer ?? $awb->job?->customer;
        
        $shipperName = $isMawb
            ? ($awb->shipper_on_mawb ?: "PT. RADIX INTERNATIONAL LOGISTICS\nJL. TEH NO 3C, JAKARTA BARAT, INDONESIA")
            : ($awb->shipper_on_hawb ?: ($customer?->name ?? '—'));
        $shipperAcc = $awb->account_number ?: '—';

        $consigneeName = $isMawb
            ? ($awb->consignee_on_mawb ?: ($awb->agent_name ?: 'TO ORDER / OVERSEAS AGENT'))
            : ($awb->consignee_on_hawb ?: ($customer?->consignees?->first()?->name ?? '—'));
        $consigneeAcc = '—';

        $issuingAgent = "PT. RADIX INTERNATIONAL LOGISTICS\nJL. TEH NO 3C, TAMAN SARI, JAKARTA 11110";
        $agentIata = $awb->airline_code ?: '—';
        $agentAcc = $awb->account_number ?: '—';
        $accInfo = $awb->remarks ?: 'FREIGHT ' . strtoupper($awb->freight_term ?: 'PREPAID');

        $departureAirport = $awb->airport_of_departure ?: ($awb->job?->pol ?? 'SOEKARNO HATTA INTL (CGK)');
        $destAirport = $awb->airport_of_destination ?: ($awb->job?->pod ?? '—');

        $airlineName = $awb->airline ?: ($awb->carrier ?: 'PT. RADIX INTERNATIONAL LOGISTICS');
        $flightNo = $awb->flight_number ?: ($awb->job?->flight_number ?: '—');
        $flightDate = $awb->flight_date ? $awb->flight_date->format('d/m') : ($awb->etd ? $awb->etd->format('d/m') : '—');

        $currency = strtoupper($awb->currency ?: 'IDR');
        $chgCode = 'PP';
        $valCarriage = $awb->value_of_carriage ?: 'N.V.D.';
        $valCustoms = $awb->value_of_customs ?: 'N.C.V.';

        $pieces = $awb->pieces ?: ($awb->job?->package_count ?: 1);
        $gw = $awb->gross_weight ?: ($awb->job?->gross_weight ?: 0);
        $gwUnit = strtolower($awb->gross_weight_unit ?: 'k') === 'k' ? 'K' : 'K';
        $chargeableWeight = $awb->chargeable_weight ?: ($awb->gross_weight ?: $gw);
        $rateClass = 'Q';
        $rateCharge = 'AS AGREED';
        $totalCharge = 'AS AGREED';
        $natureGoods = $awb->commodity ?: ($awb->job?->cargo_description ?: 'GENERAL CARGO');
        if ($awb->volume || $awb->job?->volume) {
            $vol = $awb->volume ?: $awb->job->volume;
            $natureGoods .= "\nVOL: " . number_format((float)$vol, 3) . " CBM";
        }
    @endphp
    <style>
        @page {
            margin: 14pt 18pt 12pt 18pt;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000000;
            font-size: 7pt;
            line-height: 1.15;
            margin: 0;
            padding: 0;
        }
        table.awb-main {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid {{ $themeColor }};
            table-layout: fixed;
        }
        table.awb-main td {
            border: 1px solid {{ $themeColor }};
            vertical-align: top;
            padding: 2pt 3pt;
        }
        .f-lbl {
            font-size: 6.5pt;
            color: #1e293b;
            margin-bottom: 1.5pt;
            display: block;
        }
        .f-lbl-bold {
            font-size: 6.5pt;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 1.5pt;
            display: block;
        }
        .f-val {
            font-size: 7.5pt;
            font-weight: bold;
            color: #000000;
            line-height: 1.25;
        }
        .title-awb {
            font-size: 15pt;
            font-weight: 800;
            color: {{ $themeColor }};
            margin: 1pt 0;
        }
        .legal-notice-sm {
            font-size: 5.5pt;
            line-height: 1.15;
            color: #334155;
            text-align: justify;
        }
        table.rating-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.rating-table th {
            border: none;
            border-bottom: 1px solid {{ $themeColor }};
            font-size: 6.5pt;
            color: #0f172a;
            font-weight: bold;
            text-align: center;
            padding: 3pt 2pt;
        }
        table.rating-table td {
            border: none;
            vertical-align: top;
            padding: 3pt 2pt;
            font-size: 7.5pt;
        }
        .footer-banner {
            text-align: center;
            font-size: 14pt;
            font-weight: 800;
            color: {{ $themeColor }};
            margin-top: 10pt;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<table class="awb-main">
    {{-- ROW 1: SHIPPER vs AIR WAYBILL HEADER --}}
    <tr>
        <td style="width: 32%; height: 50pt;">
            <span class="f-lbl-bold">Shipper's Name and Address</span>
            <div class="f-val">{!! nl2br(e($shipperName)) !!}</div>
        </td>
        <td style="width: 24%; height: 50pt;">
            <span class="f-lbl">Shipper's Account Number</span>
            <div class="f-val">{{ $shipperAcc }}</div>
        </td>
        <td colspan="2" style="width: 44%; height: 50pt; padding: 2pt 4pt;">
            <div style="display: table; width: 100%;">
                <div style="display: table-cell; vertical-align: top;">
                    <span class="f-lbl">Not Negotiable</span>
                    <div class="title-awb">Air Waybill</div>
                    @if($isDraft)
                        <div style="color: #dc2626; font-weight: 800; font-size: 7.5pt; letter-spacing: 0.5px; margin-bottom: 2pt;">*** DRAFT COPY — NOT NEGOTIABLE ***</div>
                    @elseif($isMawb)
                        <div style="color: #dc2626; font-weight: 800; font-size: 7.5pt; letter-spacing: 0.5px; margin-bottom: 2pt;">*** MASTER AIR WAYBILL (MAWB) ***</div>
                    @else
                        <div style="color: #1d4ed8; font-weight: 800; font-size: 7.5pt; letter-spacing: 0.5px; margin-bottom: 2pt;">*** HOUSE AIR WAYBILL (HAWB) ***</div>
                    @endif
                    <span class="f-lbl">Issued By <strong>{{ $airlineName }}</strong></span>
                </div>
                <div style="display: table-cell; vertical-align: top; text-align: right; width: 35%;">
                    <div style="font-size: 9pt; font-weight: 800; color: {{ $themeColor }};">{{ $awb->number }}</div>
                </div>
            </div>
            <div class="legal-notice-sm" style="margin-top: 3pt;">
                <strong>Copies 1, 2 and 3 of this Air Waybill are originals and have the same validity.</strong><br>
                It is agreed that goods described herein are accepted in apparent good order and condition (except as noted) for carriage SUBJECT TO THE CONDITIONS OF CONTRACT ON THE REVERSE HEREOF.
            </div>
        </td>
    </tr>

    {{-- ROW 2: CONSIGNEE --}}
    <tr>
        <td style="height: 48pt;">
            <span class="f-lbl-bold">Consignee's Name and Address</span>
            <div class="f-val">{!! nl2br(e($consigneeName)) !!}</div>
        </td>
        <td style="height: 48pt;">
            <span class="f-lbl">Consignee's Account Number</span>
            <div class="f-val">{{ $consigneeAcc }}</div>
        </td>
        <td colspan="2" style="height: 48pt; vertical-align: middle; background-color: {{ $themeBg }};">
            <div class="legal-notice-sm">
                ALL GOODS MAY BE CARRIED BY ANY OTHER MEANS INCLUDING ROAD OR ANY OTHER CARRIER UNLESS SPECIFIC CONTRARY INSTRUCTIONS ARE GIVEN HEREON BY THE SHIPPER. THE SHIPPER'S ATTENTION IS DRAWN TO THE NOTICE CONCERNING CARRIER'S LIMITATION OF LIABILITY.
            </div>
        </td>
    </tr>

    {{-- ROW 3: ISSUING AGENT vs ACCOUNTING INFO --}}
    <tr>
        <td colspan="2" style="height: 32pt;">
            <span class="f-lbl-bold">Issuing Carrier's Agent Name and City</span>
            <div class="f-val">{!! nl2br(e($issuingAgent)) !!}</div>
        </td>
        <td colspan="2" rowspan="2" style="height: 52pt;">
            <span class="f-lbl-bold">Accounting Information</span>
            <div class="f-val">{!! nl2br(e($accInfo)) !!}</div>
        </td>
    </tr>

    {{-- ROW 4: AGENT IATA CODE & ACCOUNT NO --}}
    <tr>
        <td style="height: 20pt;">
            <span class="f-lbl">Agent's IATA Code</span>
            <div class="f-val">{{ $agentIata }}</div>
        </td>
        <td style="height: 20pt;">
            <span class="f-lbl">Account No.</span>
            <div class="f-val">{{ $agentAcc }}</div>
        </td>
    </tr>

    {{-- ROW 5: AIRPORT OF DEPARTURE --}}
    <tr>
        <td colspan="4" style="height: 22pt;">
            <span class="f-lbl-bold">Airport of Departure (Addr. of First Carrier) and Requested Routing</span>
            <div class="f-val">{{ $departureAirport }}</div>
        </td>
    </tr>

    {{-- ROW 6: ROUTING DETAILS BAR --}}
    <tr>
        <td colspan="4" style="padding: 0;">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 6.5pt;">
                <tr style="text-align: center;">
                    <td style="border: none; border-right: 1px solid {{ $themeColor }}; width: 14%; padding: 1pt 2pt;">
                        <span class="f-lbl">To</span>
                        <div class="f-val">{{ $destAirport }}</div>
                    </td>
                    <td style="border: none; border-right: 1px solid {{ $themeColor }}; width: 18%; padding: 1pt 2pt;">
                        <span class="f-lbl">By first Carrier</span>
                        <div class="f-val">{{ $flightNo }}</div>
                    </td>
                    <td style="border: none; border-right: 1px solid {{ $themeColor }}; width: 8%; padding: 1pt 2pt;">
                        <span class="f-lbl">to</span>
                        <div class="f-val">—</div>
                    </td>
                    <td style="border: none; border-right: 1px solid {{ $themeColor }}; width: 8%; padding: 1pt 2pt;">
                        <span class="f-lbl">by</span>
                        <div class="f-val">—</div>
                    </td>
                    <td style="border: none; border-right: 1px solid {{ $themeColor }}; width: 8%; padding: 1pt 2pt;">
                        <span class="f-lbl">Currency</span>
                        <div class="f-val">{{ $currency }}</div>
                    </td>
                    <td style="border: none; border-right: 1px solid {{ $themeColor }}; width: 6%; padding: 1pt 2pt;">
                        <span class="f-lbl">CHGS</span>
                        <div class="f-val">{{ $chgCode }}</div>
                    </td>
                    <td style="border: none; border-right: 1px solid {{ $themeColor }}; width: 9%; padding: 1pt 2pt;">
                        <span class="f-lbl">WT/VAL PPD</span>
                        <div class="f-val">PPD</div>
                    </td>
                    <td style="border: none; border-right: 1px solid {{ $themeColor }}; width: 9%; padding: 1pt 2pt;">
                        <span class="f-lbl">Other PPD</span>
                        <div class="f-val">PPD</div>
                    </td>
                    <td style="border: none; border-right: 1px solid {{ $themeColor }}; width: 10%; padding: 1pt 2pt;">
                        <span class="f-lbl">Carriage Value</span>
                        <div class="f-val">{{ $valCarriage }}</div>
                    </td>
                    <td style="border: none; width: 10%; padding: 1pt 2pt;">
                        <span class="f-lbl">Customs Value</span>
                        <div class="f-val">{{ $valCustoms }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- ROW 7: AIRPORT DESTINATION & FLIGHT DATES --}}
    <tr>
        <td colspan="4" style="padding: 0;">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 6.5pt;">
                <tr>
                    <td style="border: none; border-right: 1px solid {{ $themeColor }}; width: 25%; padding: 1pt 3pt;">
                        <span class="f-lbl">Airport of Destination</span>
                        <div class="f-val">{{ $destAirport }}</div>
                    </td>
                    <td style="border: none; border-right: 1px solid {{ $themeColor }}; width: 25%; padding: 1pt 3pt;">
                        <span class="f-lbl">Flight/Date</span>
                        <div class="f-val">{{ $flightNo }} / {{ $flightDate }}</div>
                    </td>
                    <td style="border: none; border-right: 1px solid {{ $themeColor }}; width: 25%; padding: 1pt 3pt;">
                        <span class="f-lbl">Amount of Insurance</span>
                        <div class="f-val">XXX</div>
                    </td>
                    <td style="border: none; width: 25%; padding: 1pt 3pt; font-size: 5.5pt; color: #475569;">
                        INSURANCE - If Carrier offers insurance, requested amount to be insured.
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- ROW 8: HANDLING INFORMATION --}}
    <tr>
        <td colspan="4" style="height: 22pt;">
            <span class="f-lbl-bold">Handling Information</span>
            <div class="f-val">{{ $awb->handling_information ?: 'NOTIFY APPLICANT UPON ARRIVAL' }}</div>
        </td>
    </tr>

    {{-- ROW 9: RATING TABLE --}}
    <tr>
        <td colspan="4" style="padding: 0;">
            <table class="rating-table">
                <thead>
                    <tr>
                        <th style="width: 8%; border-right: 1px solid {{ $themeColor }};">No. of Pieces RCP</th>
                        <th style="width: 10%; border-right: 1px solid {{ $themeColor }};">Gross Weight</th>
                        <th style="width: 5%; border-right: 1px solid {{ $themeColor }};">kg lb</th>
                        <th style="width: 14%; border-right: 1px solid {{ $themeColor }};">Rate Class Commodity Item No.</th>
                        <th style="width: 11%; border-right: 1px solid {{ $themeColor }};">Chargeable Weight</th>
                        <th style="width: 10%; border-right: 1px solid {{ $themeColor }};">Rate Charge</th>
                        <th style="width: 10%; border-right: 1px solid {{ $themeColor }};">Total</th>
                        <th style="width: 32%;">Nature and Quantity of Goods (Incl. Dimensions or Volume)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="height: 140pt; border-right: 1px solid {{ $themeColor }}; text-align: center; font-weight: bold;">
                            {{ $pieces }}
                        </td>
                        <td style="height: 140pt; border-right: 1px solid {{ $themeColor }}; text-align: right; font-weight: bold;">
                            {{ number_format((float)$gw, 2) }}
                        </td>
                        <td style="height: 140pt; border-right: 1px solid {{ $themeColor }}; text-align: center;">
                            {{ $gwUnit }}
                        </td>
                        <td style="height: 140pt; border-right: 1px solid {{ $themeColor }}; text-align: center;">
                            {{ $rateClass }}
                        </td>
                        <td style="height: 140pt; border-right: 1px solid {{ $themeColor }}; text-align: right; font-weight: bold;">
                            {{ number_format((float)$chargeableWeight, 2) }}
                        </td>
                        <td style="height: 140pt; border-right: 1px solid {{ $themeColor }}; text-align: center;">
                            {{ $rateCharge }}
                        </td>
                        <td style="height: 140pt; border-right: 1px solid {{ $themeColor }}; text-align: center;">
                            {{ $totalCharge }}
                        </td>
                        <td style="height: 140pt; padding: 4pt 6pt;">
                            {!! nl2br(e($natureGoods)) !!}
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>

    {{-- ROW 10: CHARGES SUMMARY & SIGNATURE --}}
    <tr>
        <td colspan="2" style="width: 45%; padding: 0; vertical-align: top;">
            <table style="width: 100%; border-collapse: collapse; font-size: 6.5pt;">
                <tr>
                    <td style="border: none; border-bottom: 1px solid {{ $themeColor }}; border-right: 1px solid {{ $themeColor }}; width: 50%; padding: 2pt 4pt;">
                        <span class="f-lbl">Prepaid Weight Charge</span>
                        <div class="f-val">AS AGREED</div>
                    </td>
                    <td style="border: none; border-bottom: 1px solid {{ $themeColor }}; width: 50%; padding: 2pt 4pt;">
                        <span class="f-lbl">Collect Weight Charge</span>
                        <div class="f-val">—</div>
                    </td>
                </tr>
                <tr>
                    <td style="border: none; border-bottom: 1px solid {{ $themeColor }}; border-right: 1px solid {{ $themeColor }}; padding: 2pt 4pt;">
                        <span class="f-lbl">Valuation Charge</span>
                        <div class="f-val">NIL</div>
                    </td>
                    <td style="border: none; border-bottom: 1px solid {{ $themeColor }}; padding: 2pt 4pt;">
                        <span class="f-lbl">Tax</span>
                        <div class="f-val">NIL</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border: none; border-bottom: 1px solid {{ $themeColor }}; padding: 2pt 4pt;">
                        <span class="f-lbl">Total Other Charges Due Agent</span>
                        <div class="f-val">AS AGREED</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border: none; border-bottom: 1px solid {{ $themeColor }}; padding: 2pt 4pt;">
                        <span class="f-lbl">Total Other Charges Due Carrier</span>
                        <div class="f-val">AS AGREED</div>
                    </td>
                </tr>
                <tr style="background-color: {{ $themeBar }};">
                    <td style="border: none; border-right: 1px solid {{ $themeColor }}; padding: 2pt 4pt;">
                        <span class="f-lbl-bold">Total Prepaid</span>
                        <div class="f-val">AS AGREED</div>
                    </td>
                    <td style="border: none; padding: 2pt 4pt;">
                        <span class="f-lbl-bold">Total Collect</span>
                        <div class="f-val">—</div>
                    </td>
                </tr>
            </table>
        </td>
        <td colspan="2" style="width: 55%; padding: 4pt; vertical-align: top;">
            <div class="legal-notice-sm">
                Shipper certifies that the particulars on the face hereof are correct and that INSOFAR AS ANY PART OF THE CONSIGNMENT CONTAINS DANGEROUS GOODS, SUCH PART IS PROPERLY DESCRIBED BY NAME AND IS IN PROPER CONDITION FOR CARRIAGE BY AIR ACCORDING TO THE APPLICABLE DANGEROUS GOODS REGULATIONS.
            </div>
            <div style="margin-top: 14pt; border-bottom: 1px dashed {{ $themeColor }}; padding-bottom: 2pt;">
                <span class="f-lbl">Signature of Shipper or his Agent</span>
            </div>
            <div style="margin-top: 16pt;">
                <table style="width: 100%; border-collapse: collapse; font-size: 6.5pt;">
                    <tr>
                        <td style="border: none; width: 33%; padding: 0;">
                            <span class="f-lbl">Executed on (Date)</span>
                            <div class="f-val">{{ $awb->awb_date ? $awb->awb_date->format('d-M-Y') : now()->format('d-M-Y') }}</div>
                        </td>
                        <td style="border: none; width: 33%; padding: 0;">
                            <span class="f-lbl">at (Place)</span>
                            <div class="f-val">JAKARTA</div>
                        </td>
                        <td style="border: none; width: 34%; padding: 0; text-align: right;">
                            <span class="f-lbl">Signature of Issuing Carrier / Agent</span>
                            <div class="f-val" style="margin-top: 4pt;">PT. RADIX INTL LOGISTICS</div>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<div class="footer-banner">
    {{ $footerText }}
</div>

</body>
</html>
