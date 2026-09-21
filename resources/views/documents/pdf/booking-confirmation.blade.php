<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Booking Confirmation {{ $bc->number }}</title>
    <style>
        @page {
            margin: 25pt 30pt 20pt 30pt;
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
        .logo-box {
            margin-bottom: 40pt;
        }
        .logo-img {
            height: 26pt;
            width: auto;
            display: block;
        }
        .title-box {
            text-align: center;
            margin-bottom: 20pt;
        }
        .title-main {
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .title-no {
            font-size: 8.5pt;
            font-weight: bold;
            margin-top: 3pt;
        }
        .hr-line {
            border: none;
            border-top: 1px solid #000000;
            margin: 6pt 0;
        }
        table.meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 8pt;
        }
        table.meta-table td {
            vertical-align: top;
            padding: 0;
        }
        .lbl-bold {
            font-weight: bold;
        }
        .salutation-box {
            margin-top: 14pt;
            margin-bottom: 6pt;
            font-size: 8.5pt;
            line-height: 1.3;
        }
        table.shipment-details {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin: 4pt 0;
        }
        table.shipment-details td {
            vertical-align: top;
            padding: 1.5pt 0;
        }
        table.cargo-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin: 4pt 0;
        }
        table.cargo-grid th {
            text-align: left;
            font-weight: bold;
            padding: 0 4pt 2pt 0;
        }
        table.cargo-grid td {
            vertical-align: top;
            padding: 0 4pt 50pt 0;
        }
        .seaworthy-box {
            font-size: 8.5pt;
            margin: 6pt 0 10pt 0;
        }
        table.delivery-cutoff-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin: 2pt 0;
        }
        table.delivery-cutoff-table th {
            text-align: left;
            font-weight: bold;
            padding: 0 4pt 2pt 0;
        }
        table.delivery-cutoff-table td {
            vertical-align: top;
            padding: 0 4pt 0 0;
        }
        .note-box {
            margin-top: 68pt;
            font-size: 8.5pt;
        }
        .note-title {
            font-weight: normal;
            margin-bottom: 3pt;
        }
        .note-item {
            margin-bottom: 2pt;
            line-height: 1.25;
            padding-left: 10pt;
            text-indent: -10pt;
        }
        .disclaimer-center {
            text-align: center;
            font-size: 8.5pt;
            font-weight: bold;
            line-height: 1.35;
            margin-top: 42pt;
            margin-bottom: 10pt;
        }
        .thank-you {
            text-align: center;
            font-size: 8.5pt;
            font-weight: bold;
            margin-top: 14pt;
        }
    </style>
</head>
<body>

@php
    $job = $bc->job;
    $consigneeName = $bc->consignee_name ?: ($job?->consignee_name ?: ($bc->customer?->consignees?->first()?->name ?? ($bc->customer?->name ?? '—')));
    $consigneeAddress = $bc->consignee_address ?: ($job?->consignee_address ?: ($bc->customer?->consignees?->first()?->address ?? ($bc->customer?->address ?? '')));
    $consigneeContact = $bc->contact_person ?: ($bc->consignee_contact ?: ($job?->consignee_contact ?: ($bc->customer?->consignees?->first()?->contact_name ?: ($bc->customer?->contact_name ?? '—'))));

    $shipperCustomer = $bc->customer ?? $job?->customer;
    $shipperName = $shipperCustomer?->name ?? ($bc->shipper_name ?: '—');
    $shipperAddress = $shipperCustomer?->address ?? ($job?->shipper_address ?? '');

    $carrierStr = $bc->carrier_name ?: '—';
    if ($bc->carrier_booking_no) {
        $carrierStr .= ' (' . $bc->carrier_booking_no . ')';
    }

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

    // 6 Standard Legal Clauses from reference BOOKING CONFIRMATION.jpg
    $defaultClauses = [
        'This booking confirmation validates your space booking with us according to all details stated in the Shipping Instruction, we reserve right to shut out cargo for any discrepancy that disables us from doing groupage consol.',
        'This booking confirmation does not function as a guarantee of departure, we reserve right to shut out cargo in case of failures in export procedures and/or handicaps in export documentations.',
        'This booking confirmation is automatically invalid upon your booking cancellation, kindly inform us immediately for any cancellation.',
        'This booking confirmation is automatically invalid upon any cases of cargo/documentations problems, including failure of export declaration.',
        "This booking confirmation is automatically invalid for following undeclared cargo:\nLONG LENGTH/OVERWEIGHT/FOODS & BEVERAGES, DUTIABLE/DANGEROUS GOODS/LAW FORBIDDEN/LIVE ANIMALS.",
        'This booking confirmation is not valid for any claim.',
    ];

    $rawNotes = trim($bc->notes ?? '');
    $bulletClauses = [];
    if ($rawNotes !== '') {
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
    if (empty($bulletClauses)) {
        $bulletClauses = $defaultClauses;
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
<table class="meta-table">
    <tr>
        <td style="width: 56%; padding-right: 15pt;">
            <div><strong class="lbl-bold">To :</strong> {{ $consigneeName }}</div>
            @if($consigneeAddress)
                <div style="padding-left: 28px;">{{ $consigneeAddress }}</div>
            @endif
        </td>
        <td style="width: 44%; padding-left: 15pt;">
            <table style="width: 100%; border-collapse: collapse; font-size: inherit;">
                <tr>
                    <td style="width: 46%;"><strong class="lbl-bold">Date</strong></td>
                    <td style="width: 4%;">:</td>
                    <td style="width: 50%;">{{ $bc->booking_date ? $bc->booking_date->format('d-M-Y') : '—' }}</td>
                </tr>
                <tr>
                    <td><strong class="lbl-bold">Contact Person</strong></td>
                    <td>:</td>
                    <td>{{ $consigneeContact }}</td>
                </tr>
                <tr>
                    <td><strong class="lbl-bold">Job No.</strong></td>
                    <td>:</td>
                    <td>{{ $bc->job?->number ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong class="lbl-bold">Customer Ref</strong></td>
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
<table class="shipment-details">
    <tr>
        <td style="width: 56%; padding-right: 15pt;">
            <table style="width: 100%; border-collapse: collapse; font-size: inherit;">
                <tr>
                    <td style="width: 38%;"><strong class="lbl-bold">Shipper</strong></td>
                    <td style="width: 4%;">:</td>
                    <td style="width: 58%;">
                        {{ $shipperName }}
                        @if($shipperAddress)
                            <div>{{ $shipperAddress }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td><strong class="lbl-bold">Carrier Booking</strong></td>
                    <td>:</td>
                    <td>{{ $carrierStr }}</td>
                </tr>
                <tr>
                    <td><strong class="lbl-bold">Vessel</strong></td>
                    <td>:</td>
                    <td>{{ $bc->vessel_voyage ?: '—' }}</td>
                </tr>
                <tr>
                    <td><strong class="lbl-bold">Services</strong></td>
                    <td>:</td>
                    <td>{{ $bc->service_term ?: 'CY/CY' }}</td>
                </tr>
            </table>
        </td>
        <td style="width: 44%; padding-left: 15pt;">
            <table style="width: 100%; border-collapse: collapse; font-size: inherit;">
                <tr>
                    <td style="width: 32%;"><strong class="lbl-bold">POL</strong></td>
                    <td style="width: 4%;">:</td>
                    <td style="width: 64%;">{{ $bc->pol ?: '—' }}</td>
                </tr>
                <tr>
                    <td><strong class="lbl-bold">POD</strong></td>
                    <td>:</td>
                    <td>{{ $bc->pod ?: '—' }}</td>
                </tr>
                <tr>
                    <td><strong class="lbl-bold">ETD</strong></td>
                    <td>:</td>
                    <td>{{ $bc->etd ? $bc->etd->format('d-M-Y') : '—' }}</td>
                </tr>
                <tr>
                    <td><strong class="lbl-bold">ETA</strong></td>
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
            <th style="width: 25%;"><strong class="lbl-bold">Quantity:</strong></th>
            <th style="width: 35%;"><strong class="lbl-bold">Description:</strong></th>
            <th style="width: 22%;"><strong class="lbl-bold">Gross Weight:</strong></th>
            <th style="width: 18%;"><strong class="lbl-bold">CBM:</strong></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $bc->quantity ?: '—' }}{{ $bc->package_unit ? ' ' . $bc->package_unit : '' }}</td>
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

{{-- DELIVERY CARGO TO & CUT-OFF (HORIZONTALLY ALIGNED) --}}
<table class="delivery-cutoff-table">
    <thead>
        <tr>
            <th style="width: 44%;"><strong class="lbl-bold">Delivery cargo to:</strong></th>
            <th style="width: 20%;"><strong class="lbl-bold">Doc Cut-Off</strong></th>
            <th style="width: 20%;"><strong class="lbl-bold">CY Cut-Off</strong></th>
            <th style="width: 16%;"><strong class="lbl-bold">Delivery</strong></th>
        </tr>
    </thead>
    <tbody>
        @if($bc->delivery_cargo_to || $bc->doc_cutoff_at || $bc->cy_cutoff_at || $bc->delivery_cutoff_at)
        <tr>
            <td>{{ $bc->delivery_cargo_to }}</td>
            <td>{{ $bc->doc_cutoff_at ? $bc->doc_cutoff_at->format('d-M-Y H:i') : '' }}</td>
            <td>{{ $bc->cy_cutoff_at ? $bc->cy_cutoff_at->format('d-M-Y H:i') : '' }}</td>
            <td>{{ $bc->delivery_cutoff_at ? $bc->delivery_cutoff_at->format('d-M-Y H:i') : '' }}</td>
        </tr>
        @endif
    </tbody>
</table>

{{-- IMPORTANT NOTE --}}
<div class="note-box">
    <div class="note-title">Important Note:</div>
    <div class="note-content">
        @foreach($bulletClauses as $clause)
            @if(str_contains($clause, 'LONG LENGTH/OVERWEIGHT'))
                @php
                    $splitPos = strpos($clause, 'LONG LENGTH');
                    $before = trim(substr($clause, 0, $splitPos));
                    $after = trim(substr($clause, $splitPos));
                @endphp
                <div class="note-item">• {{ $before }}<br><span style="padding-left: 10pt; display: inline-block;">{{ $after }}</span></div>
            @else
                <div class="note-item">• {{ $clause }}</div>
            @endif
        @endforeach
    </div>
</div>

{{-- DISCLAIMER CENTER BOLD --}}
<div class="disclaimer-center">
    <div>THIS BOOKING IS SUBJECT TO CHANGE FOR DOOR (HAULAGE) DELIVERY.</div>
    <div>DATE/ TIME AS WELL AS TO VESSEL SPACE AND VESSEL SCHEDULE MAY BE CHANGED WITHOUT NOTICE</div>
</div>

<div class="thank-you">
    Thank you for choosing us
</div>

</body>
</html>
