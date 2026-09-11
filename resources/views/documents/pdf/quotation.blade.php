<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 16px 26px 16px 26px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1a1a1a; font-size: 9.5px; line-height: 1.35; margin: 0; padding: 0; }
        .header-logo { float: left; width: 45%; }
        .header-logo img { max-width: 160px; max-height: 48px; }
        .header-company { float: right; width: 52%; text-align: right; font-size: 8.5px; line-height: 1.45; }
        .header-company .company-name { font-weight: 700; font-size: 10.5px; text-transform: uppercase; margin-bottom: 1px; }
        .header-company .company-label { font-weight: 700; text-decoration: underline; }
        .clear { clear: both; }
        .header { margin-bottom: 12px; }
        .top-block { margin-bottom: 10px; }
        .to-block { float: left; width: 55%; font-size: 9.5px; }
        .to-label { font-weight: 700; color: #1a1a1a; margin-bottom: 2px; }
        .to-name { font-weight: 700; color: #cc0000; font-size: 10.5px; text-transform: uppercase; }
        .to-address { color: #cc0000; font-weight: 700; font-size: 9.5px; }
        .quo-block { float: right; width: 42%; text-align: right; }
        .quo-title { font-size: 22px; font-weight: 900; letter-spacing: 2px; color: #1a1a1a; margin-bottom: 4px; }
        .quo-meta { border-collapse: collapse; margin-left: auto; font-size: 9.5px; }
        .quo-meta td { padding: 1px 3px; }
        .quo-meta td.lbl { font-weight: 700; }
        .shipment-info { border-collapse: collapse; margin-bottom: 10px; font-size: 9.5px; width: 100%; }
        .shipment-info td { padding: 1px 0; vertical-align: top; }
        .shipment-info td.field-label { font-weight: 700; width: 125px; }
        .shipment-info td.colon { width: 12px; }
        .val { font-weight: 700; color: #cc0000; }
        .intro { margin-bottom: 6px; font-size: 9.5px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 9px; }
        table.items thead tr { background: #1a1a1a; color: #fff; }
        table.items thead th { padding: 4px 4px; text-align: center; border: 1px solid #555; font-weight: 700; font-size: 8.5px; text-transform: uppercase; }
        table.items tbody tr { background: #fff; }
        table.items tbody tr:nth-child(even) { background: #f9f9f9; }
        table.items tbody td { padding: 3px 4px; border: 1px solid #ccc; vertical-align: middle; }
        table.items td.center { text-align: center; }
        table.items td.right { text-align: right; }
        .totals-wrap { margin-bottom: 10px; }
        .totals-table { float: right; font-size: 9.5px; border-collapse: collapse; min-width: 260px; }
        .totals-table td { padding: 2px 6px; border: 1px solid #ccc; }
        .totals-table td.lbl { font-weight: 700; background: #f0f0f0; }
        .totals-table td.amt { text-align: right; }
        .totals-table tr.grand td { font-weight: 700; background: #e0e0e0; }
        .remarks { margin-bottom: 8px; font-size: 8px; line-height: 1.25; }
        .remarks-title { font-weight: 700; margin-bottom: 2px; }
        .remarks ol { margin: 0; padding-left: 16px; }
        .remarks ol li { margin-bottom: 1px; }
        .custom-remarks { margin-top: 6px; font-size: 8.5px; }
        .cr-label { font-weight: 700; color: #cc0000; text-decoration: underline; }
        .closing { margin: 8px 0 10px 0; font-size: 9px; }
        .sign { margin-top: 8px; page-break-inside: avoid; }
        .sign-logo img { max-height: 40px; max-width: 100px; margin-bottom: 2px; }
        .sign-name { font-weight: 700; color: #cc0000; text-decoration: underline; font-size: 9.5px; }
        .sign-role { font-size: 8.5px; }
        .sign-contact { font-size: 8.5px; text-decoration: underline; }
        .sign-generated { margin-top: 6px; font-style: italic; font-size: 8px; color: #888; }
        .footer { position: fixed; bottom: -10px; left: 0; right: 0; text-align: center; font-size: 8px; color: #999; border-top: 1px solid #eee; padding-top: 2px; }
    </style>
</head>
<body>

{{-- HEADER --}}
<div class="header">
    <div class="header-logo">
        <img src="{{ public_path('images/logo.png') }}" alt="RDX Logistics">
    </div>
    <div class="header-company">
        <div class="company-name">PT.RADIX INTERNATIONAL LOGISTICS</div>
        <div><span class="company-label">Address :</span> Jl.Teh No 3C Tamansari Pinangsia</div>
        <div>Jakarta Barat Indonesia 11110</div>
        <div><span class="company-label">Telp :</span> 021-38873060</div>
    </div>
    <div class="clear"></div>
</div>

{{-- TO + QUOTATION TITLE --}}
<div class="top-block">
    <div class="to-block">
        <div class="to-label">To :</div>
        <div class="to-name">{{ $quotation->customer_snapshot['name'] ?? '' }}</div>
        @if(!empty($quotation->customer_snapshot['address']))
        <div class="to-address">{{ $quotation->customer_snapshot['address'] }}</div>
        @endif
    </div>
    <div class="quo-block">
        <div class="quo-title">QUOTATION</div>
        <table class="quo-meta">
            <tr><td class="lbl">QUO NO.</td><td>:</td><td>{{ $quotation->number }}</td></tr>
            <tr><td class="lbl">QUO DATE</td><td>:</td><td>{{ $quotation->quotation_date->format('d/m/Y') }}</td></tr>
            <tr><td class="lbl">VALID UNTIL</td><td>:</td><td>{{ $quotation->valid_until->format('d/m/Y') }}</td></tr>
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
        <td class="field-label">Terms of <u>Delivery</u></td>
        <td class="colon">:</td>
        <td class="val">{{ strtoupper(config('operations.terms_of_delivery.'.$quotation->terms_of_delivery) ?? ($quotation->terms_of_delivery ?? 'EXW/FOB/DDP/DAP/DDU/CIF/CIP/CFR/D2D')) }}</td>
    </tr>
    <tr>
        <td class="field-label">Quantity</td>
        <td class="colon">:</td>
        <td>{{ $quotation->cargo_qty ?? $quotation->subject }}</td>
    </tr>
    <tr>
        @php $totalWeight = $quotation->items->sum(fn($i) => (float)($i->gross_weight ?? 0));
             $totalVolume = $quotation->items->sum(fn($i) => (float)($i->volume ?? 0)); @endphp
        <td class="field-label">Weight/Meas</td>
        <td class="colon">:</td>
        <td>{{ $quotation->weight_meas ?? (($totalWeight > 0 ? number_format($totalWeight,2).' kg' : '—').($totalVolume > 0 ? ' / '.$totalVolume.' m³' : '')) }}</td>
    </tr>
    <tr>
        <td class="field-label">Commodity</td>
        <td class="colon">:</td>
        <td>{{ $quotation->commodity ?? 'General Cargo' }}</td>
    </tr>
    <tr>
        <td class="field-label">Port of Loading</td>
        <td class="colon">:</td>
        <td>{{ $quotation->origin ?? '—' }}</td>
    </tr>
    <tr>
        <td class="field-label">Port of <u>Discharge</u></td>
        <td class="colon">:</td>
        <td>{{ $quotation->destination ?? '—' }}</td>
    </tr>
</table>

<div class="intro">We are pleased to quote you the <u>following :</u></div>

{{-- ITEMS TABLE --}}
<table class="items">
    <thead>
        <tr>
            <th style="width:28px;">NO</th>
            <th>CHARGES DESCRIPTION</th>
            <th style="width:36px;">CUR</th>
            <th style="width:80px;">PRICE</th>
            <th style="width:36px;">QTY</th>
            <th style="width:60px;">UNIT</th>
            <th style="width:60px;">EXC.RATE</th>
            <th style="width:90px;">AMOUNT IDR</th>
            <th style="width:85px;">NOTE</th>
        </tr>
    </thead>
    <tbody>
        @foreach($quotation->items as $item)
        @php
            $currency = strtoupper($item->currency ?? 'IDR');
            $excRate = ($currency !== 'IDR' && $item->exchange_rate && $item->exchange_rate != 1)
                       ? \App\Support\Money::format($item->exchange_rate) : '';

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
                    $truckParts[] = $pCurr . ' ' . \App\Support\Money::format($item->pricing_snapshot['price'] ?? 0) . ' @ kurs ' . \App\Support\Money::format($item->pricing_snapshot['exchange_rate'] ?? 1);
                }
                $subText = 'Tarif trucking' . (!empty($truckParts) ? ' · ' . implode(' · ', $truckParts) : '');
            } elseif ($item->container_type) {
                $parts = [strtoupper($item->container_type)];
                if ($item->overweight) {
                    $parts[] = 'OVERWEIGHT';
                }
                if ($item->gross_weight !== null && $item->gross_weight !== '') {
                    $parts[] = 'BW ' . \App\Support\Money::format($item->gross_weight) . ' kg';
                }
                if ($item->volume !== null && $item->volume !== '') {
                    $parts[] = $item->volume . ' m³';
                }
                $subText = implode(' · ', $parts);
            }
        @endphp
        <tr>
            <td class="center">{{ $loop->iteration }}</td>
            <td>
                {{ strtoupper($item->description) }}
                @if($subText)
                    <br/><span style="font-size:8.5px;color:#555;">{{ $subText }}</span>
                @endif
            </td>
            <td class="center">{{ $currency }}</td>
            <td class="right">{{ \App\Support\Money::format($item->unit_price) }}</td>
            <td class="center">{{ \App\Support\Money::format($item->quantity) }}</td>
            <td class="center">{{ $item->unit }}</td>
            <td class="center">{{ $excRate }}</td>
            <td class="right">{{ \App\Support\Money::format($item->total_price) }}</td>
            <td style="font-size:8.5px;">{{ $item->note ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- TOTALS --}}
<div class="totals-wrap">
    <table class="totals-table">
        <tr>
            <td class="lbl">Sub Total</td>
            <td class="amt">IDR &nbsp;{{ \App\Support\Money::format($quotation->subtotal) }}</td>
        </tr>
        @if(($quotation->discount ?? 0) > 0)
        <tr>
            <td class="lbl">Discount</td>
            <td class="amt">IDR &nbsp;{{ \App\Support\Money::format($quotation->discount) }}</td>
        </tr>
        @endif
        @if(($quotation->tax_rate ?? 0) > 0)
        <tr>
            <td class="lbl">PPN {{ \App\Support\Money::format($quotation->tax_rate) }}%</td>
            <td class="amt">IDR &nbsp;{{ \App\Support\Money::format($quotation->tax_amount) }}</td>
        </tr>
        @endif
        <tr class="grand">
            <td class="lbl">GRAND TOTAL</td>
            <td class="amt">IDR &nbsp;{{ \App\Support\Money::format($quotation->grand_total ?? $quotation->subtotal) }}</td>
        </tr>
    </table>
    <div class="clear"></div>
</div>

{{-- STANDARD REMARKS --}}
<div class="remarks">
    <div class="remarks-title">Remarks:</div>
    <ol>
        <li>All fees and charges listed must be paid in IDR (Indonesian Rupiah) according to the Invoice date and subject to PPN 1.1%</li>
        <li>Shipping is not insured unless specifically requested by the customer.</li>
        <li>Importers or exporters are required to prepare NPWP/NIB and other supporting documents to complete the order.</li>
        <li>Term of Payment and penalty for late charges will be implemented according to the general Term and condition.</li>
        <li>Every Cancellation shipment is required to pay a Cancellation Fee if any.</li>
        <li>Payment is made 7 days normally from the invoice received by the customer.</li>
        <li>All prices above are valid according to the available valid date and are not binding if it has passed.</li>
    </ol>
</div>

{{-- CUSTOM REMARKS (from notes field) --}}
@if($quotation->notes)
<div class="custom-remarks">
    <div class="cr-label">REMARKS :</div>
    <div>{{ $quotation->notes }}</div>
</div>
@endif

{{-- CLOSING --}}
<div class="closing">
    Will be happy to assist your shipment and for further information you may need please do not hesitate to contact us.
</div>

{{-- SIGNATURE --}}
<div class="sign">
    <p style="margin:0 0 3px 0;font-size:9px;">Yours Faithfully,</p>
    @php $salesUser = $quotation->sales ?? $quotation->creator; @endphp
    <div class="sign-logo">
        <img src="{{ public_path('images/logo.png') }}" alt="RDX">
    </div>
    <div class="sign-name">{{ strtoupper($salesUser?->name ?? '') }}</div>
    <div class="sign-role">MARKETING</div>
    @if($salesUser?->email)
    <div class="sign-contact">Mobile : &nbsp; &nbsp;</div>
    <div class="sign-contact">Email : {{ $salesUser->email }}</div>
    @endif
    <div class="sign-generated"><em>This is computer-generated does not require signature</em></div>
</div>

<div class="footer">Page <script type="text/php">if(isset($pdf)){echo $PAGE_NUM.' of '.$PAGE_COUNT;}</script></div>
</body>
</html>
