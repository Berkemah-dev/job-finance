@extends('layouts.app')
@section('title', 'Booking Confirmation ' . $bc->number)
@section('content')

@php
    $consigneeName = $bc->consignee_name ?: ($bc->job?->consignee_name ?: ($bc->customer?->consignees?->first()?->name ?? '—'));
    $consigneeAddress = $bc->consignee_address ?: ($bc->job?->consignee_address ?: ($bc->customer?->consignees?->first()?->address ?? '—'));
    $consigneeContact = $bc->contact_person ?: ($bc->consignee_contact ?: ($job?->consignee_contact ?: ($bc->customer?->consignees?->first()?->contact_name ?: ($bc->customer?->consignees?->first()?->phone ?? '—'))));

    $shipperCustomer = $bc->customer ?? $bc->job?->customer;
    $shipperName = $shipperCustomer?->name ?? ($bc->shipper_name ?: '—');
    $shipperAddress = $shipperCustomer?->address ?? ($bc->job?->shipper_address ?? '—');

    $carrierStr = $bc->carrier_name ?: '—';
    if ($bc->carrier_booking_no) {
        $carrierStr .= ' (' . $bc->carrier_booking_no . ')';
    }

    $rawNotes = $bc->notes;
    $bulletClauses = [];
    if ($rawNotes) {
        $lines = explode("\n", str_replace("\r", "", $rawNotes));
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') continue;
            if (preg_match('/^[0-9]+\.\s*(.*)$/', $trimmed, $m)) {
                $bulletClauses[] = $m[1];
            } elseif (str_starts_with($trimmed, '•')) {
                $bulletClauses[] = trim(ltrim($trimmed, '• '));
            } elseif (str_starts_with($trimmed, '-')) {
                $bulletClauses[] = trim(ltrim($trimmed, '- '));
            } else {
                $bulletClauses[] = $trimmed;
            }
        }
    }
@endphp

@php
    $backUrl = $bc->job_id 
        ? route('jobs.show', $bc->job_id) . '#tab-booking' 
        : route('booking-confirmations.index');
@endphp

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL</p>
        <h1>{{ $bc->number }}</h1>
        <p>Customer: <strong>{{ $shipperName }}</strong> @if($bc->job) · Job Order: <a class="text-link" href="{{ route('jobs.show', $bc->job) }}">{{ $bc->job->number }}</a>@endif</p>
    </div>
    <a class="button button-secondary" href="{{ $backUrl }}">← Kembali</a>
</div>

{{-- ACTION BUTTONS --}}
<div class="quote-actions" style="margin-bottom: 20px; display: flex; gap: 8px; flex-wrap: wrap;">
    <a class="button button-secondary" href="{{ route('booking-confirmations.edit', $bc) }}">
        <x-icon name="pencil"/> Edit Booking Confirmation
    </a>
    <a class="button button-secondary" href="{{ route('booking-confirmations.preview', $bc) }}" target="_blank">
        <x-icon name="printer"/> Preview / Unduh PDF
    </a>
    @if($bc->job)
        <a class="button button-secondary" href="{{ route('jobs.show', $bc->job) }}">
            <x-icon name="briefcase"/> Lihat Job Order
        </a>
    @endif
    <form method="POST" action="{{ route('booking-confirmations.destroy', $bc) }}" data-confirm="Hapus Booking Confirmation {{ $bc->number }}?" style="display:inline;">
        @csrf
        @method('DELETE')
        <button class="button button-danger" style="background:#ef4444;border-color:#ef4444;color:#fff;">
            <x-icon name="trash"/> Hapus
        </button>
    </form>
</div>

{{-- 1. INFORMASI UTAMA & STATUS --}}
<section class="panel" style="margin-bottom: 20px;">
    <div class="panel-heading">
        <h2><x-icon name="file" style="width:16px;height:16px;margin-right:6px;display:inline-block;vertical-align:middle;color:#0f1f3d;"/> Informasi Utama Dokumen</h2>
        <span class="status-badge status-{{ $bc->status === 'confirmed' ? 'approved' : ($bc->status === 'draft' ? 'draft' : 'rejected') }}">
            Status: {{ ucfirst($bc->status) }}
        </span>
    </div>
    <dl class="detail-grid">
        <div>
            <dt>Nomor Booking Confirmation</dt>
            <dd><strong style="font-size: 15px; color: #0f172a;">{{ $bc->number }}</strong></dd>
        </div>
        <div>
            <dt>Tanggal Booking</dt>
            <dd>{{ $bc->booking_date ? $bc->booking_date->format('d/m/Y') : '—' }}</dd>
        </div>
        <div>
            <dt>Customer (Pemilik Muatan)</dt>
            <dd><strong>{{ $shipperName }}</strong></dd>
        </div>
        <div>
            <dt>Terkait Job Order</dt>
            <dd>
                @if($bc->job)
                    <a class="text-link" href="{{ route('jobs.show', $bc->job) }}"><strong>{{ $bc->job->number }}</strong></a>
                @else
                    <span class="subtle">— Tidak terhubung —</span>
                @endif
            </dd>
        </div>
        <div>
            <dt>Customer Ref / No. Referensi</dt>
            <dd>{{ $bc->customer_ref ?: '—' }}</dd>
        </div>
        <div>
            <dt>Dibuat Oleh</dt>
            <dd>{{ $bc->creator?->name ?? 'System' }}</dd>
        </div>
    </dl>
</section>

{{-- 2. PIHAK PENGIRIM & PENERIMA --}}
<section class="panel" style="margin-bottom: 20px;">
    <div class="panel-heading">
        <h2><x-icon name="user" style="width:16px;height:16px;margin-right:6px;display:inline-block;vertical-align:middle;color:#0f1f3d;"/> Pihak Pengirim & Penerima</h2>
    </div>
    <dl class="detail-grid">
        <div>
            <dt>Shipper (Pengirim)</dt>
            <dd><strong>{{ $shipperName }}</strong></dd>
        </div>
        <div>
            <dt>Consignee (Penerima)</dt>
            <dd><strong>{{ $consigneeName }}</strong></dd>
        </div>
        <div>
            <dt>Alamat Shipper</dt>
            <dd style="line-height: 1.4;">{{ $shipperAddress }}</dd>
        </div>
        <div>
            <dt>Alamat Consignee</dt>
            <dd style="line-height: 1.4;">{{ $consigneeAddress }}</dd>
        </div>
        <div>
            <dt>Contact Person Consignee</dt>
            <dd>{{ $consigneeContact }}</dd>
        </div>
    </dl>
</section>

{{-- 3. RINCIAN PENGAPALAN & RUTE --}}
<section class="panel" style="margin-bottom: 20px;">
    <div class="panel-heading">
        <h2><x-icon name="map-pin" style="width:16px;height:16px;margin-right:6px;display:inline-block;vertical-align:middle;color:#0f1f3d;"/> Rincian Pengapalan & Rute (Shipment & Routing)</h2>
    </div>
    <dl class="detail-grid">
        <div>
            <dt>Carrier / Vendor Pelayaran</dt>
            <dd><strong>{{ $bc->carrier_name ?: '—' }}</strong></dd>
        </div>
        <div>
            <dt>Nomor Booking Carrier</dt>
            <dd><strong>{{ $bc->carrier_booking_no ?: '—' }}</strong></dd>
        </div>
        <div>
            <dt>Kapal / Vessel & Voyage</dt>
            <dd>{{ $bc->vessel_voyage ?: '—' }}</dd>
        </div>
        <div>
            <dt>Services (Term Layanan)</dt>
            <dd><span class="badge-pill">{{ $bc->service_term ?: 'CY/CY' }}</span></dd>
        </div>
        <div>
            <dt>Port of Loading (POL)</dt>
            <dd>{{ $bc->pol ?: '—' }}</dd>
        </div>
        <div>
            <dt>Port of Discharge (POD)</dt>
            <dd>{{ $bc->pod ?: '—' }}</dd>
        </div>
        <div>
            <dt>ETD (Estimasi Keberangkatan)</dt>
            <dd>{{ $bc->etd ? $bc->etd->format('d/m/Y') : '—' }}</dd>
        </div>
        <div>
            <dt>ETA (Estimasi Kedatangan)</dt>
            <dd>{{ $bc->eta ? $bc->eta->format('d/m/Y') : '—' }}</dd>
        </div>
    </dl>
</section>

{{-- 4. RINCIAN MUATAN (CARGO DETAILS) --}}
<section class="panel" style="margin-bottom: 20px;">
    <div class="panel-heading">
        <h2><x-icon name="briefcase" style="width:16px;height:16px;margin-right:6px;display:inline-block;vertical-align:middle;color:#0f1f3d;"/> Rincian Muatan (Cargo Details)</h2>
    </div>
    <dl class="detail-grid">
        <div>
            <dt>Quantity (Kuantitas & Satuan)</dt>
            <dd><strong>{{ $bc->quantity ?: '—' }}{{ $bc->package_unit ? ' ' . $bc->package_unit : '' }}</strong></dd>
        </div>
        <div>
            <dt>Gross Weight (Berat Kotor)</dt>
            <dd>{{ $bc->gross_weight ? \App\Support\Money::format($bc->gross_weight) . ' KGS' : '—' }}</dd>
        </div>
        <div>
            <dt>Volume (Kubikasi)</dt>
            <dd>{{ $bc->volume ? \App\Support\Money::format($bc->volume) . ' M3' : '—' }}</dd>
        </div>
        <div class="span-2">
            <dt>Description (Deskripsi / Uraian Barang)</dt>
            <dd style="white-space: pre-wrap; line-height: 1.4;">{{ $bc->cargo_description ?: 'General Cargo' }}</dd>
        </div>
    </dl>
</section>

{{-- 5. PENYERAHAN MUATAN & JADWAL CUT-OFF --}}
<section class="panel" style="margin-bottom: 20px;">
    <div class="panel-heading">
        <h2><x-icon name="calendar" style="width:16px;height:16px;margin-right:6px;display:inline-block;vertical-align:middle;color:#0f1f3d;"/> Penyerahan Muatan & Jadwal Cut-Off</h2>
    </div>
    <dl class="detail-grid">
        <div class="span-2">
            <dt>Delivery Cargo To (Lokasi Penyerahan Terminal/Depo)</dt>
            <dd style="font-size: 14px; font-weight: 600; color: #0f172a;">{{ $bc->delivery_cargo_to ?: '—' }}</dd>
        </div>
        <div>
            <dt>Doc Cut-Off (Dokumen)</dt>
            <dd>{{ $bc->doc_cutoff_at ? $bc->doc_cutoff_at->format('d/m/Y H:i') : '—' }}</dd>
        </div>
        <div>
            <dt>CY Cut-Off (Closing Container)</dt>
            <dd>{{ $bc->cy_cutoff_at ? $bc->cy_cutoff_at->format('d/m/Y H:i') : '—' }}</dd>
        </div>
        <div>
            <dt>Delivery Cut-Off (Muatan Fisik)</dt>
            <dd>{{ $bc->delivery_cutoff_at ? $bc->delivery_cutoff_at->format('d/m/Y H:i') : '—' }}</dd>
        </div>
    </dl>
</section>

{{-- 6. KLAUSUL & CATATAN BOOKING (IMPORTANT NOTES) --}}
<section class="panel">
    <div class="panel-heading">
        <h2><x-icon name="file" style="width:16px;height:16px;margin-right:6px;display:inline-block;vertical-align:middle;color:#0f1f3d;"/> Klausul & Catatan Booking (Important Notes)</h2>
        <span class="subtle">Syarat & Ketentuan Standar Pengapalan</span>
    </div>
    <div style="padding: 18px 22px;">
        @if(!empty($bulletClauses))
            <ol style="margin: 0; padding-left: 20px; line-height: 1.65; font-size: 13px; color: #334155;">
                @foreach($bulletClauses as $clause)
                    <li style="margin-bottom: 8px;">
                        @if(str_contains($clause, 'LONG LENGTH/OVERWEIGHT'))
                            @php
                                $splitPos = strpos($clause, 'LONG LENGTH');
                                $before = trim(substr($clause, 0, $splitPos));
                                $after = trim(substr($clause, $splitPos));
                            @endphp
                            {{ $before }}
                            <div style="margin-top: 4px; padding: 6px 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; font-weight: 600; color: #991b1b; font-size: 12px;">
                                ⚠️ {{ $after }}
                            </div>
                        @else
                            {{ $clause }}
                        @endif
                    </li>
                @endforeach
            </ol>
        @else
            <p class="subtle" style="margin: 0;">Tidak ada catatan khusus.</p>
        @endif
    </div>
</section>

@endsection
