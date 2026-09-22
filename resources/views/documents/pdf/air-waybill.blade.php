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
            // HAWB / Original 3 (For Shipper) - Deep IATA Slate Navy matching HAWB.docx
            $themeColor = '#35477d';
            $tintBg = '#eceff6';
            $solidBlockColor = '#eceff6';
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
        $by1 = $awb->airline_code ?: ($awb->carrier ?: 'GA');
        if (str_contains($by1, '-')) {
            $parts = explode('-', $by1);
            $by1 = $parts[0];
        }
        $to2 = '';
        $by2 = '';

        $currency = strtoupper($awb->currency ?: 'USD');
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
            function renderTrapTab($text, $color, $width = 110, $height = 11, $slant = 5, $align = 'center', $mode = 'both', $extraStyle = '', $bgColor = '#ffffff', $bottomLineHeight = 0) {
                $x1 = 0.5;
                $y1 = 0.5;
                $x2 = ($mode === 'both' || $mode === 'left') ? $slant : 0.5;
                $y2 = $height - 0.5;
                $x3 = ($mode === 'both' || $mode === 'right') ? ($width - $slant) : ($width - 0.5);
                $y3 = $height - 0.5;
                $x4 = $width - 0.5;
                $y4 = 0.5;
                
                $totalHeight = $height + $bottomLineHeight;
                $points = "{$x1},{$y1} {$x2},{$y2} {$x3},{$y3} {$x4},{$y4}";
                $textX = $width / 2;
                $textY = ($height / 2) + 2.5;
                
                $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $totalHeight . '" viewBox="0 0 ' . $width . ' ' . $totalHeight . '">
                    <polygon points="' . $points . '" fill="' . $bgColor . '" stroke="' . $color . '" stroke-width="0.75" />
                    <text x="' . $textX . '" y="' . $textY . '" fill="' . $color . '" font-size="5.2pt" font-family="Arial, Helvetica, sans-serif" font-weight="bold" text-anchor="middle">' . htmlspecialchars($text, ENT_QUOTES) . '</text>';
                if ($bottomLineHeight > 0) {
                    $svg .= '<line x1="' . ($width / 2) . '" y1="' . $y2 . '" x2="' . ($width / 2) . '" y2="' . $totalHeight . '" stroke="' . $color . '" stroke-width="0.75" />';
                }
                $svg .= '</svg>';
                
                $src = 'data:image/svg+xml;base64,' . base64_encode($svg);
                $margin = $align === 'center' ? 'margin: 0 auto;' : ($align === 'left' ? 'margin: 0;' : 'margin: 0 0 0 auto;');
                $display = $align === 'center' ? 'block' : 'inline-block';
                return '<img src="' . $src . '" style="display: ' . $display . '; ' . $margin . ' margin-top: -0.75px; width: ' . $width . 'px; height: ' . $totalHeight . 'px; ' . $extraStyle . '" />';
            }
        }

        if (!function_exists('renderSplitHeader')) {
            function renderSplitHeader($title, $color, $valPpd = '', $valColl = '', $width = 39, $height = 35) {
                $midX = $width / 2;
                $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">
                    <text x="' . $midX . '" y="7.5" fill="' . $color . '" font-size="4.2pt" font-family="Arial, Helvetica, sans-serif" font-weight="bold" text-anchor="middle">' . htmlspecialchars($title) . '</text>
                    <line x1="0" y1="10.5" x2="' . $width . '" y2="10.5" stroke="' . $color . '" stroke-width="0.5" />
                    <line x1="' . $midX . '" y1="10.5" x2="' . $midX . '" y2="' . $height . '" stroke="' . $color . '" stroke-width="0.5" />
                    <text x="' . ($midX / 2) . '" y="16" fill="' . $color . '" font-size="3.8pt" font-family="Arial, Helvetica, sans-serif" font-weight="bold" text-anchor="middle">PPD</text>
                    <text x="' . ($midX + $midX / 2) . '" y="16" fill="' . $color . '" font-size="3.8pt" font-family="Arial, Helvetica, sans-serif" font-weight="bold" text-anchor="middle">COLL</text>
                    <text x="' . ($midX / 2) . '" y="27" fill="' . $color . '" font-size="5.5pt" font-family="Arial, Helvetica, sans-serif" font-weight="bold" text-anchor="middle">' . htmlspecialchars($valPpd) . '</text>
                    <text x="' . ($midX + $midX / 2) . '" y="27" fill="' . $color . '" font-size="5.5pt" font-family="Arial, Helvetica, sans-serif" font-weight="bold" text-anchor="middle">' . htmlspecialchars($valColl) . '</text>
                </svg>';
                $src = 'data:image/svg+xml;base64,' . base64_encode($svg);
                return '<img src="' . $src . '" style="display: block; width: 100%; height: ' . $height . 'px;" />';
            }
        }

        if (!function_exists('renderRateChargeHeader')) {
            function renderRateChargeHeader($color, $width = 65, $height = 24) {
                $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">
                    <line x1="14" y1="' . ($height - 3) . '" x2="' . ($width - 14) . '" y2="3" stroke="' . $color . '" stroke-width="0.75" />
                    <text x="6" y="9" fill="' . $color . '" font-size="5.5pt" font-family="Arial, Helvetica, sans-serif" font-weight="bold" text-anchor="start">Rate</text>
                    <text x="' . ($width - 6) . '" y="' . ($height - 3) . '" fill="' . $color . '" font-size="5.5pt" font-family="Arial, Helvetica, sans-serif" font-weight="bold" text-anchor="end">Charge</text>
                </svg>';
                $src = 'data:image/svg+xml;base64,' . base64_encode($svg);
                return '<img src="' . $src . '" style="display: block; margin: 0 auto; width: ' . $width . 'px; height: ' . $height . 'px;" />';
            }
        }

        if (!function_exists('renderTaxCell')) {
            function renderTaxCell($color, $width = 54, $totalHeight = 21, $tabHeight = 11, $slant = 4.5) {
                $tabY = ($totalHeight - $tabHeight) / 2;
                $x1 = 0.5; $y1 = $tabY;
                $x2 = $slant; $y2 = $tabY + $tabHeight;
                $x3 = $width - $slant; $y3 = $tabY + $tabHeight;
                $x4 = $width - 0.5; $y4 = $tabY;
                
                $points = "{$x1},{$y1} {$x2},{$y2} {$x3},{$y3} {$x4},{$y4}";
                $textX = $width / 2;
                $textY = $tabY + ($tabHeight / 2) + 2.5;
                $midX = $width / 2;
                
                $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $totalHeight . '" viewBox="0 0 ' . $width . ' ' . $totalHeight . '">
                    <line x1="' . $midX . '" y1="0" x2="' . $midX . '" y2="' . $totalHeight . '" stroke="' . $color . '" stroke-width="0.75" />
                    <polygon points="' . $points . '" fill="#ffffff" stroke="' . $color . '" stroke-width="0.75" />
                    <text x="' . $textX . '" y="' . $textY . '" fill="' . $color . '" font-size="5.2pt" font-family="Arial, Helvetica, sans-serif" font-weight="bold" text-anchor="middle">Tax</text>
                </svg>';
                $src = 'data:image/svg+xml;base64,' . base64_encode($svg);
                return '<img src="' . $src . '" style="display: block; margin: 0 auto; width: ' . $width . 'px; height: ' . $totalHeight . 'px;" />';
            }
        }
    @endphp
    <style>
        @page {
            margin: 10pt 16pt 6pt 16pt;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: {{ $themeColor }};
            font-size: 6.8pt;
            line-height: 1.15;
            margin: 0;
            padding: 0;
        }
        .awb-block {
            width: 100%;
            border-collapse: collapse;
            border: 0.75px solid {{ $themeColor }};
            margin-top: -0.75px;
            table-layout: fixed;
        }
        .awb-block td, .awb-block th {
            border: 0.75px solid {{ $themeColor }};
            vertical-align: top;
            padding: 0;
        }
        .f-lbl {
            font-size: 5pt;
            font-weight: bold;
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
            font-size: 13.5pt;
            font-weight: bold;
            color: {{ $themeColor }};
            margin-top: 5pt;
            letter-spacing: 0.3px;
        }
    </style>
</head>
<body>

<!-- BLOCK 1: Shipper / Air Waybill Title & Consignee / Contract Clause -->
<table class="awb-block" style="margin-top: 0;">
    <tr style="height: 70pt;">
        <td style="width: 49%; vertical-align: top;">
            <table style="width: 100%; border-collapse: collapse; height: 70pt;">
                <tr>
                    <td style="border: none; padding: 2pt 4pt; vertical-align: top; width: 51%;">
                        <span class="f-lbl">Shipper's Name and Address</span>
                        <div class="f-val" style="margin-top: 2pt;">
                            <strong>{{ $shipperName }}</strong><br>
                            {!! nl2br(e($shipperAddress)) !!}
                        </div>
                    </td>
                    <td style="border: none; width: 49%; padding: 0; vertical-align: top;">
                        <div style="border-left: 0.75px solid {{ $themeColor }}; border-bottom: 0.75px solid {{ $themeColor }}; padding: 2pt 3pt; height: 26pt; text-align: center;">
                            <span class="f-lbl" style="text-align: center;">Shipper's Account Number</span>
                            <div class="f-val" style="font-weight: bold; margin-top: 2pt;">{{ $shipperAcc }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
        <td style="width: 51%; vertical-align: top; padding: 0;">
            <table style="width: 100%; border-collapse: collapse; height: 70pt;">
                <tr style="height: 57pt;">
                    <td style="border: none; padding: 2pt 8pt 0 8pt; vertical-align: top; height: 57pt;">
                        <div style="height: 52pt;">
                            <div style="font-size: 5.5pt; font-weight: bold; color: {{ $themeColor }};">Not Negotiable</div>
                            <div style="font-size: 14pt; font-weight: bold; color: {{ $themeColor }}; margin: 2pt 0;">Air Waybill</div>
                            <div style="font-size: 5.5pt; font-weight: bold; color: {{ $themeColor }};">Issued By</div>
                        </div>
                    </td>
                </tr>
                <tr style="height: 13pt;">
                    <td style="border: none; border-top: 0.75px solid {{ $themeColor }}; padding: 1.5pt 6pt; vertical-align: middle; height: 13pt;">
                        <div style="font-size: 5.2pt; font-weight: bold; color: {{ $themeColor }};">
                            Copies 1, 2 and 3 of this Air Waybill are originals and have the same validity.
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr style="height: 66pt;">
        <td style="width: 49%; vertical-align: top;">
            <table style="width: 100%; border-collapse: collapse; height: 66pt;">
                <tr>
                    <td style="border: none; padding: 2pt 4pt; vertical-align: top; width: 51%;">
                        <span class="f-lbl">Consignee's Name and Address</span>
                        <div class="f-val" style="margin-top: 2pt;">
                            <strong>{{ $consigneeName }}</strong><br>
                            {!! nl2br(e($consigneeAddress)) !!}
                        </div>
                    </td>
                    <td style="border: none; width: 49%; padding: 0; vertical-align: top;">
                        <div style="border-left: 0.75px solid {{ $themeColor }}; border-bottom: 0.75px solid {{ $themeColor }}; background-color: {{ $tintBg }}; padding: 2pt 3pt; height: 26pt; text-align: center;">
                            <span class="f-lbl" style="text-align: center; font-weight: bold;">Consignee's Account Number</span>
                            <div class="f-val" style="font-weight: bold; margin-top: 2pt;">{{ $consigneeAcc }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
        <td style="width: 51%; vertical-align: top; padding: 3pt 6pt; height: 66pt;">
            <div style="font-size: 4.2pt; line-height: 1.15; text-align: justify; color: {{ $themeColor }};">
                It is agreed that goods described herein are accepted in apparent good order and condition (except as noted) for carriage SUBJECT TO THE CONDITIONS OF CONTRACT ON THE REVERSE HEREOF. ALL GOODS MAY BE CARRIED BY ANY OTHER MEANS INCLUDING ROAD OR ANY OTHER CARRIER UNLESS SPECIFIC CONTRARY INSTRUCTIONS ARE GIVEN HEREON BY THE SHIPPER, AND SHIPPER AGREES THAT THE SHIPMENT MAY BE CARRIED VIA INTERMEDIATE STOPPING PLACES WHICH THE CARRIER DEEMS APPROPRIATE. THE SHIPPER'SATTENTION IS DRAWN TO THE NOTICE CONCERNING CARRIER'S LIMITATION OF LIABILITY. Shipper may increase such limitation of liability by declaring a higher value for carriage and paying a supplemental charge if required.
            </div>
        </td>
    </tr>
</table>

<!-- BLOCK 2: Issuing Agent / Accounting Information, Agent IATA / Account No, & Airport of Departure -->
<table class="awb-block">
    <tr>
        <td colspan="2" style="width: 49%; vertical-align: top; padding: 2pt 4pt; height: 38pt;">
            <span class="f-lbl">Issuing Carrier's Agent Name and City</span>
            <div class="f-val" style="margin-top: 1pt;">
                {!! nl2br(e($issuingAgent)) !!}
            </div>
        </td>
        <td rowspan="3" style="width: 51%; vertical-align: top; padding: 2pt 6pt;">
            <span class="f-lbl">Accounting Information</span>
            <div class="f-val" style="margin-top: 2pt; line-height: 1.25;">
                {!! nl2br(e($accountingInfo)) !!}
            </div>
        </td>
    </tr>
    <tr style="height: 22pt;">
        <td style="width: 24.5%; vertical-align: top; padding: 1.5pt 4pt; height: 22pt;">
            <span class="f-lbl">Agent's IATA Code</span>
            <div class="f-val" style="font-weight: bold;">{{ $agentIata }}</div>
        </td>
        <td style="width: 24.5%; vertical-align: top; padding: 1.5pt 4pt; height: 22pt;">
            <span class="f-lbl">Account No.</span>
            <div class="f-val" style="font-weight: bold;">{{ $agentAcc }}</div>
        </td>
    </tr>
    <tr style="height: 22pt;">
        <td colspan="2" style="width: 49%; vertical-align: top; padding: 2pt 4pt; height: 22pt;">
            <span class="f-lbl">Airport of Departure (Addr. of First Carrier) and Requested Routing</span>
            <div class="f-val" style="font-size: 7pt; font-weight: bold; margin-top: 1pt;">{{ $departureAirport }}</div>
        </td>
    </tr>
</table>

<!-- BLOCK 4: Routing & Financial Declarations (12 columns) -->
<table class="awb-block">
    <tr style="height: 27pt;">
        <td style="width: 5.5%; height: 27pt;"></td>
        <td style="width: 30.5%; padding: 0; vertical-align: top; height: 27pt;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="border: none; width: 45%; padding: 1pt 3px; vertical-align: top;">
                        <span class="f-lbl" style="font-size: 4.8pt; line-height: 1;">By first Carrier</span>
                    </td>
                    <td style="border: none; width: 55%; padding: 0; vertical-align: top;">
                        {!! renderTrapTab('Routing and Destination', $themeColor, 88, 11, 4.5, 'right') !!}
                    </td>
                </tr>
            </table>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="border: none; width: 45%; padding: 0 3px; vertical-align: top;">
                        <div class="f-val" style="font-size: 6.2pt; font-weight: bold; line-height: 1.15;">{{ $firstCarrier }}</div>
                    </td>
                    <td style="border: none; width: 55%; padding: 0 2px; vertical-align: top; text-align: center;">
                        <div class="f-val" style="font-size: 6.2pt; font-weight: bold; line-height: 1.15;">{{ $routingDest }}</div>
                    </td>
                </tr>
            </table>
        </td>
        <td style="width: 3.25%; padding: 1pt 1px; vertical-align: top; text-align: center; height: 27pt;">
            <span class="f-lbl" style="font-size: 4.8pt;">to</span>
            <div class="f-val" style="font-size: 6.5pt; font-weight: bold;">{{ $to1 }}</div>
        </td>
        <td style="width: 3.25%; padding: 1pt 1px; vertical-align: top; text-align: center; height: 27pt;">
            <span class="f-lbl" style="font-size: 4.8pt;">by</span>
            <div class="f-val" style="font-size: 5.8pt; font-weight: bold; white-space: nowrap;">{{ $by1 }}</div>
        </td>
        <td style="width: 3.25%; padding: 1pt 1px; vertical-align: top; text-align: center; height: 27pt;">
            <span class="f-lbl" style="font-size: 4.8pt;">to</span>
            <div class="f-val" style="font-size: 6.5pt; font-weight: bold;">{{ $to2 }}</div>
        </td>
        <td style="width: 3.25%; padding: 1pt 1px; vertical-align: top; text-align: center; height: 27pt;">
            <span class="f-lbl" style="font-size: 4.8pt;">by</span>
            <div class="f-val" style="font-size: 5.8pt; font-weight: bold;">{{ $by2 }}</div>
        </td>
        <td style="width: 5.6%; padding: 1pt 1px; vertical-align: top; text-align: center; height: 27pt;">
            <span class="f-lbl" style="font-size: 4.8pt;">Currency</span>
            <div class="f-val" style="font-size: 6.5pt; font-weight: bold; margin-top: 1.5pt;">{{ $currency }}</div>
        </td>
        <td style="width: 4.1%; padding: 1pt 1px; vertical-align: top; text-align: center; height: 27pt;">
            <span class="f-lbl" style="font-size: 4.2pt; line-height: 1;">Chgs<br>Code</span>
            <div class="f-val" style="font-size: 6pt; font-weight: bold; margin-top: 1pt;">{{ $chgCode }}</div>
        </td>
        <td style="width: 6.7%; padding: 0; vertical-align: top; height: 27pt;">
            {!! renderSplitHeader('WT VAL', $themeColor, $wtValPpd, $wtValColl, 39, 35) !!}
        </td>
        <td style="width: 6.7%; padding: 0; vertical-align: top; height: 27pt;">
            {!! renderSplitHeader('Other', $themeColor, $otherPpd, $otherColl, 39, 35) !!}
        </td>
        <td style="width: 14.0%; padding: 1pt 2px; vertical-align: top; text-align: center; height: 27pt;">
            <span class="f-lbl" style="font-size: 4.5pt; line-height: 1;">Declared Value for Carriage</span>
            <div class="f-val" style="font-size: 6.5pt; font-weight: bold; margin-top: 1.5pt;">{{ $valCarriage }}</div>
        </td>
        <td style="width: 13.9%; padding: 1pt 2px; vertical-align: top; text-align: center; height: 27pt;">
            <span class="f-lbl" style="font-size: 4.5pt; line-height: 1;">Declared Value for Customs</span>
            <div class="f-val" style="font-size: 6.5pt; font-weight: bold; margin-top: 1.5pt;">{{ $valCustoms }}</div>
        </td>
    </tr>
</table>

<!-- BLOCK 5: Destination Airport & Insurance (4 columns with combined Flight/Date) -->
<table class="awb-block">
    <tr style="height: 27pt;">
        <td style="width: 25.5%; padding: 1pt 4px; vertical-align: top; height: 27pt;">
            <span class="f-lbl">Airport of Destination</span>
            <div class="f-val" style="font-size: 6.8pt; font-weight: bold;">{{ $destAirport }}</div>
        </td>
        <td style="width: 23.5%; padding: 0; vertical-align: top; background-color: {{ $tintBg }}; height: 27pt;">
            <table style="width: 100%; border-collapse: collapse; height: 27pt;">
                <tr style="height: 11pt;">
                    <td style="border: none; width: 28%; text-align: center; vertical-align: middle; padding: 0;">
                        <span style="font-size: 4.8pt; font-weight: bold; color: {{ $themeColor }};">Flight/ Date</span>
                    </td>
                    <td style="border: none; width: 44%; text-align: center; vertical-align: top; padding: 0;">
                        {!! renderTrapTab('For Carrier Use Only', $themeColor, 66, 11, 4.5, 'center', 'both', '', '#ffffff') !!}
                    </td>
                    <td style="border: none; width: 28%; text-align: center; vertical-align: middle; padding: 0;">
                        <span style="font-size: 4.8pt; font-weight: bold; color: {{ $themeColor }};">Flight/ Date</span>
                    </td>
                </tr>
                <tr style="height: 21pt;">
                    <td colspan="3" style="border: none; padding: 0;">
                        <table style="width: 100%; border-collapse: collapse; height: 21pt;">
                            <tr>
                                <td style="border: none; border-right: 0.75px solid {{ $themeColor }}; width: 50%; text-align: center; vertical-align: middle; padding: 0 1px; height: 21pt;">
                                    <div class="f-val" style="font-size: 6.5pt; font-weight: bold;">{{ $flightNo }} {{ $flightDate }}</div>
                                </td>
                                <td style="border: none; width: 50%; text-align: center; vertical-align: middle; padding: 0 1px; height: 21pt;">
                                    <div class="f-val" style="font-size: 6.2pt; font-weight: bold;">{{ $connectingFlight }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
        <td style="width: 15.3%; padding: 1pt 4px; vertical-align: top; height: 27pt;">
            <span class="f-lbl">Amount of Insurance</span>
            <div class="f-val" style="font-size: 6.5pt; text-align: center; font-weight: bold; margin-top: 1pt;">{{ $amountInsurance }}</div>
        </td>
        <td style="width: 35.7%; padding: 1pt 4px; vertical-align: middle; height: 27pt;">
            <div style="font-size: 4.2pt; line-height: 1.1; color: {{ $themeColor }}; text-align: justify;">
                INSURANCE - If Carrier offers Insurance and such insurance is requested in accordance with conditions on reserve hereof indicate amount to be insured in figures in box marked "Amount of Insurance."
            </div>
        </td>
    </tr>
</table>

<!-- BLOCK 6: Handling Information -->
<table class="awb-block">
    <tr style="height: 30pt;">
        <td style="width: 100%; vertical-align: top; padding: 2pt 5pt; height: 30pt;">
            <span class="f-lbl" style="font-weight: bold;">Handling Information</span>
            <div class="f-val" style="font-size: 6.8pt; margin-top: 1.5pt; font-weight: 600;">
                {!! nl2br(e($handlingInfo)) !!}
            </div>
        </td>
    </tr>
</table>

<!-- BLOCK 7: Cargo Grid (13 columns: 8 data + 5 shaded separators) -->
<table class="awb-block">
    <thead>
        <tr style="height: 24pt;">
            <th style="width: 5.5%; font-size: 5.2pt; font-weight: bold; color: {{ $themeColor }}; text-align: center; padding: 1pt; vertical-align: middle;">
                No.of<br>Pieces<br>RCP
            </th>
            <th style="width: 9.0%; font-size: 5.5pt; font-weight: bold; color: {{ $themeColor }}; text-align: center; padding: 1pt; vertical-align: middle;">
                Gross<br>Weight
            </th>
            <th style="width: 2.5%; font-size: 5pt; font-weight: bold; color: {{ $themeColor }}; text-align: center; padding: 1pt; vertical-align: middle;">
                kg<br>lb
            </th>
            <th style="width: 1.4%; background-color: {{ $tintBg }}; padding: 0;"></th>
            <th style="width: 9.0%; font-size: 5.2pt; font-weight: bold; color: {{ $themeColor }}; text-align: center; padding: 0; vertical-align: middle;">
                <div style="font-size: 5.2pt;">Rate Class</div>
                <div style="border-top: 0.75px solid {{ $themeColor }}; font-size: 4.5pt; margin-top: 1pt; padding-top: 1pt;">Commodity Item No.</div>
            </th>
            <th style="width: 1.4%; background-color: {{ $tintBg }}; padding: 0;"></th>
            <th style="width: 10.5%; font-size: 5.5pt; font-weight: bold; color: {{ $themeColor }}; text-align: center; padding: 1pt; vertical-align: middle;">
                Chargeable<br>Weight
            </th>
            <th style="width: 1.4%; background-color: {{ $tintBg }}; padding: 0;"></th>
            <th style="width: 10.5%; padding: 0; vertical-align: middle;">
                {!! renderRateChargeHeader($themeColor, 65, 23) !!}
            </th>
            <th style="width: 1.4%; background-color: {{ $tintBg }}; padding: 0;"></th>
            <th style="width: 16.0%; font-size: 5.8pt; font-weight: bold; color: {{ $themeColor }}; text-align: center; padding: 1pt; vertical-align: middle;">
                Total
            </th>
            <th style="width: 1.4%; background-color: {{ $tintBg }}; padding: 0;"></th>
            <th style="width: 30.0%; font-size: 5.5pt; font-weight: bold; color: {{ $themeColor }}; text-align: center; padding: 1pt; vertical-align: middle;">
                Nature and Quantity of Goods<br>(Incl. Dimensions or Volume)
            </th>
        </tr>
    </thead>
    <tbody>
        <tr style="height: 125pt;">
            <td style="text-align: center; vertical-align: top; padding: 4pt 1px; font-size: 7.2pt; font-weight: bold; height: 125pt;">
                <div style="height: 125pt;">{{ $pieces }}</div>
            </td>
            <td style="text-align: right; vertical-align: top; padding: 4pt 3px; font-size: 7.2pt; font-weight: bold; height: 125pt;">
                <div style="height: 125pt;">{{ $gwFormatted }}</div>
            </td>
            <td rowspan="2" style="text-align: center; vertical-align: top; padding: 4pt 1px; font-size: 7pt; font-weight: bold;">
                <div>K</div>
            </td>
            <td rowspan="2" style="background-color: {{ $tintBg }}; padding: 0;">
                <div></div>
            </td>
            <td rowspan="2" style="text-align: center; vertical-align: top; padding: 4pt 1px; font-size: 7pt; font-weight: bold;">
                <div>{{ $rateClass }}</div>
            </td>
            <td rowspan="2" style="background-color: {{ $tintBg }}; padding: 0;">
                <div></div>
            </td>
            <td rowspan="2" style="text-align: right; vertical-align: top; padding: 4pt 3px; font-size: 7.2pt; font-weight: bold;">
                <div>{{ $cwFormatted }}</div>
            </td>
            <td rowspan="2" style="background-color: {{ $tintBg }}; padding: 0;">
                <div></div>
            </td>
            <td rowspan="2" style="text-align: center; vertical-align: top; padding: 4pt 1px; font-size: 7pt; font-weight: bold;">
                <div>{{ $rateCharge }}</div>
            </td>
            <td rowspan="2" style="background-color: {{ $tintBg }}; padding: 0;">
                <div></div>
            </td>
            <td style="text-align: right; vertical-align: top; padding: 4pt 3px; font-size: 7.2pt; font-weight: bold; height: 125pt;">
                <div style="height: 125pt;">{{ $totalCharge }}</div>
            </td>
            <td rowspan="2" style="background-color: {{ $tintBg }}; padding: 0;">
                <div></div>
            </td>
            <td rowspan="2" style="text-align: left; vertical-align: top; padding: 4pt 5px; font-size: 7pt; font-weight: bold; line-height: 1.3;">
                <div>{!! nl2br(e($natureGoods)) !!}</div>
            </td>
        </tr>
        <tr style="height: 13pt;">
            <td style="text-align: center; vertical-align: middle; font-size: 7pt; font-weight: bold; height: 13pt;">{{ $pieces }}</td>
            <td style="text-align: right; vertical-align: middle; font-size: 7pt; font-weight: bold; padding-right: 3px; height: 13pt;">{{ $gwFormatted }}</td>
            <td style="text-align: right; vertical-align: middle; font-size: 7pt; font-weight: bold; padding-right: 3px; height: 13pt;">{{ $totalCharge }}</td>
        </tr>
    </tbody>
</table>

<!-- BLOCK 8A: Financial Upper (Prepaid/Tax/Due Agent vs Other Charges & Shipper Cert) -->
<table class="awb-block">
    {{-- Row 1: Prepaid / Weight Charge / Collect vs Other Charges --}}
    <tr style="height: 22pt;">
        <td style="width: 37.9%; vertical-align: top; padding: 0; height: 22pt;">
            <table style="width: 100%; border-collapse: collapse; height: 22pt;">
                <tr>
                    <td style="border: none; width: 28%; vertical-align: top; padding: 0; text-align: left;">
                        {!! renderTrapTab('Prepaid', $themeColor, 54, 11, 4.5, 'left') !!}
                        <div class="f-val" style="font-weight: bold; text-align: center; padding-top: 1.5pt; font-size: 6.5pt;">{{ $isPrepaid ? $totalCharge : '' }}</div>
                    </td>
                    <td style="border: none; width: 44%; vertical-align: top; padding: 0; text-align: center;">
                        {!! renderTrapTab('Weight Charge', $themeColor, 72, 11, 4.5, 'center', 'both', '', '#ffffff', 18) !!}
                    </td>
                    <td style="border: none; width: 28%; vertical-align: top; padding: 0; text-align: right;">
                        {!! renderTrapTab('Collect', $themeColor, 54, 11, 4.5, 'right') !!}
                        <div class="f-val" style="font-weight: bold; text-align: center; padding-top: 1.5pt; font-size: 6.5pt;">{{ !$isPrepaid ? $totalCharge : '' }}</div>
                    </td>
                </tr>
            </table>
        </td>
        <td style="width: 62.1%; vertical-align: top; padding: 2pt 5pt; height: 22pt;">
            <span class="f-lbl">Other Charges</span>
        </td>
    </tr>

    {{-- Row 2: Valuation Charge vs Empty Cell --}}
    <tr style="height: 15pt;">
        <td style="width: 37.9%; vertical-align: top; padding: 0; text-align: center; height: 15pt;">
            {!! renderTrapTab('Valuation Charge', $themeColor, 94, 11, 4.5, 'center', 'both', '', '#ffffff', 9) !!}
        </td>
        <td style="width: 62.1%; height: 15pt;"></td>
    </tr>

    {{-- Row 3: Tax vs Empty Cell --}}
    <tr style="height: 16pt;">
        <td style="width: 37.9%; vertical-align: top; padding: 0; text-align: center; height: 16pt;">
            {!! renderTaxCell($themeColor, 54, 21, 11, 4.5) !!}
        </td>
        <td style="width: 62.1%; height: 16pt;"></td>
    </tr>

    {{-- Row 4: Due Agent / Due Carrier / Solid Block vs Shipper Cert --}}
    <tr style="height: 50pt;">
        <td style="width: 37.9%; vertical-align: top; padding: 0; height: 50pt;">
            <table style="width: 100%; border-collapse: collapse; height: 50pt;">
                <tr style="height: 15pt;">
                    <td style="border: none; border-bottom: 0.75px solid {{ $themeColor }}; vertical-align: top; padding: 0; text-align: center;">
                        {!! renderTrapTab('Total Other Charges Due Agent', $themeColor, 150, 11, 4.5, 'center', 'both', '', '#ffffff', 9) !!}
                    </td>
                </tr>
                <tr style="height: 15pt;">
                    <td style="border: none; border-bottom: 0.75px solid {{ $themeColor }}; vertical-align: top; padding: 0; text-align: center;">
                        {!! renderTrapTab('Total Other Charges Due Carrier', $themeColor, 150, 11, 4.5, 'center', 'both', '', '#ffffff', 9) !!}
                    </td>
                </tr>
                <tr style="height: 20pt;">
                    <td style="border: none; background-color: {{ $solidBlockColor }}; height: 20pt;"></td>
                </tr>
            </table>
        </td>
        <td style="width: 62.1%; vertical-align: top; padding: 0; height: 50pt;">
            <div style="padding: 2.5pt 5pt 0 5pt; font-size: 4.8pt; font-weight: bold; line-height: 1.25; color: {{ $themeColor }}; text-align: justify;">
                Shipper certifies that the particulars on the face hereof are correct and that INSOFAR AS ANY PART OF THE CONSIGNMENT CONTAINS DANGEROUS GOODS, SUCH PART IS PROPERLY DESCRIBED BY NAME AND IS IN PROPER CONDITION FOR CARRIAGE BY AIR ACCORDING TO THE APPLICABLE DANGEROUS GOODS REGULATIOS.
            </div>
            <div style="margin-top: 12pt; padding: 0 5pt 2pt 5pt;">
                <div style="border-top: 0.75px dotted {{ $themeColor }}; text-align: center; padding-top: 1.5pt; font-size: 5.5pt; font-weight: bold; color: {{ $themeColor }};">
                    Signature of Shipper or his Agent.
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- BLOCK 8B: Financial Bottom (Total Prepaid/Collect, Currency Conversion, Carrier Dest) -->
<table class="awb-block">
    <tr style="height: 22pt;">
        <td style="width: 18.95%; text-align: center; vertical-align: top; padding: 0; height: 22pt;">
            {!! renderTrapTab('Total Prepaid', $themeColor, 76, 11, 4.5, 'center') !!}
            <div class="f-val" style="font-weight: bold; text-align: center; padding-top: 1.5pt; font-size: 6.5pt;">{{ $isPrepaid ? $totalCharge : '' }}</div>
        </td>
        <td style="width: 18.95%; text-align: center; vertical-align: top; padding: 0; height: 22pt;">
            {!! renderTrapTab('Total Collect', $themeColor, 76, 11, 4.5, 'center') !!}
            <div class="f-val" style="font-weight: bold; text-align: center; padding-top: 1.5pt; font-size: 6.5pt;">{{ !$isPrepaid ? $totalCharge : '' }}</div>
        </td>
        <td colspan="2" style="width: 62.1%; vertical-align: top; padding: 3pt 8pt 0 8pt; text-align: right; height: 22pt;">
            <span style="font-size: 6.8pt; font-weight: bold; color: #000;">PT. RADIX INTERNATIONAL LOGISTICS</span>
        </td>
    </tr>
    <tr style="height: 18pt;">
        <td style="width: 18.95%; text-align: center; vertical-align: top; padding: 0; height: 18pt; background-color: {{ $tintBg }};">
            {!! renderTrapTab('Currency Conversion Rates', $themeColor, 102, 11, 4.5, 'center') !!}
        </td>
        <td style="width: 18.95%; text-align: center; vertical-align: top; padding: 0; height: 18pt; background-color: {{ $tintBg }};">
            {!! renderTrapTab('cc Charges in Dest. Currency', $themeColor, 102, 11, 4.5, 'center') !!}
        </td>
        <td colspan="2" style="width: 62.1%; vertical-align: bottom; padding: 0 4pt 2pt 4pt; height: 18pt;">
            <div style="border-top: 0.75px dotted {{ $themeColor }}; padding-top: 2pt;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="border: none; width: 33%; font-size: 5.2pt; color: {{ $themeColor }}; font-weight: bold; padding: 0;">
                            Executed on &nbsp;<span style="color: #000; font-size: 6.5pt;">{{ $execDate }}</span>&nbsp; (Date)
                        </td>
                        <td style="border: none; width: 27%; font-size: 5.2pt; color: {{ $themeColor }}; font-weight: bold; padding: 0;">
                            at &nbsp;<span style="color: #000; font-size: 6.5pt;">{{ $execPlace }}</span>&nbsp; (Place)
                        </td>
                        <td style="border: none; width: 40%; font-size: 5.2pt; color: {{ $themeColor }}; font-weight: bold; text-align: right; padding: 0;">
                            Signature of Issuing Carrier or its Agent.
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
    <tr style="height: 20pt;">
        <td style="width: 18.95%; vertical-align: middle; text-align: center; padding: 1pt; background-color: {{ $tintBg }}; height: 20pt;">
            <span style="font-size: 4.8pt; font-weight: bold; color: {{ $themeColor }}; line-height: 1.15; display: block;">For Carrier's Use Only<br>at Destination</span>
        </td>
        <td style="width: 18.95%; text-align: center; vertical-align: top; padding: 0; background-color: {{ $tintBg }}; height: 20pt;">
            {!! renderTrapTab('Charges at Destination', $themeColor, 94, 11, 4.5, 'center', 'both', '', '#ffffff') !!}
        </td>
        <td style="width: 23.6%; text-align: center; vertical-align: top; padding: 0; background-color: {{ $tintBg }}; height: 20pt;">
            {!! renderTrapTab('Total Collect Charges', $themeColor, 94, 11, 4.5, 'center', 'both', '', '#ffffff') !!}
        </td>
        <td style="width: 38.5%; height: 20pt;"></td>
    </tr>
</table>

{{-- FOOTER IDENTITAS BESAR TERPUSAT PERSIS HAWB.DOCX --}}
<div class="footer-caption">
    {{ $footerText }}
</div>

</body>
</html>
