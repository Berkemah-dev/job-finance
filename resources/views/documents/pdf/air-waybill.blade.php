<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ strtoupper($type ?? 'HAWB') }} {{ $awb->number }}</title>
    @php
        $rawType = strtolower($type ?? request('type', 'hawb'));
        $isDraft = $rawType === 'draft';
        $isMawb = $rawType === 'mawb' || $rawType === 'original-2' || $rawType === 'red';

        if ($isDraft) {
            $themeColor = '#475569';
            $tintBg = '#f1f5f9';
            $solidBlockColor = '#cbd5e1';
            $footerText = '*** DRAFT COPY - NOT NEGOTIABLE ***';
        } elseif ($isMawb) {
            $themeColor = '#991b1b';
            $tintBg = '#fdf2f2';
            $solidBlockColor = '#fecaca';
            $footerText = 'Original 2 - (For Consignee)';
        } else {
            // HAWB / Original 3 (For Shipper) - Matching reference Gambar 2 steel slate blue
            $themeColor = '#35477d';
            $tintBg = '#eaedf3';
            $solidBlockColor = '#dbe3ee';
            $footerText = 'Original 3 - (For Shipper)';
        }

        $customer = $awb->customer ?? $awb->job?->customer;
        
        $shipperName = $isMawb
            ? ($awb->shipper_on_mawb ?: "PT. RADIX INTERNATIONAL LOGISTICS\nJL. TEH NO 3C, JAKARTA BARAT, INDONESIA")
            : ($awb->shipper_on_hawb ?: ($customer?->name ?? '—'));
        $shipperAddress = $isMawb
            ? "JL. TEH NO 3C, PINANGSIA, TAMAN SARI, JAKARTA BARAT"
            : ($customer?->address ?? ($awb->job?->shipper_address ?? ''));
        $shipperAcc = $awb->account_number ?: '—';

        $consigneeName = $isMawb
            ? ($awb->consignee_on_mawb ?: ($awb->agent_name ?: 'TO ORDER / OVERSEAS AGENT'))
            : ($awb->consignee_on_hawb ?: ($customer?->consignees?->first()?->name ?? '—'));
        $consigneeAddress = $isMawb
            ? "OVERSEAS AGENT PORT / DESTINATION"
            : ($customer?->consignees?->first()?->address ?? ($awb->job?->consignee_address ?? ''));
        $consigneeAcc = '—';

        $issuingAgent = "PT. RADIX INTERNATIONAL LOGISTICS\nJL. TEH NO 3C, TAMAN SARI, JAKARTA 11110";
        $agentIata = $awb->airline_code ?: '—';
        $agentAcc = $awb->account_number ?: '—';
        
        $accountingInfo = $awb->remarks ?: "FREIGHT " . strtoupper($awb->freight_term ?: 'PREPAID');
        if ($awb->job?->number) {
            $accountingInfo .= "\nREF JOB: " . $awb->job->number;
        }

        $departureAirport = $awb->airport_of_departure ?: ($awb->job?->pol ?? 'SOEKARNO HATTA INTL (CGK)');
        $destAirport = $awb->airport_of_destination ?: ($awb->job?->pod ?? '—');

        $firstCarrier = $awb->airline ?: ($awb->carrier ?: 'GA');
        $routingDest = $destAirport;
        $to1 = strtoupper(substr(trim($destAirport), 0, 3));
        $by1 = $awb->airline_code ?: 'GA';
        $to2 = '';
        $by2 = '';

        $currency = strtoupper($awb->currency ?: 'IDR');
        $chgCode = 'PP';
        $isPrepaid = strtoupper($awb->freight_term ?? 'PREPAID') === 'PREPAID';
        $wtValPpd = $isPrepaid ? 'PPD' : '';
        $wtValColl = !$isPrepaid ? 'COLL' : '';
        $otherPpd = $isPrepaid ? 'PPD' : '';
        $otherColl = !$isPrepaid ? 'COLL' : '';

        $valCarriage = $awb->value_of_carriage ?: 'N.V.D.';
        $valCustoms = $awb->value_of_customs ?: 'N.C.V.';
        $amountInsurance = 'XXX';

        $flightNo = $awb->flight_number ?: ($awb->job?->flight_number ?: '—');
        $flightDate = $awb->flight_date ? $awb->flight_date->format('d/m') : ($awb->etd ? $awb->etd->format('d/m') : '—');
        $connectingFlight = $awb->connecting_flight ?: '—';

        $handlingInfo = "NO SPECIAL HANDLING REQUIRED / GENERAL CARGO\n" . ($isMawb ? "CONSOL CARGO AS PER ATTACHED MANIFEST" : "DIRECT AIR SHIPMENT");

        $pieces = $awb->pieces ?: ($awb->job?->package_count ?: 1);
        $gw = $awb->gross_weight ?: ($awb->job?->gross_weight ?: 0);
        $gwFormatted = number_format((float)$gw, 2, '.', ',');
        $chargeableWeight = $awb->chargeable_weight ?: ($awb->gross_weight ?: $gw);
        $cwFormatted = number_format((float)$chargeableWeight, 2, '.', ',');
        $rateClass = 'Q';
        $rateCharge = 'AS AGREED';
        $totalCharge = 'AS AGREED';

        $natureGoods = $awb->commodity ?: ($awb->job?->cargo_description ?: 'GENERAL CARGO');
        if ($awb->volume || $awb->job?->volume) {
            $vol = $awb->volume ?: $awb->job->volume;
            $natureGoods .= "\nVOL: " . number_format((float)$vol, 3) . " CBM";
        }

        $execDate = $awb->awb_date ? $awb->awb_date->format('d/m/Y') : now()->format('d/m/Y');
        $execPlace = 'JAKARTA';

        if (!function_exists('renderTrapTab')) {
            function renderTrapTab($text, $color, $align = 'center', $extraStyle = '') {
                $margin = $align === 'center' ? 'margin: 0 auto;' : ($align === 'left' ? 'margin: 0;' : 'margin: 0 0 0 auto;');
                return '<table style="border-collapse: collapse; display: inline-table; vertical-align: top; ' . $margin . ' ' . $extraStyle . '">
                    <tr>
                        <td style="width: 7px; height: 11px; padding: 0; margin: 0; vertical-align: top; border: none; line-height: 0;">
                            <svg width="7" height="11" viewBox="0 0 7 11" style="display: block;">
                                <line x1="0" y1="0" x2="7" y2="11" stroke="' . $color . '" stroke-width="0.75" />
                            </svg>
                        </td>
                        <td style="border: none; border-bottom: 0.75px solid ' . $color . '; padding: 0 4px 1px 4px; font-size: 5pt; font-weight: bold; color: ' . $color . '; line-height: 9px; height: 11px; text-align: center; white-space: nowrap; vertical-align: middle;">
                            ' . $text . '
                        </td>
                        <td style="width: 7px; height: 11px; padding: 0; margin: 0; vertical-align: top; border: none; line-height: 0;">
                            <svg width="7" height="11" viewBox="0 0 7 11" style="display: block;">
                                <line x1="7" y1="0" x2="0" y2="11" stroke="' . $color . '" stroke-width="0.75" />
                            </svg>
                        </td>
                    </tr>
                </table>';
            }
        }
    @endphp
    <style>
        @page {
            margin: 8mm 9mm 6mm 9mm;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: {{ $themeColor }};
            font-size: 7pt;
            line-height: 1.15;
            margin: 0;
            padding: 0;
        }
        table.awb-main {
            width: 100%;
            border-collapse: collapse;
            border: 0.75px solid {{ $themeColor }};
            table-layout: fixed;
        }
        table.awb-main > tbody > tr > td {
            border: 0.75px solid {{ $themeColor }};
            vertical-align: top;
            padding: 0;
        }
        .f-lbl {
            font-size: 5.5pt;
            color: {{ $themeColor }};
            display: block;
            margin-bottom: 1.5pt;
        }
        .f-val {
            font-size: 6.8pt;
            color: #000000;
            line-height: 1.25;
        }
        .footer-caption {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            color: {{ $themeColor }};
            margin-top: 8pt;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<table class="awb-main">
    {{-- BARIS 1: SHIPPER vs TITLE & COPIES VALIDITY --}}
    <tr>
        {{-- SHIPPER (KIRI 50%) --}}
        <td style="width: 50%; vertical-align: top; height: 68pt;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="border: none; padding: 2pt 4pt; vertical-align: top; width: 65%;">
                        <span class="f-lbl">Shipper's Name and Address</span>
                        <div class="f-val" style="margin-top: 2pt;">
                            <strong>{{ $shipperName }}</strong><br>
                            {!! nl2br(e($shipperAddress)) !!}
                        </div>
                    </td>
                    <td style="border: none; width: 35%; padding: 0; vertical-align: top;">
                        <div style="border-left: 0.75px solid {{ $themeColor }}; border-bottom: 0.75px solid {{ $themeColor }}; padding: 2pt 4pt; height: 28pt; text-align: center;">
                            <span class="f-lbl" style="text-align: center;">Shipper's Account Number</span>
                            <div class="f-val" style="font-weight: bold; margin-top: 2pt;">{{ $shipperAcc }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </td>

        {{-- TITLE & VALIDITY (KANAN 50%) --}}
        <td style="width: 50%; vertical-align: top; height: 68pt;">
            <div style="padding: 2pt 6pt;">
                <div style="font-size: 5.5pt; color: {{ $themeColor }};">Not Negotiable</div>
                <div style="font-size: 14pt; font-weight: bold; color: {{ $themeColor }}; margin: 1pt 0;">Air Waybill</div>
                <div style="font-size: 5.5pt; color: {{ $themeColor }};">Issued By</div>
            </div>
            <div style="border-top: 0.75px solid {{ $themeColor }}; border-bottom: 0.75px solid {{ $themeColor }}; padding: 1.5pt 6pt; font-size: 5.5pt; font-weight: bold; color: {{ $themeColor }};">
                Copies 1, 2 and 3 of this Air Waybill are originals and have the same validity.
            </div>
        </td>
    </tr>

    {{-- BARIS 2: CONSIGNEE & CONTRACT CLAUSE --}}
    <tr>
        {{-- CONSIGNEE (KIRI 50%) --}}
        <td style="vertical-align: top; height: 58pt;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="border: none; padding: 2pt 4pt; vertical-align: top; width: 65%;">
                        <span class="f-lbl">Consignee's Name and Address</span>
                        <div class="f-val" style="margin-top: 2pt;">
                            <strong>{{ $consigneeName }}</strong><br>
                            {!! nl2br(e($consigneeAddress)) !!}
                        </div>
                    </td>
                    <td style="border: none; width: 35%; padding: 0; vertical-align: top;">
                        <div style="border-left: 0.75px solid {{ $themeColor }}; border-bottom: 0.75px solid {{ $themeColor }}; background-color: {{ $tintBg }}; padding: 2pt 4pt; height: 26pt; text-align: center;">
                            <span class="f-lbl" style="text-align: center; font-weight: bold;">Consignee's Account Number</span>
                            <div class="f-val" style="font-weight: bold; margin-top: 1pt;">{{ $consigneeAcc }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </td>

        {{-- CONTRACT CLAUSE (KANAN 50%) --}}
        <td style="vertical-align: top; padding: 3pt 6pt; height: 58pt;">
            <div style="font-size: 4.4pt; line-height: 1.15; text-align: justify; color: {{ $themeColor }};">
                It is agreed that goods described herein are accepted in apparent good order and condition (except as noted) for carriage SUBJECT TO THE CONDITIONS OF CONTRACT ON THE REVERSE HEREOF. ALL GOODS MAY BE CARRIED BY ANY OTHER MEANS INCLUDING ROAD OR ANY OTHER CARRIER UNLESS SPECIFIC CONTRARY INSTRUCTIONS ARE GIVEN HEREON BY THE SHIPPER, AND SHIPPER AGREES THAT THE SHIPMENT MAY BE CARRIED VIA INTERMEDIATE STOPPING PLACES WHICH THE CARRIER DEEMS APPROPRIATE. THE SHIPPER'S ATTENTION IS DRAWN TO THE NOTICE CONCERNING CARRIER'S LIMITATION OF LIABILITY. Shipper may increase such limitation of liability by declaring a higher value for carriage and paying a supplemental charge if required.
            </div>
        </td>
    </tr>

    {{-- BARIS 3: ISSUING AGENT & ACCOUNTING INFORMATION --}}
    <tr>
        {{-- ISSUING CARRIER AGENT (KIRI 50%) --}}
        <td style="vertical-align: top; padding: 2pt 4pt; height: 32pt;">
            <span class="f-lbl">Issuing Carrier's Agent Name and City</span>
            <div class="f-val" style="margin-top: 1.5pt;">
                {!! nl2br(e($issuingAgent)) !!}
            </div>
        </td>

        {{-- ACCOUNTING INFORMATION (KANAN 50%, SPANS OPPOSITE ISSUING AGENT + AGENT IATA CODE) --}}
        <td rowspan="2" style="vertical-align: top; padding: 2pt 6pt;">
            <span class="f-lbl">Accounting Information</span>
            <div class="f-val" style="margin-top: 2pt; font-size: 6.8pt; line-height: 1.25;">
                {!! nl2br(e($accountingInfo)) !!}
            </div>
        </td>
    </tr>

    {{-- BARIS 4: AGENT IATA CODE & ACCOUNT NO --}}
    <tr>
        <td style="vertical-align: top; height: 20pt;">
            <table style="width: 100%; border-collapse: collapse; height: 20pt;">
                <tr>
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 50%; padding: 1.5pt 4pt; vertical-align: top;">
                        <span class="f-lbl">Agent's IATA Code</span>
                        <div class="f-val" style="font-weight: bold;">{{ $agentIata }}</div>
                    </td>
                    <td style="border: none; width: 50%; padding: 1.5pt 4pt; vertical-align: top;">
                        <span class="f-lbl">Account No.</span>
                        <div class="f-val" style="font-weight: bold;">{{ $agentAcc }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- BARIS 5: AIRPORT OF DEPARTURE --}}
    <tr>
        <td colspan="2" style="vertical-align: top; padding: 2pt 4pt; height: 20pt;">
            <span class="f-lbl">Airport of Departure (Addr. of First Carrier) and Requested Routing</span>
            <div class="f-val" style="font-size: 7.2pt; font-weight: bold; margin-top: 1pt;">{{ $departureAirport }}</div>
        </td>
    </tr>

    {{-- BARIS 6: ROUTING & FINANCIAL DECLARATIONS --}}
    <tr>
        {{-- KIRI: ROUTING --}}
        <td style="vertical-align: top; height: 24pt;">
            <table style="width: 100%; border-collapse: collapse; height: 24pt;">
                <tr>
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 20%; padding: 1pt 2px; vertical-align: top;">
                        <span class="f-lbl" style="font-size: 4.8pt; line-height: 1;">By first Carrier</span>
                        <div class="f-val" style="font-size: 6.5pt; font-weight: bold; margin-top: 2pt;">{{ $firstCarrier }}</div>
                    </td>
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 36%; padding: 0; vertical-align: top;">
                        {!! renderTrapTab('Routing and Destination', $themeColor, 'center') !!}
                        <div class="f-val" style="font-size: 6.5pt; font-weight: bold; text-align: center; margin-top: 2pt;">{{ $routingDest }}</div>
                    </td>
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 11%; padding: 1pt 1px; vertical-align: top; text-align: center;">
                        <span class="f-lbl" style="font-size: 5pt;">to</span>
                        <div class="f-val" style="font-size: 6.5pt; font-weight: bold;">{{ $to1 }}</div>
                    </td>
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 11%; padding: 1pt 1px; vertical-align: top; text-align: center;">
                        <span class="f-lbl" style="font-size: 5pt;">by</span>
                        <div class="f-val" style="font-size: 6.5pt; font-weight: bold;">{{ $by1 }}</div>
                    </td>
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 11%; padding: 1pt 1px; vertical-align: top; text-align: center;">
                        <span class="f-lbl" style="font-size: 5pt;">to</span>
                        <div class="f-val" style="font-size: 6.5pt; font-weight: bold;">{{ $to2 }}</div>
                    </td>
                    <td style="border: none; width: 11%; padding: 1pt 1px; vertical-align: top; text-align: center;">
                        <span class="f-lbl" style="font-size: 5pt;">by</span>
                        <div class="f-val" style="font-size: 6.5pt; font-weight: bold;">{{ $by2 }}</div>
                    </td>
                </tr>
            </table>
        </td>

        {{-- KANAN: FINANCIAL / DECLARATIONS --}}
        <td style="vertical-align: top; height: 24pt;">
            <table style="width: 100%; border-collapse: collapse; height: 24pt;">
                <tr>
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 11%; padding: 1pt 1px; vertical-align: top; text-align: center;">
                        <span class="f-lbl" style="font-size: 4.8pt;">Currency</span>
                        <div class="f-val" style="font-size: 6.5pt; font-weight: bold; margin-top: 2pt;">{{ $currency }}</div>
                    </td>
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 8%; padding: 1pt 1px; vertical-align: top; text-align: center;">
                        <span class="f-lbl" style="font-size: 4.2pt; line-height: 1;">Chgs<br>Code</span>
                        <div class="f-val" style="font-size: 6pt; font-weight: bold; margin-top: 1pt;">{{ $chgCode }}</div>
                    </td>
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 15%; padding: 0; vertical-align: top;">
                        <div style="text-align: center; border-bottom: 0.5px solid {{ $themeColor }}; font-size: 4.2pt; padding: 0.5pt 0; color: {{ $themeColor }};">WT VAL</div>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="border: none; border-right: 0.5px solid {{ $themeColor }}; width: 50%; text-align: center; font-size: 4pt; color: {{ $themeColor }}; padding: 0;">PPD</td>
                                <td style="border: none; width: 50%; text-align: center; font-size: 4pt; color: {{ $themeColor }}; padding: 0;">COLL</td>
                            </tr>
                            <tr>
                                <td style="border: none; border-right: 0.5px solid {{ $themeColor }}; text-align: center; font-size: 5.5pt; font-weight: bold;">{{ $wtValPpd }}</td>
                                <td style="border: none; text-align: center; font-size: 5.5pt; font-weight: bold;">{{ $wtValColl }}</td>
                            </tr>
                        </table>
                    </td>
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 15%; padding: 0; vertical-align: top;">
                        <div style="text-align: center; border-bottom: 0.5px solid {{ $themeColor }}; font-size: 4.2pt; padding: 0.5pt 0; color: {{ $themeColor }};">Other</div>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="border: none; border-right: 0.5px solid {{ $themeColor }}; width: 50%; text-align: center; font-size: 4pt; color: {{ $themeColor }}; padding: 0;">PPD</td>
                                <td style="border: none; width: 50%; text-align: center; font-size: 4pt; color: {{ $themeColor }}; padding: 0;">COLL</td>
                            </tr>
                            <tr>
                                <td style="border: none; border-right: 0.5px solid {{ $themeColor }}; text-align: center; font-size: 5.5pt; font-weight: bold;">{{ $otherPpd }}</td>
                                <td style="border: none; text-align: center; font-size: 5.5pt; font-weight: bold;">{{ $otherColl }}</td>
                            </tr>
                        </table>
                    </td>
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 25.5%; padding: 1pt 2px; vertical-align: top; text-align: center;">
                        <span class="f-lbl" style="font-size: 4.5pt; line-height: 1;">Declared Value for Carriage</span>
                        <div class="f-val" style="font-size: 6.5pt; font-weight: bold; margin-top: 2pt;">{{ $valCarriage }}</div>
                    </td>
                    <td style="border: none; width: 25.5%; padding: 1pt 2px; vertical-align: top; text-align: center;">
                        <span class="f-lbl" style="font-size: 4.5pt; line-height: 1;">Declared Value for Customs</span>
                        <div class="f-val" style="font-size: 6.5pt; font-weight: bold; margin-top: 2pt;">{{ $valCustoms }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- BARIS 7: DESTINATION AIRPORT & INSURANCE --}}
    <tr>
        {{-- KIRI: DESTINATION & FLIGHTS --}}
        <td style="vertical-align: top; height: 21pt;">
            <table style="width: 100%; border-collapse: collapse; height: 21pt;">
                <tr>
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 44%; padding: 1pt 4px; vertical-align: top;">
                        <span class="f-lbl">Airport of Destination</span>
                        <div class="f-val" style="font-size: 6.8pt; font-weight: bold;">{{ $destAirport }}</div>
                    </td>
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 24%; padding: 1pt 2px; vertical-align: top; text-align: center;">
                        <span class="f-lbl" style="font-size: 5pt;">Flight/ Date</span>
                        <div class="f-val" style="font-size: 6.5pt; font-weight: bold;">{{ $flightNo }} {{ $flightDate }}</div>
                    </td>
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 14%; padding: 0; vertical-align: top;">
                        {!! renderTrapTab('For Carrier Use Only', $themeColor, 'center') !!}
                    </td>
                    <td style="border: none; width: 18%; padding: 1pt 2px; vertical-align: top; text-align: center;">
                        <span class="f-lbl" style="font-size: 5pt;">Flight/ Date</span>
                        <div class="f-val" style="font-size: 6.5pt; font-weight: bold;">{{ $connectingFlight }}</div>
                    </td>
                </tr>
            </table>
        </td>

        {{-- KANAN: INSURANCE --}}
        <td style="vertical-align: top; height: 21pt;">
            <table style="width: 100%; border-collapse: collapse; height: 21pt;">
                <tr>
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 30%; padding: 1pt 4px; vertical-align: top;">
                        <span class="f-lbl">Amount of Insurance</span>
                        <div class="f-val" style="font-size: 6.5pt; text-align: center; font-weight: bold;">{{ $amountInsurance }}</div>
                    </td>
                    <td style="border: none; width: 70%; padding: 1pt 4px; vertical-align: middle;">
                        <div style="font-size: 4.2pt; line-height: 1.1; color: {{ $themeColor }}; text-align: justify;">
                            INSURANCE - If Carrier offers Insurance and such insurance is requested in accordance with conditions on reserve hereof indicate amount to be insured in figures in box marked "Amount of Insurance."
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- BARIS 8: HANDLING INFORMATION (TALL SPACIOUS OPEN BOX MATCHING GAMBAR 2) --}}
    <tr>
        <td colspan="2" style="vertical-align: top; padding: 2pt 5pt; height: 54pt;">
            <span class="f-lbl" style="font-weight: bold;">Handling Information</span>
            <div class="f-val" style="font-size: 6.8pt; margin-top: 2pt; font-weight: 600;">
                {!! nl2br(e($handlingInfo)) !!}
            </div>
        </td>
    </tr>

    {{-- BARIS 9: CARGO GRID (8 COLUMNS EXTENDING FULL HEIGHT) --}}
    <tr>
        <td colspan="2" style="vertical-align: top; padding: 0;">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                <thead>
                    <tr style="height: 24pt;">
                        <th style="border: none; border-right: 0.75px solid {{ $themeColor }}; border-bottom: 0.75px solid {{ $themeColor }}; width: 7.5%; font-size: 5.2pt; color: {{ $themeColor }}; text-align: center; padding: 1pt; vertical-align: middle;">
                            No.of<br>Pieces<br>RCP
                        </th>
                        <th style="border: none; border-right: 0.75px solid {{ $themeColor }}; border-bottom: 0.75px solid {{ $themeColor }}; width: 10%; font-size: 5.8pt; color: {{ $themeColor }}; text-align: center; padding: 1pt; vertical-align: middle;">
                            Gross<br>Weight
                        </th>
                        <th style="border: none; border-right: 0.75px solid {{ $themeColor }}; border-bottom: 0.75px solid {{ $themeColor }}; width: 4.5%; font-size: 5.2pt; color: {{ $themeColor }}; text-align: center; padding: 1pt; vertical-align: middle; background-color: {{ $tintBg }};">
                            kg<br>lb
                        </th>
                        <th style="border: none; border-right: 0.75px solid {{ $themeColor }}; border-bottom: 0.75px solid {{ $themeColor }}; width: 13.5%; font-size: 5.5pt; color: {{ $themeColor }}; text-align: center; padding: 0; vertical-align: middle; background-color: {{ $tintBg }};">
                            Rate Class<br>
                            <div style="border-top: 0.5px solid {{ $themeColor }}; padding-top: 1pt; margin-top: 1pt; font-size: 4.8pt;">
                                Commodity Item No.
                            </div>
                        </th>
                        <th style="border: none; border-right: 0.75px solid {{ $themeColor }}; border-bottom: 0.75px solid {{ $themeColor }}; width: 11%; font-size: 5.8pt; color: {{ $themeColor }}; text-align: center; padding: 1pt; vertical-align: middle;">
                            Chargeable<br>Weight
                        </th>
                        <th style="border: none; border-right: 0.75px solid {{ $themeColor }}; border-bottom: 0.75px solid {{ $themeColor }}; width: 11.5%; font-size: 5.5pt; color: {{ $themeColor }}; text-align: center; padding: 0; vertical-align: middle; background-color: {{ $tintBg }};">
                            <table style="width: 100%; border-collapse: collapse; height: 22pt;">
                                <tr>
                                    <td style="border: none; text-align: left; padding: 1pt 3pt; font-size: 5.5pt; color: {{ $themeColor }}; width: 35%;">Rate</td>
                                    <td style="border: none; width: 30%; text-align: center; vertical-align: middle; padding: 0;">
                                        <svg width="18" height="20" viewBox="0 0 18 20" style="display: block; margin: 0 auto;">
                                            <line x1="2" y1="18" x2="16" y2="2" stroke="{{ $themeColor }}" stroke-width="0.75" />
                                        </svg>
                                    </td>
                                    <td style="border: none; text-align: right; padding: 1pt 3pt; font-size: 5.5pt; color: {{ $themeColor }}; width: 35%; vertical-align: bottom;">Charge</td>
                                </tr>
                            </table>
                        </th>
                        <th style="border: none; border-right: 0.75px solid {{ $themeColor }}; border-bottom: 0.75px solid {{ $themeColor }}; width: 13.5%; font-size: 5.8pt; color: {{ $themeColor }}; text-align: center; padding: 1pt; vertical-align: middle;">
                            Total
                        </th>
                        <th style="border: none; border-bottom: 0.75px solid {{ $themeColor }}; width: 28.5%; font-size: 5.8pt; color: {{ $themeColor }}; text-align: center; padding: 1pt; vertical-align: middle;">
                            Nature and Quantity of Goods<br>(Incl. Dimensions or Volume)
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="height: 140pt;">
                        <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; text-align: center; vertical-align: top; padding: 4pt 2px; font-size: 7.2pt; font-weight: bold;">
                            {{ $pieces }}
                        </td>
                        <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; text-align: right; vertical-align: top; padding: 4pt 4px; font-size: 7.2pt; font-weight: bold;">
                            {{ $gwFormatted }}
                        </td>
                        <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; text-align: center; vertical-align: top; padding: 4pt 2px; font-size: 7pt; font-weight: bold;">
                            K
                        </td>
                        <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; text-align: center; vertical-align: top; padding: 4pt 2px; font-size: 7pt; font-weight: bold;">
                            {{ $rateClass }}
                        </td>
                        <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; text-align: right; vertical-align: top; padding: 4pt 4px; font-size: 7.2pt; font-weight: bold;">
                            {{ $cwFormatted }}
                        </td>
                        <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; text-align: center; vertical-align: top; padding: 4pt 2px; font-size: 7pt; font-weight: bold;">
                            {{ $rateCharge }}
                        </td>
                        <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; text-align: right; vertical-align: top; padding: 4pt 4px; font-size: 7.2pt; font-weight: bold;">
                            {{ $totalCharge }}
                        </td>
                        <td style="border: none; text-align: left; vertical-align: top; padding: 4pt 6px; font-size: 7.2pt; font-weight: bold; line-height: 1.3;">
                            {!! nl2br(e($natureGoods)) !!}
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>

    {{-- BARIS 10: CHARGES, SHIPPER CERTIFICATION & SIGNATURES --}}
    <tr>
        {{-- KOLOM KIRI (38% WIDTH): STACKED BOXES DENGAN TRAPEZOID TABS --}}
        <td style="width: 38%; vertical-align: top; padding: 0;">
            <table style="width: 100%; border-collapse: collapse;">
                {{-- PREPAID / WEIGHT CHARGE / COLLECT --}}
                <tr style="height: 18pt;">
                    <td style="border: none; border-bottom: 0.75px solid {{ $themeColor }}; width: 100%; padding: 0; vertical-align: top;" colspan="2">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="border: none; width: 28%; padding: 0; vertical-align: top; text-align: left;">
                                    {!! renderTrapTab('Prepaid', $themeColor, 'left', 'margin-left: 2px;') !!}
                                </td>
                                <td style="border: none; width: 44%; padding: 0; vertical-align: top; text-align: center;">
                                    {!! renderTrapTab('Weight Charge', $themeColor, 'center') !!}
                                </td>
                                <td style="border: none; width: 28%; padding: 0; vertical-align: top; text-align: right;">
                                    {!! renderTrapTab('Collect', $themeColor, 'right', 'margin-right: 2px;') !!}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- VALUATION CHARGE --}}
                <tr style="height: 17pt;">
                    <td colspan="2" style="border: none; border-bottom: 0.75px solid {{ $themeColor }}; padding: 0; vertical-align: top;">
                        {!! renderTrapTab('Valuation Charge', $themeColor, 'center') !!}
                    </td>
                </tr>

                {{-- TAX --}}
                <tr style="height: 17pt;">
                    <td colspan="2" style="border: none; border-bottom: 0.75px solid {{ $themeColor }}; padding: 0; vertical-align: top;">
                        {!! renderTrapTab('Tax', $themeColor, 'center') !!}
                    </td>
                </tr>

                {{-- TOTAL OTHER CHARGES DUE AGENT --}}
                <tr style="height: 17pt;">
                    <td colspan="2" style="border: none; border-bottom: 0.75px solid {{ $themeColor }}; padding: 0; vertical-align: top;">
                        {!! renderTrapTab('Total Other Charges Due Agent', $themeColor, 'center') !!}
                    </td>
                </tr>

                {{-- TOTAL OTHER CHARGES DUE CARRIER --}}
                <tr style="height: 17pt;">
                    <td colspan="2" style="border: none; border-bottom: 0.75px solid {{ $themeColor }}; padding: 0; vertical-align: top;">
                        {!! renderTrapTab('Total Other Charges Due Carrier', $themeColor, 'center') !!}
                    </td>
                </tr>

                {{-- SOLID COLORED BLOCK MATCHING GAMBAR 2 --}}
                <tr style="height: 18pt; background-color: {{ $solidBlockColor }};">
                    <td colspan="2" style="border: none; border-bottom: 0.75px solid {{ $themeColor }};"></td>
                </tr>

                {{-- TOTAL PREPAID & TOTAL COLLECT --}}
                <tr style="height: 20pt;">
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; border-bottom: 0.75px solid {{ $themeColor }}; width: 50%; padding: 0; vertical-align: top;">
                        {!! renderTrapTab('Total Prepaid', $themeColor, 'center') !!}
                    </td>
                    <td style="border: none; border-bottom: 0.75px solid {{ $themeColor }}; width: 50%; padding: 0; vertical-align: top;">
                        {!! renderTrapTab('Total Collect', $themeColor, 'center') !!}
                    </td>
                </tr>

                {{-- CURRENCY CONVERSION RATES & CC CHARGES IN DEST. CURRENCY --}}
                <tr style="height: 20pt;">
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; border-bottom: 0.75px solid {{ $themeColor }}; width: 50%; padding: 0; vertical-align: top;">
                        {!! renderTrapTab('Currency Conversion Rates', $themeColor, 'center') !!}
                    </td>
                    <td style="border: none; border-bottom: 0.75px solid {{ $themeColor }}; width: 50%; padding: 0; vertical-align: top;">
                        {!! renderTrapTab('cc Charges in Dest. Currency', $themeColor, 'center') !!}
                    </td>
                </tr>

                {{-- FOR CARRIER'S USE ONLY AT DESTINATION & CHARGES AT DESTINATION --}}
                <tr style="height: 22pt;">
                    <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 50%; padding: 2pt 2px; vertical-align: middle; text-align: center; background-color: {{ $tintBg }};">
                        <span style="font-size: 4.8pt; font-weight: bold; color: {{ $themeColor }};">For Carrier's Use Only<br>at Destination</span>
                    </td>
                    <td style="border: none; width: 50%; padding: 0; vertical-align: top; background-color: {{ $tintBg }};">
                        {!! renderTrapTab('Charges at Destination', $themeColor, 'center') !!}
                    </td>
                </tr>
            </table>
        </td>

        {{-- KOLOM KANAN (62% WIDTH): OTHER CHARGES, SHIPPER CERTIFICATION & SIGNATURES --}}
        <td style="width: 62%; vertical-align: top; padding: 0;">
            <table style="width: 100%; border-collapse: collapse;">
                {{-- OTHER CHARGES BOX --}}
                <tr style="height: 58pt;">
                    <td colspan="3" style="border: none; border-bottom: 0.75px solid {{ $themeColor }}; padding: 2pt 5pt; vertical-align: top;">
                        <span class="f-lbl">Other Charges</span>
                    </td>
                </tr>

                {{-- SHIPPER CERTIFICATION & DANGEROUS GOODS (MATCHING GAMBAR 2 TEXT) --}}
                <tr style="height: 48pt;">
                    <td colspan="3" style="border: none; padding: 3pt 6pt; vertical-align: top;">
                        <div style="font-size: 5.2pt; font-weight: bold; line-height: 1.25; color: {{ $themeColor }}; text-align: justify;">
                            Shipper certifies that the particulars on the face hereof are correct and that INSOFAR AS ANY PART OF THE CONSIGNMENT CONTAINS DANGEROUS GOODS, SUCH PART IS PROPERLY DESCRIBED BY NAME AND IS IN PROPER CONDITION FOR CARRIAGE BY AIR ACCORDING TO THE APPLICABLE DANGEROUS GOODS REGULATIONS.
                        </div>
                    </td>
                </tr>

                {{-- DOTTED SIGNATURE LINE FOR SHIPPER --}}
                <tr style="height: 22pt;">
                    <td colspan="3" style="border: none; padding: 1pt 6pt; vertical-align: bottom;">
                        <div style="border-top: 0.75px dotted {{ $themeColor }}; text-align: center; padding-top: 1.5pt; font-size: 5.8pt; font-weight: bold; color: {{ $themeColor }};">
                            Signature of Shipper or his Agent.
                        </div>
                    </td>
                </tr>

                {{-- EXECUTED ON DATE / PLACE, TOTAL COLLECT CHARGES & CARRIER SIGNATURE --}}
                <tr style="border-top: 0.75px solid {{ $themeColor }};">
                    <td colspan="3" style="border: none; padding: 0;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr style="height: 18pt;">
                                <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 26%; padding: 1pt 3pt; vertical-align: top;">
                                    <span class="f-lbl" style="font-size: 4.8pt;">Executed on (Date)</span>
                                    <div class="f-val" style="font-size: 6.5pt; font-weight: bold;">{{ $execDate }}</div>
                                </td>
                                <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 24%; padding: 1pt 3pt; vertical-align: top;">
                                    <span class="f-lbl" style="font-size: 4.8pt;">at (Place)</span>
                                    <div class="f-val" style="font-size: 6.5pt; font-weight: bold;">{{ $execPlace }}</div>
                                </td>
                                <td style="border: none; width: 50%; padding: 1.5pt 3pt; vertical-align: top; text-align: center;" rowspan="2">
                                    <span class="f-lbl" style="font-size: 4.8pt;">Signature of Issuing Carrier or its Agent.</span>
                                    <div class="f-val" style="font-size: 6.2pt; font-weight: bold; margin-top: 5pt;">PT. RADIX INTERNATIONAL LOGISTICS</div>
                                </td>
                            </tr>
                            <tr style="height: 14pt; border-top: 0.75px solid {{ $themeColor }};">
                                <td colspan="2" style="border: none; border-right: 0.75px solid {{ $themeColor }}; padding: 0; vertical-align: top;">
                                    {!! renderTrapTab('Total Collect Charges', $themeColor, 'left', 'margin-left: 2px;') !!}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- FOOTER IDENTITAS BESAR TERPUSAT --}}
<div class="footer-caption">
    {{ $footerText }}
</div>

</body>
</html>
