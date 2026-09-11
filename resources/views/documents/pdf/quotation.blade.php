<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->number }}</title>
    <style>
        @page {
            margin: 22px 30px 22px 30px;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', Courier, 'DejaVu Sans Mono', monospace;
            color: #000;
            font-size: 8.5pt;
            line-height: 1.35;
            margin: 0;
            padding: 0;
            position: relative;
            min-height: 100%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            vertical-align: top;
        }
        .clear {
            clear: both;
        }
        .header {
            margin-bottom: 20px;
        }
        .header-logo {
            float: left;
            width: 45%;
        }
        .header-logo img {
            max-width: 190px;
            max-height: 52px;
        }
        .header-company {
            float: right;
            width: 53%;
            text-align: right;
            font-size: 8.5pt;
            line-height: 1.3;
        }
        .header-company .company-name {
            font-weight: bold;
            margin-bottom: 2px;
        }
        .top-block {
            margin-bottom: 16px;
        }
        .to-block {
            float: left;
            width: 55%;
            font-size: 8.5pt;
            line-height: 1.35;
        }
        .to-label {
            font-weight: bold;
            color: #000;
        }
        .to-name {
            font-weight: bold;
            color: #cc0000;
            text-transform: uppercase;
        }
        .to-address {
            color: #cc0000;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .quo-block {
            float: right;
            width: 42%;
            text-align: right;
        }
        .quo-title {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .quo-meta {
            border-collapse: collapse;
            margin-left: auto;
            font-size: 8.5pt;
        }
        .quo-meta td {
            padding: 1px 0;
        }
        .quo-meta td.lbl {
            font-weight: bold;
            text-align: left;
            padding-right: 2px;
            width: 95px;
        }
        .quo-meta td.colon {
            width: 12px;
            text-align: center;
        }
        .quo-meta td.val {
            text-align: left;
            padding-left: 4px;
        }
        .shipment-info {
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 8.5pt;
            width: 100%;
        }
        .shipment-info td {
            padding: 1.5px 0;
            vertical-align: top;
        }
        .shipment-info td.field-label {
            font-weight: bold;
            color: #000;
            width: 155px;
        }
        .shipment-info td.colon {
            width: 14px;
            text-align: center;
        }
        .shipment-info td.val {
            font-weight: bold;
            color: #cc0000;
            text-transform: uppercase;
        }
        .intro {
            margin-bottom: 8px;
            font-size: 8.5pt;
        }
        table.items {
            width: 100%;
            border: 1px solid #000;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 8pt;
        }
        table.items thead tr {
            background-color: #e0e0e0;
        }
        table.items thead th {
            padding: 5px 3px;
            text-align: center;
            border: 1px solid #000;
            font-weight: bold;
            font-size: 7.5pt;
            text-transform: uppercase;
        }
        table.items tbody tr {
            background-color: #fff;
        }
        table.items tbody td {
            padding: 4px 4px;
            border: 1px solid #000;
            vertical-align: middle;
        }
        table.items td.center {
            text-align: center;
        }
        table.items td.right {
            text-align: right;
        }
        .remarks {
            margin-bottom: 12px;
            font-size: 8pt;
            line-height: 1.35;
        }
        .remarks-title {
            font-weight: bold;
            margin-bottom: 3px;
        }
        .custom-remarks {
            margin-bottom: 12px;
            font-size: 8pt;
            line-height: 1.35;
        }
        .cr-label {
            font-weight: bold;
            color: #cc0000;
        }
        .cr-content {
            font-weight: bold;
            color: #cc0000;
            text-transform: uppercase;
        }
        .closing {
            margin: 12px 0 16px 0;
            font-size: 8.5pt;
            line-height: 1.35;
        }
        .sign {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            font-size: 8.5pt;
            line-height: 1.3;
        }
        .sign-logo {
            margin: 8px 0 6px 0;
        }
        .sign-logo img {
            max-height: 36px;
            max-width: 110px;
        }
        .sign-name {
            font-weight: bold;
            color: #cc0000;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .sign-role {
            font-weight: normal;
        }
        .sign-contact {
            font-weight: normal;
        }
        .sign-generated {
            margin-top: 8px;
            font-style: italic;
            font-size: 7.5pt;
            color: #333;
        }
    </style>
</head>
<body>

{{-- HEADER --}}
<div class="header">
    <div class="header-logo">
        <img src="{{ public_path('images/logo.png') }}" alt="RDX LOGISTICS">
    </div>
    <div class="header-company">
        <div class="company-name">PT.RADIX INTERNATIONAL LOGISTICS</div>
        <div>Address : Jl.Teh No 3C Tamansari Pinangsia</div>
        <div>Jakarta Barat Indonesia 11110</div>
        <div>Telp : 021-38873060</div>
    </div>
    <div class="clear"></div>
</div>

{{-- TO + QUOTATION TITLE --}}
<div class="top-block">
    <div class="to-block">
        <span class="to-label">To : </span><span class="to-name">{{ $quotation->customer_snapshot['name'] ?? ($quotation->customer?->name ?? '—') }}</span>
        @if(!empty($quotation->customer_snapshot['address']) || !empty($quotation->customer?->address))
        <div class="to-address">{!! nl2br(e($quotation->customer_snapshot['address'] ?? $quotation->customer?->address)) !!}</div>
        @endif
    </div>
    <div class="quo-block">
        <div class="quo-title">QUOTATION</div>
        <table class="quo-meta">
            <tr>
                <td class="lbl">QUO NO.</td>
                <td class="colon">:</td>
                <td class="val">{{ $quotation->number }}</td>
            </tr>
            <tr>
                <td class="lbl">QUO DATE</td>
                <td class="colon">:</td>
                <td class="val">{{ $quotation->quotation_date ? $quotation->quotation_date->format('d/m/Y') : '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">VALID UNTIL</td>
                <td class="colon">:</td>
                <td class="val">{{ $quotation->valid_until ? $quotation->valid_until->format('d/m/Y') : '-' }}</td>
            </tr>
        </table>
    </div>
    <div class="clear"></div>
</div>

{{-- SHIPMENT INFO --}}
<table class="shipment-info">
    <tr>
        <td class="field-label">Services</td>
        <td class="colon">:</td>
        <td class="val">{{ strtoupper(config('operations.service_types.'.$quotation->service_type) ?? ($quotation->service_type ?? '—')) }}</td>
    </tr>
    <tr>
        <td class="field-label">Terms of Delivery</td>
        <td class="colon">:</td>
        <td class="val">{{ strtoupper(config('operations.terms_of_delivery.'.$quotation->terms_of_delivery) ?? ($quotation->terms_of_delivery ?? '—')) }}</td>
    </tr>
    <tr>
        <td class="field-label">Quantity</td>
        <td class="colon">:</td>
        <td class="val">{{ $quotation->cargo_qty ?: ($quotation->subject ?: '—') }}</td>
    </tr>
    <tr>
        @php
            $totalWeight = $quotation->items->sum(fn($i) => (float)($i->gross_weight ?? 0));
            $totalVolume = $quotation->items->sum(fn($i) => (float)($i->volume ?? 0));
            $calcWeightMeas = ($totalWeight > 0 ? number_format($totalWeight, 2, '.', ',').' kg' : '') . ($totalVolume > 0 ? ($totalWeight > 0 ? ' / ' : '') . $totalVolume . ' m³' : '');
        @endphp
        <td class="field-label">Weight/Meas</td>
        <td class="colon">:</td>
        <td class="val">{{ $quotation->weight_meas ?: ($calcWeightMeas ?: '—') }}</td>
    </tr>
    <tr>
        <td class="field-label">Commodity</td>
        <td class="colon">:</td>
        <td class="val">{{ $quotation->commodity ?: 'General Cargo' }}</td>
    </tr>
    <tr>
        <td class="field-label">Port of Loading</td>
        <td class="colon">:</td>
        <td class="val">{{ $quotation->origin ?: '—' }}</td>
    </tr>
    <tr>
        <td class="field-label">Port of Discharge</td>
        <td class="colon">:</td>
        <td class="val">{{ $quotation->destination ?: '—' }}</td>
    </tr>
</table>

<div class="intro">We are pleased to quote you the following :</div>

{{-- ITEMS TABLE --}}
<table class="items">
    <thead>
        <tr>
            <th style="width:28px;">NO</th>
            <th>CHARGES DESCRIPTION</th>
            <th style="width:36px;">CUR</th>
            <th style="width:85px;">PRICE</th>
            <th style="width:36px;">QTY</th>
            <th style="width:55px;">UNIT</th>
            <th style="width:60px;">EXC.RATE</th>
            <th style="width:95px;">AMOUNT IDR</th>
            <th style="width:70px;">NOTE</th>
        </tr>
    </thead>
    <tbody>
        @foreach($quotation->items as $index => $item)
        @php
            $currency = strtoupper($item->currency ?? ($quotation->currency ?? 'IDR'));
            $isIdr = ($currency === 'IDR');
            $excRate = (!$isIdr && $item->exchange_rate && $item->exchange_rate != 1)
                       ? number_format((float)$item->exchange_rate, 2, '.', ',') : '';

            $priceFormatted = number_format((float)$item->unit_price, 2, '.', ',');
            $qtyFormatted = ((float)$item->quantity == (int)$item->quantity)
                            ? (int)$item->quantity
                            : number_format((float)$item->quantity, 2, '.', ',');
            $amountFormatted = number_format((float)$item->total_price, 2, '.', ',');

            $subText = '';
            if ($item->pricing_source === 'trucking') {
                $truckParts = [];
                $pOrig = $item->pricing_snapshot['port_origin'] ?? '';
                $pDest = $item->pricing_snapshot['destination'] ?? '';
                if ($pOrig || $pDest) {
                    $truckParts[] = $pOrig . ' → ' . $pDest;
                }
                if ($item->container_type) {
                    $truckParts[] = strtoupper($item->container_type) . ($item->overweight ? ' · OVERWEIGHT' : '');
                }
                $pCurr = $item->pricing_snapshot['currency'] ?? 'IDR';
                if ($pCurr !== 'IDR') {
                    $truckParts[] = $pCurr . ' ' . number_format((float)($item->pricing_snapshot['price'] ?? 0), 2, '.', ',') . ' @ kurs ' . number_format((float)($item->pricing_snapshot['exchange_rate'] ?? 1), 2, '.', ',');
                }
                $subText = 'Tarif trucking' . (!empty($truckParts) ? ' · ' . implode(' · ', $truckParts) : '');
            } elseif ($item->container_type) {
                $parts = [strtoupper($item->container_type)];
                if ($item->overweight) {
                    $parts[] = 'OVERWEIGHT';
                }
                if ($item->gross_weight !== null && $item->gross_weight !== '') {
                    $parts[] = 'BW ' . number_format((float)$item->gross_weight, 2, '.', ',') . ' kg';
                }
                if ($item->volume !== null && $item->volume !== '') {
                    $parts[] = $item->volume . ' m³';
                }
                $subText = implode(' · ', $parts);
            }
        @endphp
        <tr>
            <td class="center">{{ $index + 1 }}</td>
            <td>
                {{ strtoupper($item->description) }}
                @if($subText)
                    <br/><span style="font-size:7pt;color:#444;">{{ $subText }}</span>
                @endif
            </td>
            <td class="center">{{ $currency }}</td>
            <td class="right">{{ $priceFormatted }}</td>
            <td class="center">{{ $qtyFormatted }}</td>
            <td class="center">{{ $item->unit }}</td>
            <td class="center">{{ $excRate }}</td>
            <td class="right">{{ $amountFormatted }}</td>
            <td style="font-size:7pt;">{{ $item->note ?? '' }}</td>
        </tr>
        @endforeach

        @if(($quotation->discount ?? 0) > 0)
        <tr>
            <td colspan="7" class="right" style="font-weight:bold;">DISCOUNT:</td>
            <td class="right" style="font-weight:bold;">- {{ number_format((float)$quotation->discount, 2, '.', ',') }}</td>
            <td></td>
        </tr>
        @endif
        @if(($quotation->tax_rate ?? 0) > 0)
        <tr>
            <td colspan="7" class="right" style="font-weight:bold;">PPN {{ number_format((float)$quotation->tax_rate, 2, '.', ',') }}%:</td>
            <td class="right" style="font-weight:bold;">{{ number_format((float)$quotation->tax_amount, 2, '.', ',') }}</td>
            <td></td>
        </tr>
        @endif
    </tbody>
</table>

{{-- STANDARD REMARKS --}}
<div class="remarks">
    <div class="remarks-title">Remarks:</div>
    1. All fees and charges listed must be paid in IDR (Indonesian Rupiah) according to the Invoice date and subject to PPN 1.1%<br>
    2. Shipping is not insured unless specifically requested by the customer.<br>
    3. Importers or exporters are required to prepare NPWP/NIB and other supporting documents to complete the order.<br>
    4. Term of Payment and penalty for late charges will be implemented according to the general Term and condition.<br>
    5. Every Cancellation shipment is required to pay a Cancellation Fee if any.<br>
    6. Payment is made 7 days normally from the invoice received by the customer.<br>
    7. All prices above are valid according to the available valid date and are not binding if it has passed.
</div>

{{-- CUSTOM REMARKS --}}
@if(!empty(trim($quotation->notes ?? '')))
<div class="custom-remarks">
    <div class="cr-label">REMARKS :</div>
    <div class="cr-content">{!! nl2br(e($quotation->notes)) !!}</div>
</div>
@endif

{{-- CLOSING --}}
<div class="closing">
    Will be happy to assist your shipment and for further information you may need please do not hesitate to contact us.
</div>

{{-- SIGNATURE (DILETAKKAN DI PALING BAWAH HALAMAN) --}}
<div class="sign">
    <div>Yours Faithfully,</div>
    <div class="sign-logo">
        <img src="{{ public_path('images/logo.png') }}" alt="RDX">
    </div>
    @php
        $salesUser = $quotation->sales ?? ($quotation->creator ?? null);
    @endphp
    <div class="sign-name">{{ strtoupper($salesUser?->name ?? 'NAMA SALES') }}</div>
    <div class="sign-role">MARKETING</div>
    <div class="sign-contact">Mobile : {{ $salesUser?->phone ?? '' }}</div>
    <div class="sign-contact">Email : {{ $salesUser?->email ?? '' }}</div>
    <div class="sign-generated">This is computer-generated does not require signature</div>
</div>

</body>
</html>
