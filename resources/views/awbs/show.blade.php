@extends('layouts.app')
@section('title', 'AWB ' . $awb->number)
@section('content')

@php
    $backUrl = $awb->job_id ? route('jobs.show', $awb->job_id) . '#tab-awb' : route('awbs.index');
@endphp

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL — EXPORT AIR</p>
        <h1>{{ $awb->number }}</h1>
        <p>✈️ Airline: <strong>{{ $awb->airline ?: '—' }}</strong> @if($awb->job) · Job: <a class="text-link" href="{{ route('jobs.show', $awb->job) }}">{{ $awb->job->number }}</a>@endif</p>
    </div>
    <a class="button button-secondary" href="{{ $backUrl }}">← Kembali</a>
</div>

<div class="quote-actions" style="margin-bottom: 20px; display: flex; gap: 8px; flex-wrap: wrap;">
    <a class="button button-secondary" href="{{ route('awbs.preview', [$awb, 'type' => 'draft']) }}" target="_blank">📄 Cetak AWB Draft</a>
    <a class="button button-secondary" href="{{ route('awbs.preview', [$awb, 'type' => 'hawb']) }}" target="_blank">📄 Cetak HAWB</a>
    <a class="button button-secondary" href="{{ route('awbs.preview', [$awb, 'type' => 'mawb']) }}" target="_blank">📄 Cetak MAWB</a>
    <a class="button button-secondary" href="{{ route('awbs.edit', $awb) }}">Edit AWB</a>
    @if($awb->job)
        <a class="button button-secondary" href="{{ route('jobs.show', $awb->job) . '#tab-awb' }}">Lihat Job Order</a>
    @endif
    <form method="POST" action="{{ route('awbs.destroy', $awb) }}" data-confirm="Hapus AWB {{ $awb->number }}?" style="display:inline;">
        @csrf @method('DELETE')
        <button class="button button-danger" style="background:#ef4444;border-color:#ef4444;">Hapus</button>
    </form>
</div>

<section class="panel" style="overflow:hidden;margin-bottom:24px;border:1px solid #000;background:#fff;padding:0;">
    {{-- HEADER --}}
    <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 20px;background:#f8fafc;border-bottom:1px solid #000;">
        <div style="display:flex;align-items:center;gap:14px;">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" style="max-height:40px;max-width:160px;" onerror="this.style.display='none'">
            <div>
                <div style="font-weight:800;font-size:15px;letter-spacing:0.5px;">AIR WAYBILL (AWB)</div>
                <div style="font-size:13px;font-weight:700;color:#334155;">{{ $awb->number }}</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <span class="status-badge status-{{ in_array($awb->status, ['issued','completed']) ? 'approved' : ($awb->status === 'cancelled' ? 'rejected' : 'draft') }}">
                {{ ucfirst($awb->status) }}
            </span>
            <span class="badge-pill" style="font-weight:700;">{{ $awb->freight_term }}</span>
            <span class="badge-pill">{{ $awb->currency }} (Kurs: {{ number_format($awb->exchange_rate ?? 1, 2) }})</span>
        </div>
    </div>

    <div style="padding:16px;">
        {{-- SUMMARY HEADER GRID --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            {{-- SHIPPER BOX --}}
            <div style="border:1px solid #000;padding:0;">
                <div style="background:#f1f5f9;padding:6px 12px;font-weight:800;border-bottom:1px solid #000;">SHIPPER</div>
                <div style="padding:10px 12px;min-height:70px;font-size:12.5px;">
                    <div><strong>Shipper on HAWB:</strong> {{ $awb->shipper_on_hawb ?: '—' }}</div>
                    @if($awb->shipper_on_mawb)
                        <div style="margin-top:4px;color:#64748b;"><strong>Shipper on MAWB:</strong> {{ $awb->shipper_on_mawb }}</div>
                    @endif
                </div>
            </div>

            {{-- CONSIGNEE BOX --}}
            <div style="border:1px solid #000;padding:0;">
                <div style="background:#f1f5f9;padding:6px 12px;font-weight:800;border-bottom:1px solid #000;">CONSIGNEE & NOTIFY</div>
                <div style="padding:10px 12px;min-height:70px;font-size:12.5px;">
                    <div><strong>Consignee on HAWB:</strong> {{ $awb->consignee_on_hawb ?: '—' }}</div>
                    @if($awb->consignee_on_mawb)
                        <div style="margin-top:4px;color:#64748b;"><strong>Consignee on MAWB:</strong> {{ $awb->consignee_on_mawb }}</div>
                    @endif
                    @if($awb->notify_party)
                        <div style="margin-top:4px;"><strong>Notify Party:</strong> {{ $awb->notify_party }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- FLIGHT & ROUTING TABLE --}}
        <table style="width:100%;border-collapse:collapse;border:1px solid #000;margin-bottom:16px;font-size:12px;">
            <thead>
                <tr style="background:#e2e8f0;font-weight:800;">
                    <th style="padding:7px 10px;border:1px solid #000;text-align:left;">AWB Date</th>
                    <th style="padding:7px 10px;border:1px solid #000;text-align:left;">HAWB No.</th>
                    <th style="padding:7px 10px;border:1px solid #000;text-align:left;">MAWB No.</th>
                    <th style="padding:7px 10px;border:1px solid #000;text-align:left;">Airlines</th>
                    <th style="padding:7px 10px;border:1px solid #000;text-align:left;">Flight 1 & Date</th>
                    <th style="padding:7px 10px;border:1px solid #000;text-align:left;">Conn. Flight</th>
                    <th style="padding:7px 10px;border:1px solid #000;text-align:left;">AOL &rarr; AOD</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding:8px 10px;border:1px solid #000;">{{ $awb->awb_date->format('d/m/Y') }}</td>
                    <td style="padding:8px 10px;border:1px solid #000;font-weight:600;">{{ $awb->hawb_number ?: '—' }}</td>
                    <td style="padding:8px 10px;border:1px solid #000;font-weight:600;">{{ $awb->mawb_number ?: '—' }}</td>
                    <td style="padding:8px 10px;border:1px solid #000;font-weight:700;">{{ $awb->airline ?: '—' }} {{ $awb->airline_code ? '('.$awb->airline_code.')' : '' }}</td>
                    <td style="padding:8px 10px;border:1px solid #000;">{{ $awb->flight_number ?: '—' }} @if($awb->flight_date)<br><small>{{ $awb->flight_date->format('d/m/Y') }}</small>@endif</td>
                    <td style="padding:8px 10px;border:1px solid #000;">{{ $awb->connecting_flight ?: '—' }} @if($awb->connecting_flight_date)<br><small>{{ $awb->connecting_flight_date->format('d/m/Y') }}</small>@endif</td>
                    <td style="padding:8px 10px;border:1px solid #000;font-weight:600;">
                        {{ $awb->airport_of_departure ?: '—' }}
                        @if($awb->transit_airport) &rarr; <span style="color:#2563eb;">{{ $awb->transit_airport }}</span> @endif
                        &rarr; {{ $awb->airport_of_destination ?: '—' }}
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- CARGO & VALUATION TABLE --}}
        <table style="width:100%;border-collapse:collapse;border:1px solid #000;font-size:12px;">
            <thead>
                <tr style="background:#e2e8f0;font-weight:800;">
                    <th style="padding:7px 10px;border:1px solid #000;text-align:left;">Nature & Quantity of Goods (Commodity)</th>
                    <th style="padding:7px 10px;border:1px solid #000;text-align:center;">Pieces</th>
                    <th style="padding:7px 10px;border:1px solid #000;text-align:center;">Gross Weight</th>
                    <th style="padding:7px 10px;border:1px solid #000;text-align:center;">Chg. Weight</th>
                    <th style="padding:7px 10px;border:1px solid #000;text-align:center;">Volume (CBM)</th>
                    <th style="padding:7px 10px;border:1px solid #000;text-align:center;">Declared Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding:10px;border:1px solid #000;white-space:pre-wrap;">{{ $awb->commodity ?: '—' }}</td>
                    <td style="padding:10px;border:1px solid #000;text-align:center;font-weight:600;">{{ $awb->pieces ?: '—' }}</td>
                    <td style="padding:10px;border:1px solid #000;text-align:center;font-weight:600;">{{ $awb->gross_weight ? number_format($awb->gross_weight, 2) . ' ' . ($awb->gross_weight_unit ?: 'KGS') : '—' }}</td>
                    <td style="padding:10px;border:1px solid #000;text-align:center;font-weight:600;">{{ $awb->chargeable_weight ? number_format($awb->chargeable_weight, 2) . ' KGS' : '—' }}</td>
                    <td style="padding:10px;border:1px solid #000;text-align:center;">{{ $awb->volume ? number_format($awb->volume, 3) : '—' }}</td>
                    <td style="padding:10px;border:1px solid #000;font-size:11px;">
                        <div>Carriage: {{ $awb->value_of_carriage ?: 'N.V.D.' }}</div>
                        <div>Customs: {{ $awb->value_of_customs ?: 'N.C.V.' }}</div>
                    </td>
                </tr>
            </tbody>
        </table>

        @if($awb->agent_name)
        <div style="margin-top:12px;padding:8px 12px;background:#f8fafc;border:1px solid #000;font-size:12px;">
            <strong>Destination Agent:</strong> {{ $awb->agent_name }}
        </div>
        @endif

        @if($awb->remarks)
        <div style="margin-top:12px;padding:10px 12px;background:#f8fafc;border:1px solid #000;font-size:12px;">
            <div style="font-weight:800;margin-bottom:4px;">HANDLING INFORMATION / REMARKS</div>
            <div style="white-space:pre-wrap;">{{ $awb->remarks }}</div>
        </div>
        @endif

        <div style="margin-top:16px;font-size:12px;color:#64748b;">
            Diterbitkan oleh: {{ $awb->creator?->name ?? '—' }} · Tanggal AWB: {{ $awb->awb_date->format('d/m/Y') }}
        </div>
    </div>
</section>

@endsection
