<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shipping Instruction {{ $si->number }}</title>
    <style>
        @page {
            margin: 20px 28px 20px 28px;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #000;
            font-size: 9px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }

        /* HEADER KOP */
        .top-header {
            width: 100%;
            margin-bottom: 8px;
            padding-bottom: 4px;
        }
        .top-header-left {
            float: left;
            width: 50%;
        }
        .top-header-left img {
            max-width: 170px;
            max-height: 48px;
        }
        .top-header-right {
            float: right;
            width: 48%;
            text-align: right;
            font-size: 8px;
            color: #444;
            padding-top: 6px;
        }
        .clear {
            clear: both;
        }

        /* MAIN B/L TABLE */
        table.si-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            margin-bottom: 6px;
        }
        table.si-table td, table.si-table th {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: top;
        }
        .cell-title {
            font-weight: 700;
            font-size: 9.5px;
            background-color: #f1f1f1;
            padding: 2px 5px;
            border-bottom: 1px solid #000;
            margin: -4px -6px 4px -6px;
        }
        .cell-content {
            font-size: 8.5px;
            line-height: 1.35;
        }

        /* TO CARRIER BOX */
        .si-title {
            text-align: center;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 1px;
        }
        .si-number {
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        table.carrier-meta {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            margin-bottom: 4px;
        }
        table.carrier-meta td {
            border: none;
            padding: 1.5px 0;
        }
        .si-notice {
            font-size: 8px;
            font-style: italic;
            padding-top: 3px;
            border-top: 0.5px dashed #666;
            margin-top: 3px;
        }

        /* CARGO HEADERS */
        th.cargo-th {
            background-color: #e5e5e5;
            font-size: 9px;
            font-weight: 700;
            text-align: left;
            padding: 4px 6px;
        }

        /* FOOTER */
        .page-footer {
            position: fixed;
            bottom: -5px;
            left: 0;
            right: 0;
            text-align: right;
            font-size: 8px;
            color: #555;
        }
    </style>
</head>
<body>

{{-- TOP LOGO HEADER --}}
<div class="top-header">
    <div class="top-header-left">
        <img src="{{ public_path('images/logo.png') }}" alt="RDX Logistics">
    </div>
    <div class="top-header-right">
        <strong>PT. RADIX INTERNATIONAL LOGISTICS</strong><br>
        International Freight Forwarder & Customs Brokerage
    </div>
    <div class="clear"></div>
</div>

{{-- MAIN B/L TABLE --}}
<table class="si-table">
    {{-- ROW 1: SHIPPER vs TO CARRIER (ROWSPAN 3) --}}
    <tr>
        <td style="width: 50%; height: 60px;">
            <div class="cell-title">SHIPPER</div>
            <div class="cell-content">
                <strong>{{ $si->shipper_name }}</strong><br>
                {!! nl2br(e($si->shipper_address)) !!}
            </div>
        </td>
        <td rowspan="3" style="width: 50%;">
            <div class="si-title">SHIPPING INSTRUCTION</div>
            <div class="si-number">{{ $si->number }}</div>
            <table class="carrier-meta">
                <tr>
                    <td style="width: 22%; font-weight: 700;">To</td>
                    <td style="width: 3%;">:</td>
                    <td style="width: 75%; font-weight: 700;">{{ $si->to_carrier }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Attn</td>
                    <td>:</td>
                    <td>{{ $si->carrier_attn ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Telp/Fax</td>
                    <td>:</td>
                    <td>{{ $si->carrier_contact ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Date</td>
                    <td>:</td>
                    <td>{{ $si->si_date->format('d/m/Y') }}</td>
                </tr>
            </table>
            <div class="si-notice">
                Please kindly arrange space for our booking as according to below mention
            </div>
        </td>
    </tr>

    {{-- ROW 2: CONSIGNEE --}}
    <tr>
        <td style="height: 60px;">
            <div class="cell-title">CONSIGNEE</div>
            <div class="cell-content">
                <strong>{{ $si->consignee_name }}</strong><br>
                {!! nl2br(e($si->consignee_address)) !!}
            </div>
        </td>
    </tr>

    {{-- ROW 3: NOTIFY PARTY --}}
    <tr>
        <td style="height: 50px;">
            <div class="cell-title">NOTIFY PARTY</div>
            <div class="cell-content">
                {{ $si->notify_party ?: 'SAME AS CONSIGNEE' }}
            </div>
        </td>
    </tr>

    {{-- ROW 4: VESSEL & ETD/ETA vs SHIPMENT TERM --}}
    <tr>
        <td style="padding: 4px 6px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="border: none; padding: 1px 0; width: 30%; font-weight: 700;">Vessel Name</td>
                    <td style="border: none; padding: 1px 0; width: 3%;">:</td>
                    <td style="border: none; padding: 1px 0; width: 67%; font-weight: 700;">{{ $si->vessel_voyage ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 1px 0; font-weight: 700;">ETD</td>
                    <td style="border: none; padding: 1px 0;">:</td>
                    <td style="border: none; padding: 1px 0;">{{ $si->etd?->format('d/m/Y') ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 1px 0; font-weight: 700;">ETA</td>
                    <td style="border: none; padding: 1px 0;">:</td>
                    <td style="border: none; padding: 1px 0;">{{ $si->eta?->format('d/m/Y') ?: '—' }}</td>
                </tr>
            </table>
        </td>
        <td style="vertical-align: middle; padding: 6px;">
            <strong>Shipment Term :</strong>
            <span style="font-weight: 700; font-size: 10px; margin-left: 6px;">
                {{ $si->shipment_term }}
            </span>
        </td>
    </tr>

    {{-- ROW 5: CONNECTING VESSEL vs LOADING & DISCHARGE --}}
    <tr>
        <td style="vertical-align: middle;">
            <strong>Connecting Vessel :</strong> {{ $si->connecting_vessel ?: '—' }}
        </td>
        <td style="padding: 0;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="border: none; border-bottom: 1px solid #000; border-right: 1px solid #000; width: 30%; font-weight: 700; background: #f8fafc; padding: 3px 6px;">
                        LOADING
                    </td>
                    <td style="border: none; border-bottom: 1px solid #000; font-weight: 700; padding: 3px 6px;">
                        {{ $si->pol }}
                    </td>
                </tr>
                <tr>
                    <td style="border: none; border-right: 1px solid #000; font-weight: 700; background: #f8fafc; padding: 3px 6px;">
                        DISCHARGE
                    </td>
                    <td style="border: none; font-weight: 700; padding: 3px 6px;">
                        {{ $si->pod }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- ROW 6: CARGO HEADERS --}}
    <tr>
        <th class="cargo-th" style="width: 30%;">MARKS AND NUMBER</th>
        <th class="cargo-th" style="width: 44%;">DESCRIPTION</th>
        <th class="cargo-th" style="width: 26%;">GW/MEASUREMENT</th>
    </tr>

    {{-- ROW 7: CARGO CONTENTS --}}
    <tr>
        <td style="height: 180px; vertical-align: top; font-size: 8.5px;">
            {!! nl2br(e($si->marks_numbers ?: "N/M\n(NO MARKS)")) !!}
        </td>
        <td style="height: 180px; vertical-align: top; font-size: 8.5px;">
            {!! nl2br(e($si->cargo_description)) !!}
        </td>
        <td style="height: 180px; vertical-align: top; font-size: 8.5px; line-height: 1.6;">
            <strong>G.W :</strong> {{ $si->gross_weight ? \App\Support\Money::format($si->gross_weight) . ' KGS' : '—' }}<br>
            <strong>N.W :</strong> {{ $si->net_weight ? \App\Support\Money::format($si->net_weight) . ' KGS' : '—' }}<br>
            <strong>MEAS :</strong> {{ $si->measurement ? \App\Support\Money::format($si->measurement) . ' CBM' : '—' }}
        </td>
    </tr>

    {{-- ROW 8: REMARKS --}}
    <tr>
        <td colspan="3" style="padding: 0;">
            <div class="cell-title">REMARKS</div>
            <div class="cell-content" style="padding: 4px 6px; min-height: 45px;">
                {!! nl2br(e($si->remarks ?: '—')) !!}
            </div>
        </td>
    </tr>
</table>

<div class="page-footer">Page 1/1</div>

</body>
</html>
