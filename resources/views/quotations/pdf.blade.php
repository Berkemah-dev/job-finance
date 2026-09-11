<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->number }}</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 10pt; color: #000; margin: 0; padding: 10px 30px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        
        .logo-text { font-family: 'Arial', sans-serif; font-size: 28pt; font-weight: bold; letter-spacing: -1px; }
        .logo-rdx-r { color: #cc0000; }
        .logo-rdx-dx { color: #000066; }
        .logo-logistics { color: #000066; font-size: 18pt; letter-spacing: 0; margin-left: 5px; }
        
        .company-info { font-size: 9pt; }
        
        .page-title { font-family: 'Times New Roman', Times, serif; font-size: 22pt; font-weight: bold; letter-spacing: 1px; margin-bottom: 5px; }
        
        .doc-info-table td { padding: 1px 0; }
        
        .shipment-details td { padding: 1px 0; }
        .shipment-label { font-weight: bold; width: 150px; }
        
        .items-table { width: 100%; border: 1px solid #000; margin-top: 15px; margin-bottom: 20px; font-size: 9pt; }
        .items-table th, .items-table td { border: 1px solid #000; padding: 4px; }
        .items-table th { background-color: #e6e6e6; text-align: center; font-weight: bold; }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }

        .remarks-list { margin: 5px 0 15px 0; padding-left: 0; list-style-type: none; }
        .remarks-list li { margin-bottom: 2px; position: relative; padding-left: 15px; }
        .remarks-list li .num { position: absolute; left: 0; }
        
        .footer { margin-top: 30px; line-height: 1.3; }
        .signature-logo { font-family: 'Arial', sans-serif; font-size: 24pt; font-weight: bold; letter-spacing: -1px; margin: 10px 0; }
    </style>
</head>
<body>
    <table style="margin-bottom: 40px;">
        <tr>
            <td style="width: 55%;">
                <div class="logo-text"><span class="logo-rdx-r">R</span><span class="logo-rdx-dx">DX</span><span class="logo-logistics">LOGISTICS</span></div>
            </td>
            <td style="width: 45%;" class="company-info">
                <strong>PT.RADIX INTERNATIONAL LOGISTICS</strong><br>
                <table style="width: 100%; margin-top: 2px;">
                    <tr><td style="width: 60px;">Address</td><td style="width: 10px;">:</td><td>Jl.Teh No 3C Tamansari Pinangsia<br>Jakarta Barat Indonesia 11110</td></tr>
                    <tr><td>Telp</td><td>:</td><td>021-38873060</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table style="margin-bottom: 25px;">
        <tr>
            <td style="width: 60%;">
                <strong style="text-transform: uppercase;">To : {{ $quotation->customer_snapshot['name'] ?? '' }}</strong><br>
                <div style="text-transform: uppercase; margin-top: 2px;">
                    {!! nl2br(e($quotation->customer_snapshot['address'] ?? '')) !!}
                    @if(!empty($quotation->customer_snapshot['contact_name']))
                    <br>ATTN: {{ $quotation->customer_snapshot['contact_name'] }}
                    @endif
                </div>
            </td>
            <td style="width: 40%;">
                <div class="page-title">QUOTATION</div>
                <table class="doc-info-table">
                    <tr><td style="width: 90px; font-weight: bold;">QUO NO.</td><td style="width: 10px;">:</td><td>{{ $quotation->number }}</td></tr>
                    <tr><td style="font-weight: bold;">QUO DATE</td><td>:</td><td>{{ $quotation->quotation_date->format('d M Y') }}</td></tr>
                    <tr><td style="font-weight: bold;">VALID UNTIL</td><td>:</td><td>{{ $quotation->valid_until->format('d M Y') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="shipment-details">
        <tr><td class="shipment-label">Services</td><td style="width: 10px;">:</td><td>{{ config('operations.service_types.'.$quotation->service_type) ?? $quotation->service_type ?? '-' }}</td></tr>
        <tr><td class="shipment-label">Terms of Delivery</td><td>:</td><td>{{ config('operations.customer_payment_terms.'.$quotation->payment_terms) ?? $quotation->payment_terms ?? '-' }}</td></tr>
        <tr><td class="shipment-label">Quantity</td><td>:</td><td>-</td></tr>
        <tr><td class="shipment-label">Weight/Meas</td><td>:</td><td>-</td></tr>
        <tr><td class="shipment-label">Commodity</td><td>:</td><td>-</td></tr>
        <tr><td class="shipment-label">Port of Loading</td><td>:</td><td>{{ $quotation->origin ?? '-' }}</td></tr>
        <tr><td class="shipment-label">Port of Discharge</td><td>:</td><td>{{ $quotation->destination ?? '-' }}</td></tr>
    </table>

    <div style="margin-top: 15px;">
        We are pleased to quote you the following :
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30px;">NO</th>
                <th>CHARGES DESCRIPTION</th>
                <th style="width: 35px;">CUR</th>
                <th style="width: 80px;">PRICE</th>
                <th style="width: 35px;">QTY</th>
                <th style="width: 50px;">UNIT</th>
                <th style="width: 60px;">EXC.RATE</th>
                <th style="width: 90px;">AMOUNT IDR</th>
                <th style="width: 60px;">NOTE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $index => $item)
            @php
                $amountIdr = $quotation->currency === 'IDR' ? $item->total_price : bcmul((string)$item->total_price, (string)($quotation->exchange_rate ?: 1), 2);
                $excRate = $quotation->currency === 'IDR' ? '' : ($quotation->exchange_rate ?: 1);
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    {{ $item->description }}
                    @if($item->container_type)
                    <br><small>{{ strtoupper($item->container_type) }} @if($item->overweight) · Overweight @endif</small>
                    @endif
                </td>
                <td class="text-center">{{ $quotation->currency ?? 'IDR' }}</td>
                <td class="text-right">{{ \App\Support\Money::format($item->unit_price) }}</td>
                <td class="text-center">{{ \App\Support\Money::format($item->quantity) }}</td>
                <td class="text-center">{{ $item->unit }}</td>
                <td class="text-center">{{ $excRate ? \App\Support\Money::format($excRate) : '' }}</td>
                <td class="text-right">{{ \App\Support\Money::format($amountIdr) }}</td>
                <td></td>
            </tr>
            @endforeach
            
            @if($quotation->discount > 0)
            <tr>
                <td colspan="7" class="text-right" style="border-right: none;">Discount:</td>
                <td class="text-right" style="border-left: none;">- {{ \App\Support\Money::format($quotation->discount) }}</td>
                <td></td>
            </tr>
            @endif
            @if($quotation->tax_rate > 0)
            <tr>
                <td colspan="7" class="text-right" style="border-right: none;">PPN ({{ \App\Support\Money::format($quotation->tax_rate) }}%):</td>
                <td class="text-right" style="border-left: none;">{{ \App\Support\Money::format($quotation->tax_amount) }}</td>
                <td></td>
            </tr>
            @endif
            <tr>
                <td colspan="7" class="text-right" style="border-right: none; font-weight: bold;">Grand Total:</td>
                <td class="text-right" style="border-left: none; font-weight: bold;">{{ \App\Support\Money::format($quotation->grand_total) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div style="font-weight: bold;">Remarks:</div>
    <ul class="remarks-list">
        <li><span class="num">1.</span>All fees and charges listed must be paid in IDR (Indonesian Rupiah) according to the Invoice date and subject to PPN 1.1%</li>
        <li><span class="num">2.</span>Shipping is not insured unless specifically requested by the customer.</li>
        <li><span class="num">3.</span>Importers or exporters are required to prepare NPWP/NIB and other supporting documents to complete the order.</li>
        <li><span class="num">4.</span>Term of Payment and penalty for late charges will be implemented according to the general Term and condition.</li>
        <li><span class="num">5.</span>Every Cancellation shipment is required to pay a Cancellation Fee if any.</li>
        <li><span class="num">6.</span>Payment is made {{ preg_match('/\d+/', $quotation->payment_terms ?? '', $m) ? $m[0] : '7' }} days normally from the invoice received by the customer.</li>
        <li><span class="num">7.</span>All prices above are valid according to the available valid date and are not binding if it has passed.</li>
    </ul>

    <div style="text-transform: uppercase;">
        <span style="color: #cc0000;">REMARKS :</span><br>
        <span style="color: #cc0000;">{!! $quotation->notes ? nl2br(e($quotation->notes)) : 'TIDAK ADA' !!}</span>
    </div>

    <div class="footer">
        Will be happy to assist your shipment and for further information you may need please do not hesitate to contact us.<br><br>
        Yours Faithfully,<br>
        <div class="signature-logo"><span class="logo-rdx-r">R</span><span class="logo-rdx-dx">DX</span></div>
        <span style="text-decoration: underline; font-weight: bold; text-transform: uppercase; color: #cc0000;">{{ $quotation->creator?->name ?? 'NAMA SALES' }}</span><br>
        MARKETING<br>
        Mobile : {{ $quotation->creator?->phone ?? '-' }}<br>
        Email : {{ $quotation->creator?->email ?? '-' }}<br><br>
        <i style="color: #666; font-size: 8pt;">This is computer-generated does not require signature</i>
    </div>
</body>
</html>
