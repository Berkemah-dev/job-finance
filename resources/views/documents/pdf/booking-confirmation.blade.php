<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Booking Confirmation {{ $bc->number }}</title>
    <style>
        @page {
            margin: 30pt 28pt 18pt 22pt;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            color: #000000;
            font-size: 8pt;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }

        /* LOGO */
        .logo-box {
            margin-bottom: 22pt;
        }
        .logo-img {
            width: 208.5pt;
            height: 29.5pt;
            display: block;
        }

        /* CENTERED TITLE */
        .title-box {
            text-align: center;
            margin-bottom: 16pt;
        }
        .title-main {
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 0.2px;
        }
        .title-no {
            font-size: 8pt;
            margin-top: 2pt;
        }

        /* DIVIDER */
        .hr-line {
            border: none;
            border-top: 1px solid #000000;
            margin: 4pt 0 4pt 0;
        }

        /* TO & METADATA TABLE */
        table.top-meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-bottom: 2pt;
        }
        table.top-meta-table td {
            vertical-align: top;
            padding: 0;
        }

        /* SALUTATION */
        .salutation-box {
            margin-top: 8pt;
            margin-bottom: 5pt;
            font-size: 8pt;
            line-height: 1.3;
        }

        /* 2-COLUMN SHIPMENT DETAILS */
        table.shipment-details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin: 1pt 0;
        }
        table.shipment-details-table td {
            vertical-align: top;
            padding: 0.5pt 0;
        }

        /* 4-COLUMN CARGO TABLE */
        table.cargo-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin: 1pt 0;
        }
        table.cargo-grid th {
            text-align: left;
            font-weight: bold;
            padding: 0 4pt 1pt 0;
        }
        table.cargo-grid td {
            vertical-align: top;
            padding: 0 4pt 1pt 0;
        }

        /* PACKAGING */
        .seaworthy-box {
            font-size: 8pt;
            margin: 4pt 0 4pt 0;
        }

        /* DELIVERY & CUT-OFF COMBINED TABLE */
        table.delivery-cutoff-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin: 1pt 0;
        }
        table.delivery-cutoff-table td {
            vertical-align: top;
            padding: 0;
        }

        /* IMPORTANT NOTE */
        .note-box {
            margin-top: 16pt;
        }
        .note-title {
            font-size: 8pt;
            margin-bottom: 3pt;
        }
        .note-content {
            font-size: 8pt;
            line-height: 1.25;
            margin-bottom: 4pt;
        }
        .note-item {
            margin-bottom: 2.5pt;
            padding-left: 12pt;
            text-indent: -12pt;
        }

        /* DISCLAIMER */
        .disclaimer-center {
            text-align: center;
            font-size: 8pt;
            line-height: 1.35;
            margin-top: 26pt;
            margin-bottom: 8pt;
        }
        .disclaimer-center div {
            font-weight: bold;
        }
        .thank-you {
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            margin-top: 12pt;
        }
    </style>
</head>
<body>

@php
    $consigneeName = $bc->consignee_name ?: ($job?->consignee_name ?: ($bc->customer?->consignees?->first()?->name ?? '—'));
    $consigneeAddress = $bc->consignee_address ?: ($job?->consignee_address ?: ($bc->customer?->consignees?->first()?->address ?? ''));
    $consigneeContact = $bc->contact_person ?: ($bc->consignee_contact ?: ($job?->consignee_contact ?: ($bc->customer?->consignees?->first()?->contact_name ?: ($bc->customer?->consignees?->first()?->phone ?? '—'))));

    $shipperCustomer = $bc->customer ?? $job?->customer;
    $shipperName = $shipperCustomer?->name ?? ($bc->shipper_name ?: '—');
    $shipperAddress = $shipperCustomer?->address ?? ($job?->shipper_address ?? '');

    $carrierStr = $bc->carrier_name ?: '—';
    if ($bc->carrier_booking_no) {
        $carrierStr .= ' (' . $bc->carrier_booking_no . ')';
    }

    $logoPath = file_exists(public_path('images/rdx-logistics-doc-logo.png'))
        ? public_path('images/rdx-logistics-doc-logo.png')
        : public_path('images/logo.png');

    // Parse clauses
    $rawNotes = $bc->notes;
    $bulletClauses = [];
    if ($rawNotes) {
        $lines = explode("\n", str_replace("\r", "", $rawNotes));
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') continue;
            if (preg_match('/^[0-9]+\.\s*(.*)$/', $trimmed, $m)) {
                $bulletClauses[] = $m[1];
            } elseif (str_starts_with($trimmed, '•')) {
                $bulletClauses[] = trim(ltrim($trimmed, '• '));
            } elseif (str_starts_with($trimmed, '-')) {
                $bulletClauses[] = trim(ltrim($trimmed, '- '));
            } else {
                $bulletClauses[] = $trimmed;
            }
        }
    }
@endphp

{{-- LOGO (TOP LEFT) --}}
<div class="logo-box">
    <img class="logo-img" src="{{ $logoPath }}" alt="RDX LOGISTICS">
</div>

{{-- CENTERED TITLE & NUMBER --}}
<div class="title-box">
    <div class="title-main">BOOKING CONFIRMATION</div>
    <div class="title-no">NO.: {{ $bc->number }}</div>
</div>

{{-- TO & METADATA TABLE --}}
<table class="top-meta-table">
    <tr>
        <td style="width: 58%;">
            <div style="font-weight: bold;">To:</div>
            <div>{{ $consigneeName }}</div>
            @if($consigneeAddress)
                <div>{{ $consigneeAddress }}</div>
            @endif
        </td>
        <td style="width: 42%;">
            <table style="width: 100%; border-collapse: collapse; font-size: inherit;">
                <tr>
                    <td style="width: 44%; font-weight: bold;">Date</td>
                    <td style="width: 4%;">:</td>
                    <td style="width: 52%;">{{ $bc->booking_date ? $bc->booking_date->format('d-M-Y') : '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Contact Person</td>
                    <td>:</td>
                    <td>{{ $consigneeContact }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Job No.</td>
                    <td>:</td>
                    <td>{{ $bc->job?->number ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Customer Ref</td>
                    <td>:</td>
                    <td>{{ $bc->customer_ref ?: '—' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- SALUTATION --}}
<div class="salutation-box">
    We thank you for your booking.<br>
    Please review the following details and advise if any discrepancy:
</div>

<hr class="hr-line">

{{-- 2-COLUMN SHIPMENT DETAILS --}}
<table class="shipment-details-table">
    <tr>
        <td style="width: 58%;">
            <table style="width: 100%; border-collapse: collapse; font-size: inherit;">
                <tr>
                    <td style="width: 32%; font-weight: bold;">Shipper</td>
                    <td style="width: 3%;">:</td>
                    <td style="width: 65%;">
                        {{ $shipperName }}
                        @if($shipperAddress)
                            <div>{{ $shipperAddress }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Carrier Booking</td>
                    <td>:</td>
                    <td>{{ $carrierStr }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Vessel</td>
                    <td>:</td>
                    <td>{{ $bc->vessel_voyage ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Services</td>
                    <td>:</td>
                    <td>{{ $bc->service_term ?: 'CY/CY' }}</td>
                </tr>
            </table>
        </td>
        <td style="width: 42%;">
            <table style="width: 100%; border-collapse: collapse; font-size: inherit;">
                <tr>
                    <td style="width: 25%; font-weight: bold;">POL</td>
                    <td style="width: 4%;">:</td>
                    <td style="width: 71%;">{{ $bc->pol ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">POD</td>
                    <td>:</td>
                    <td>{{ $bc->pod ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">ETD</td>
                    <td>:</td>
                    <td>{{ $bc->etd ? $bc->etd->format('d-M-Y') : '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">ETA</td>
                    <td>:</td>
                    <td>{{ $bc->eta ? $bc->eta->format('d-M-Y') : '—' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<hr class="hr-line">

{{-- 4-COLUMN CARGO DETAILS --}}
<table class="cargo-grid">
    <thead>
        <tr>
            <th style="width: 22%;">Quantity:</th>
            <th style="width: 36%;">Description:</th>
            <th style="width: 24%;">Gross Weight:</th>
            <th style="width: 18%;">CBM:</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $bc->quantity ?: '—' }}</td>
            <td style="white-space: pre-wrap;">{{ $bc->cargo_description ?: 'General Cargo' }}</td>
            <td>{{ $bc->gross_weight ? \App\Support\Money::format($bc->gross_weight) . ' KGS' : '—' }}</td>
            <td>{{ $bc->volume ? \App\Support\Money::format($bc->volume) . ' M3' : '—' }}</td>
        </tr>
    </tbody>
</table>

<hr class="hr-line">

{{-- PACKAGING NOTICE --}}
<div class="seaworthy-box">
    PLEASE MAKE SURE TO USE SEAWORTHY PACKAGING.
</div>

{{-- DELIVERY CARGO TO & CUT-OFF (HORIZONTALLY ALIGNED ON SAME ROW) --}}
<table class="delivery-cutoff-table">
    <tr>
        <td style="width: 44%; padding-right: 12pt;">
            <div style="font-weight: bold;">Delivery cargo to:</div>
            <div style="margin-top: 1pt;">{{ $bc->delivery_cargo_to ?: '—' }}</div>
        </td>
        <td style="width: 56%;">
            <table style="width: 100%; border-collapse: collapse; font-size: inherit;">
                <tr>
                    <td style="width: 36%; font-weight: bold; padding: 0 4pt 1pt 0;">Doc Cut-Off</td>
                    <td style="width: 36%; font-weight: bold; padding: 0 4pt 1pt 0;">CY Cut-Off</td>
                    <td style="width: 28%; font-weight: bold; padding: 0 0 1pt 0;">Delivery</td>
                </tr>
                <tr>
                    <td style="padding: 0 4pt 0 0;">{{ $bc->doc_cutoff_at ? $bc->doc_cutoff_at->format('d-M-Y H:i') : '—' }}</td>
                    <td style="padding: 0 4pt 0 0;">{{ $bc->cy_cutoff_at ? $bc->cy_cutoff_at->format('d-M-Y H:i') : '—' }}</td>
                    <td style="padding: 0;">{{ $bc->delivery_cutoff_at ? $bc->delivery_cutoff_at->format('d-M-Y H:i') : '—' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- IMPORTANT NOTE (NO HORIZONTAL LINE ABOVE) --}}
<div class="note-box">
    <div class="note-title">Important Note:</div>
    <div class="note-content">
        @if(!empty($bulletClauses))
            @foreach($bulletClauses as $clause)
                @if(str_contains($clause, 'LONG LENGTH/OVERWEIGHT'))
                    @php
                        $splitPos = strpos($clause, 'LONG LENGTH');
                        $before = trim(substr($clause, 0, $splitPos));
                        $after = trim(substr($clause, $splitPos));
                    @endphp
                    <div class="note-item">• {{ $before }}<br><span style="padding-left: 12pt; display: inline-block;">{{ $after }}</span></div>
                @else
                    <div class="note-item">• {{ $clause }}</div>
                @endif
            @endforeach
        @else
            <div>—</div>
        @endif
    </div>
</div>

{{-- DISCLAIMER --}}
<div class="disclaimer-center">
    <div>THIS BOOKING IS SUBJECT TO CHANGE FOR DOOR (HAULAGE) DELIVERY.</div>
    <div>DATE/ TIME AS WELL AS TO VESSEL SPACE AND VESSEL SCHEDULE MAY BE CHANGED WITHOUT NOTICE</div>
</div>

<div class="thank-you">
    Thank you for choosing us
</div>

</body>
</html>
