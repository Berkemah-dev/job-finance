@extends('layouts.app')
@section('title', 'Shipping Instruction ' . $si->number)
@section('content')

@php
    $backUrl = $si->job_id 
        ? route('jobs.show', $si->job_id) . '#tab-si' 
        : route('shipping-instructions.index');
@endphp

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL</p>
        <h1>{{ $si->number }}</h1>
        <p>Carrier: <strong>{{ $si->to_carrier }}</strong> @if($si->job) · Job Order: <a class="text-link" href="{{ route('jobs.show', $si->job) }}">{{ $si->job->number }}</a>@endif</p>
    </div>
    <a class="button button-secondary" href="{{ $backUrl }}">← Kembali</a>
</div>

<div class="quote-actions" style="margin-bottom: 20px; display: flex; gap: 8px; flex-wrap: wrap;">
    <a class="button button-secondary" href="{{ route('shipping-instructions.edit', $si) }}">
        <x-icon name="pencil"/> Edit Shipping Instruction
    </a>
    <a class="button button-secondary" href="{{ route('shipping-instructions.preview', $si) }}" target="_blank">
        <x-icon name="printer"/> Preview / Unduh PDF
    </a>
    @if($si->job)
        <a class="button button-secondary" href="{{ route('jobs.show', $si->job) }}">
            <x-icon name="briefcase"/> Lihat Job Order
        </a>
    @endif
    <form method="POST" action="{{ route('shipping-instructions.destroy', $si) }}" data-confirm="Hapus Shipping Instruction {{ $si->number }}?" style="display:inline;">
        @csrf
        @method('DELETE')
        <button class="button button-danger" style="background:#ef4444;border-color:#ef4444;color:#fff;">
            <x-icon name="trash"/> Hapus
        </button>
    </form>
</div>

{{-- PANEL UTAMA FORMAT DOKUMEN RESMI (SHIPPING INSTRUCTION - CS.docx) --}}
<section class="panel" style="overflow: hidden; margin-bottom: 24px; border: 1px solid #000; background: #fff; padding: 0;">
    {{-- STATUS BAR HEADER --}}
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; background: #f8fafc; border-bottom: 1px solid #000;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <img src="{{ asset('images/logo.png') }}" alt="RDX Logistics" style="max-height: 40px; max-width: 160px;" onerror="this.style.display='none'">
            <span style="font-weight: 700; color: #0f172a; font-size: 14px;">PT. RADIX INTERNATIONAL LOGISTICS</span>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <span class="status-badge status-{{ $si->status === 'submitted' || $si->status === 'completed' ? 'approved' : ($si->status === 'draft' ? 'draft' : 'rejected') }}">
                Status: {{ ucfirst($si->status) }}
            </span>
            <span class="badge-pill" style="font-weight: 700;">Term: {{ $si->shipment_term }}</span>
        </div>
    </div>

    {{-- TABEL UTAMA SESUAI FORMAT B/L DOCX --}}
    <div style="padding: 16px;">
        <table style="width: 100%; border-collapse: collapse; border: 1.5px solid #000; font-size: 12.5px; line-height: 1.4;">
            {{-- ROW 1: SHIPPER vs TO CARRIER HEADER --}}
            <tr>
                <td style="width: 50%; border: 1px solid #000; vertical-align: top; padding: 0;">
                    <div style="background: #f1f5f9; padding: 5px 10px; font-weight: 800; border-bottom: 1px solid #000; font-size: 13px;">
                        SHIPPER
                    </div>
                    <div style="padding: 8px 10px; min-height: 55px;">
                        <strong style="color: #0f172a;">{{ $si->shipper_name }}</strong>
                    </div>
                </td>
                <td rowspan="3" style="width: 50%; border: 1px solid #000; vertical-align: top; padding: 12px 14px; background: #fff;">
                    <div style="text-align: center; margin-bottom: 8px;">
                        <div style="font-size: 16px; font-weight: 900; letter-spacing: 1px; color: #0f172a;">SHIPPING INSTRUCTION</div>
                        <div style="font-size: 13px; font-weight: 800; color: #1e293b; margin-top: 2px;">{{ $si->number }}</div>
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 12px;">
                        <tr>
                            <td style="width: 25%; font-weight: 700; padding: 2px 0;">To</td>
                            <td style="width: 3%;">:</td>
                            <td style="font-weight: 700; color: #0f172a;">{{ $si->to_carrier }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700; padding: 2px 0;">Attn</td>
                            <td>:</td>
                            <td style="font-weight: 600;">{{ $si->carrier_attn ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700; padding: 2px 0;">Date</td>
                            <td>:</td>
                            <td style="font-weight: 600;">{{ $si->si_date->format('d/m/Y') }}</td>
                        </tr>
                    </table>
                    <div style="font-size: 11px; font-style: italic; color: #334155; padding-top: 4px; border-top: 1px dashed #94a3b8;">
                        Please kindly arrange space for our booking as according to below mention
                    </div>
                </td>
            </tr>

            {{-- ROW 2: CONSIGNEE --}}
            <tr>
                <td style="border: 1px solid #000; vertical-align: top; padding: 0;">
                    <div style="background: #f1f5f9; padding: 5px 10px; font-weight: 800; border-bottom: 1px solid #000; font-size: 13px;">
                        CONSIGNEE
                    </div>
                    <div style="padding: 8px 10px; min-height: 55px;">
                        <strong style="color: #0f172a;">{{ $si->consignee_name }}</strong>
                    </div>
                </td>
            </tr>

            {{-- ROW 3: NOTIFY PARTY --}}
            <tr>
                <td style="border: 1px solid #000; vertical-align: top; padding: 0;">
                    <div style="background: #f1f5f9; padding: 5px 10px; font-weight: 800; border-bottom: 1px solid #000; font-size: 13px;">
                        NOTIFY PARTY
                    </div>
                    <div style="padding: 8px 10px; min-height: 45px; font-weight: 600; color: #1e293b; white-space: pre-wrap;">{{ $si->notify_party ?: 'SAME AS CONSIGNEE' }}</div>
                </td>
            </tr>

            {{-- ROW 4: VESSEL & SCHEDULE vs SHIPMENT TERM --}}
            <tr>
                <td style="border: 1px solid #000; vertical-align: top; padding: 8px 10px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                        <div><strong style="color: #334155;">Vessel Name :</strong> <span style="font-weight: 700; color: #0f172a;">{{ $si->vessel_voyage ?: '—' }}</span></div>
                    </div>
                    <div style="display: flex; gap: 24px; font-size: 12px;">
                        <div><strong style="color: #334155;">ETD :</strong> <span style="font-weight: 700;">{{ $si->etd?->format('d/m/Y') ?: '—' }}</span></div>
                        <div><strong style="color: #334155;">ETA :</strong> <span style="font-weight: 700;">{{ $si->eta?->format('d/m/Y') ?: '—' }}</span></div>
                    </div>
                </td>
                <td style="border: 1px solid #000; vertical-align: middle; padding: 8px 10px; font-size: 12.5px;">
                    <strong style="color: #334155;">Shipment Term :</strong>
                    <span style="font-weight: 800; font-size: 13.5px; color: #0f172a; margin-left: 6px; padding: 2px 8px; background: #e2e8f0; border-radius: 4px;">
                        {{ $si->shipment_term }}
                    </span>
                </td>
            </tr>

            {{-- ROW 5: VESSEL (TRANSHIPMENT) vs LOADING & DISCHARGE --}}
            <tr>
                <td style="border: 1px solid #000; vertical-align: middle; padding: 8px 10px;">
                    @if($si->is_transhipment)
                        <div style="display:inline-block;background:#fef3c7;color:#92400e;font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px;margin-bottom:4px;">TRANSHIPMENT</div>
                        <div style="font-size:12px;">
                            <strong style="color:#334155;">Transit Port :</strong>
                            <span style="font-weight:700;color:#0f172a;">{{ $si->transit_port ?: '—' }}</span>
                        </div>
                        <div style="font-size:12px;display:flex;gap:16px;margin-top:2px;">
                            <div><strong style="color:#334155;">ETD Transit :</strong> <span style="font-weight:600;">{{ $si->transit_etd?->format('d/m/Y') ?: '—' }}</span></div>
                            <div><strong style="color:#334155;">ETA Transit :</strong> <span style="font-weight:600;">{{ $si->transit_eta?->format('d/m/Y') ?: '—' }}</span></div>
                        </div>
                        @if($si->connecting_vessel)
                            <div style="font-size:12px;margin-top:2px;"><strong style="color:#334155;">Connecting Vessel :</strong> <span style="font-weight:600;color:#0f172a;">{{ $si->connecting_vessel }}</span></div>
                        @endif
                    @else
                        <strong style="color: #334155;">Connecting Vessel :</strong>
                        <span style="font-weight: 600; color: #0f172a;">{{ $si->connecting_vessel ?: '—' }}</span>
                    @endif
                </td>
                <td style="border: 1px solid #000; vertical-align: middle; padding: 0;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr style="border-bottom: 1px solid #000;">
                            <td style="width: 32%; padding: 6px 10px; font-weight: 800; background: #f8fafc; border-right: 1px solid #000;">LOADING</td>
                            <td style="padding: 6px 10px; font-weight: 700; color: #0f172a;">{{ $si->pol }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 10px; font-weight: 800; background: #f8fafc; border-right: 1px solid #000;">DISCHARGE</td>
                            <td style="padding: 6px 10px; font-weight: 700; color: #0f172a;">{{ $si->pod }}</td>
                        </tr>
                    </table>
                </td>
            </tr>

            {{-- ROW 6: TABLE HEADER FOR CARGO (3 KOLOM) --}}
            <tr style="background: #e2e8f0; font-weight: 800; text-align: left; font-size: 12.5px;">
                <td style="width: 30%; border: 1px solid #000; padding: 6px 10px;">MARKS AND NUMBER</td>
                <td style="width: 45%; border: 1px solid #000; padding: 6px 10px;">DESCRIPTION</td>
                <td style="width: 25%; border: 1px solid #000; padding: 6px 10px;">GW/MEASUREMENT</td>
            </tr>

            {{-- ROW 7: TABLE BODY FOR CARGO (3 KOLOM) --}}
            <tr>
                <td style="border: 1px solid #000; vertical-align: top; padding: 10px; min-height: 120px; font-weight: 600; white-space: pre-wrap;">
                    {{ $si->marks_numbers ?: "N/M\n(NO MARKS)" }}
                </td>
                <td style="border: 1px solid #000; vertical-align: top; padding: 10px; font-weight: 600; white-space: pre-wrap; color: #0f172a;">
                    {{ $si->cargo_description }}
                </td>
                <td style="border: 1px solid #000; vertical-align: top; padding: 10px; font-weight: 600; line-height: 1.8;">
                    @if($si->quantity)
                        <div><strong>QTY :</strong> {{ $si->quantity }} {{ $si->package_unit }}</div>
                    @endif
                    <div><strong>G.W :</strong> {{ $si->gross_weight ? \App\Support\Money::format($si->gross_weight) . ' KGS' : '—' }}</div>
                    <div><strong>N.W :</strong> {{ $si->net_weight ? \App\Support\Money::format($si->net_weight) . ' KGS' : '—' }}</div>
                    <div><strong>MEAS :</strong> {{ $si->measurement ? \App\Support\Money::format($si->measurement) . ' CBM' : '—' }}</div>
                </td>
            </tr>

            {{-- ROW 8: REMARKS --}}
            <tr>
                <td colspan="3" style="border: 1px solid #000; padding: 0;">
                    <div style="background: #f1f5f9; padding: 5px 10px; font-weight: 800; border-bottom: 1px solid #000; font-size: 13px;">
                        REMARKS
                    </div>
                    <div style="padding: 10px; min-height: 60px; font-size: 12px; font-weight: 600; white-space: pre-wrap; color: #1e293b;">
                        {{ $si->remarks ?: '—' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
</section>

@endsection
