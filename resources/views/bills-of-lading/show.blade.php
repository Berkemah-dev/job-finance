@extends('layouts.app')
@section('title', 'B/L ' . $bl->number)
@section('content')

@php
    $backUrl = $bl->job_id ? route('jobs.show', $bl->job_id) . '#tab-bl' : route('bills-of-lading.index');
@endphp

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL — EXPORT SEA</p>
        <h1>{{ $bl->number }}</h1>
        <p>🚢 Carrier: <strong>{{ $bl->carrier ?: '—' }}</strong> @if($bl->job) · Job: <a class="text-link" href="{{ route('jobs.show', $bl->job) }}">{{ $bl->job->number }}</a>@endif</p>
    </div>
    <a class="button button-secondary" href="{{ $backUrl }}">← Kembali</a>
</div>

<div class="quote-actions" style="margin-bottom:20px;">
    <a class="button button-secondary" href="{{ route('bills-of-lading.edit', $bl) }}">Edit B/L</a>
    @if($bl->job)
        <a class="button button-secondary" href="{{ route('jobs.show', $bl->job) . '#tab-bl' }}">Lihat Job Order</a>
    @endif
    <form method="POST" action="{{ route('bills-of-lading.destroy', $bl) }}" data-confirm="Hapus B/L {{ $bl->number }}?" style="display:inline;">
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
                <div style="font-weight:800;font-size:15px;letter-spacing:0.5px;">BILL OF LADING (B/L)</div>
                <div style="font-size:13px;font-weight:700;color:#334155;">{{ $bl->number }}</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <span class="status-badge status-{{ in_array($bl->status, ['issued','released','completed']) ? 'approved' : ($bl->status === 'cancelled' ? 'rejected' : 'draft') }}">
                {{ ucfirst($bl->status) }}
            </span>
            <span class="badge-pill" style="font-weight:700;">{{ $bl->freight_term }}</span>
            <span class="badge-pill">{{ strtoupper($bl->bl_type) }}</span>
        </div>
    </div>

    <div style="padding:16px;">
        {{-- TABEL B/L FORMAT DOKUMEN --}}
        <table style="width:100%;border-collapse:collapse;border:1.5px solid #000;font-size:12.5px;line-height:1.4;">
            {{-- ROW 1: SHIPPER vs HEADER INFO --}}
            <tr>
                <td style="width:50%;border:1px solid #000;vertical-align:top;padding:0;">
                    <div style="background:#f1f5f9;padding:5px 10px;font-weight:800;border-bottom:1px solid #000;">SHIPPER</div>
                    <div style="padding:8px 10px;min-height:55px;">
                        <strong>{{ $bl->shipper_name ?: '—' }}</strong>
                        @if($bl->shipper_switch)
                            <div style="font-size:11px;color:#64748b;margin-top:4px;">Switch Shipper: {{ $bl->shipper_switch }}</div>
                        @endif
                    </div>
                </td>
                <td rowspan="3" style="width:50%;border:1px solid #000;vertical-align:top;padding:12px 14px;">
                    <table style="width:100%;border-collapse:collapse;font-size:12px;">
                        <tr>
                            <td style="width:38%;font-weight:700;padding:2px 0;">B/L Date</td>
                            <td style="width:3%;">:</td>
                            <td style="font-weight:700;color:#0f172a;">{{ $bl->bl_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:700;padding:2px 0;">HBL No.</td>
                            <td>:</td>
                            <td style="font-weight:600;">{{ $bl->hbl_number ?: $bl->number }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:700;padding:2px 0;">MBL No.</td>
                            <td>:</td>
                            <td style="font-weight:600;">{{ $bl->mbl_number ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:700;padding:2px 0;">No. of Original BL</td>
                            <td>:</td>
                            <td style="font-weight:600;">{{ $bl->original_bl_count ?? 3 }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:700;padding:2px 0;">Place / Date of Issue</td>
                            <td>:</td>
                            <td style="font-weight:600;">{{ $bl->place_of_issue ?: 'JAKARTA' }} / {{ $bl->date_of_issue?->format('d/m/Y') ?: $bl->bl_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:700;padding:2px 0;">Freight Payable at</td>
                            <td>:</td>
                            <td style="font-weight:600;">{{ $bl->freight_payable_at ?: 'JAKARTA' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:700;padding:2px 0;">Carrier / Line</td>
                            <td>:</td>
                            <td style="font-weight:700;color:#0f172a;">{{ $bl->carrier ?: '—' }}</td>
                        </tr>
                        @if($bl->carrier_bl_number)
                        <tr>
                            <td style="font-weight:700;padding:2px 0;">Carrier B/L No.</td>
                            <td>:</td>
                            <td>{{ $bl->carrier_bl_number }}</td>
                        </tr>
                        @endif
                        @if($bl->customer_ref_number)
                        <tr>
                            <td style="font-weight:700;padding:2px 0;">Customer Ref No.</td>
                            <td>:</td>
                            <td>{{ $bl->customer_ref_number }}</td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
            <tr>
                <td style="border:1px solid #000;vertical-align:top;padding:0;">
                    <div style="background:#f1f5f9;padding:5px 10px;font-weight:800;border-bottom:1px solid #000;">CONSIGNEE</div>
                    <div style="padding:8px 10px;min-height:55px;">
                        <strong>{{ $bl->consignee_name ?: '—' }}</strong>
                        @if($bl->consignee_switch)
                            <div style="font-size:11px;color:#64748b;margin-top:4px;">Switch Consignee: {{ $bl->consignee_switch }}</div>
                        @endif
                    </div>
                </td>
            </tr>
            <tr>
                <td style="border:1px solid #000;vertical-align:top;padding:0;">
                    <div style="background:#f1f5f9;padding:5px 10px;font-weight:800;border-bottom:1px solid #000;">NOTIFY PARTY</div>
                    <div style="padding:8px 10px;min-height:40px;font-weight:600;white-space:pre-wrap;">{{ $bl->notify_party ?: 'SAME AS CONSIGNEE' }}</div>
                </td>
            </tr>
            {{-- VESSEL & ROUTE ROW --}}
            <tr>
                <td style="border:1px solid #000;vertical-align:middle;padding:8px 10px;">
                    <div><strong>Pre Carriage :</strong> {{ $bl->pre_carriage ?: '—' }}</div>
                    <div><strong>Vessel / Voyage :</strong> {{ $bl->vessel_voyage ?: '—' }}</div>
                    <div style="display:flex;gap:20px;font-size:12px;margin-top:2px;">
                        <div><strong>ETD :</strong> {{ $bl->etd?->format('d/m/Y') ?: '—' }}</div>
                        <div><strong>ETA :</strong> {{ $bl->eta?->format('d/m/Y') ?: '—' }}</div>
                    </div>
                    @if($bl->shipped_on_board_date)
                        <div style="margin-top:2px;"><strong>Shipped on Board :</strong> {{ $bl->shipped_on_board_date->format('d/m/Y') }}</div>
                    @endif
                </td>
                <td style="border:1px solid #000;vertical-align:middle;padding:0;">
                    <table style="width:100%;border-collapse:collapse;">
                        @if($bl->place_of_receipt)
                        <tr style="border-bottom:1px solid #000;">
                            <td style="width:32%;padding:6px 10px;font-weight:800;background:#f8fafc;border-right:1px solid #000;">RECEIPT</td>
                            <td style="padding:6px 10px;font-weight:700;">{{ $bl->place_of_receipt }}</td>
                        </tr>
                        @endif
                        <tr style="border-bottom:1px solid #000;">
                            <td style="width:32%;padding:6px 10px;font-weight:800;background:#f8fafc;border-right:1px solid #000;">PORT OF LOADING</td>
                            <td style="padding:6px 10px;font-weight:700;">{{ $bl->pol ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 10px;font-weight:800;background:#f8fafc;border-right:1px solid #000;">PORT OF DISCHARGE</td>
                            <td style="padding:6px 10px;font-weight:700;">{{ $bl->pod ?: '—' }}</td>
                        </tr>
                        @if($bl->place_of_delivery)
                        <tr>
                            <td style="padding:6px 10px;font-weight:800;background:#f8fafc;border-right:1px solid #000;border-top:1px solid #000;">PLACE OF DELIVERY</td>
                            <td style="padding:6px 10px;font-weight:700;border-top:1px solid #000;">{{ $bl->place_of_delivery }}</td>
                        </tr>
                        @endif
                        @if($bl->final_destination)
                        <tr>
                            <td style="padding:6px 10px;font-weight:800;background:#f8fafc;border-right:1px solid #000;border-top:1px solid #000;">DESTINATION</td>
                            <td style="padding:6px 10px;font-weight:700;border-top:1px solid #000;">{{ $bl->final_destination }}</td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
            {{-- CARGO HEADERS --}}
            <tr style="background:#e2e8f0;font-weight:800;font-size:12px;">
                <td style="border:1px solid #000;padding:6px 10px;">MARKS AND NUMBER / QTY</td>
                <td style="border:1px solid #000;padding:6px 10px;">DESCRIPTION OF GOODS / GW / MEAS</td>
            </tr>
            {{-- CARGO CONTENT --}}
            <tr>
                <td style="border:1px solid #000;vertical-align:top;padding:10px;min-height:120px;font-size:12px;">
                    <div style="white-space:pre-wrap;margin-bottom:10px;">{{ $bl->marks_numbers ?: "N/M\n(NO MARKS)" }}</div>
                    @if($bl->package_count)
                        <div><strong>Quantity :</strong> {{ $bl->package_count }} {{ $bl->package_unit ?: 'Package' }}</div>
                    @endif
                    @if($bl->party)
                        <div><strong>Party :</strong> {{ $bl->party }}</div>
                    @endif
                </td>
                <td style="border:1px solid #000;vertical-align:top;padding:10px;font-size:12px;">
                    <div style="white-space:pre-wrap;margin-bottom:10px;">{{ $bl->cargo_description ?: '—' }}</div>
                    <div><strong>Gross Weight :</strong> {{ $bl->gross_weight ? number_format($bl->gross_weight, 2) . ' KGS' : '—' }}</div>
                    @if($bl->net_weight)
                        <div><strong>Net Weight :</strong> {{ number_format($bl->net_weight, 2) }} KGS</div>
                    @endif
                    <div><strong>Measurement :</strong> {{ $bl->measurement ? number_format($bl->measurement, 3) . ' M3' : '—' }}</div>
                </td>
            </tr>
            {{-- AGENT & REMARKS --}}
            @if($bl->agent_name)
            <tr>
                <td colspan="2" style="border:1px solid #000;padding:8px 10px;background:#f8fafc;">
                    <strong>Delivery Agent at Port of Discharge:</strong> {{ $bl->agent_name }}
                </td>
            </tr>
            @endif
            @if($bl->remarks)
            <tr>
                <td colspan="2" style="border:1px solid #000;padding:0;">
                    <div style="background:#f1f5f9;padding:5px 10px;font-weight:800;border-bottom:1px solid #000;">REMARKS</div>
                    <div style="padding:8px 10px;white-space:pre-wrap;font-size:12px;">{{ $bl->remarks }}</div>
                </td>
            </tr>
            @endif
        </table>
    </div>
</section>

@endsection
