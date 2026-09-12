<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Booking Confirmation {{ $bc->number }}</title>
    <style>
        @page {
            margin: 24px 32px 24px 32px;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #000;
            font-size: 9.5px;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }

        /* HEADER */
        .header {
            width: 100%;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1.5px solid #000;
        }
        .header-left {
            float: left;
            width: 48%;
        }
        .header-left img {
            max-width: 180px;
            max-height: 55px;
        }
        .header-right {
            float: right;
            width: 50%;
            text-align: right;
        }
        .doc-title {
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin: 0 0 3px 0;
        }
        .doc-no {
            font-size: 11px;
            font-weight: 700;
        }
        .clear {
            clear: both;
        }

        /* METADATA 2-COL */
        table.meta-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.meta-grid td {
            vertical-align: top;
            padding: 2px 4px;
        }
        .meta-lbl {
            width: 28%;
            font-weight: 700;
        }
        .meta-sep {
            width: 3%;
        }
        .meta-val {
            width: 69%;
            font-weight: 600;
        }

        /* SALUTATION */
        .salutation {
            margin-bottom: 10px;
            font-size: 9.5px;
        }

        /* SHIPMENT DETAILS */
        table.shipment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.shipment-table td {
            padding: 2.5px 4px;
            vertical-align: top;
        }

        /* CARGO DETAILS (4 COLUMNS BORDERED) */
        table.cargo-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.cargo-table th {
            border: 1px solid #000;
            background-color: #f1f1f1;
            padding: 4px 6px;
            font-weight: 700;
            text-align: left;
            font-size: 9.5px;
        }
        table.cargo-table td {
            border: 1px solid #000;
            padding: 5px 6px;
            vertical-align: top;
            font-weight: 600;
            font-size: 9px;
        }

        /* NOTICE PACKAGING */
        .packaging-notice {
            font-size: 9px;
            font-weight: 700;
            color: #000;
            margin-bottom: 8px;
        }

        /* DELIVERY & CUT-OFF */
        .delivery-box {
            border: 1px solid #000;
            padding: 6px 8px;
            margin-bottom: 10px;
        }
        table.cutoff-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            margin-top: 5px;
        }
        table.cutoff-table th {
            border: 1px solid #000;
            background-color: #e5e5e5;
            padding: 3px 4px;
            font-weight: 700;
            font-size: 9px;
        }
        table.cutoff-table td {
            border: 1px solid #000;
            padding: 4px;
            font-weight: 600;
            font-size: 8.5px;
        }

        /* IMPORTANT NOTE */
        .note-title {
            font-size: 9.5px;
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 3px;
        }
        .note-content {
            font-size: 8.5px;
            line-height: 1.35;
            margin-bottom: 8px;
            white-space: pre-wrap;
        }

        /* DISCLAIMER */
        .disclaimer {
            font-size: 8.5px;
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        .closing {
            font-size: 9.5px;
            font-weight: 700;
        }
    </style>
</head>
<body>

{{-- HEADER --}}
<div class="header">
    <div class="header-left">
        <img src="{{ public_path('images/logo.png') }}" alt="RDX Logistics">
    </div>
    <div class="header-right">
        <div class="doc-title">BOOKING CONFIRMATION</div>
        <div class="doc-no">NO.: {{ $bc->number }}</div>
    </div>
    <div class="clear"></div>
</div>

@php
    $consigneeName = $bc->consignee_name ?: ($job?->consignee_name ?: ($bc->customer?->consignees?->first()?->name ?? '—'));
    $consigneeAddress = $bc->consignee_address ?: ($job?->consignee_address ?: ($bc->customer?->consignees?->first()?->address ?? ''));
    $consigneeContact = $bc->contact_person ?: ($bc->consignee_contact ?: ($job?->consignee_contact ?: ($bc->customer?->consignees?->first()?->contact_name ?: ($bc->customer?->consignees?->first()?->phone ?? '—'))));

    $shipperCustomer = $bc->customer ?? $job?->customer;
    $shipperName = $shipperCustomer?->name ?? ($bc->shipper_name ?: '—');
    $shipperAddress = $shipperCustomer?->address ?? ($job?->shipper_address ?? '');
    $docStatus = [];
    if ($shipperCustomer?->tax_number) $docStatus[] = 'NPWP: ' . $shipperCustomer->tax_number;
    if ($shipperCustomer?->npwp_file) $docStatus[] = 'NPWP (Terlampir)';
    if ($shipperCustomer?->nib_file) $docStatus[] = 'NIB (Terlampir)';
@endphp

{{-- METADATA PENERIMA (CONSIGNEE) --}}
<table class="meta-grid">
    <tr>
        <td style="width: 52%;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 32%; font-weight: 700;">To (Consignee):</td>
                    <td style="width: 68%; font-weight: 700; font-size: 10.5px;">{{ $consigneeName }}</td>
                </tr>
                @if($consigneeAddress)
                <tr>
                    <td></td>
                    <td style="font-size: 8.5px; color: #333;">{{ $consigneeAddress }}</td>
                </tr>
                @endif
                <tr>
                    <td style="font-weight: 700; padding-top: 4px;">Contact Person</td>
                    <td style="font-weight: 600; padding-top: 4px;">: {{ $consigneeContact }}</td>
                </tr>
            </table>
        </td>
        <td style="width: 48%;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td class="meta-lbl">Date</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ $bc->booking_date->format('d-M-Y') }}</td>
                </tr>
                <tr>
                    <td class="meta-lbl">Job No.</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ $bc->job?->number ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="meta-lbl">Customer Ref</td>
                    <td class="meta-sep">:</td>
                    <td class="meta-val">{{ $bc->customer_ref ?: '—' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- SALUTATION --}}
<div class="salutation">
    We thank you for your booking.<br>
    Please review the following details and advise if any discrepancy:
</div>

{{-- SHIPMENT DETAILS (2 KOLOM) --}}
<table class="shipment-table">
    <tr>
        <td style="width: 52%;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 32%; font-weight: 700;">Shipper</td>
                    <td style="width: 3%;">:</td>
                    <td style="width: 65%; font-weight: 600;">
                        {{ $shipperName }}
                        @if($shipperAddress)
                            <div style="font-size: 8px; color: #444; font-weight: normal;">{{ $shipperAddress }}</div>
                        @endif
                        @if(!empty($docStatus))
                            <div style="font-size: 7.5px; color: #0284c7; font-weight: normal; margin-top: 1px;">{{ implode(' | ', $docStatus) }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Carrier Booking</td>
                    <td>:</td>
                    <td style="font-weight: 600;">
                        {{ $bc->carrier_name ?: '—' }}
                        @if($bc->carrier_booking_no)
                            ({{ $bc->carrier_booking_no }})
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Vessel</td>
                    <td>:</td>
                    <td style="font-weight: 600;">{{ $bc->vessel_voyage ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Services</td>
                    <td>:</td>
                    <td style="font-weight: 600;">{{ $bc->service_term ?: 'CY/CY' }}</td>
                </tr>
            </table>
        </td>
        <td style="width: 48%;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 25%; font-weight: 700;">POL</td>
                    <td style="width: 3%;">:</td>
                    <td style="width: 72%; font-weight: 600;">{{ $bc->pol ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">POD</td>
                    <td>:</td>
                    <td style="font-weight: 600;">{{ $bc->pod ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">ETD</td>
                    <td>:</td>
                    <td style="font-weight: 600;">{{ $bc->etd?->format('d-M-Y') ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">ETA</td>
                    <td>:</td>
                    <td style="font-weight: 600;">{{ $bc->eta?->format('d-M-Y') ?: '—' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- CARGO DETAILS (4 KOLOM TABEL BERGARIS) --}}
<table class="cargo-table">
    <thead>
        <tr>
            <th style="width: 20%;">Quantity</th>
            <th style="width: 42%;">Description</th>
            <th style="width: 20%;">Gross Weight</th>
            <th style="width: 18%;">CBM</th>
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

{{-- SEAWORTHY PACKAGING NOTICE --}}
<div class="packaging-notice">
    PLEASE MAKE SURE TO USE SEAWORTHY PACKAGING.
</div>

{{-- DELIVERY CARGO TO & CUT-OFF --}}
<div class="delivery-box">
    <div style="margin-bottom: 4px;">
        <strong>Delivery cargo to:</strong> {{ $bc->delivery_cargo_to ?: '—' }}
    </div>
    <table class="cutoff-table">
        <thead>
            <tr>
                <th style="width: 33%;">Doc Cut-Off</th>
                <th style="width: 33%;">CY Cut-Off</th>
                <th style="width: 34%;">Delivery Cut-Off</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $bc->doc_cutoff_at?->format('d-M-Y H:i') ?: '—' }}</td>
                <td>{{ $bc->cy_cutoff_at?->format('d-M-Y H:i') ?: '—' }}</td>
                <td>{{ $bc->delivery_cutoff_at?->format('d-M-Y H:i') ?: '—' }}</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- IMPORTANT NOTE --}}
<div class="note-title">Important Note:</div>
<div class="note-content">{{ $bc->notes ?: '—' }}</div>

{{-- DISCLAIMER --}}
<div class="disclaimer">
    THIS BOOKING IS SUBJECT TO CHANGE FOR DOOR (HAULAGE) DELIVERY.<br>
    DATE/ TIME AS WELL AS TO VESSEL SPACE AND VESSEL SCHEDULE MAY BE CHANGED WITHOUT NOTICE
</div>

<div class="closing">
    Thank you for choosing us
</div>

</body>
</html>
