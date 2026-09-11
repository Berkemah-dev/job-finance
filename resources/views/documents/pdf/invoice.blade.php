<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        @page {
            margin: 24px 30px 24px 30px;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            color: #000;
            font-size: 9.5px;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 8px;
        }
        .header-logo {
            float: left;
            width: 45%;
        }
        .header-logo img {
            max-width: 190px;
            max-height: 65px;
        }
        .header-company {
            float: right;
            width: 53%;
            text-align: right;
            font-size: 9.5px;
            line-height: 1.45;
        }
        .header-company .company-name {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 2px;
        }
        .header-company .company-label {
            text-decoration: underline;
        }
        .clear {
            clear: both;
        }
        .doc-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 6px 0 10px 0;
            letter-spacing: 1px;
        }
        .doc-title u {
            text-underline-offset: 3px;
        }
        .bill-meta-section {
            margin-bottom: 10px;
        }
        .bill-to-col {
            float: left;
            width: 48%;
            font-size: 9.5px;
        }
        .bill-to-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 3px;
        }
        .bill-to-name {
            font-weight: bold;
            font-size: 10px;
        }
        .bill-to-address {
            font-size: 9px;
            color: #222;
        }
        .meta-col {
            float: right;
            width: 50%;
            text-align: right;
        }
        .meta-table {
            border-collapse: collapse;
            margin-left: auto;
            font-size: 9.5px;
        }
        .meta-table td {
            padding: 1px 2px;
            white-space: nowrap;
        }
        .meta-table td.lbl {
            font-weight: bold;
            text-decoration: underline;
        }
        .big-amount-box {
            margin-top: 8px;
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .shipment-details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9.5px;
        }
        .shipment-details td {
            padding: 1.5px 0;
            vertical-align: top;
        }
        .shipment-details td.field-lbl {
            font-weight: bold;
            text-decoration: underline;
            white-space: nowrap;
        }
        .shipment-details td.colon {
            width: 10px;
            text-align: center;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 9px;
            border: 1px solid #000;
        }
        table.items-table th {
            background-color: #dddddd;
            border: 1px solid #000;
            padding: 5px 3px;
            text-align: center;
            font-weight: bold;
            font-size: 9px;
        }
        table.items-table td {
            border: 1px solid #000;
            padding: 3.5px 4px;
            vertical-align: top;
        }
        table.items-table td.center {
            text-align: center;
        }
        table.items-table td.right {
            text-align: right;
            white-space: nowrap;
        }
        table.items-table tr.section-header td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            background: #fafafa;
        }
        table.items-table tr.subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        table.items-table tr.summary-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .rate-said-box {
            margin-top: 6px;
            margin-bottom: 12px;
            font-size: 9.5px;
            line-height: 1.5;
        }
        .rate-line {
            font-weight: bold;
            margin-bottom: 3px;
        }
        .said-line {
            font-style: italic;
        }
        .said-line u {
            font-weight: bold;
            font-style: normal;
        }
        .bottom-section {
            margin-top: 8px;
        }
        .payment-box {
            float: left;
            width: 60%;
            font-size: 9px;
            line-height: 1.45;
        }
        .payment-title {
            font-weight: bold;
            margin-bottom: 3px;
        }
        .payment-box .bank-detail {
            margin-top: 2px;
        }
        .signature-box {
            float: right;
            width: 35%;
            text-align: center;
            margin-top: 25px;
            font-size: 9.5px;
        }
        .sign-line {
            border-top: 1px solid #000;
            width: 85%;
            margin: 40px auto 4px auto;
        }
        .sign-label {
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: -6px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 2px;
        }
    </style>
</head>
<body>

@php
    $job = $invoice->job;
    $quotation = $job?->quotation;
    $charges = $invoice->items->where('type', 'provision');
    $reimbursements = $invoice->items->where('type', 'temporary');

    $chargesAmount = (float) $charges->sum('amount');
    $reimbursementsAmount = (float) $reimbursements->sum('amount');

    // Tax calculation
    $totalTax = (float) $invoice->tax;
    $chargesTax = $totalTax; // Tax is applied to charges
    $chargesTotal = $chargesAmount + $chargesTax;

    $reimbursementsTax = 0;
    $reimbursementsTotal = $reimbursementsAmount;

    $subTotalAmount = $chargesAmount + $reimbursementsAmount;
    $subTotalVat = $totalTax;
    $subTotalSum = $subTotalAmount + $subTotalVat;

    // Materai: if total is set or if difference between invoice total and subtotal + tax exists
    $materai = max(0, (float)$invoice->total - ($subTotalAmount + $subTotalVat));
@endphp

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

{{-- TITLE --}}
<div class="doc-title"><u>INVOICE</u></div>

{{-- BILL TO & INVOICE META --}}
<div class="bill-meta-section">
    <div class="bill-to-col">
        <div class="bill-to-title">BILL TO :</div>
        <div class="bill-to-name">{{ $invoice->customer_snapshot['name'] ?? $job?->customer?->name ?? '—' }}</div>
        @if(!empty($invoice->customer_snapshot['address']))
            <div class="bill-to-address">{{ $invoice->customer_snapshot['address'] }}</div>
        @elseif(!empty($job?->customer?->address))
            <div class="bill-to-address">{{ $job->customer->address }}</div>
        @endif
        @if(!empty($invoice->customer_snapshot['tax_number']))
            <div class="bill-to-address">NPWP: {{ $invoice->customer_snapshot['tax_number'] }}</div>
        @endif
    </div>
    <div class="meta-col">
        <table class="meta-table">
            <tr>
                <td class="lbl">Inv. No</td><td>:</td><td>{{ $invoice->number }}</td>
                <td style="padding-left:10px;" class="lbl">Inv. Date</td><td>:</td><td>{{ $invoice->invoice_date->format('d-m-Y') }}</td>
                <td style="padding-left:10px;" class="lbl">Due. Date</td><td>:</td><td>{{ $invoice->due_date->format('d-m-Y') }}</td>
            </tr>
        </table>
        <div class="big-amount-box">
            {{ $invoice->currency }}. {{ number_format((float) ($invoice->currency === 'IDR' ? $invoice->total : $invoice->inInvoiceCurrency('total')), 2, '.', ',') }}
        </div>
    </div>
    <div class="clear"></div>
</div>

{{-- SHIPMENT DETAILS (2 COLUMNS) --}}
<table class="shipment-details">
    <tr>
        {{-- Left Column --}}
        <td style="width: 14%;" class="field-lbl">JOB. NO</td>
        <td class="colon">:</td>
        <td style="width: 34%;">{{ $job?->number ?? '—' }}</td>

        {{-- Right Column --}}
        <td style="width: 14%;" class="field-lbl">POL</td>
        <td class="colon">:</td>
        <td style="width: 34%;">{{ strtoupper($job?->pol ?? $job?->origin ?? $quotation?->origin ?? '—') }}</td>
    </tr>
    <tr>
        <td class="field-lbl">NO. HAWB</td>
        <td class="colon">:</td>
        <td>{{ $job?->hawb_number ?? $job?->hbl_number ?? '—' }}</td>

        <td class="field-lbl">POD</td>
        <td class="colon">:</td>
        <td>{{ strtoupper($job?->pod ?? $job?->destination ?? $quotation?->destination ?? '—') }}</td>
    </tr>
    <tr>
        <td class="field-lbl">NO. MAWB</td>
        <td class="colon">:</td>
        <td>{{ $job?->mawb_number ?? $job?->bl_number ?? $job?->awb_number ?? '—' }}</td>

        <td class="field-lbl">ETD</td>
        <td class="colon">:</td>
        <td>{{ $job?->etd ? $job->etd->format('d-m-Y') : '—' }}</td>
    </tr>
    <tr>
        <td class="field-lbl">NO. AJU</td>
        <td class="colon">:</td>
        <td>{{ $job?->booking_reference ?? '—' }}</td>

        <td class="field-lbl">ETA</td>
        <td class="colon">:</td>
        <td>{{ $job?->eta ? $job->eta->format('d-m-Y') : '—' }}</td>
    </tr>
    <tr>
        <td class="field-lbl">SHIPPER</td>
        <td class="colon">:</td>
        <td>{{ $job?->shipper_name ?? $invoice->customer_snapshot['name'] ?? '—' }}</td>

        <td class="field-lbl">QUANTITY</td>
        <td class="colon">:</td>
        <td>
            @if($job?->package_count)
                {{ $job->package_count }} Box
            @elseif($job?->container_type)
                1x {{ strtoupper($job->container_type) }}
            @else
                —
            @endif
        </td>
    </tr>
    <tr>
        <td class="field-lbl">CONSIGNEE</td>
        <td class="colon">:</td>
        <td>{{ $job?->consignee_name ?? $invoice->customer_snapshot['name'] ?? '—' }}</td>

        <td class="field-lbl">WEIGHT</td>
        <td class="colon">:</td>
        <td>{{ $job?->gross_weight ? number_format((float)$job->gross_weight, 2, '.', ',') . ' KGS' : '—' }}</td>
    </tr>
    <tr>
        <td class="field-lbl">REMARKS</td>
        <td class="colon">:</td>
        <td>{{ $job?->notes ?? $quotation?->subject ?? '—' }}</td>

        <td class="field-lbl">VOLUME</td>
        <td class="colon">:</td>
        <td>{{ $job?->volume ? number_format((float)$job->volume, 3, '.', ',') . ' M3' : '—' }}</td>
    </tr>
</table>

{{-- ITEMS TABLE --}}
<table class="items-table">
    <thead>
        <tr>
            <th style="width: 4%;">NO</th>
            <th style="width: 29%;">DESCRIPTION</th>
            <th style="width: 6%;">QTY</th>
            <th style="width: 6%;">CUR</th>
            <th style="width: 13%;">PRICE</th>
            <th style="width: 14%;">AMOUNT</th>
            <th style="width: 13%;">VAT</th>
            <th style="width: 15%;">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp

        {{-- CHARGES SECTION --}}
        @if($charges->isNotEmpty())
            <tr class="section-header">
                <td></td>
                <td colspan="7"><strong>CHARGES</strong></td>
            </tr>
            @foreach($charges as $item)
                @php
                    $itemAmount = (float) $item->amount;
                    // Proportional VAT if applicable
                    $itemVat = $chargesAmount > 0 ? ($itemAmount / $chargesAmount) * $chargesTax : 0;
                    $itemTotal = $itemAmount + $itemVat;
                @endphp
                <tr>
                    <td class="center">{{ $no++ }}.</td>
                    <td>{{ strtoupper($item->description) }}</td>
                    <td class="center">{{ (float)$item->quantity == (int)$item->quantity ? (int)$item->quantity : number_format((float)$item->quantity, 2, '.', ',') }}</td>
                    <td class="center">{{ $invoice->currency }}</td>
                    <td class="right">{{ number_format((float)$item->unit_price, 2, '.', ',') }}</td>
                    <td class="right">{{ number_format($itemAmount, 2, '.', ',') }}</td>
                    <td class="right">{{ number_format($itemVat, 2, '.', ',') }}</td>
                    <td class="right">{{ number_format($itemTotal, 2, '.', ',') }}</td>
                </tr>
            @endforeach
            <tr class="subtotal-row">
                <td></td>
                <td colspan="4"><strong>TOTAL CHARGES</strong></td>
                <td class="right"><strong>{{ number_format($chargesAmount, 2, '.', ',') }}</strong></td>
                <td class="right"><strong>{{ number_format($chargesTax, 2, '.', ',') }}</strong></td>
                <td class="right"><strong>{{ number_format($chargesTotal, 2, '.', ',') }}</strong></td>
            </tr>
        @endif

        {{-- REIMBURSEMENT SECTION --}}
        @if($reimbursements->isNotEmpty())
            <tr class="section-header">
                <td></td>
                <td colspan="7"><strong>REIMBURSMENT</strong></td>
            </tr>
            @foreach($reimbursements as $item)
                @php
                    $itemAmount = (float) $item->amount;
                    $itemVat = 0.00;
                    $itemTotal = $itemAmount;
                @endphp
                <tr>
                    <td class="center">{{ $no++ }}.</td>
                    <td>{{ strtoupper($item->description) }}</td>
                    <td class="center">{{ (float)$item->quantity == (int)$item->quantity ? (int)$item->quantity : number_format((float)$item->quantity, 2, '.', ',') }}</td>
                    <td class="center">{{ $invoice->currency }}</td>
                    <td class="right">{{ number_format((float)$item->unit_price, 2, '.', ',') }}</td>
                    <td class="right">{{ number_format($itemAmount, 2, '.', ',') }}</td>
                    <td class="right">0.00</td>
                    <td class="right">{{ number_format($itemTotal, 2, '.', ',') }}</td>
                </tr>
            @endforeach
        @endif

        {{-- SUB TOTAL ROW --}}
        <tr class="summary-row">
            <td colspan="5" style="text-align: right;"><strong>SUB TOTAL</strong></td>
            <td class="right"><strong>{{ number_format($subTotalAmount, 2, '.', ',') }}</strong></td>
            <td class="right"><strong>{{ number_format($subTotalVat, 2, '.', ',') }}</strong></td>
            <td class="right"><strong>{{ number_format($subTotalSum, 2, '.', ',') }}</strong></td>
        </tr>

        {{-- MATERAI ROW --}}
        @if($materai > 0)
        <tr class="summary-row">
            <td colspan="5" style="text-align: right;"><strong>MATERAI</strong></td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right"><strong>{{ number_format($materai, 2, '.', ',') }}</strong></td>
        </tr>
        @endif

        {{-- GRAND TOTAL ROW --}}
        <tr class="summary-row" style="background-color: #f5f5f5;">
            <td colspan="5" style="text-align: right; font-size: 10px;"><strong>TOTAL</strong></td>
            <td class="right"></td>
            <td class="right"></td>
            <td class="right" style="font-size: 10px;"><strong>{{ number_format((float)$invoice->total, 2, '.', ',') }}</strong></td>
        </tr>
    </tbody>
</table>

{{-- RATE & TERBILANG --}}
<div class="rate-said-box">
    <div class="rate-line">
        EXC. RATE : {{ number_format((float)$invoice->exchange_rate, 2, '.', ',') }}
    </div>
    <div class="said-line">
        <u>Said :</u> <strong>{{ \App\Support\Money::terbilang($invoice->total, $invoice->currency) }}</strong>
    </div>
</div>

{{-- PAYMENT INFO & SIGNATURE --}}
<div class="bottom-section">
    <div class="payment-box">
        <div class="payment-title">INFORMASI PEMBAYARAN/PAYMENT DETAILS:</div>
        <div>NAMA AKUN/ACCOUNT NAME : <strong>PT RADIX INTERNATIONAL LOGISTICS</strong></div>
        <div class="bank-detail">1. <span style="text-decoration: underline;">NAMA BANK/BANK DETAILS</span> : <strong>BANK CENTRAL ASIA - CENGKEH</strong></div>
        <div style="padding-left: 16px;"><span style="text-decoration: underline;">NOMOR REKENING/ACCOUNT NUMBER</span> : <strong>240-0375-758</strong></div>
        <div class="bank-detail">2. <span style="text-decoration: underline;">NAMA BANK/BANK DETAILS</span> : <strong>BANK MANDIRI - JAKARTA KOTA</strong></div>
        <div style="padding-left: 16px;"><span style="text-decoration: underline;">NOMOR REKENING/ACCOUNT NUMBER</span> : <strong>115-00-1053704-3</strong></div>
    </div>
    <div class="signature-box">
        <div class="sign-line"></div>
        <div class="sign-label">Authorized Signature</div>
    </div>
    <div class="clear"></div>
</div>

{{-- FOOTER --}}
<div class="footer">Page <script type="text/php">if(isset($pdf)){echo $PAGE_NUM.' of '.$PAGE_COUNT;}</script></div>

</body>
</html>
