@extends('layouts.app')
@section('title','Detail Job Order ' . $job->number)
@section('content')

@php
    $quotation = $job->quotation;
    $customerName = $job->customer?->name ?? $job->quotation_snapshot['customer']['name'] ?? '—';
    $marketingName = $job->sales?->name ?? $quotation?->sales?->name ?? '—';
    $serviceTypeRaw = strtolower($job->service_type ?? '');
    
    // Categorize shipment service
    $isExportSea = $serviceTypeRaw === 'exp_sea' || ($serviceTypeRaw === 'sea' && !str_contains($serviceTypeRaw, 'imp'));
    $isExportAir = $serviceTypeRaw === 'exp_air' || ($serviceTypeRaw === 'air' && !str_contains($serviceTypeRaw, 'imp'));
    $isImport = str_contains($serviceTypeRaw, 'imp');
    $isDomestic = str_contains($serviceTypeRaw, 'dom') || $serviceTypeRaw === 'land' || $serviceTypeRaw === 'domestic';
    
    // Fallback default
    if (!$isExportSea && !$isExportAir && !$isImport && !$isDomestic) {
        if (str_contains($serviceTypeRaw, 'exp')) {
            $isExportSea = true;
        } else {
            $isImport = true;
        }
    }

    $serviceCategoryTitle = $isExportSea ? 'EXPORT SHIPMENT (SEA)' : 
        ($isExportAir ? 'EXPORT SHIPMENT (AIR)' : 
        ($serviceTypeRaw === 'imp_sea' ? 'IMPORT SHIPMENT (SEA)' :
        ($serviceTypeRaw === 'imp_air' ? 'IMPORT SHIPMENT (AIR)' : 'DOMESTIC / TRUCKING')));

    $serviceTypeLabel = \App\Models\ServiceType::label($job->service_type);
    $loadingPort = $job->pol ?? $job->origin ?? $quotation?->origin ?? '—';
    $dischargePort = $job->pod ?? $job->destination ?? $quotation?->destination ?? '—';
    $etdDate = $job->etd ? $job->etd->format('d-m-Y') : '—';
    $etaDate = $job->eta ? $job->eta->format('d-m-Y') : '—';
    $noAju = $job->booking_reference ?? '—';
    $noNopen = $job->nopen ?? '—';
    $noNpe = $job->npe_number ?? '—';
    $noPeb = $job->peb_number ?? '—';
    $pebDate = $job->peb_date ? $job->peb_date->format('d/m/Y') : '—';
    $noHbl = $job->hbl_number ?? $job->hawb_number ?? '—';
    $noMbl = $job->bl_number ?? $job->mawb_number ?? '—';
    $vesselName = $job->vessel_voyage ?? $job->flight_number ?? '—';
    $quantityStr = $job->package_count ? $job->package_count . ' Box' : ($job->container_type ? '1x ' . strtoupper($job->container_type) : ($quotation?->cargo_qty ?? '—'));
    $grossWeightStr = $job->gross_weight ? \App\Support\Money::format($job->gross_weight) . ' KGS' : ($quotation?->weight_meas ?? '—');
    $quotationVolume = $quotation?->items?->sum(fn($i) => (float)($i->volume ?? 0));
    $volumeStr = $job->volume ? $job->volume . ' M3' : ($quotationVolume > 0 ? $quotationVolume . ' M3' : '—');
    $commodityStr = $job->cargo_description ?? $quotation?->commodity ?? 'General Cargo';
    $noteContent = $job->operational_notes ?? $quotation?->notes ?? '';
@endphp

<div class="page-heading">
    <div>
        <p class="eyebrow">OPERASIONAL / JOB ORDER · {{ $serviceCategoryTitle }}</p>
        <h1>{{ $job->number }}</h1>
        <p>{{ $job->subject }} · Customer: <strong>{{ $customerName }}</strong>@if($job->pol || $job->pod) · {{ $job->pol ?? '—' }} → {{ $job->pod ?? '—' }}@elseif($job->origin || $job->destination) · {{ $job->origin ?? '—' }} → {{ $job->destination ?? '—' }}@endif</p>
    </div>
    <a class="text-link" href="{{ route('jobs.index') }}">← Kembali ke daftar</a>
</div>

{{-- TOP ACTION BUTTONS --}}
<div class="quote-actions" style="margin-bottom: 20px;">
    @can('update',$job)
        <a class="button button-secondary" href="{{ route('jobs.edit',$job) }}">Edit Operasional</a>
    @endcan
    <a class="button button-secondary" href="{{ route('jobs.preview', $job) }}" target="_blank">🖨 Preview PDF Job</a>
    
    @can('open',$job)
        <form method="POST" action="{{ route('jobs.open',$job) }}" data-confirm="Buka job ini? Finance dapat mulai mencatat biaya setelah job berstatus Open.">
            @csrf
            <input type="hidden" name="lock_version" value="{{ $job->lock_version }}">
            <button class="button button-primary">Buka Job</button>
        </form>
    @endcan
    @can('costs.manage')
        <a class="button button-primary" href="{{ route('jobs.costs.index',$job) }}"><x-icon name="wallet"/>Lihat Biaya Aktual</a>
    @endcan
    @can('jobs.close')
        @if($job->status==='open')
            <a class="button button-primary" href="{{ route('closing.create',$job) }}">Closing Job</a>
        @endif
    @endcan
    @can('invoices.manage')
        @if($job->invoice)
            <a class="button button-primary" href="{{ route('invoices.show',$job->invoice) }}">Lihat Invoice</a>
        @endif
    @endcan
    @if($job->status==='open' && !$job->do_confirmed_at)
        @can('jobs.confirm-do')
            <form method="POST" action="{{ route('jobs.confirm-do',$job) }}" data-confirm="Konfirmasi bahwa Delivery Order (DO) telah selesai?">
                @csrf
                <button class="button button-primary" style="background:#16a34a;border-color:#16a34a">DO Selesai</button>
            </form>
        @endcan
    @elseif($job->do_confirmed_at)
        <span class="status-badge status-paid">DO Selesai ({{ $job->do_confirmed_at->format('d/m/Y H:i') }})@if($job->doConfirmedBy)<br><small>oleh {{ $job->doConfirmedBy->name }}</small>@endif</span>
    @endif
</div>

{{-- HORIZONTAL PILL TABS MENU KE KANAN (SESUAI REQUEST & SCREENSHOT CLIENT) --}}
<nav class="job-pill-tabs-nav" style="display: flex; gap: 8px; background: #e2e8f0; padding: 6px; border-radius: 9999px; margin-bottom: 24px; overflow-x: auto;">
    <button type="button" class="job-tab-btn active" data-tab="tab-shipping" style="padding: 10px 22px; border-radius: 9999px; font-weight: 700; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: #fff; color: #0f172a; box-shadow: 0 1px 4px rgba(0,0,0,0.12);">1. Data Pengapalan</button>
    <button type="button" class="job-tab-btn" data-tab="tab-customs" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">2. {{ $isImport ? 'SK DO / SK Pabean' : 'Customs & AJU' }}</button>
    <button type="button" class="job-tab-btn" data-tab="tab-documents" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">3. {{ $isImport ? 'DNP / Surat Jalan' : ($isExportAir ? 'Dokumen (AWB)' : 'Dokumen (BL/CIPL)') }}</button>
    <button type="button" class="job-tab-btn" data-tab="tab-delivery" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">4. Tanda Terima</button>
    @if($isExportSea || $isExportAir)
        <button type="button" class="job-tab-btn" data-tab="tab-booking" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">6. Booking Confirmation</button>
        <button type="button" class="job-tab-btn" data-tab="tab-si" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">7. Shipping Instruction</button>
    @endif
    <button type="button" class="job-tab-btn" data-tab="tab-financial" style="padding: 10px 22px; border-radius: 9999px; font-weight: 600; font-size: 13.5px; border: none; cursor: pointer; transition: all 0.2s; background: transparent; color: #475569;">8. Biaya & Profit</button>
</nav>

{{-- ========================================================================= --}}
{{-- TAB 1: DATA PENGAPALAN                                                    --}}
{{-- ========================================================================= --}}
<div id="tab-shipping" class="job-tab-content">
    <section class="panel" style="overflow: hidden; margin-bottom: 24px; border: 1px solid #000; background: #fff;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 24px; border-bottom: 1px solid #000; background: #fff;">
            <div style="flex: 1;">
                <img src="{{ asset('images/logo.png') }}" alt="RDX Logistics" style="max-height: 55px; max-width: 220px;" onerror="this.style.display='none'">
                <div style="margin-top: 8px;">
                    <span class="status-badge status-{{ $job->status }}">{{ config('operations.job_statuses.'.$job->status) }}</span>
                    @if($job->shipment_status)
                        <span class="status-badge status-{{ $job->shipment_status }}" style="margin-left: 6px;">{{ config('operations.shipment_statuses.'.$job->shipment_status) ?? $job->shipment_status }}</span>
                    @endif
                    @if($job->status==='open' && $job->etaApproaching())
                        <span class="status-badge" style="background:#fef2f2;color:#b91c1c;border-color:#fecaca; margin-left: 6px;">Mendekati ETA</span>
                    @endif
                </div>
            </div>
            <div style="width: 280px;">
                <div style="border: 1px solid #000; width: 100%; border-collapse: collapse; background: #fff;">
                    <div style="text-align: center; font-size: 15px; font-weight: 700; padding: 6px; letter-spacing: 1px; border-bottom: 1px solid #000; font-family: monospace, sans-serif;">
                        JOB ORDER
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <tr>
                            <td style="padding: 4px 8px; border-bottom: 1px solid #000; border-right: 1px solid #000; width: 38%; font-weight: 600;">JO. No</td>
                            <td style="padding: 4px 8px; border-bottom: 1px solid #000; font-weight: 700; color: #0f172a;">{{ $job->number }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 8px; border-bottom: 1px solid #000; border-right: 1px solid #000; font-weight: 600;">Date</td>
                            <td style="padding: 4px 8px; border-bottom: 1px solid #000; font-weight: 700;">{{ $job->job_date?->format('d-m-Y') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 8px; border-bottom: 1px solid #000; border-right: 1px solid #000; font-weight: 600;">Type</td>
                            <td style="padding: 4px 8px; border-bottom: 1px solid #000; font-weight: 700;">{{ $serviceTypeLabel }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 8px; border-right: 1px solid #000; font-weight: 600;">Marketing</td>
                            <td style="padding: 4px 8px; font-weight: 700;">{{ strtoupper($marketingName) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- DATA JOB ORDER TABLE --}}
        <div style="padding: 24px;">
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 13px;">
                <thead>
                    <tr>
                        <th colspan="2" style="background-color: #e2e8f0; color: #000; font-weight: 700; text-align: left; padding: 7px 12px; border: 1px solid #000; font-size: 13.5px; letter-spacing: 0.5px;">
                            DATA JOB ORDER ({{ $serviceCategoryTitle }})
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 25%; font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Customer</td>
                        <td style="width: 75%; font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $customerName }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Shipper</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">
                            {{ $job->shipper_name ?? '—' }}
                            @if($job->shipper_address)
                                <div style="font-size: 12px; font-weight: normal; color: #64748b; margin-top: 2px;">{{ $job->shipper_address }}</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Consignee</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">
                            {{ $job->consignee_name ?? '—' }}
                            @if($job->consignee_address)
                                <div style="font-size: 12px; font-weight: normal; color: #64748b; margin-top: 2px;">{{ $job->consignee_address }}</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Loading (POL)</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $loadingPort }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Discharge (POD)</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $dischargePort }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">ETD</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $etdDate }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">ETA</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $etaDate }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Vessel / Flight</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $vesselName }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Quantity</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $quantityStr }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Gross Weight</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $grossWeightStr }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Volume</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $volumeStr }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Commodity</td>
                        <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $commodityStr }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- NOTE AREA --}}
            <div style="margin-top: 18px;">
                <div style="font-size: 13.5px; font-weight: 700; text-decoration: underline; color: #000; margin-bottom: 6px;">
                    NOTE :
                </div>
                <div style="border: 1px solid #000; min-height: 70px; padding: 12px; font-size: 13px; line-height: 1.5; white-space: pre-wrap; background: #fff; color: #0f172a;">
                    {{ $noteContent ?: '—' }}
                </div>
            </div>
        </div>
    </section>

    @if($job->statusHistory->isNotEmpty())
        <section class="panel" style="margin-bottom: 24px;">
            <div class="panel-heading"><h2>Riwayat status</h2></div>
            <ol class="approval-timeline">
                @foreach($job->statusHistory as $event)
                    <li>
                        <span></span>
                        <div>
                            <strong>
                                @if($event->from_status)
                                    {{ config('operations.job_statuses.'.$event->from_status) ?? $event->from_status }} → {{ config('operations.job_statuses.'.$event->to_status) ?? $event->to_status }}
                                @else
                                    {{ config('operations.job_statuses.'.$event->to_status) ?? $event->to_status }}
                                @endif
                            </strong>
                            <p>{{ $event->user?->name ?? 'System' }} · {{ $event->created_at->format('d/m/Y H:i') }}@if($event->note)<br>{{ $event->note }}@endif</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif
</div>

{{-- ========================================================================= --}}
{{-- TAB 2: CUSTOMS & AJU (PERSIS SEPERTI GAMBAR CLIENT)                        --}}
{{-- ========================================================================= --}}
<div id="tab-customs" class="job-tab-content" style="display: none;">
    <section class="panel" style="padding: 24px; margin-bottom: 24px;">
        <div class="panel-heading" style="margin-bottom: 20px;">
            <h2>Dokumen Kepabeanan & Status Jalur Cukai</h2>
            <span class="subtle">{{ $isImport ? 'Nomor Pengajuan AJU, Nopen, SPJM/SPPB' : 'No AJU 6 digit terakhir, NOPEN PEB, tanggal PEB, dan NPE' }}</span>
        </div>

        {{-- GRID NOMOR PENGAJUAN & NOPEN (PERSIS GAMBAR) --}}
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <label style="display: block; font-size: 13.5px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">
                    No AJU (6 digit terakhir)
                </label>
                <div style="padding: 12px 16px; border: 2px solid #f97316; border-radius: 8px; font-size: 16px; font-weight: 700; color: #0f172a; background: #fff;">
                    {{ $noAju }}
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 13.5px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">
                    {{ $isImport ? 'Nomor Pendaftaran (Nopen)' : 'NOPEN PEB' }}
                </label>
                <div style="padding: 12px 16px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 16px; font-weight: 600; color: #0f172a; background: #fff;">
                    {{ $isImport ? $noNopen : $noPeb }}
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            @if(!$isImport)
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">
                        Tanggal PEB
                    </label>
                    <div style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14.5px; font-weight: 600; color: #0f172a; background: #f8fafc;">
                        {{ $pebDate }}
                    </div>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">
                        Nomor NPE
                    </label>
                    <div style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14.5px; font-weight: 600; color: #0f172a; background: #f8fafc;">
                        {{ $noNpe }}
                    </div>
                </div>
            @endif
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">
                    Nomor BL / MBL
                </label>
                <div style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14.5px; font-weight: 600; color: #0f172a; background: #f8fafc;">
                    {{ $noMbl }}
                </div>
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">
                    Nomor HBL / HAWB
                </label>
                <div style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14.5px; font-weight: 600; color: #0f172a; background: #f8fafc;">
                    {{ $noHbl }}
                </div>
            </div>
        </div>

        {{-- STATUS KEPABEANAN BANNER (PERSIS GAMBAR JALUR HIJAU / MERAH) --}}
        @if($job->shipment_status === 'sppb')
            <div style="padding: 18px 24px; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; margin-bottom: 24px;">
                <div style="font-size: 15px; font-weight: 800; color: #166534; letter-spacing: 0.5px;">
                    STATUS: JALUR HIJAU (SPPB TERBIT)
                </div>
                <div style="font-size: 13px; color: #15803d; margin-top: 6px; font-weight: 500;">
                    Surat Persetujuan Pengeluaran Barang telah terverifikasi oleh sistem Bea Cukai Tanjung Priok / Bandara.
                </div>
            </div>
        @elseif($job->shipment_status === 'spjm')
            <div style="padding: 18px 24px; background: #fef2f2; border: 1.5px solid #fca5a5; border-radius: 12px; margin-bottom: 24px;">
                <div style="font-size: 15px; font-weight: 800; color: #991b1b; letter-spacing: 0.5px;">
                    STATUS: JALUR MERAH (SPJM DITERBITKAN)
                </div>
                <div style="font-size: 13px; color: #b91c1c; margin-top: 6px; font-weight: 500;">
                    Pemberitahuan Jalur Merah - Diperlukan pemeriksaan fisik barang (Behandle) di Terminal / TPS.
                </div>
            </div>
        @elseif($job->shipment_status === 'npe')
            <div style="padding: 18px 24px; background: #eef2ff; border: 1.5px solid #a5b4fc; border-radius: 12px; margin-bottom: 24px;">
                <div style="font-size: 15px; font-weight: 800; color: #3730a3; letter-spacing: 0.5px;">
                    STATUS: NPE TERBIT
                </div>
                <div style="font-size: 13px; color: #4338ca; margin-top: 6px; font-weight: 500;">
                    Nota Pelayanan Ekspor sudah tercatat untuk proses dokumen export.
                </div>
            </div>
        @else
            <div style="padding: 18px 24px; background: #f0f9ff; border: 1.5px solid #7dd3fc; border-radius: 12px; margin-bottom: 24px;">
                <div style="font-size: 15px; font-weight: 800; color: #075985; letter-spacing: 0.5px;">
                    STATUS KEPABEANAN: {{ strtoupper(config('operations.shipment_statuses.'.$job->shipment_status) ?? $job->shipment_status ?? 'DALAM PROSES DOKUMEN') }}
                </div>
                <div style="font-size: 13px; color: #0369a1; margin-top: 6px; font-weight: 500;">
                    Proses kepabeanan dan pendaftaran dokumen aktif dalam pemantauan operasional.
                </div>
            </div>
        @endif

        {{-- FORM UPDATE STATUS KEPABEANAN --}}
        @if($job->status === 'open' && auth()->user()->can('update', $job))
            <form class="transition-form" method="POST" action="{{ route('jobs.shipment-status',$job) }}" style="padding-top: 16px; border-top: 1px solid #e2e8f0;">
                @csrf
                <input type="hidden" name="lock_version" value="{{ $job->lock_version }}">
                <label for="shipment_status_customs" style="font-weight: 700;">Update Status Kepabeanan / Pengiriman</label>
                <select name="shipment_status" id="shipment_status_customs">
                    @foreach(config('operations.shipment_statuses') as $value=>$label)
                        @continue($isImport && $value === 'npe')
                        @continue(!$isImport && in_array($value, ['spjm', 'sppb'], true))
                        <option value="{{ $value }}" @selected($job->shipment_status===$value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="form-help">Pilih status untuk mencatat progress SPJM (Jalur Merah) atau SPPB (Jalur Hijau).</p>
                <button class="button button-secondary">Simpan Status Kepabeanan</button>
            </form>
        @endif
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB 3: DOKUMEN (BL/CIPL/SI/BC/SK DO/DNP/SK PABEAN)                        --}}
{{-- ========================================================================= --}}
<div id="tab-documents" class="job-tab-content" style="display: none;">
    <section class="panel" style="padding: 24px; margin-bottom: 24px;">
        <div class="panel-heading" style="margin-bottom: 16px;">
            <h2>Dokumen Operasional: {{ $serviceCategoryTitle }}</h2>
            <span class="subtle">Dokumen resmi pengapalan sesuai kategori layanan</span>
        </div>

        {{-- FILE LAMPIRAN BL / CIPL / COO DLL --}}
        <div class="panel-heading" style="margin-bottom: 12px;">
            <h2>File Lampiran Dokumen (BL, Packing List, CIPL, dll)</h2>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Tipe Dokumen</th>
                        <th>Nama File</th>
                        <th>Ukuran</th>
                        <th>Waktu Upload</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($job->documents as $doc)
                        <tr>
                            <td><span class="badge-pill">{{ $doc->documentType->code }}</span><br><small>{{ $doc->documentType->name }}</small></td>
                            <td><strong>{{ $doc->original_name }}</strong><br><small>Oleh: {{ $doc->uploader?->name ?? 'Sistem' }}</small>@if($doc->notes)<p class="form-help" style="margin-top:4px">{{ $doc->notes }}</p>@endif</td>
                            <td>{{ $doc->file_size_formatted }}</td>
                            <td>{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn-action btn-action-primary" href="{{ route('jobs.documents.download', [$job, $doc]) }}" target="_blank" title="Download Dokumen" data-tooltip="Download" aria-label="Download Dokumen"><x-icon name="download"/></a>
                                    @can('update',$job)
                                        <form method="POST" action="{{ route('jobs.documents.destroy', [$job, $doc]) }}" data-confirm="Hapus dokumen ini?">
                                            @csrf @method('DELETE')
                                            <button class="btn-action btn-action-danger" title="Hapus Dokumen" data-tooltip="Hapus" aria-label="Hapus Dokumen"><x-icon name="trash"/></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <x-icon name="file"/>
                                    <h3>Belum ada lampiran dokumen</h3>
                                    <p>Unggah dokumen PDF maksimal 3 MB sesuai jenis dokumen service.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @can('update',$job)
            <form class="data-form" style="margin-top:20px;padding-top:20px;border-top:1px solid #e2e8f0" method="POST" action="{{ route('jobs.documents.store',$job) }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div class="field">
                        <label for="document_type_id">Jenis Dokumen</label>
                        <select name="document_type_id" id="document_type_id" required>
                            <option value="">Pilih tipe dokumen</option>
                            @foreach($documentTypes as $dt)
                                <option value="{{ $dt->id }}">{{ $dt->code }} - {{ $dt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="file">File PDF <span class="required">*</span></label>
                        <input type="file" name="file" id="file" required accept="application/pdf,.pdf">
                        <small class="form-help">Hanya PDF, maksimal 3 MB.</small>
                    </div>
                    <div class="field span-2">
                        <label for="notes">Keterangan Tambahan</label>
                        <input type="text" name="notes" id="notes" maxlength="255" placeholder="Opsional">
                    </div>
                </div>

                @if($isImport || $isExportSea || $isExportAir)
                    <div style="margin-top: 18px; padding: 18px; border: 1px solid #dbeafe; border-radius: 14px; background: #f8fbff;">
                        <div class="panel-heading" style="margin-bottom: 12px;">
                            <h2 style="font-size: 16px;">Data Kepabeanan dari Dokumen</h2>
                            <span class="subtle">Isi jika dokumen yang diupload adalah {{ $isImport ? 'SPJM/SPPB' : 'NPE' }}. Data ini akan langsung masuk ke tab Customs & AJU.</span>
                        </div>
                        <div class="form-grid">
                            <div class="field">
                                <label for="customs_document_kind">Jenis Dokumen Bea Cukai</label>
                                <select name="customs_document_kind" id="customs_document_kind">
                                    <option value="">Tidak update status</option>
                                    @if($isImport)
                                        <option value="spjm" @selected(old('customs_document_kind') === 'spjm')>SPJM - Jalur Merah</option>
                                        <option value="sppb" @selected(old('customs_document_kind') === 'sppb')>SPPB - Jalur Hijau</option>
                                    @else
                                        <option value="npe" @selected(old('customs_document_kind') === 'npe')>NPE - Nota Pelayanan Ekspor</option>
                                    @endif
                                </select>
                            </div>
                            <div class="field">
                                <label for="customs_submission_number_upload">Nomor Pengajuan Penuh</label>
                                <input type="text" name="customs_submission_number" id="customs_submission_number_upload" maxlength="100" placeholder="Paste nomor pengajuan penuh dari dokumen">
                                <small class="form-help">Sistem otomatis mengambil 6 digit terakhir sebagai No AJU.</small>
                            </div>
                            <div class="field">
                                <label for="booking_reference_upload">No AJU (6 digit terakhir)</label>
                                <input type="text" name="booking_reference" id="booking_reference_upload" maxlength="60" value="{{ old('booking_reference', $job->booking_reference) }}" placeholder="contoh: 260200">
                            </div>
                            @if($isImport)
                                <div class="field">
                                    <label for="nopen_upload">Nomor Pendaftaran (Nopen)</label>
                                    <input type="text" name="nopen" id="nopen_upload" maxlength="60" value="{{ old('nopen', $job->nopen) }}" placeholder="Isi Nopen dari SPPB/SPJM">
                                </div>
                            @else
                                <div class="field">
                                    <label for="peb_number_upload">NOPEN PEB</label>
                                    <input type="text" name="peb_number" id="peb_number_upload" maxlength="60" value="{{ old('peb_number', $job->peb_number) }}" placeholder="contoh: 415575">
                                </div>
                                <div class="field">
                                    <label for="peb_date_upload">Tanggal PEB</label>
                                    <input type="date" name="peb_date" id="peb_date_upload" value="{{ old('peb_date', $job->peb_date?->format('Y-m-d')) }}">
                                </div>
                                <div class="field">
                                    <label for="npe_number_upload">Nomor NPE</label>
                                    <input type="text" name="npe_number" id="npe_number_upload" maxlength="60" value="{{ old('npe_number', $job->npe_number) }}" placeholder="Isi nomor NPE">
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="form-actions">
                    <button class="button button-primary">Upload Dokumen</button>
                </div>
            </form>
        @endcan
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB 4: TANDA TERIMA & DELIVERY                                            --}}
{{-- ========================================================================= --}}
<div id="tab-delivery" class="job-tab-content" style="display: none;">
    <section class="panel" style="padding: 24px; margin-bottom: 24px;">
        <div class="panel-heading" style="margin-bottom: 16px;">
            <h2>Tanda Terima & Delivery Serah Terima</h2>
            <span class="subtle">Dokumen serah terima barang dan konfirmasi pengantaran</span>
        </div>

        <div style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;">
            <a class="button button-secondary" href="{{ route('jobs.surat-jalan.pdf', $job) }}" target="_blank">
                <x-icon name="file"/> Cetak Surat Jalan (Delivery Order)
            </a>
            <a class="button button-secondary" href="{{ route('jobs.tanda-terima.pdf', $job) }}" target="_blank">
                <x-icon name="file"/> Cetak Tanda Terima Dokumen & Barang
            </a>
        </div>

        {{-- STATUS DO --}}
        <div style="padding: 18px; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #fff; margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Status Delivery Order (DO)</span>
                    <div style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 4px;">
                        @if($job->do_confirmed_at)
                            <span style="color: #16a34a;">✓ Selesai Dikonfirmasi</span>
                            <div style="font-size: 13px; font-weight: normal; color: #64748b;">
                                Dikonfirmasi pada {{ $job->do_confirmed_at->format('d/m/Y H:i') }} oleh {{ $job->doConfirmedBy?->name ?? 'Petugas' }}
                            </div>
                        @else
                            <span style="color: #ea580c;">Menunggu Penyelesaian Pengantaran</span>
                        @endif
                    </div>
                </div>
                @if(!$job->do_confirmed_at && $job->status === 'open')
                    @can('jobs.confirm-do')
                        <form method="POST" action="{{ route('jobs.confirm-do',$job) }}" data-confirm="Konfirmasi bahwa Delivery Order (DO) telah selesai?">
                            @csrf
                            <button class="button button-primary" style="background:#16a34a;border-color:#16a34a">Konfirmasi DO Selesai</button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>

        {{-- TIMELINE PENGIRIMAN --}}
        @if($job->shipmentStatusHistory->isNotEmpty())
            <div class="panel-heading"><h2>Timeline Pengiriman & Milestone</h2></div>
            <ol class="approval-timeline">
                @foreach($job->shipmentStatusHistory as $event)
                    <li>
                        <span></span>
                        <div>
                            <strong>
                                @if($event->from_status)
                                    {{ config('operations.shipment_statuses.'.$event->from_status) ?? $event->from_status }} → {{ config('operations.shipment_statuses.'.$event->to_status) ?? $event->to_status }}
                                @else
                                    {{ config('operations.shipment_statuses.'.$event->to_status) ?? $event->to_status }}
                                @endif
                            </strong>
                            <p>{{ $event->user?->name ?? 'System' }} · {{ $event->created_at->format('d/m/Y H:i') }}@if($event->note)<br>{{ $event->note }}@endif</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</div>

{{-- ========================================================================= --}}
{{-- TAB 5: BIAYA & PROFIT                                                     --}}
{{-- ========================================================================= --}}
@if($isExportSea || $isExportAir)
<div id="tab-booking" class="job-tab-content" style="display: none;">
    <section class="panel" style="padding:0; overflow:hidden; border:1px solid #dbe5f1; box-shadow:0 10px 28px rgba(15,23,42,.06);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:20px; padding:24px 28px; background:linear-gradient(135deg,#eff6ff 0%,#ffffff 72%); border-bottom:1px solid #e2e8f0;">
            <div style="display:flex; align-items:center; gap:15px;"><div style="width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:#dbeafe;color:#1d4ed8;font-size:23px;">▣</div><div><p class="eyebrow" style="margin-bottom:4px;">DOKUMEN OPERASIONAL</p><h2 style="margin:0 0 4px;">Booking Confirmation</h2><p style="margin:0;color:#64748b;">Konfirmasi booking yang terhubung dengan Job Order ini.</p></div></div>
            <a class="button button-primary" href="{{ route('booking-confirmations.create', ['job_id' => $job->id]) }}">+ Buat Booking Confirmation</a>
        </div>
        @if($job->bookingConfirmations->isNotEmpty())
            <div class="table-scroll"><table><thead><tr><th>Nomor</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@foreach($job->bookingConfirmations as $bc)<tr><td><strong>{{ $bc->number }}</strong></td><td>{{ $bc->booking_date?->format('d/m/Y') ?? '—' }}</td><td><span class="status-badge">{{ ucfirst($bc->status ?? 'Draft') }}</span></td><td><a class="button button-secondary button-sm" href="{{ route('booking-confirmations.preview', $bc) }}" target="_blank">Preview PDF</a> <a class="button button-secondary button-sm" href="{{ route('booking-confirmations.edit', $bc) }}">Edit</a></td></tr>@endforeach</tbody></table></div>
        @else
            <div style="margin:28px; padding:42px 24px; text-align:center; border:1px dashed #cbd5e1; border-radius:14px; background:#f8fafc;"><div style="width:54px;height:54px;margin:0 auto 14px;border-radius:50%;display:grid;place-items:center;background:#e0ecff;color:#2563eb;font-size:25px;">▣</div><h3 style="margin:0 0 7px;color:#0f172a;">Belum ada Booking Confirmation</h3><p style="margin:0;color:#64748b;">Buat dokumen BC dari tab ini agar tetap terhubung dengan Job Order.</p></div>
        @endif
    </section>
</div>
<div id="tab-si" class="job-tab-content" style="display: none;">
    <section class="panel" style="padding:0; overflow:hidden; border:1px solid #dbe5f1; box-shadow:0 10px 28px rgba(15,23,42,.06);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:20px; padding:24px 28px; background:linear-gradient(135deg,#eff6ff 0%,#ffffff 72%); border-bottom:1px solid #e2e8f0;">
            <div style="display:flex; align-items:center; gap:15px;"><div style="width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:#dbeafe;color:#1d4ed8;font-size:23px;">▤</div><div><p class="eyebrow" style="margin-bottom:4px;">DOKUMEN OPERASIONAL</p><h2 style="margin:0 0 4px;">Shipping Instruction</h2><p style="margin:0;color:#64748b;">Instruksi pengiriman yang terhubung dengan Job Order ini.</p></div></div>
            <a class="button button-primary" href="{{ route('shipping-instructions.create', ['job_id' => $job->id]) }}">+ Buat Shipping Instruction</a>
        </div>
        @if($job->shippingInstructions->isNotEmpty())
            <div class="table-scroll"><table><thead><tr><th>Nomor</th><th>Carrier</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@foreach($job->shippingInstructions as $si)<tr><td><strong>{{ $si->number }}</strong></td><td>{{ $si->to_carrier ?? '—' }}</td><td>{{ $si->si_date?->format('d/m/Y') ?? '—' }}</td><td><span class="status-badge">{{ ucfirst($si->status ?? 'Draft') }}</span></td><td><a class="button button-secondary button-sm" href="{{ route('shipping-instructions.preview', $si) }}" target="_blank">Preview PDF</a> <a class="button button-secondary button-sm" href="{{ route('shipping-instructions.edit', $si) }}">Edit</a></td></tr>@endforeach</tbody></table></div>
        @else
            <div style="margin:28px; padding:42px 24px; text-align:center; border:1px dashed #cbd5e1; border-radius:14px; background:#f8fafc;"><div style="width:54px;height:54px;margin:0 auto 14px;border-radius:50%;display:grid;place-items:center;background:#e0ecff;color:#2563eb;font-size:25px;">▤</div><h3 style="margin:0 0 7px;color:#0f172a;">Belum ada Shipping Instruction</h3><p style="margin:0;color:#64748b;">Buat dokumen SI dari tab ini agar seluruh data operasional tersusun dalam satu Job Order.</p></div>
        @endif
    </section>
</div>
@endif

<div id="tab-financial" class="job-tab-content" style="display: none;">
    <div class="section-heading"><h2>Estimasi Penawaran</h2><span class="subtle">Snapshot Quotation Asal</span></div>
    @can('financial.view')
        <section class="panel">
            <div class="table-scroll">
                <table class="quote-detail-table">
                    <thead>
                        <tr>
                            <th>Uraian</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                            <th class="money">Estimasi modal</th>
                            <th class="money">Estimasi tagihan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($job->quotation_snapshot['items'] as $item)
                            <tr>
                                <td>{{ $item['description'] }}</td>
                                <td>{{ ucfirst($item['type']) }}</td>
                                <td>{{ \App\Support\Money::format($item['quantity']) }} {{ $item['unit'] }}</td>
                                <td class="money">{{ \App\Support\Money::format($item['total_cost']) }}</td>
                                <td class="money">{{ \App\Support\Money::format($item['total_price']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="summary-box">
                <div class="summary-row"><span>Estimasi profit</span><strong>Rp {{ \App\Support\Money::format($job->quotation_snapshot['totals']['profit']) }}</strong></div>
                <div class="summary-row summary-total"><span>Total sebelum pajak</span><strong>Rp {{ \App\Support\Money::format($job->quotation_snapshot['totals']['subtotal']) }}</strong></div>
            </div>
        </section>
    @else
        <section class="panel">
            <div class="table-scroll">
                <table class="quote-detail-table">
                    <thead>
                        <tr>
                            <th>Uraian</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                            <th class="money">Estimasi tagihan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($job->quotation_snapshot['items'] as $item)
                            <tr>
                                <td>{{ $item['description'] }}</td>
                                <td>{{ ucfirst($item['type']) }}</td>
                                <td>{{ \App\Support\Money::format($item['quantity']) }} {{ $item['unit'] }}</td>
                                <td class="money">{{ \App\Support\Money::format($item['total_price']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="summary-box">
                <div class="summary-row summary-total"><span>Total sebelum pajak</span><strong>Rp {{ \App\Support\Money::format($job->quotation_snapshot['totals']['subtotal']) }}</strong></div>
            </div>
        </section>
    @endcan

    @can('financial.view')
        <div class="section-heading"><h2>Ringkasan keuangan</h2><span class="subtle">Biaya aktual final · Khusus Finance</span></div>
        <section class="panel">
            <div class="cost-summary-body">
                <div class="summary-box">
                    @if($summary['count'] > 0)
                        <div class="summary-row"><span>Total modal aktual (Provision)</span><strong>Rp {{ \App\Support\Money::format($summary['provision_cost']) }}</strong></div>
                        <div class="summary-row"><span>Total tagihan aktual</span><strong>Rp {{ \App\Support\Money::format($summary['provision_sell']) }}</strong></div>
                        <div class="summary-row"><span>Biaya temporary (Reimbursement)</span><strong>Rp {{ \App\Support\Money::format($summary['temporary']) }}</strong></div>
                        <div class="summary-row"><span>Laba (rugi) aktual</span><strong>Rp {{ \App\Support\Money::format($summary['profit']) }}</strong></div>
                        <div class="summary-row summary-total"><span>Margin</span><strong>{{ \App\Support\Money::format($summary['margin']) }}%</strong></div>
                    @else
                        <div class="empty-state">
                            <h3>Belum ada biaya final</h3>
                            <p>Ringkasan keuangan tampil setelah Finance mencatat dan memfinalisasi biaya.</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endcan
</div>

{{-- SCRIPT TOGGLE TAB HORIZONTAL --}}
<script>
function activateJobTab(targetTabId, updateHash = true) {
    const activeButton = document.querySelector(`.job-tab-btn[data-tab="${targetTabId}"]`);
    const target = document.getElementById(targetTabId);

    if (!activeButton || !target) return;

    document.querySelectorAll('.job-tab-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = 'transparent';
        btn.style.color = '#475569';
        btn.style.fontWeight = '600';
        btn.style.boxShadow = 'none';
    });

    activeButton.classList.add('active');
    activeButton.style.background = '#fff';
    activeButton.style.color = '#0f172a';
    activeButton.style.fontWeight = '700';
    activeButton.style.boxShadow = '0 1px 4px rgba(0,0,0,0.12)';

    document.querySelectorAll('.job-tab-content').forEach(content => {
        content.style.display = 'none';
    });
    target.style.display = 'block';

    if (updateHash) {
        history.replaceState(null, '', `#${targetTabId}`);
    }
}

document.querySelectorAll('.job-tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        activateJobTab(this.dataset.tab);
    });
});

const requestedJobTab = window.location.hash.replace('#', '');
if (requestedJobTab) {
    activateJobTab(requestedJobTab, false);
}
</script>

@endsection
