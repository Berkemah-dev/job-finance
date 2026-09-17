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

<div class="quote-actions" style="margin-bottom: 20px;">
    <a class="button button-secondary" href="{{ route('awbs.edit', $awb) }}">Edit AWB</a>
    @if($awb->job)
        <a class="button button-secondary" href="{{ route('jobs.show', $awb->job) }}">Lihat Job Order</a>
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
                Status: {{ ucfirst($awb->status) }}
            </span>
            <span class="badge-pill" style="font-weight:700;">{{ $awb->freight_term }}</span>
        </div>
    </div>

    <div style="padding:20px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            {{-- SHIPPER --}}
            <div style="border:1px solid #000;padding:0;">
                <div style="background:#f1f5f9;padding:5px 12px;font-weight:800;border-bottom:1px solid #000;">SHIPPER</div>
                <div style="padding:10px 12px;min-height:50px;">
                    <strong>{{ $awb->shipper_name ?: '—' }}</strong>
                </div>
            </div>
            {{-- CONSIGNEE --}}
            <div style="border:1px solid #000;padding:0;">
                <div style="background:#f1f5f9;padding:5px 12px;font-weight:800;border-bottom:1px solid #000;">CONSIGNEE</div>
                <div style="padding:10px 12px;min-height:50px;">
                    <strong>{{ $awb->consignee_name ?: '—' }}</strong>
                </div>
            </div>
        </div>

        {{-- FLIGHT DETAILS --}}
        <table style="width:100%;border-collapse:collapse;border:1px solid #000;margin-bottom:16px;font-size:13px;">
            <thead>
                <tr style="background:#e2e8f0;">
                    <th style="padding:8px 12px;border:1px solid #000;text-align:left;">Airline</th>
                    <th style="padding:8px 12px;border:1px solid #000;text-align:left;">Flight No.</th>
                    <th style="padding:8px 12px;border:1px solid #000;text-align:left;">From</th>
                    <th style="padding:8px 12px;border:1px solid #000;text-align:left;">To</th>
                    <th style="padding:8px 12px;border:1px solid #000;text-align:left;">ETD</th>
                    <th style="padding:8px 12px;border:1px solid #000;text-align:left;">ETA</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding:8px 12px;border:1px solid #000;font-weight:700;">{{ $awb->airline ?: '—' }} {{ $awb->airline_code ? '('.$awb->airline_code.')' : '' }}</td>
                    <td style="padding:8px 12px;border:1px solid #000;">{{ $awb->flight_number ?: '—' }}</td>
                    <td style="padding:8px 12px;border:1px solid #000;font-weight:600;">{{ $awb->airport_of_departure ?: '—' }}</td>
                    <td style="padding:8px 12px;border:1px solid #000;font-weight:600;">{{ $awb->airport_of_destination ?: '—' }}</td>
                    <td style="padding:8px 12px;border:1px solid #000;">{{ $awb->etd?->format('d/m/Y') ?: '—' }}</td>
                    <td style="padding:8px 12px;border:1px solid #000;">{{ $awb->eta?->format('d/m/Y') ?: '—' }}</td>
                </tr>
            </tbody>
        </table>

        {{-- CARGO DETAILS --}}
        <table style="width:100%;border-collapse:collapse;border:1px solid #000;font-size:13px;">
            <thead>
                <tr style="background:#e2e8f0;">
                    <th style="padding:8px 12px;border:1px solid #000;text-align:left;">Commodity</th>
                    <th style="padding:8px 12px;border:1px solid #000;text-align:center;">Pieces</th>
                    <th style="padding:8px 12px;border:1px solid #000;text-align:center;">G.W (KGS)</th>
                    <th style="padding:8px 12px;border:1px solid #000;text-align:center;">Chg. Weight</th>
                    <th style="padding:8px 12px;border:1px solid #000;text-align:center;">Volume (CBM)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding:8px 12px;border:1px solid #000;">{{ $awb->commodity ?: '—' }}</td>
                    <td style="padding:8px 12px;border:1px solid #000;text-align:center;">{{ $awb->pieces ?: '—' }}</td>
                    <td style="padding:8px 12px;border:1px solid #000;text-align:center;">{{ $awb->gross_weight ? number_format($awb->gross_weight, 2) : '—' }}</td>
                    <td style="padding:8px 12px;border:1px solid #000;text-align:center;">{{ $awb->chargeable_weight ? number_format($awb->chargeable_weight, 2) : '—' }}</td>
                    <td style="padding:8px 12px;border:1px solid #000;text-align:center;">{{ $awb->volume ? number_format($awb->volume, 3) : '—' }}</td>
                </tr>
            </tbody>
        </table>

        @if($awb->remarks)
        <div style="margin-top:16px;padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;">
            <div style="font-weight:700;font-size:12px;color:#475569;margin-bottom:4px;">REMARKS</div>
            <div style="white-space:pre-wrap;font-size:13px;">{{ $awb->remarks }}</div>
        </div>
        @endif

        <div style="margin-top:16px;font-size:12px;color:#64748b;">
            Dibuat oleh: {{ $awb->creator?->name ?? '—' }} · Tanggal AWB: {{ $awb->awb_date->format('d/m/Y') }}
        </div>
    </div>
</section>

@endsection
