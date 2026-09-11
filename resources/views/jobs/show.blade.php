@extends('layouts.app')
@section('title','Detail Job Order ' . $job->number)
@section('content')

@php
    $quotation = $job->quotation;
    $customerName = $job->customer?->name ?? $job->quotation_snapshot['customer']['name'] ?? '—';
    $marketingName = $job->sales?->name ?? $quotation?->sales?->name ?? '—';
    $serviceType = strtoupper(config('operations.service_types.'.$job->service_type) ?? ($job->service_type ?? '—'));
    $loadingPort = strtoupper($job->pol ?? $job->origin ?? $quotation?->origin ?? '—');
    $dischargePort = strtoupper($job->pod ?? $job->destination ?? $quotation?->destination ?? '—');
    $etdDate = $job->etd ? $job->etd->format('d-m-Y') : '—';
    $etaDate = $job->eta ? $job->eta->format('d-m-Y') : '—';
    $noAju = $job->booking_reference ?? '—';
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
        <p class="eyebrow">OPERASIONAL / JOB ORDER</p>
        <h1>{{ $job->number }}</h1>
        <p>{{ $job->subject }} · Customer: <strong>{{ $customerName }}</strong></p>
    </div>
    <a class="text-link" href="{{ route('jobs.index') }}">← Kembali ke daftar</a>
</div>

<div class="quote-actions" style="margin-bottom: 20px;">
    @can('update',$job)
        <a class="button button-secondary" href="{{ route('jobs.edit',$job) }}">Edit operasional</a>
    @endcan
    <a class="button button-secondary" href="{{ route('jobs.preview', $job) }}" target="_blank">🖨 Preview PDF</a>
    <a class="button button-secondary" href="{{ route('booking-confirmations.create', ['job_id' => $job->id]) }}">+ Booking Confirmation</a>
    <a class="button button-secondary" href="{{ route('shipping-instructions.create', ['job_id' => $job->id]) }}">+ Shipping Instruction</a>
    @can('open',$job)
        <form method="POST" action="{{ route('jobs.open',$job) }}" data-confirm="Buka job ini? Finance dapat mulai mencatat biaya setelah job berstatus Open.">
            @csrf
            <input type="hidden" name="lock_version" value="{{ $job->lock_version }}">
            <button class="button button-primary">Buka job</button>
        </form>
    @endcan
    @can('costs.manage')
        <a class="button button-primary" href="{{ route('jobs.costs.index',$job) }}"><x-icon name="wallet"/>Lihat biaya aktual</a>
    @endcan
    @can('jobs.close')
        @if($job->status==='open')
            <a class="button button-primary" href="{{ route('closing.create',$job) }}">Closing job</a>
        @endif
    @endcan
    @can('invoices.manage')
        @if($job->invoice)
            <a class="button button-primary" href="{{ route('invoices.show',$job->invoice) }}">Lihat invoice</a>
        @endif
    @endcan
    @if($job->status==='open' && !$job->do_confirmed_at)
        @can('update',$job)
            <form method="POST" action="{{ route('jobs.confirm-do',$job) }}" data-confirm="Konfirmasi bahwa Delivery Order (DO) telah selesai?">
                @csrf
                <button class="button button-primary" style="background:#16a34a;border-color:#16a34a">DO Selesai</button>
            </form>
        @endcan
    @elseif($job->do_confirmed_at)
        <span class="status-badge status-paid">DO Selesai ({{ $job->do_confirmed_at->format('d/m/Y') }})</span>
    @endif
</div>

{{-- PANEL UTAMA TAMPILAN SESUAI FORMAT PERUSAHAAN (JOBORDER - CS.docx & Gambar 1) --}}
<section class="panel" style="overflow: hidden; margin-bottom: 24px;">
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
                        <td style="padding: 4px 8px; border-bottom: 1px solid #000; font-weight: 700;">{{ $serviceType }}</td>
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
                        DATA JOB ORDER
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
                    <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Loading</td>
                    <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $loadingPort }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Discharge</td>
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
                    <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">No. AJU</td>
                    <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $noAju }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">No. HBL</td>
                    <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $noHbl }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">No. MBL</td>
                    <td style="font-weight: 700; padding: 6px 12px; border: 1px solid #000;">{{ $noMbl }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 600; padding: 6px 12px; border: 1px solid #000;">Vessel</td>
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
            <div style="border: 1px solid #000; min-height: 90px; padding: 12px; font-size: 13px; line-height: 1.5; white-space: pre-wrap; background: #fff; color: #0f172a;">
                {{ $noteContent ?: '—' }}
            </div>
        </div>

        @if($job->cancellation_reason)
            <div style="margin-top: 16px; padding: 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; color: #b91c1c;">
                <strong>Alasan Pembatalan:</strong> {{ $job->cancellation_reason }}<br>
                <small>Dibatalkan pada: {{ $job->cancelled_at?->format('d/m/Y H:i') }}</small>
            </div>
        @endif
    </div>
</section>
<div class="section-heading"><h2>Dokumen Pengiriman</h2><span class="subtle">File BL, AWB, SI, dll</span></div>
<section class="panel">
<div class="table-scroll"><table><thead><tr><th>Tipe Dokumen</th><th>Nama File</th><th>Ukuran</th><th>Waktu Upload</th><th>Aksi</th></tr></thead><tbody>@forelse($job->documents as $doc)<tr><td><span class="badge-pill">{{ $doc->documentType->code }}</span><br><small>{{ $doc->documentType->name }}</small></td><td><strong>{{ $doc->original_name }}</strong><br><small>Oleh: {{ $doc->uploader?->name ?? 'Sistem' }}</small>@if($doc->notes)<p class="form-help" style="margin-top:4px">{{ $doc->notes }}</p>@endif</td><td>{{ $doc->file_size_formatted }}</td><td>{{ $doc->created_at->format('d/m/Y H:i') }}</td><td><div class="action-group"><a class="text-link" href="{{ route('jobs.documents.download', [$job, $doc]) }}" target="_blank">Download</a>@can('update',$job)<form method="POST" action="{{ route('jobs.documents.destroy', [$job, $doc]) }}" data-confirm="Hapus dokumen ini?">@csrf @method('DELETE')<button class="text-link" style="color:#ef4444">Hapus</button></form>@endcan</div></td></tr>@empty<tr><td colspan="5"><div class="empty-state"><x-icon name="file"/><h3>Belum ada dokumen yang diupload</h3><p>Upload dokumen pengiriman terkait pekerjaan ini.</p></div></td></tr>@endforelse</tbody></table></div>
@can('update',$job)
<form class="data-form" style="margin-top:20px;padding-top:20px;border-top:1px solid #e2e8f0" method="POST" action="{{ route('jobs.documents.store',$job) }}" enctype="multipart/form-data">@csrf
<div class="form-grid">
<div class="field"><label for="document_type_id">Jenis Dokumen</label><select name="document_type_id" id="document_type_id" required><option value="">Pilih tipe dokumen</option>@foreach($documentTypes as $dt)<option value="{{ $dt->id }}">{{ $dt->code }} - {{ $dt->name }}</option>@endforeach</select></div>
<div class="field"><label for="file">File (PDF/Image/Excel/Word)</label><input type="file" name="file" id="file" required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"></div>
<div class="field span-2"><label for="notes">Keterangan Tambahan</label><input type="text" name="notes" id="notes" maxlength="255" placeholder="Opsional"></div>
</div><div class="form-actions"><button class="button button-primary">Upload Dokumen</button></div>
</form>
@endcan
</section>

@if($job->status === 'open' || $job->shipment_status)
<div class="section-heading"><h2>Status pengiriman</h2><span class="subtle">Booked · In Progress · Departed · Arrived · SPJM · SPPB · DO Process · Completed</span></div>
<section class="panel">
@if($job->status === 'open' && auth()->user()->can('update', $job))
<form class="transition-form" method="POST" action="{{ route('jobs.shipment-status',$job) }}" >@csrf<input type="hidden" name="lock_version" value="{{ $job->lock_version }}"><label for="shipment_status">Status pengiriman</label><select name="shipment_status" id="shipment_status">@foreach(config('operations.shipment_statuses') as $value=>$label)<option value="{{ $value }}" @selected($job->shipment_status===$value)>{{ $label }}</option>@endforeach</select><p class="form-help">Ubah status untuk memantau progres shipment di daftar job dan dashboard.</p><button class="button button-secondary">Simpan status pengiriman</button></form>
@else
<div class="cost-summary-body"><div class="summary-box"><div class="summary-row"><span>Status pengiriman</span><strong>{{ config('operations.shipment_statuses.'.$job->shipment_status) ?? '—' }}</strong></div>@if($job->shipment_status_at)<div class="summary-row"><span>Terakhir diperbarui</span><strong>{{ $job->shipment_status_at->format('d/m/Y H:i') }}</strong></div>@endif</div></div>
@endif
@if($job->shipmentStatusHistory->isNotEmpty())
<div class="panel-heading"><h2>Timeline pengiriman</h2></div>
<ol class="approval-timeline">@foreach($job->shipmentStatusHistory as $event)<li><span></span><div><strong>@if($event->from_status){{ config('operations.shipment_statuses.'.$event->from_status) ?? $event->from_status }} → {{ config('operations.shipment_statuses.'.$event->to_status) ?? $event->to_status }}@else{{ config('operations.shipment_statuses.'.$event->to_status) ?? $event->to_status }}@endif</strong><p>{{ $event->user?->name ?? 'System' }} · {{ $event->created_at->format('d/m/Y H:i') }}@if($event->note)<br>{{ $event->note }}@endif</p></div></li>@endforeach</ol>
@endif
</section>
@endif
<div class="section-heading"><h2>Estimasi penawaran</h2><span class="subtle">Snapshot quotation · Bukan biaya aktual</span></div>
@can('financial.view')
<section class="panel"><div class="table-scroll"><table class="quote-detail-table"><thead><tr><th>Uraian</th><th>Jenis</th><th>Jumlah</th><th class="money">Estimasi modal</th><th class="money">Estimasi tagihan</th></tr></thead><tbody>@foreach($job->quotation_snapshot['items'] as $item)<tr><td>{{ $item['description'] }}</td><td>{{ ucfirst($item['type']) }}</td><td>{{ \App\Support\Money::format($item['quantity']) }} {{ $item['unit'] }}</td><td class="money">{{ \App\Support\Money::format($item['total_cost']) }}</td><td class="money">{{ \App\Support\Money::format($item['total_price']) }}</td></tr>@endforeach</tbody></table></div><div class="summary-box"><div class="summary-row"><span>Estimasi profit</span><strong>Rp {{ \App\Support\Money::format($job->quotation_snapshot['totals']['profit']) }}</strong></div><div class="summary-row summary-total"><span>Total sebelum pajak</span><strong>Rp {{ \App\Support\Money::format($job->quotation_snapshot['totals']['subtotal']) }}</strong></div></div></section>
@else
<section class="panel"><div class="table-scroll"><table class="quote-detail-table"><thead><tr><th>Uraian</th><th>Jenis</th><th>Jumlah</th><th class="money">Estimasi tagihan</th></tr></thead><tbody>@foreach($job->quotation_snapshot['items'] as $item)<tr><td>{{ $item['description'] }}</td><td>{{ ucfirst($item['type']) }}</td><td>{{ \App\Support\Money::format($item['quantity']) }} {{ $item['unit'] }}</td><td class="money">{{ \App\Support\Money::format($item['total_price']) }}</td></tr>@endforeach</tbody></table></div><div class="summary-box"><div class="summary-row summary-total"><span>Total sebelum pajak</span><strong>Rp {{ \App\Support\Money::format($job->quotation_snapshot['totals']['subtotal']) }}</strong></div></div></section>
@endcan
@can('financial.view')
<div class="section-heading"><h2>Ringkasan keuangan</h2><span class="subtle">Biaya aktual Final · Hanya untuk Finance</span></div>
<section class="panel"><div class="cost-summary-body"><div class="summary-box">@if($summary['count'] > 0)<div class="summary-row"><span>Total modal aktual (provision)</span><strong>Rp {{ \App\Support\Money::format($summary['provision_cost']) }}</strong></div><div class="summary-row"><span>Total tagihan aktual</span><strong>Rp {{ \App\Support\Money::format($summary['provision_sell']) }}</strong></div><div class="summary-row"><span>Biaya temporary</span><strong>Rp {{ \App\Support\Money::format($summary['temporary']) }}</strong></div><div class="summary-row"><span>Laba (rugi) aktual</span><strong>Rp {{ \App\Support\Money::format($summary['profit']) }}</strong></div><div class="summary-row summary-total"><span>Margin</span><strong>{{ \App\Support\Money::format($summary['margin']) }}%</strong></div>@else<div class="empty-state"><h3>Belum ada biaya Final</h3><p>Ringkasan keuangan tampil setelah Finance mencatat dan memfinalisasi biaya.</p></div>@endif</div></div></section>
@endcan
<div class="section-heading"><h2>Riwayat status</h2><span class="subtle">Perjalanan persetujuan dan status</span></div>
<section class="panel"><div class="cost-summary-body"><ol class="approval-timeline">@forelse($job->statusHistory as $event)<li><span></span><div><strong>@if($event->from_status){{ config('operations.job_statuses.'.$event->from_status) ?? $event->from_status }} → {{ config('operations.job_statuses.'.$event->to_status) ?? $event->to_status }}@else Dibuat → {{ config('operations.job_statuses.'.$event->to_status) ?? $event->to_status }}@endif</strong><p>{{ $event->user?->name ?? 'System' }} · {{ $event->created_at->format('d/m/Y H:i') }}@if($event->note)<br>{{ $event->note }}@endif</p></div></li>@empty<li><span></span><div><strong>Tidak ada riwayat</strong><p>Riwayat akan tercatat saat job dibuat atau berubah status.</p></div></li>@endforelse</ol></div></section>
@can('cancel',$job)<section class="panel job-cancel"><form class="transition-form" method="POST" action="{{ route('jobs.cancel',$job) }}" data-confirm="Batalkan job ini? Job hanya dapat dibatalkan jika tidak memiliki biaya aktif.">@csrf<input type="hidden" name="lock_version" value="{{ $job->lock_version }}"><label for="reason">Alasan pembatalan job</label><textarea name="reason" id="reason" rows="2" maxlength="1000" required placeholder="Jelaskan alasan pembatalan">{{ old('reason') }}</textarea><p class="form-help">Job yang masih memiliki biaya aktif tidak dapat dibatalkan.</p><button class="button button-danger">Batalkan job</button></form></section>@endcan
@endsection