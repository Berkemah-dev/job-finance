@extends('layouts.app')
@section('title', 'Detail Invoice ' . $invoice->number)
@section('content')

@php
    $job = $invoice->job;
    $quotation = $job?->quotation;
    $charges = $invoice->items->where('type', 'provision');
    $reimbursements = $invoice->items->where('type', 'temporary');
    $chargesAmount = (float) $charges->sum('amount');
    $reimbursementAmount = (float) $reimbursements->sum('amount');
    $chargesTax = (float) $invoice->tax;
    $chargesTotal = $chargesAmount + $chargesTax;
    $hasForeignCurrency = $invoice->currency !== 'IDR' && (float)$invoice->exchange_rate > 0;
@endphp

<div class="page-heading">
    <div>
        <p class="eyebrow">FINANCE / INVOICE</p>
        <h1>{{ $invoice->number }}</h1>
        <p>{{ $invoice->customer_snapshot['name'] }} · Job: <a class="text-link" href="{{ route('jobs.show', $job) }}">{{ $job?->number ?? '—' }}</a></p>
    </div>
    <a class="button button-secondary" href="{{ route('invoices.index') }}">← Kembali</a>
</div>

<div class="quote-actions" style="margin-bottom: 20px;">
    @if($invoice->status !== 'paid')
        <button type="button" class="button button-primary" onclick="document.getElementById('modal-add-payment').showModal()">+ Add Payment</button>
    @endif
    <a class="button button-secondary" href="{{ route('invoices.preview', $invoice) }}" target="_blank">🖨 Preview PDF</a>
    @if((float) $invoice->tax > 0)
        <a class="button button-secondary" href="{{ route('invoices.coretax', $invoice) }}">Ekspor XML Coretax</a>
    @endif
    <a class="button button-secondary" href="{{ route('jobs.show', $job) }}">Lihat Job Order</a>
    @can('jobs.close')
        @if($job && $job->status === 'closed' && (!$invoice->payments()->exists() && (float)$invoice->paid_amount == 0))
            <button type="button" class="button button-secondary" style="color: #dc2626; border-color: #fca5a5;" onclick="document.getElementById('modal-reopen-job').showModal()">
                ↺ Undo / Buka Kembali Job
            </button>
        @endif
    @endcan
</div>

@if((float) $invoice->tax > 0 && $charges->isNotEmpty())
<section class="panel" style="margin-bottom:20px;">
    <div class="panel-heading"><div><h2>XML Coretax dari Charges Terpilih</h2><p>Pilih charge yang akan digabungkan menjadi satu XML Coretax untuk invoice ini.</p></div></div>
    <form method="POST" action="{{ route('invoices.coretax.selected', $invoice) }}" style="padding:0 24px 20px;">
        @csrf
        @foreach($charges as $charge)
            <label style="display:flex;gap:10px;align-items:center;padding:9px 0;border-bottom:1px solid #e2e8f0;cursor:pointer;">
                <input type="checkbox" name="item_ids[]" value="{{ $charge->id }}" checked>
                <span style="flex:1;"><strong>{{ $charge->description }}</strong> · {{ \App\Support\Money::format($charge->quantity) }} {{ $charge->unit }}</span>
                <strong>Rp {{ \App\Support\Money::format($charge->amount) }}</strong>
            </label>
        @endforeach
        <div style="margin-top:14px;"><button class="button button-secondary"><x-icon name="download"/> Unduh XML charges terpilih</button></div>
    </form>
</section>
@endif

{{-- PANEL UTAMA TAMPILAN INVOICE SESUAI FORMAT DOCX --}}
<section class="panel" style="overflow: hidden; margin-bottom: 24px;">
    {{-- HEADER KOP RESMI PERUSAHAAN --}}
    <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 24px; border-bottom: 1px solid #e2e8f0; background: #fafbfc;">
        <div style="display: flex; gap: 16px; align-items: center;">
            <img src="{{ asset('images/logo.png') }}" alt="RDX Logistics" style="max-height: 48px; max-width: 140px;" onerror="this.style.display='none'">
            <div>
                <strong style="font-size: 14px; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">PT. RADIX INTERNATIONAL LOGISTICS</strong>
                <p style="margin: 3px 0 0 0; font-size: 11px; color: #475569; line-height: 1.5;">
                    Jl. Teh No 3C Tamansari Pinangsia, Jakarta Barat Indonesia 11110<br>
                    Telp : 021-38873060
                </p>
            </div>
        </div>
        <div style="text-align: right;">
            <span class="status-badge status-{{ $invoice->status }}" style="font-size: 11px; padding: 6px 12px;">{{ str_replace('_', ' ', ucwords($invoice->status, '_')) }}</span>
            <div style="font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 6px; letter-spacing: 1px;">INVOICE</div>
        </div>
    </div>

    {{-- SECTION BILL TO & METADATA INVOICE --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; padding: 20px 24px; border-bottom: 1px solid #e2e8f0; background: #fff;">
        <div>
            <div style="font-size: 11px; font-weight: 700; text-decoration: underline; color: #334155; margin-bottom: 6px;">BILL TO :</div>
            <div style="font-size: 13px; font-weight: 700; color: #0f172a; text-transform: uppercase;">{{ $invoice->customer_snapshot['name'] }}</div>
            <div style="font-size: 11.5px; color: #475569; margin-top: 4px; line-height: 1.5;">
                {{ $invoice->customer_snapshot['address'] ?? 'Alamat tidak tertera' }}
            </div>
            @if(!empty($invoice->customer_snapshot['contact_name']) || !empty($invoice->customer_snapshot['phone']))
            <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                Attn: {{ $invoice->customer_snapshot['contact_name'] ?? '—' }} 
                @if(!empty($invoice->customer_snapshot['phone'])) · Telp: {{ $invoice->customer_snapshot['phone'] }} @endif
            </div>
            @endif
        </div>

        <div style="display: flex; flex-direction: column; align-items: flex-end; justify-content: space-between;">
            <table style="border-collapse: collapse; font-size: 11px; color: #334155;">
                <tr>
                    <td style="font-weight: 700; padding: 2px 6px; text-decoration: underline;">Inv. No</td>
                    <td style="padding: 2px 4px;">:</td>
                    <td style="font-weight: 700; color: #0f172a;">{{ $invoice->number }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; padding: 2px 6px; text-decoration: underline;">Inv. Date</td>
                    <td style="padding: 2px 4px;">:</td>
                    <td>{{ $invoice->invoice_date->format('d-m-Y') }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; padding: 2px 6px; text-decoration: underline;">Due. Date</td>
                    <td style="padding: 2px 4px;">:</td>
                    <td>{{ $invoice->due_date->format('d-m-Y') }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; padding: 2px 6px; text-decoration: underline;">Mata uang</td>
                    <td style="padding: 2px 4px;">:</td>
                    <td>{{ $invoice->currency }}</td>
                </tr>
            </table>

            <div style="margin-top: 10px; background: #f1f5f9; padding: 8px 16px; border-radius: 8px; border: 1px solid #cbd5e1; text-align: right;">
                <span style="font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase;">Total Tagihan:</span>
                <div style="font-size: 18px; font-weight: 800; color: #0f172a; font-family: monospace;">
                    {{ $invoice->currency }}. {{ \App\Support\Money::format($invoice->total) }}
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION SHIPMENT DETAILS (2 KOLOM SESUAI FORMAT DOCX) --}}
    <div style="padding: 16px 24px; border-bottom: 1px solid #e2e8f0; background: #fafbfc;">
        <h3 style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 12px; letter-spacing: 0.5px;">Rincian Pengiriman / Shipment Details</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px 40px; font-size: 11px;">
            {{-- KOLOM KIRI --}}
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 110px; font-weight: 700; text-decoration: underline; padding: 2.5px 0;">JOB. NO</td>
                    <td style="width: 12px;">:</td>
                    <td style="font-weight: 600;">{{ $job?->number ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; text-decoration: underline; padding: 2.5px 0;">NO. HAWB</td>
                    <td>:</td>
                    <td>{{ $job?->hawb_number ?? $job?->hbl_number ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; text-decoration: underline; padding: 2.5px 0;">NO. MAWB</td>
                    <td>:</td>
                    <td>{{ $job?->mawb_number ?? $job?->bl_number ?? $job?->awb_number ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; text-decoration: underline; padding: 2.5px 0;">NO. AJU</td>
                    <td>:</td>
                    <td>{{ $job?->booking_reference ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; text-decoration: underline; padding: 2.5px 0;">SHIPPER</td>
                    <td>:</td>
                    <td>{{ $job?->shipper_name ?? $invoice->customer_snapshot['name'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; text-decoration: underline; padding: 2.5px 0;">CONSIGNEE</td>
                    <td>:</td>
                    <td>{{ $job?->consignee_name ?? $invoice->customer_snapshot['name'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; text-decoration: underline; padding: 2.5px 0;">REMARKS</td>
                    <td>:</td>
                    <td>{{ $job?->operational_notes ?? $quotation?->notes ?? '—' }}</td>
                </tr>
            </table>

            {{-- KOLOM KANAN --}}
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 110px; font-weight: 700; text-decoration: underline; padding: 2.5px 0;">POL</td>
                    <td style="width: 12px;">:</td>
                    <td style="font-weight: 600;">{{ strtoupper($job?->pol ?? $job?->origin ?? $quotation?->origin ?? '—') }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; text-decoration: underline; padding: 2.5px 0;">POD</td>
                    <td>:</td>
                    <td style="font-weight: 600;">{{ strtoupper($job?->pod ?? $job?->destination ?? $quotation?->destination ?? '—') }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; text-decoration: underline; padding: 2.5px 0;">ETD</td>
                    <td>:</td>
                    <td>{{ $job?->etd ? $job->etd->format('d-m-Y') : '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; text-decoration: underline; padding: 2.5px 0;">ETA</td>
                    <td>:</td>
                    <td>{{ $job?->eta ? $job->eta->format('d-m-Y') : '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; text-decoration: underline; padding: 2.5px 0;">QUANTITY</td>
                    <td>:</td>
                    <td>
                        @if($job?->package_count)
                            {{ $job->package_count }} Box
                        @elseif($job?->container_type)
                            1x {{ strtoupper($job->container_type) }}
                        @elseif(!empty($quotation?->cargo_qty))
                            {{ $quotation->cargo_qty }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 700; text-decoration: underline; padding: 2.5px 0;">WEIGHT</td>
                    <td>:</td>
                    <td>{{ $job?->gross_weight ? \App\Support\Money::format($job->gross_weight).' KGS' : ($quotation?->weight_meas ?? '—') }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 700; text-decoration: underline; padding: 2.5px 0;">VOLUME</td>
                    <td>:</td>
                    <td>{{ $job?->volume ? $job->volume.' M3' : '—' }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- TABEL RINCIAN BIAYA (8 KOLOM SESUAI FORMAT DOCX) --}}
    <div class="table-scroll">
        <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
            <thead>
                <tr style="background: #1e293b; color: #fff;">
                    <th style="padding: 10px 8px; width: 40px; text-align: center;">NO</th>
                    <th style="padding: 10px 12px; text-align: left;">DESCRIPTION</th>
                    <th style="padding: 10px 8px; width: 60px; text-align: center;">QTY</th>
                    <th style="padding: 10px 8px; width: 60px; text-align: center;">CUR</th>
                    <th style="padding: 10px 12px; width: 110px; text-align: right;">PRICE</th>
                    <th style="padding: 10px 12px; width: 120px; text-align: right;">AMOUNT (IDR)</th>
                    <th style="padding: 10px 12px; width: 100px; text-align: right;">VAT (IDR)</th>
                    <th style="padding: 10px 12px; width: 130px; text-align: right;">TOTAL (IDR)</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp

                {{-- SECTION 1: CHARGES (PROVISION) --}}
                @if($charges->isNotEmpty())
                    <tr style="background: #f1f5f9;">
                        <td style="text-align: center; font-weight: 700; border-bottom: 1px solid #cbd5e1;"></td>
                        <td colspan="7" style="font-weight: 700; color: #0f172a; padding: 8px 12px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; letter-spacing: 0.5px;">
                            CHARGES (Provision)
                        </td>
                    </tr>
                    @foreach($charges as $item)
                        @php
                            $itemAmount = (float) $item->amount;
                            $itemVat = $chargesAmount > 0 ? ($itemAmount / $chargesAmount) * $chargesTax : 0;
                            $itemTotal = $itemAmount + $itemVat;
                        @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="text-align: center; padding: 8px 4px; color: #64748b;">{{ $no++ }}.</td>
                            <td style="padding: 8px 12px; font-weight: 600; color: #1e293b;">{{ strtoupper($item->description) }}</td>
                            <td style="text-align: center; padding: 8px 4px;">{{ \App\Support\Money::format($item->quantity) }}</td>
                            <td style="text-align: center; padding: 8px 4px; font-weight: 600; color: #475569;">{{ $invoice->currency }}</td>
                            <td style="text-align: right; padding: 8px 12px; font-family: monospace;">{{ \App\Support\Money::format($item->unit_price) }}</td>
                            <td style="text-align: right; padding: 8px 12px; font-family: monospace;">{{ \App\Support\Money::format($itemAmount) }}</td>
                            <td style="text-align: right; padding: 8px 12px; font-family: monospace; color: #0369a1;">{{ \App\Support\Money::format($itemVat) }}</td>
                            <td style="text-align: right; padding: 8px 12px; font-family: monospace; font-weight: 700; color: #0f172a;">{{ \App\Support\Money::format($itemTotal) }}</td>
                        </tr>
                    @endforeach
                    <tr style="background: #e2e8f0; font-weight: 700; border-top: 1px solid #cbd5e1; border-bottom: 2px solid #94a3b8;">
                        <td></td>
                        <td colspan="4" style="padding: 8px 12px; text-transform: uppercase;">TOTAL CHARGES</td>
                        <td style="text-align: right; padding: 8px 12px; font-family: monospace;">{{ \App\Support\Money::format($chargesAmount) }}</td>
                        <td style="text-align: right; padding: 8px 12px; font-family: monospace; color: #0369a1;">{{ \App\Support\Money::format($chargesTax) }}</td>
                        <td style="text-align: right; padding: 8px 12px; font-family: monospace; color: #0f172a;">{{ \App\Support\Money::format($chargesTotal) }}</td>
                    </tr>
                @endif

                {{-- SECTION 2: REIMBURSEMENT (TEMPORARY) --}}
                @if($reimbursements->isNotEmpty())
                    <tr style="background: #f1f5f9;">
                        <td style="text-align: center; font-weight: 700; border-bottom: 1px solid #cbd5e1;"></td>
                        <td colspan="7" style="font-weight: 700; color: #0f172a; padding: 8px 12px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; letter-spacing: 0.5px;">
                            REIMBURSEMENT (Temporary)
                        </td>
                    </tr>
                    @foreach($reimbursements as $item)
                        @php
                            $itemAmount = (float) $item->amount;
                            $itemVat = 0.00;
                            $itemTotal = $itemAmount;
                        @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="text-align: center; padding: 8px 4px; color: #64748b;">{{ $no++ }}.</td>
                            <td style="padding: 8px 12px; font-weight: 600; color: #1e293b;">{{ strtoupper($item->description) }}</td>
                            <td style="text-align: center; padding: 8px 4px;">{{ \App\Support\Money::format($item->quantity) }}</td>
                            <td style="text-align: center; padding: 8px 4px; font-weight: 600; color: #475569;">{{ $invoice->currency }}</td>
                            <td style="text-align: right; padding: 8px 12px; font-family: monospace;">{{ \App\Support\Money::format($item->unit_price) }}</td>
                            <td style="text-align: right; padding: 8px 12px; font-family: monospace;">{{ \App\Support\Money::format($itemAmount) }}</td>
                            <td style="text-align: right; padding: 8px 12px; font-family: monospace; color: #64748b;">0,00</td>
                            <td style="text-align: right; padding: 8px 12px; font-family: monospace; font-weight: 700; color: #0f172a;">{{ \App\Support\Money::format($itemTotal) }}</td>
                        </tr>
                    @endforeach
                    <tr style="background: #e2e8f0; font-weight: 700; border-top: 1px solid #cbd5e1; border-bottom: 2px solid #94a3b8;">
                        <td></td>
                        <td colspan="4" style="padding: 8px 12px; text-transform: uppercase;">TOTAL REIMBURSEMENT</td>
                        <td style="text-align: right; padding: 8px 12px; font-family: monospace;">{{ \App\Support\Money::format($reimbursementAmount) }}</td>
                        <td style="text-align: right; padding: 8px 12px; font-family: monospace; color: #64748b;">0,00</td>
                        <td style="text-align: right; padding: 8px 12px; font-family: monospace; color: #0f172a;">{{ \App\Support\Money::format($reimbursementAmount) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- SUMMARY TOTALS SESUAI FORMAT DOCX --}}
    <div style="display: flex; justify-content: flex-end; padding: 20px 24px; background: #fff; border-bottom: 1px solid #e2e8f0;">
        <table style="border-collapse: collapse; min-width: 320px; font-size: 11.5px;">
            <tr>
                <td style="padding: 4px 8px; font-weight: 700; text-transform: uppercase; color: #475569;">SUB TOTAL</td>
                <td style="padding: 4px 8px; text-align: right; font-family: monospace; font-weight: 600;">
                    Rp {{ \App\Support\Money::format($invoice->subtotal) }}
                </td>
            </tr>
            @if((float) $invoice->tax > 0)
            <tr>
                <td style="padding: 4px 8px; font-weight: 700; text-transform: uppercase; color: #0369a1;">VAT / PPN</td>
                <td style="padding: 4px 8px; text-align: right; font-family: monospace; font-weight: 600; color: #0369a1;">
                    Rp {{ \App\Support\Money::format($invoice->tax) }}
                </td>
            </tr>
            @endif
            @if((float)$invoice->total >= 5000000)
            <tr>
                <td style="padding: 4px 8px; font-weight: 700; text-transform: uppercase; color: #475569;">MATERAI</td>
                <td style="padding: 4px 8px; text-align: right; font-family: monospace; font-weight: 600; color: #64748b;">
                    10.000,00
                </td>
            </tr>
            @endif
            <tr style="border-top: 2px solid #0f172a; border-bottom: 2px solid #0f172a;">
                <td style="padding: 8px; font-weight: 800; font-size: 13px; text-transform: uppercase; color: #0f172a;">TOTAL</td>
                <td style="padding: 8px; text-align: right; font-family: monospace; font-weight: 800; font-size: 14px; color: #0f172a;">
                    Rp {{ \App\Support\Money::format($invoice->total) }}
                </td>
            </tr>
            <tr>
                <td style="padding: 4px 8px; font-weight: 700; text-transform: uppercase; color: #475569;">Kurs terhadap Rupiah (EXC. RATE)</td>
                <td style="padding: 4px 8px; text-align: right; font-family: monospace;">
                    {{ $invoice->currency }} {{ $invoice->currency === 'IDR' ? '1.000,00' : \App\Support\Money::format($invoice->exchange_rate) }}
                </td>
            </tr>
            @if((float)$invoice->paid_amount > 0)
            <tr>
                <td style="padding: 4px 8px; font-weight: 700; color: #16a34a;">Sudah Dibayar</td>
                <td style="padding: 4px 8px; text-align: right; font-family: monospace; color: #16a34a;">
                    Rp {{ \App\Support\Money::format($invoice->paid_amount) }}
                </td>
            </tr>
            <tr>
                <td style="padding: 4px 8px; font-weight: 700; color: #dc2626;">Sisa Tagihan</td>
                <td style="padding: 4px 8px; text-align: right; font-family: monospace; font-weight: 700; color: #dc2626;">
                    Rp {{ \App\Support\Money::format($invoice->balance) }}
                </td>
            </tr>
            @endif
        </table>
    </div>

    {{-- BARIS TERBILANG (SAID) --}}
    <div style="padding: 14px 24px; font-size: 11.5px; color: #1e293b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; line-height: 1.6;">
        <span style="font-weight: 700; font-style: italic;">Said : </span>
        <strong style="color: #0f172a;"># {{ \App\Support\Money::terbilang($invoice->total, $invoice->currency) }} #</strong>
    </div>

    @if($hasForeignCurrency)
    <div style="padding: 12px 24px; background: #fff; border-bottom: 1px solid #e2e8f0; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 24px; font-size: 11px; color: #475569;">
        <div>Mata uang: <strong>{{ $invoice->currency }}</strong></div>
        <div>Kurs terhadap Rupiah: <strong>{{ \App\Support\Money::format($invoice->exchange_rate) }}</strong></div>
        <div>Subtotal setara: <strong>{{ $invoice->currency }} {{ \App\Support\Money::format((string) $invoice->inInvoiceCurrency('subtotal')) }}</strong></div>
        <div>Pajak setara: <strong>{{ $invoice->currency }} {{ \App\Support\Money::format((string) $invoice->inInvoiceCurrency('tax')) }}</strong></div>
        <div>Total setara: <strong>{{ $invoice->currency }} {{ \App\Support\Money::format((string) $invoice->inInvoiceCurrency('total')) }}</strong></div>
    </div>
    @endif

    {{-- KOTAK INFORMASI PEMBAYARAN BANK SESUAI DOCX --}}
    <div style="padding: 20px 24px; background: #fafbfc; border-top: 1px solid #e2e8f0;">
        <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #1e293b; text-decoration: underline; margin-bottom: 10px; letter-spacing: 0.5px;">
            INFORMASI PEMBAYARAN / PAYMENT DETAILS:
        </div>
        <div style="font-size: 11px; color: #334155; line-height: 1.7;">
            <div><span style="font-weight: 700;">NAMA AKUN / ACCOUNT NAME :</span> <strong style="color: #0f172a;">PT RADIX INTERNATIONAL LOGISTICS</strong></div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px; margin-top: 8px;">
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px;">
                    <div style="font-weight: 700; color: #1e40af; font-size: 11.5px;">BANK CENTRAL ASIA (BCA) - CENGKEH</div>
                    <div style="font-size: 13px; font-weight: 800; color: #0f172a; font-family: monospace; margin-top: 2px;">
                        240-0375-758
                    </div>
                    <div style="font-size: 10px; color: #64748b;">a/n PT RADIX INTERNATIONAL LOGISTICS</div>
                </div>
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px;">
                    <div style="font-weight: 700; color: #0f766e; font-size: 11.5px;">BANK MANDIRI - JAKARTA KOTA</div>
                    <div style="font-size: 13px; font-weight: 800; color: #0f172a; font-family: monospace; margin-top: 2px;">
                        115-00-1053704-3
                    </div>
                    <div style="font-size: 10px; color: #64748b;">a/n PT RADIX INTERNATIONAL LOGISTICS</div>
                </div>
            </div>
            <div style="margin-top: 10px; font-size: 10.5px; color: #64748b; font-style: italic;">
                * Harap cantumkan nomor invoice (<strong>{{ $invoice->number }}</strong>) pada berita transfer dan kirimkan bukti pembayaran ke finance.
            </div>
        </div>
    </div>
</section>

{{-- STATUS PENGIRIMAN INVOICE ASLI (FISIK) --}}
<div class="section-heading" style="margin-top: 24px;">
    <h2>📦 Status Pengiriman Invoice Fisik</h2>
    <span class="subtle">Update status pengiriman dokumen invoice asli ke customer</span>
</div>
<section class="panel" style="margin-bottom: 24px;">
    <div style="padding: 24px;">
        <form method="POST" action="{{ route('invoices.delivery', $invoice) }}" enctype="multipart/form-data">
            @csrf
            <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div class="field">
                    <label for="delivery_status">Status Pengiriman <span class="required">*</span></label>
                    <select id="delivery_status" name="delivery_status" required>
                        <option value="not_sent" @selected(old('delivery_status', $invoice->delivery_status ?? 'not_sent') === 'not_sent')>Belum Dikirim</option>
                        <option value="sent" @selected(old('delivery_status', $invoice->delivery_status) === 'sent')>Terkirim ke Ekspedisi / Kurir</option>
                        <option value="received" @selected(old('delivery_status', $invoice->delivery_status) === 'received')>Diterima Customer</option>
                    </select>
                </div>
                <div class="field">
                    <label for="sent_at">Tanggal Dikirim</label>
                    <input id="sent_at" name="sent_at" type="date" value="{{ old('sent_at', $invoice->sent_at?->format('Y-m-d')) }}">
                </div>
                <div class="field">
                    <label for="received_at">Tanggal Diterima</label>
                    <input id="received_at" name="received_at" type="date" value="{{ old('received_at', $invoice->received_at?->format('Y-m-d')) }}">
                </div>
                <div class="field">
                    <label for="tracking_number">No. Resi / Kurir</label>
                    <input id="tracking_number" name="tracking_number" type="text" value="{{ old('tracking_number', $invoice->tracking_number) }}" placeholder="cth: JNE-882910 / Kurir Internal">
                </div>
                <div class="field span-2">
                    <label for="delivery_notes">Catatan Pengiriman</label>
                    <textarea id="delivery_notes" name="delivery_notes" rows="2" placeholder="Catatan penerima atau ekspedisi...">{{ old('delivery_notes', $invoice->delivery_notes) }}</textarea>
                </div>
                <div class="field">
                    <label for="tax_invoice_number">No. Faktur Pajak</label>
                    <input id="tax_invoice_number" name="tax_invoice_number" type="text" maxlength="100" value="{{ old('tax_invoice_number', $invoice->tax_invoice_number) }}" placeholder="Nomor faktur pajak">
                </div>
                <div class="field">
                    <label for="tax_invoice_file">Upload Faktur Pajak</label>
                    <input id="tax_invoice_file" name="tax_invoice_file" type="file" accept=".pdf,.jpg,.jpeg,.png">
                    @if($invoice->tax_invoice_file)<a class="text-link" target="_blank" href="{{ route('invoices.tax-invoice', $invoice) }}">Lihat faktur pajak</a>@endif
                </div>
            </div>
            <div style="margin-top: 16px; text-align: right;">
                <button type="submit" class="button button-primary">Simpan Status Pengiriman</button>
            </div>
        </form>
    </div>
</section>

{{-- RIWAYAT PEMBAYARAN --}}
<div class="section-heading">
    <h2>Riwayat Pembayaran Masuk</h2>
</div>
<section class="panel">
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Nomor Bukti</th>
                    <th>Tanggal</th>
                    <th>Metode</th>
                    <th>Rekening Tujuan</th>
                    <th class="money">PPH 23</th>
                    <th class="money">Jumlah Dibayar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->payments as $payment)
                    <tr>
                        <td><strong>{{ $payment->number }}</strong></td>
                        <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td><span class="status-badge" style="background:#e0f2fe;color:#0369a1;">{{ ucfirst($payment->method) }}</span></td>
                        <td>{{ $payment->account ? ($payment->account->code . ' — ' . $payment->account->name) : '—' }}</td>
                        <td class="money" style="color:#b45309; font-weight:600;">
                            @if((float)$payment->pph23_amount > 0)
                                Rp {{ \App\Support\Money::format($payment->pph23_amount) }}
                            @else
                                <span style="color:#94a3b8;">0,00</span>
                            @endif
                        </td>
                        <td class="money" style="font-weight:700;color:#16a34a;">Rp {{ \App\Support\Money::format($payment->amount) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #94a3b8; padding: 20px;">Belum ada riwayat pembayaran untuk invoice ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{-- MODAL ADD PAYMENT (DESAIN SESUAI IMAGE 9 CLIENT) --}}
@if($invoice->status !== 'paid')
<dialog id="modal-add-payment" class="modal-dialog" style="max-width: 620px !important; width: calc(100% - 32px); border: 2px solid #3b82f6; border-radius: 8px; padding: 0; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); background: #ffffff; position: fixed; inset: 0; margin: auto; overflow: hidden;">
    <form method="POST" action="{{ route('payments.store', $invoice) }}" style="margin: 0; padding: 0;">
        @csrf
        <input type="hidden" name="lock_version" value="{{ $invoice->lock_version }}">
        <input type="hidden" name="method" value="transfer">

        {{-- SECTION 1: DATA INVOICE --}}
        <div style="background: #4682b4; color: #ffffff; padding: 8px 16px; font-weight: bold; font-size: 13.5px; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 14px;">📋</span>
                <span>Data Invoice</span>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-payment').close()" style="background: none; border: none; color: #fff; font-size: 16px; cursor: pointer; font-weight: bold;">✕</button>
        </div>

        <div style="padding: 16px 22px; background: #ffffff; border-bottom: 1px solid #cbd5e1;">
            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px 12px; align-items: center; margin-bottom: 10px;">
                <label style="text-align: right; font-weight: bold; font-size: 12.5px; color: #334155;">#Inv. No :</label>
                <input type="text" value="{{ $invoice->number }}" disabled style="max-width: 240px; background: #f8fafc; border: 1px solid #94a3b8; padding: 5px 10px; font-size: 13px; font-weight: 600; border-radius: 3px; color: #0f172a;">
            </div>
            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px 12px; align-items: center; margin-bottom: 10px;">
                <label style="text-align: right; font-weight: bold; font-size: 12.5px; color: #334155;">Customer :</label>
                <input type="text" value="{{ $invoice->customer_snapshot['name'] }}" disabled style="width: 100%; max-width: 420px; background: #fef3c7; border: 1px solid #d97706; padding: 5px 10px; font-size: 12.5px; font-weight: 600; border-radius: 3px; color: #78350f;">
            </div>
            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px 12px; align-items: center;">
                <label style="text-align: right; font-weight: bold; font-size: 12.5px; color: #334155;">Billing :</label>
                <div style="display: flex; gap: 6px; align-items: center;">
                    <input type="text" value="{{ \App\Support\Money::format($invoice->balance) }}" disabled style="width: 140px; text-align: right; background: #fef3c7; border: 1px solid #d97706; padding: 5px 10px; font-size: 13px; font-weight: 700; border-radius: 3px; color: #78350f; font-family: monospace;">
                    <span style="border: 1px solid #94a3b8; background: #f8fafc; padding: 4px 10px; font-size: 12px; font-weight: bold; border-radius: 3px; color: #334155;">{{ $invoice->currency }}</span>
                </div>
            </div>
        </div>

        {{-- SECTION 2: DATA PAYMENT --}}
        <div style="background: #4682b4; color: #ffffff; padding: 8px 16px; font-weight: bold; font-size: 13.5px; display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 14px;">📋</span>
            <span>Data Payment</span>
        </div>

        <div style="padding: 18px 22px 20px; background: #ffffff;">
            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px 12px; align-items: center; margin-bottom: 12px;">
                <label style="text-align: right; font-weight: bold; font-size: 12.5px; color: #334155;">Payment Date :</label>
                <input type="date" name="payment_date" value="{{ old('payment_date', today()->toDateString()) }}" required style="width: 170px; border: 1px solid #94a3b8; padding: 5px 10px; font-size: 12.5px; border-radius: 3px; background: #fff;">
            </div>

            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px 12px; align-items: center; margin-bottom: 12px;">
                <label style="text-align: right; font-weight: bold; font-size: 12.5px; color: #334155;">Payment Amount :</label>
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <input type="number" id="pay_amount" name="amount" min="0.01" max="{{ $invoice->balance }}" step="0.01" value="{{ old('amount', $invoice->balance) }}" required style="width: 140px; text-align: right; border: 1px solid #94a3b8; padding: 5px 10px; font-size: 13px; font-weight: 700; border-radius: 3px; font-family: monospace; background: #fff;">
                    <select name="currency" id="pay_currency" style="border: 1px solid #94a3b8; padding: 5px 8px; font-size: 12.5px; font-weight: 600; border-radius: 3px; background: #fff;">
                        <option value="IDR" @selected($invoice->currency === 'IDR')>IDR</option>
                        <option value="USD" @selected($invoice->currency === 'USD')>USD</option>
                        <option value="SGD" @selected($invoice->currency === 'SGD')>SGD</option>
                    </select>
                    <label style="font-weight: bold; font-size: 12.5px; color: #334155; margin-left: 6px;">Exc. Rate :</label>
                    <input type="number" name="exchange_rate" id="pay_rate" step="0.0001" min="0.0001" value="{{ old('exchange_rate', $invoice->currency === 'IDR' ? '1' : ($invoice->exchange_rate ?? '1')) }}" style="width: 90px; text-align: center; border: 1px solid #94a3b8; padding: 5px 8px; font-size: 12.5px; border-radius: 3px; font-family: monospace; background: #fff;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px 12px; align-items: center; margin-bottom: 12px;">
                <label style="text-align: right; font-weight: bold; font-size: 12.5px; color: #334155;">PPH 23 :</label>
                <div style="display: flex; gap: 6px; align-items: center;">
                    <input type="number" id="pay_pph23" name="pph23_amount" min="0" step="0.01" value="{{ old('pph23_amount', '0') }}" placeholder="0" style="width: 140px; text-align: right; border: 1px solid #d97706; background: #fef3c7; padding: 5px 10px; font-size: 13px; font-weight: 700; border-radius: 3px; font-family: monospace; color: #78350f;">
                    <span style="border: 1px solid #94a3b8; background: #f8fafc; padding: 4px 10px; font-size: 12px; font-weight: bold; border-radius: 3px; color: #334155;">IDR</span>
                    <small style="color: #64748b; font-size: 11px; margin-left: 6px;">(Auto jurnal ke COA PPH 23 Dibayar Dimuka)</small>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px 12px; align-items: center; margin-bottom: 20px;">
                <label style="text-align: right; font-weight: bold; font-size: 12.5px; color: #334155;">Bank/Cash :</label>
                <select id="pay_bank" name="deposit_account" required style="width: 100%; max-width: 420px; border: 1px solid #94a3b8; padding: 6px 10px; font-size: 12.5px; border-radius: 3px; background: #fff;">
                    @foreach($bankAccounts as $acc)
                        @php
                            $displayName = $acc->name;
                            if (str_contains($acc->code, '11121')) $displayName = 'BCA IDR - 240-0375-758 - IDR';
                            elseif (str_contains($acc->code, '11122')) $displayName = 'MANDIRI IDR - 115-00-1053704-3 - IDR';
                            elseif (str_contains($acc->code, '11123')) $displayName = 'BCA USD - 240-0386-172 - USD';
                            elseif (str_contains($acc->code, '11101')) $displayName = 'PETTY CASH - IDR';
                            else $displayName = $acc->name . ' (' . $acc->code . ')';
                        @endphp
                        <option value="{{ $acc->id }}" @selected(old('deposit_account') == $acc->id || (empty(old('deposit_account')) && str_contains($acc->code, '11121')))>
                            {{ $displayName }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- ACTION BUTTONS SESUAI GAMBAR 9 --}}
            <div style="display: flex; justify-content: flex-start; gap: 10px; padding-left: 132px;">
                <button type="submit" style="background: #059669; color: #ffffff; border: 1px solid #047857; padding: 7px 22px; font-size: 13px; font-weight: bold; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                    <span>💾</span> Save
                </button>
                <button type="button" onclick="document.getElementById('modal-add-payment').close()" style="background: #dc2626; color: #ffffff; border: 1px solid #b91c1c; padding: 7px 22px; font-size: 13px; font-weight: bold; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                    <span>✖</span> Cancel
                </button>
            </div>
        </div>
    </form>
</dialog>
@endif

{{-- MODAL UNDO / BUKA KEMBALI JOB --}}
@can('jobs.close')
    @if($job && $job->status === 'closed' && (!$invoice->payments()->exists() && (float)$invoice->paid_amount == 0))
    <dialog id="modal-reopen-job" class="modal-dialog" style="max-width: 500px !important; border: none; border-radius: 16px; padding: 0; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <form method="POST" action="{{ route('jobs.reopen', $job) }}">
            @csrf
            <input type="hidden" name="lock_version" value="{{ $job->lock_version }}">
            <div style="padding: 18px 24px; border-bottom: 1px solid #fee2e2; display: flex; justify-content: space-between; align-items: center; background: #fef2f2; border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 20px;">↺</span>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #991b1b;">Undo / Buka Kembali Job</h3>
                </div>
                <button type="button" onclick="document.getElementById('modal-reopen-job').close()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #991b1b;">✕</button>
            </div>
            <div style="padding: 20px 24px;">
                <p style="font-size: 13px; color: #374151; margin-top: 0; line-height: 1.5;">
                    Apakah Anda yakin ingin membuka kembali job <strong>{{ $job->number }}</strong>?
                </p>
                <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 12px; font-size: 12px; color: #92400e; margin-bottom: 16px; line-height: 1.5;">
                    ⚠️ Tindakan ini akan mengembalikan status Job ke <strong>Open</strong>, menghapus tagihan invoice <strong>{{ $invoice->number }}</strong>, dan me-reverse (membatalkan) jurnal closing secara otomatis.
                </div>
                <div class="field">
                    <label for="reopen_reason_inv" style="font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px; display: block;">Alasan Buka Kembali (opsional)</label>
                    <input type="text" id="reopen_reason_inv" name="reason" placeholder="cth: Koreksi biaya aktual / revisi invoice" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 13px; box-sizing: border-box;">
                </div>
            </div>
            <div style="padding: 14px 24px; border-top: 1px solid #e5e7eb; background: #f9fafb; display: flex; justify-content: flex-end; gap: 10px; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="button button-secondary" onclick="document.getElementById('modal-reopen-job').close()">Batal</button>
                <button type="submit" class="button button-danger" style="background: #dc2626; border-color: #dc2626; color: #ffffff !important; font-weight: 600;">Ya, Buka Kembali Job</button>
            </div>
        </form>
    </dialog>
    @endif
@endcan

<style>
dialog.modal-dialog::backdrop {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(2px);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const payModal = document.getElementById('modal-add-payment');
    if (payModal) {
        payModal.addEventListener('click', function(e) {
            const rect = payModal.getBoundingClientRect();
            const inDialog = (rect.top <= e.clientY && e.clientY <= rect.top + rect.height && rect.left <= e.clientX && e.clientX <= rect.left + rect.width);
            if (!inDialog) payModal.close();
        });
        @if(request('add_payment') == '1' || $errors->any())
            payModal.showModal();
        @endif
    }
});
</script>

@endsection
