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
            font-weight: bold;
        }
        .clear {
            clear: both;
        }
        .doc-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 8px 0 12px 0;
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
            width: 48%;
            text-align: right;
        }
        .meta-table {
            border-collapse: collapse;
            margin-left: auto;
            font-size: 9.5px;
            width: 250px;
        }
        .meta-table td {
            padding: 1.5px 0;
            text-align: left;
            white-space: nowrap;
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
            white-space: nowrap;
        }
        .shipment-details td.colon {
            width: 10px;
            text-align: center;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 9px;
        }
        table.items-table th {
            background-color: #dddddd;
            border: 1px solid #000;
            padding: 4px 3px;
            text-align: center;
            font-weight: bold;
            font-size: 9px;
        }
        table.items-table td.col-cell {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-top: none;
            border-bottom: none;
            padding: 2.5px 4px;
            vertical-align: top;
        }
        table.items-table td.center {
            text-align: center;
        }
        table.items-table td.right {
            text-align: right;
            white-space: nowrap;
        }
        table.items-table tr.total-charges-row td {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 3px 4px;
            font-weight: bold;
        }
        table.items-table tr.compartment-bottom td {
            border-bottom: 1px solid #000;
        }
        table.items-table tr.summary-row td {
            padding: 2.5px 4px;
            font-weight: bold;
        }
        .rate-said-box {
            margin-top: 4px;
            margin-bottom: 10px;
            font-size: 9.5px;
            line-height: 1.4;
        }
        .rate-line {
            font-weight: bold;
            margin-bottom: 2px;
        }
        .said-line {
            font-style: italic;
        }
        .bottom-section {
            margin-top: 6px;
        }
        .payment-box {
            float: left;
            width: 62%;
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

    $materai = (float) ($invoice->stamp_duty ?? 0);
@endphp

{{-- HEADER --}}
@include('documents.pdf.partials.letterhead')

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
                <td style="white-space: nowrap;"><nobr><strong>Inv. No :</strong> {{ $invoice->number }}</nobr></td>
            </tr>
            <tr>
                <td style="padding-left: 20px; white-space: nowrap;"><nobr><strong>Inv. Date :</strong> {{ $invoice->invoice_date ? $invoice->invoice_date->format('d-m-Y') : '—' }}</nobr></td>
            </tr>
            <tr>
                <td style="padding-left: 20px; white-space: nowrap;"><nobr><strong>Due. Date :</strong> {{ $invoice->due_date ? $invoice->due_date->format('d-m-Y') : '—' }}</nobr></td>
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
            <th style="width: 30%;">DESCRIPTION</th>
            <th style="width: 6%;">QTY</th>
            <th style="width: 6%;">CUR</th>
            <th style="width: 13%;">PRICE</th>
            <th style="width: 14%;">AMOUNT</th>
            <th style="width: 13%;">VAT</th>
            <th style="width: 14%;">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp

        {{-- CHARGES SECTION --}}
        <tr>
            <td class="col-cell center"></td>
            <td class="col-cell" style="font-weight: bold;">CHARGES</td>
            <td class="col-cell center"></td>
            <td class="col-cell center"></td>
            <td class="col-cell right"></td>
            <td class="col-cell right"></td>
            <td class="col-cell right"></td>
            <td class="col-cell right"></td>
        </tr>

        @foreach($charges as $item)
            @php
                $itemAmount = (float) $item->amount;
                $itemVat = $chargesAmount > 0 ? ($itemAmount / $chargesAmount) * $chargesTax : 0;
                $itemTotal = $itemAmount + $itemVat;
                $itemCurrency = strtoupper($item->currency ?? 'IDR');
                $itemPrice = $item->foreign_unit_price ?? $item->unit_price;
            @endphp
            <tr>
                <td class="col-cell center">{{ $no++ }}.</td>
                <td class="col-cell">{{ strtoupper($item->description) }}</td>
                <td class="col-cell center">{{ (float)$item->quantity == (int)$item->quantity ? (int)$item->quantity : number_format((float)$item->quantity, 2, '.', ',') }}</td>
                <td class="col-cell center">{{ $itemCurrency }}</td>
                <td class="col-cell right">{{ number_format((float)$itemPrice, 2, '.', ',') }}</td>
                <td class="col-cell right">{{ number_format($itemAmount, 2, '.', ',') }}</td>
                <td class="col-cell right">{{ number_format($itemVat, 2, '.', ',') }}</td>
                <td class="col-cell right">{{ number_format($itemTotal, 2, '.', ',') }}</td>
            </tr>
        @endforeach

        @php
            $minChargesRows = 3;
            $emptyCharges = max(0, $minChargesRows - count($charges));
        @endphp
        @for($i = 0; $i < $emptyCharges; $i++)
            <tr>
                <td class="col-cell">&nbsp;</td>
                <td class="col-cell">&nbsp;</td>
                <td class="col-cell">&nbsp;</td>
                <td class="col-cell">&nbsp;</td>
                <td class="col-cell">&nbsp;</td>
                <td class="col-cell">&nbsp;</td>
                <td class="col-cell">&nbsp;</td>
                <td class="col-cell">&nbsp;</td>
            </tr>
        @endfor

        {{-- TOTAL CHARGES ROW --}}
        <tr class="total-charges-row">
            <td class="center"></td>
            <td style="font-weight: bold;">TOTAL CHARGES</td>
            <td class="center"></td>
            <td class="center"></td>
            <td class="right"></td>
            <td class="right">{{ number_format($chargesAmount, 2, '.', ',') }}</td>
            <td class="right">{{ number_format($chargesTax, 2, '.', ',') }}</td>
            <td class="right">{{ number_format($chargesTotal, 2, '.', ',') }}</td>
        </tr>

        {{-- REIMBURSMENT SECTION --}}
        <tr>
            <td class="col-cell center"></td>
            <td class="col-cell" style="font-weight: bold;">REIMBURSMENT</td>
            <td class="col-cell center"></td>
            <td class="col-cell center"></td>
            <td class="col-cell right"></td>
            <td class="col-cell right"></td>
            <td class="col-cell right"></td>
            <td class="col-cell right"></td>
        </tr>

        @foreach($reimbursements as $item)
            @php
                $itemAmount = (float) $item->amount;
                $itemVat = 0.00;
                $itemTotal = $itemAmount;
                $itemCurrency = strtoupper($item->currency ?? 'IDR');
                $itemPrice = $item->foreign_unit_price ?? $item->unit_price;
            @endphp
            <tr>
                <td class="col-cell center">{{ $no++ }}.</td>
                <td class="col-cell">{{ strtoupper($item->description) }}</td>
                <td class="col-cell center">{{ (float)$item->quantity == (int)$item->quantity ? (int)$item->quantity : number_format((float)$item->quantity, 2, '.', ',') }}</td>
                <td class="col-cell center">{{ $itemCurrency }}</td>
                <td class="col-cell right">{{ number_format((float)$itemPrice, 2, '.', ',') }}</td>
                <td class="col-cell right">{{ number_format($itemAmount, 2, '.', ',') }}</td>
                <td class="col-cell right">0.00</td>
                <td class="col-cell right">{{ number_format($itemTotal, 2, '.', ',') }}</td>
            </tr>
        @endforeach

        @php
            $minReimburseRows = 6;
            $emptyReimburse = max(0, $minReimburseRows - count($reimbursements));
        @endphp
        @for($i = 0; $i < $emptyReimburse; $i++)
            <tr @if($i === $emptyReimburse - 1) class="compartment-bottom" @endif>
                <td class="col-cell">&nbsp;</td>
                <td class="col-cell">&nbsp;</td>
                <td class="col-cell">&nbsp;</td>
                <td class="col-cell">&nbsp;</td>
                <td class="col-cell">&nbsp;</td>
                <td class="col-cell">&nbsp;</td>
                <td class="col-cell">&nbsp;</td>
                <td class="col-cell">&nbsp;</td>
            </tr>
        @endfor

        {{-- SUMMARY ROWS (RIGHT-ALIGNED 4 COLUMNS BOX) --}}
        <tr class="summary-row">
            <td colspan="4" style="border: none; background: transparent;"></td>
            <td style="border: 1px solid #000; text-align: center;">SUB TOTAL</td>
            <td class="right" style="border: 1px solid #000;">{{ number_format($subTotalAmount, 2, '.', ',') }}</td>
            <td class="right" style="border: 1px solid #000;">{{ number_format($subTotalVat, 2, '.', ',') }}</td>
            <td class="right" style="border: 1px solid #000;">{{ number_format($subTotalSum, 2, '.', ',') }}</td>
        </tr>
        <tr class="summary-row">
            <td colspan="4" style="border: none; background: transparent;"></td>
            <td style="border: 1px solid #000; text-align: center;">MATERAI</td>
            <td style="border: 1px solid #000;"></td>
            <td style="border: 1px solid #000;"></td>
            <td class="right" style="border: 1px solid #000;">{{ number_format($materai, 2, '.', ',') }}</td>
        </tr>
        <tr class="summary-row">
            <td colspan="4" style="border: none; background: transparent;"></td>
            <td style="border: 1px solid #000; text-align: center;">TOTAL</td>
            <td style="border: 1px solid #000;"></td>
            <td style="border: 1px solid #000;"></td>
            <td class="right" style="border: 1px solid #000;">{{ number_format((float)$invoice->total, 2, '.', ',') }}</td>
        </tr>
    </tbody>
</table>

{{-- RATE & TERBILANG --}}
@php
    $itemExchangeRates = $invoice->items
        ->filter(fn ($item) => strtoupper((string) ($item->currency ?? 'IDR')) !== 'IDR')
        ->map(fn ($item) => number_format((float) ($item->exchange_rate ?: 1), 2, '.', ','))
        ->unique()
        ->values();
@endphp
<div class="rate-said-box">
    <div class="rate-line">
        EXC. RATE : {{ $itemExchangeRates->isEmpty() ? '1.00' : $itemExchangeRates->implode(' · ') }}
    </div>
    <div class="said-line">
        Said : <strong>{{ \App\Support\Money::terbilang($invoice->total, $invoice->currency) }}</strong>
    </div>
</div>

{{-- PAYMENT INFO & SIGNATURE --}}
<div class="bottom-section">
    <div class="payment-box">
        <div class="payment-title">INFORMASI PEMBAYARAN/PAYMENT DETAILS:</div>
        <div>NAMA AKUN/ACCOUNT NAME : <strong>PT RADIX INTERNATIONAL LOGISTICS</strong></div>
        <div class="bank-detail">1. NAMA BANK/BANK DETAILS : <strong>BANK CENTRAL ASIA - CENGKEH</strong></div>
        <div style="padding-left: 16px;">NOMOR REKENING/ACCOUNT NUMBER : <strong>240-0375-758</strong></div>
        <div class="bank-detail">2. NAMA BANK/BANK DETAILS : <strong>BANK MANDIRI - JAKARTA KOTA</strong></div>
        <div style="padding-left: 16px;">NOMOR REKENING/ACCOUNT NUMBER : <strong>115-00-1053704-3</strong></div>
    </div>
    <div class="signature-box">
        <div class="sign-line"></div>
        <div class="sign-label">Authorized Signature</div>
    </div>
    <div class="clear"></div>
</div>

</body>
</html>
