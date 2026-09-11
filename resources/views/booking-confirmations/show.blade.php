@extends('layouts.app')
@section('title', 'Booking Confirmation ' . $bc->number)
@section('content')

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL</p>
        <h1>{{ $bc->number }}</h1>
        <p>Customer: <strong>{{ $bc->customer?->name ?? '—' }}</strong> @if($bc->job) · Job Order: <a class="text-link" href="{{ route('jobs.show', $bc->job) }}">{{ $bc->job->number }}</a>@endif</p>
    </div>
    <a class="text-link" href="{{ route('booking-confirmations.index') }}">← Kembali ke daftar</a>
</div>

<div class="quote-actions" style="margin-bottom: 20px;">
    <a class="button button-secondary" href="{{ route('booking-confirmations.edit', $bc) }}">Edit Booking Confirmation</a>
    <a class="button button-secondary" href="{{ route('booking-confirmations.preview', $bc) }}" target="_blank">🖨 Preview PDF</a>
    @if($bc->job)
        <a class="button button-secondary" href="{{ route('jobs.show', $bc->job) }}">Lihat Job Order</a>
    @endif
    <form method="POST" action="{{ route('booking-confirmations.destroy', $bc) }}" data-confirm="Hapus Booking Confirmation {{ $bc->number }}?" style="display:inline;">
        @csrf
        @method('DELETE')
        <button class="button button-danger" style="background:#ef4444;border-color:#ef4444">Hapus</button>
    </form>
</div>

{{-- PANEL UTAMA FORMAT DOKUMEN PERUSAHAAN (BOOKING CONFIRMATION - CS.docx) --}}
<section class="panel" style="overflow: hidden; margin-bottom: 24px; border: 1px solid #cbd5e1; background: #fff;">
    <div style="padding: 32px 36px;">

        {{-- HEADER: LOGO & JUDUL DOKUMEN --}}
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #0f172a;">
            <div>
                <img src="{{ asset('images/logo.png') }}" alt="RDX Logistics" style="max-height: 56px; max-width: 220px;" onerror="this.style.display='none'">
                <div style="margin-top: 6px;">
                    <span class="status-badge status-{{ $bc->status === 'confirmed' ? 'approved' : ($bc->status === 'draft' ? 'draft' : 'rejected') }}">
                        Status: {{ ucfirst($bc->status) }}
                    </span>
                </div>
            </div>
            <div style="text-align: right;">
                <h2 style="font-size: 20px; font-weight: 800; letter-spacing: 1px; color: #0f172a; margin: 0 0 4px 0;">BOOKING CONFIRMATION</h2>
                <div style="font-size: 14px; font-weight: 700; color: #1e293b;">NO.: {{ $bc->number }}</div>
            </div>
        </div>

        {{-- METADATA PENERIMA & TANGGAL (2 KOLOM) --}}
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 20px; font-size: 13.5px; line-height: 1.6;">
            <div>
                <div style="margin-bottom: 6px;">
                    <strong style="color: #475569;">To:</strong><br>
                    <span style="font-size: 15px; font-weight: 700; color: #0f172a;">{{ $bc->customer?->name ?? '—' }}</span>
                    @if($bc->customer?->address)
                        <div style="font-size: 12.5px; color: #64748b;">{{ $bc->customer->address }}</div>
                    @endif
                </div>
                <div>
                    <strong style="color: #475569;">Contact Person :</strong>
                    <span style="font-weight: 600; color: #0f172a;">{{ $bc->contact_person ?: '—' }}</span>
                </div>
            </div>
            <div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 3px 0; width: 38%; font-weight: 700; color: #334155;">Date</td>
                        <td style="padding: 3px 0; width: 4%;">:</td>
                        <td style="padding: 3px 0; font-weight: 600; color: #0f172a;">{{ $bc->booking_date->format('d-M-Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 3px 0; font-weight: 700; color: #334155;">Job No.</td>
                        <td style="padding: 3px 0;">:</td>
                        <td style="padding: 3px 0; font-weight: 700; color: #0f172a;">
                            @if($bc->job)
                                <a class="text-link" href="{{ route('jobs.show', $bc->job) }}">{{ $bc->job->number }}</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 3px 0; font-weight: 700; color: #334155;">Customer Ref</td>
                        <td style="padding: 3px 0;">:</td>
                        <td style="padding: 3px 0; font-weight: 600; color: #0f172a;">{{ $bc->customer_ref ?: '—' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- SALUTATION --}}
        <div style="margin-bottom: 18px; font-size: 13.5px; color: #1e293b; background: #f8fafc; padding: 10px 14px; border-left: 3px solid #0284c7; border-radius: 2px;">
            <div>We thank you for your booking.</div>
            <div>Please review the following details and advise if any discrepancy:</div>
        </div>

        {{-- SHIPMENT DETAILS (2 KOLOM) --}}
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 22px; font-size: 13.5px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 5px 0; width: 38%; font-weight: 700; color: #334155;">Shipper</td>
                    <td style="padding: 5px 0; width: 4%;">:</td>
                    <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">{{ $bc->shipper_name ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; font-weight: 700; color: #334155;">Carrier Booking</td>
                    <td style="padding: 5px 0;">:</td>
                    <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">
                        {{ $bc->carrier_name ?: '—' }}
                        @if($bc->carrier_booking_no)
                            <span style="color: #64748b;">(No: {{ $bc->carrier_booking_no }})</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; font-weight: 700; color: #334155;">Vessel</td>
                    <td style="padding: 5px 0;">:</td>
                    <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">{{ $bc->vessel_voyage ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; font-weight: 700; color: #334155;">Services</td>
                    <td style="padding: 5px 0;">:</td>
                    <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">{{ $bc->service_term ?: 'CY/CY' }}</td>
                </tr>
            </table>

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 5px 0; width: 25%; font-weight: 700; color: #334155;">POL</td>
                    <td style="padding: 5px 0; width: 4%;">:</td>
                    <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">{{ $bc->pol ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; font-weight: 700; color: #334155;">POD</td>
                    <td style="padding: 5px 0;">:</td>
                    <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">{{ $bc->pod ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; font-weight: 700; color: #334155;">ETD</td>
                    <td style="padding: 5px 0;">:</td>
                    <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">{{ $bc->etd?->format('d-M-Y') ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; font-weight: 700; color: #334155;">ETA</td>
                    <td style="padding: 5px 0;">:</td>
                    <td style="padding: 5px 0; font-weight: 600; color: #0f172a;">{{ $bc->eta?->format('d-M-Y') ?: '—' }}</td>
                </tr>
            </table>
        </div>

        {{-- CARGO DETAILS (4 KOLOM TABEL BERGARIS) --}}
        <div style="margin-bottom: 20px;">
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 13.5px;">
                <thead>
                    <tr style="background: #f1f5f9; border-bottom: 1px solid #000;">
                        <th style="padding: 8px 12px; border: 1px solid #000; text-align: left; width: 22%; font-weight: 700;">Quantity</th>
                        <th style="padding: 8px 12px; border: 1px solid #000; text-align: left; width: 40%; font-weight: 700;">Description</th>
                        <th style="padding: 8px 12px; border: 1px solid #000; text-align: left; width: 20%; font-weight: 700;">Gross Weight</th>
                        <th style="padding: 8px 12px; border: 1px solid #000; text-align: left; width: 18%; font-weight: 700;">CBM</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 10px 12px; border: 1px solid #000; font-weight: 600; vertical-align: top;">
                            {{ $bc->quantity ?: '—' }}
                        </td>
                        <td style="padding: 10px 12px; border: 1px solid #000; font-weight: 600; vertical-align: top; white-space: pre-wrap;">
                            {{ $bc->cargo_description ?: 'General Cargo' }}
                        </td>
                        <td style="padding: 10px 12px; border: 1px solid #000; font-weight: 600; vertical-align: top;">
                            {{ $bc->gross_weight ? \App\Support\Money::format($bc->gross_weight) . ' KGS' : '—' }}
                        </td>
                        <td style="padding: 10px 12px; border: 1px solid #000; font-weight: 600; vertical-align: top;">
                            {{ $bc->volume ? \App\Support\Money::format($bc->volume) . ' M3' : '—' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- SEAWORTHY PACKAGING NOTICE --}}
        <div style="font-size: 12.5px; font-weight: 700; color: #b91c1c; margin-bottom: 20px; letter-spacing: 0.5px;">
            PLEASE MAKE SURE TO USE SEAWORTHY PACKAGING.
        </div>

        {{-- DELIVERY CARGO TO & CUT-OFF TIME --}}
        <div style="margin-bottom: 24px; padding: 16px; border: 1px solid #000; background: #fff;">
            <div style="margin-bottom: 12px; font-size: 13.5px;">
                <strong style="color: #0f172a;">Delivery cargo to:</strong><br>
                <span style="font-size: 14px; font-weight: 700; color: #0f172a;">{{ $bc->delivery_cargo_to ?: '—' }}</span>
            </div>

            <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 13px; text-align: center;">
                <thead>
                    <tr style="background: #e2e8f0; border-bottom: 1px solid #000;">
                        <th style="padding: 6px; border: 1px solid #000; font-weight: 700; width: 33%;">Doc Cut-Off</th>
                        <th style="padding: 6px; border: 1px solid #000; font-weight: 700; width: 33%;">CY Cut-Off</th>
                        <th style="padding: 6px; border: 1px solid #000; font-weight: 700; width: 34%;">Delivery Cut-Off</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #000; font-weight: 600;">
                            {{ $bc->doc_cutoff_at?->format('d-M-Y H:i') ?: '—' }}
                        </td>
                        <td style="padding: 8px; border: 1px solid #000; font-weight: 600;">
                            {{ $bc->cy_cutoff_at?->format('d-M-Y H:i') ?: '—' }}
                        </td>
                        <td style="padding: 8px; border: 1px solid #000; font-weight: 600;">
                            {{ $bc->delivery_cutoff_at?->format('d-M-Y H:i') ?: '—' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- IMPORTANT NOTES --}}
        <div style="font-size: 12px; line-height: 1.55; color: #334155; margin-bottom: 20px;">
            <div style="font-size: 13px; font-weight: 700; text-decoration: underline; color: #0f172a; margin-bottom: 6px;">
                Important Note:
            </div>
            <div style="white-space: pre-wrap; padding-left: 12px; border-left: 2px solid #cbd5e1;">{{ $bc->notes ?: '—' }}</div>
        </div>

        {{-- DISCLAIMER --}}
        <div style="font-size: 11.5px; font-weight: 700; color: #475569; margin-bottom: 16px; line-height: 1.5;">
            <div>THIS BOOKING IS SUBJECT TO CHANGE FOR DOOR (HAULAGE) DELIVERY.</div>
            <div>DATE/ TIME AS WELL AS TO VESSEL SPACE AND VESSEL SCHEDULE MAY BE CHANGED WITHOUT NOTICE</div>
        </div>

        <div style="font-size: 13.5px; font-weight: 700; color: #0f172a;">
            Thank you for choosing us
        </div>
    </div>
</section>

@endsection
