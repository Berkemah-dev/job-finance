<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->number }}</title>
    <style>
        @page {
            margin: 22px 26px 20px 26px;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            color: #000;
            font-size: 9pt;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }
        table {
            border-collapse: collapse;
        }
        td {
            vertical-align: top;
        }
        .clear {
            clear: both;
        }
        .header-table {
            width: 100%;
            margin-bottom: 76px;
        }
        .header-logo {
            width: 58%;
            vertical-align: top;
        }
        .header-logo img {
            width: 198pt;
            height: auto;
        }
        .header-company {
            width: 42%;
            vertical-align: top;
            text-align: left;
            font-size: 8pt;
            line-height: 1.2;
            padding-left: 15px;
        }
        .header-company .company-name {
            font-weight: bold;
            font-size: 9.5pt;
            margin-bottom: 2px;
        }
        .top-block-table {
            width: 100%;
            margin-bottom: 14px;
        }
        .to-block {
            width: 70%;
            vertical-align: top;
            font-size: 9pt;
            line-height: 1.25;
            padding-top: 14px;
        }
        .to-attn {
            font-weight: bold;
            color: #000;
        }
        .to-name {
            font-weight: normal;
            color: #000;
            text-transform: uppercase;
        }
        .to-address {
            color: #000;
            font-size: 9pt;
            text-transform: uppercase;
            line-height: 1.25;
        }
        .to-npwp {
            font-weight: normal;
            color: #000;
            font-size: 9pt;
        }
        .quo-block {
            width: 30%;
            vertical-align: top;
            text-align: left;
            padding-left: 5px;
        }
        .quo-title {
            font-family: 'Times New Roman', Times, serif;
            font-size: 20pt;
            font-weight: normal;
            letter-spacing: 0.5px;
            line-height: 0.85;
            margin-top: 3px;
            margin-bottom: 5px;
        }
        .quo-meta {
            border-collapse: collapse;
            font-size: 9pt;
            width: 100%;
        }
        .quo-meta td {
            padding: 1px 0;
        }
        .quo-meta td.lbl {
            font-weight: bold;
            text-align: left;
            width: 88px;
            white-space: nowrap;
        }
        .quo-meta td.colon {
            width: 10px;
            text-align: center;
        }
        .quo-meta td.val {
            text-align: left;
            padding-left: 2px;
            font-weight: normal;
            color: #000;
            white-space: nowrap;
        }
        .shipment-info {
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 9pt;
            width: 60%;
        }
        .shipment-info td {
            padding: 1px 0;
            vertical-align: top;
        }
        .shipment-info td.field-label {
            font-weight: bold;
            color: #000;
            width: 135px;
            white-space: nowrap;
        }
        .shipment-info td.colon {
            width: 10px;
            text-align: center;
        }
        .shipment-info td.val {
            font-weight: normal;
            color: #000;
            text-transform: uppercase;
            padding-left: 3px;
        }
        .intro {
            margin-bottom: 6px;
            font-size: 9pt;
            color: #000;
        }
        table.items {
            width: 100%;
            border: 1px solid #000;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 8pt;
        }
        table.items thead tr {
            background-color: #dfdfdf;
        }
        table.items thead th {
            padding: 4px 2px;
            text-align: center;
            border: 1px solid #000;
            font-weight: bold;
            font-size: 8pt;
            text-transform: uppercase;
        }
        table.items tbody tr {
            background-color: #fff;
        }
        table.items tbody td {
            padding: 3px 4px;
            border: 1px solid #000;
            vertical-align: middle;
            color: #000;
        }
        table.items td.center {
            text-align: center;
        }
        table.items td.right {
            text-align: right;
        }
        .remarks {
            margin-bottom: 8px;
            font-size: 8.5pt;
            line-height: 1.25;
            color: #000;
        }
        .remarks-title {
            font-weight: bold;
            margin-bottom: 2px;
        }
        .custom-remarks {
            margin: 6px 0 6px 0;
            font-size: 8.5pt;
            line-height: 1.25;
            color: #000;
        }
        .cr-label {
            font-weight: bold;
            color: #000;
        }
        .cr-content {
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
        }
        .closing {
            margin: 8px 0 16px 0;
            font-size: 8.5pt;
            line-height: 1.25;
            color: #000;
        }
        .sign {
            margin-top: 210px;
            font-size: 8.5pt;
            line-height: 1.25;
            color: #000;
        }
        .sign-logo {
            margin: 6px 0 6px 0;
        }
        .sign-logo img {
            width: 73pt;
            height: auto;
        }
        .sign-name {
            font-weight: bold;
            color: #000;
            text-decoration: underline;
            text-transform: uppercase;
            font-style: normal;
        }
        .sign-role {
            font-weight: normal;
            color: #000;
        }
        .sign-contact {
            font-weight: normal;
            color: #000;
        }
        .footer-disclaimer {
            position: absolute;
            bottom: 12px;
            left: 0;
            font-style: italic;
            font-size: 7.5pt;
            color: #000;
        }
    </style>
</head>
<body>

@php
    $headerLogoFile = public_path('images/rdx-logo-clean.png');
    $headerLogoSrc = file_exists($headerLogoFile)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($headerLogoFile))
        : (file_exists(public_path('images/rdx-logistics-doc-logo.png'))
            ? 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/rdx-logistics-doc-logo.png')))
            : public_path('images/rdx-logo-clean.png'));

    $signLogoFile = public_path('images/logo.png');
    $signLogoSrc = file_exists($signLogoFile)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($signLogoFile))
        : public_path('images/logo.png');
@endphp

{{-- HEADER --}}
<table class="header-table">
    <tr>
        <td class="header-logo">
            <img src="{{ $headerLogoSrc }}" alt="RDX LOGISTICS">
        </td>
        <td class="header-company">
            <div class="company-name">PT.RADIX INTERNATIONAL LOGISTICS</div>
            <div>Jl.Teh No.3C Jakarta Barat 11110 Indonesia</div>
            <div>Telp : 021-38873060</div>
        </td>
    </tr>
</table>

{{-- TO + QUOTATION TITLE --}}
<table class="top-block-table">
    <tr>
        <td class="to-block">
            @php
                $contactName = $quotation->customer_snapshot['contact_name'] ?? ($quotation->customer?->contact_name ?? null);
                $custName = $quotation->customer_snapshot['name'] ?? ($quotation->customer?->name ?? '—');
                $custAddress = $quotation->customer_snapshot['address'] ?? ($quotation->customer?->address ?? null);
                $taxNumber = $quotation->customer_snapshot['tax_number'] ?? ($quotation->customer?->tax_number ?? null);
            @endphp
            @if($contactName)
                <div class="to-attn">Attn. {{ strtoupper($contactName) }}</div>
                <div class="to-name">{{ strtoupper($custName) }}</div>
            @else
                <div class="to-name"><span style="font-weight: bold;">To : </span>{{ strtoupper($custName) }}</div>
            @endif
            @if(!empty($custAddress))
                <div class="to-address">{!! nl2br(e($custAddress)) !!}</div>
            @endif
            @if(!empty($taxNumber))
                <div class="to-npwp">NPWP : {{ $taxNumber }}</div>
            @endif
        </td>
        <td class="quo-block">
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
                    <td class="val">{{ $quotation->quotation_date ? $quotation->quotation_date->format('d-m-Y') : '-' }}</td>
                </tr>
                <tr>
                    <td class="lbl">VALID UNTIL</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $quotation->valid_until ? $quotation->valid_until->format('d-m-Y') : '' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- SHIPMENT INFO --}}
<table class="shipment-info">
    <tr>
        <td class="field-label">Services</td>
        <td class="colon">:</td>
        <td class="val">{{ strtoupper(\App\Models\ServiceType::label($quotation->service_type)) }}</td>
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
            <th style="width:65px;">UNIT</th>
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
            <td>{{ $item->unit }}</td>
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
    1.All fees and charges listed must be paid in IDR (Indonesian Rupiah) according to the Invoice date and subject to PPN 1.1%<br>
    2.Shipping is not insured unless specifically requested by the customer.<br>
    3.Importers or exporters are required to prepare NPWP/NIB and other supporting documents to complete the order.<br>
    4.Term of Payment and penalty for late charges will be implemented according to the general Term and condition.<br>
    5.Every Cancellation shipment is required to pay a Cancellation Fee if any.<br>
    6.Payment is made 7 days normally from the invoice received by the customer.<br>
    7.All prices above are valid according to the available valid date and are not binding if it has passed.
</div>

{{-- CUSTOM REMARKS / NOTE --}}
@if(!empty(trim($quotation->notes ?? '')))
<div class="custom-remarks">
    <div class="cr-label">NOTE:</div>
    <div class="cr-content">{!! nl2br(e($quotation->notes)) !!}</div>
</div>
@endif

{{-- CLOSING --}}
<div class="closing">
    Will be happy to assist your shipment and for further information you may need please do not hesitate to contact us.
</div>

{{-- SIGNATURE --}}
<div class="sign">
    <div>Yours Faithfully,</div>
    <div class="sign-logo">
        <img src="{{ $signLogoSrc }}" alt="RDX">
    </div>
    @php
        $salesUser = $quotation->sales ?? ($quotation->creator ?? null);
    @endphp
    <div class="sign-name">{{ strtoupper($salesUser?->name ?? 'STEVEN JOMAN') }}</div>
    <div class="sign-role">MARKETING</div>
    <div class="sign-contact">Mobile : {{ $salesUser?->phone ?? '082246500047' }}</div>
    <div class="sign-contact">Email : {{ $salesUser?->email ?? 'steven@rdx-interlog.com' }}</div>
</div>

{{-- FOOTER DISCLAIMER --}}
<div class="footer-disclaimer">
    This is computer-generated does not require signature
</div>

</body>
</html>
