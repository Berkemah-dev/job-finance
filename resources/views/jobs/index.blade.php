@extends('layouts.app')
@section('title','Job Order')
@section('content')
<x-menu-banner
    tag="OPERASIONAL"
    title="Job Order Operasional"
    description="Pantau pergerakan pengiriman, jadwal ETD/ETA, PIC lapangan, dan status pekerjaan."
    :action-url="auth()->user()->can('quotations.manage') ? route('quotations.index') : null"
    action-label="Lihat Quotation"
    action-icon="arrow"
    icon="briefcase"
    art-title="Setiap kontainer,"
    art-subtitle="termonitoring."
/>
<section class="panel"><form class="filter-bar" method="GET">
<input name="search" value="{{ $search }}" placeholder="Cari nomor, nama pekerjaan, atau customer" aria-label="Cari job">
<div class="date-filter-group">
    <x-icon name="calendar"/>
    <input name="date_from" type="date" value="{{ $dateFrom }}" aria-label="Dari tanggal" title="Dari tanggal">
    <span class="date-sep">→</span>
    <input name="date_to" type="date" value="{{ $dateTo }}" aria-label="Sampai tanggal" title="Sampai tanggal">
</div>
<select name="service_type" aria-label="Layanan"><option value="">Semua layanan</option>@foreach(config('operations.canonical_service_types') as $key=>$label)<option value="{{ $key }}" @selected($serviceType===$key)>{{ $label }}</option>@endforeach</select>
<select name="sales_id" aria-label="Sales"><option value="">Semua sales</option>@foreach($assignees as $user)<option value="{{ $user->id }}" @selected($salesId===$user->id)>{{ $user->name }}</option>@endforeach</select>
<select name="cs_id" aria-label="Customer service"><option value="">Semua CS</option>@foreach($assignees as $user)<option value="{{ $user->id }}" @selected($csId===$user->id)>{{ $user->name }}</option>@endforeach</select>
<select name="status" aria-label="Status job"><option value="">Semua status</option>@foreach(config('operations.job_statuses') as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select>
<select name="shipment_status" aria-label="Status pengiriman"><option value="">Semua shipment</option>@foreach(config('operations.shipment_statuses') as $value=>$label)<option value="{{ $value }}" @selected($shipmentStatus===$value)>{{ $label }}</option>@endforeach</select>
<button class="button button-primary">Cari</button><a class="text-link" href="{{ route('jobs.index') }}">Reset</a>
</form>
<div class="table-scroll">
<table>
<thead>
    <tr>
        <th>No. Job</th>
        <th>Customer & Remark Quote</th>
        <th>No. BL / AWB</th>
        <th>Service</th>
        <th>No. Quote & Sales</th>
        <th>CS PIC</th>
        <th>Status</th>
        <th>Aksi</th>
    </tr>
</thead>
<tbody>
@forelse($jobs as $job)
    <tr>
        <td>
            <strong>{{ $job->number }}</strong>
            <br><small class="muted-cell">{{ $job->job_date?->format('d/m/Y') }}</small>
        </td>
        <td>
            <strong>{{ $job->quotation_snapshot['customer']['name'] ?? $job->customer?->name ?? '—' }}</strong>
            @if($job->quotation?->notes || $job->operational_notes || $job->subject)
                <br><small class="muted-cell" title="{{ $job->quotation?->notes ?? $job->operational_notes ?? $job->subject }}">{{ Str::limit($job->quotation?->notes ?? $job->operational_notes ?? $job->subject, 45) }}</small>
            @endif
        </td>
        <td>
            @if($job->bl_number)
                <div><small class="muted-cell">BL:</small> <strong>{{ $job->bl_number }}</strong></div>
            @endif
            @if($job->hbl_number)
                <div><small class="muted-cell">HBL:</small> {{ $job->hbl_number }}</div>
            @endif
            @if($job->awb_number)
                <div><small class="muted-cell">AWB:</small> <strong>{{ $job->awb_number }}</strong></div>
            @endif
            @if($job->hawb_number)
                <div><small class="muted-cell">HAWB:</small> {{ $job->hawb_number }}</div>
            @endif
            @if(!$job->bl_number && !$job->hbl_number && !$job->awb_number && !$job->hawb_number)
                <span class="muted-cell">—</span>
            @endif
        </td>
        <td>
            <span class="status-badge" style="background:#e0f2fe; color:#0369a1; font-weight:600;">
                {{ config('operations.service_types.'.$job->service_type) ?? strtoupper($job->service_type ?? '—') }}
            </span>
            @if($job->pol || $job->pod)
                <br><small class="muted-cell">{{ $job->pol ?? '—' }} → {{ $job->pod ?? '—' }}</small>
            @endif
        </td>
        <td>
            @if($job->quotation)
                <strong>{{ $job->quotation->number }}</strong><br>
            @endif
            <small class="muted-cell">Sales: {{ $job->sales?->name ?? '—' }}</small>
        </td>
        <td>
            <strong>{{ $job->cs?->name ?? '—' }}</strong>
            <br><small class="muted-cell">ID CS: #{{ $job->cs_id ?? ($job->created_by ?? '—') }}</small>
        </td>
        <td>
            <span class="status-badge status-{{ $job->status }}">{{ config('operations.job_statuses.'.$job->status) }}</span>
            @if($job->shipment_status)
                <br><small style="margin-top:4px; display:inline-block;"><span class="status-badge status-{{ $job->shipment_status }}">{{ config('operations.shipment_statuses.'.$job->shipment_status) }}</span></small>
            @endif
        </td>
        <td>
            <div class="table-actions">
                <a class="btn-action btn-action-primary" href="{{ route('jobs.show',$job) }}" title="Detail Job" data-tooltip="Detail" aria-label="Detail Job"><x-icon name="eye"/></a>
                <a class="btn-action btn-action-purple" href="{{ route('jobs.preview',$job) }}" target="_blank" title="Cetak PDF Job" data-tooltip="PDF" aria-label="Cetak PDF Job"><x-icon name="printer"/></a>
                @php
                    $isExportJob = str_contains(strtolower($job->service_type ?? ''), 'exp') || $job->service_type === 'export' || $job->bookingConfirmations->isNotEmpty();
                @endphp
                @if($isExportJob)
                    @if($job->bookingConfirmations->isNotEmpty())
                        <a class="btn-action btn-action-success" href="{{ route('booking-confirmations.preview', $job->bookingConfirmations->first()) }}" target="_blank" title="Cetak Booking Confirmation Export ({{ $job->bookingConfirmations->first()->number }})" data-tooltip="Booking Confirmation" aria-label="Booking Confirmation"><x-icon name="clipboard"/></a>
                    @else
                        <a class="btn-action btn-action-success" href="{{ route('booking-confirmations.create', ['job_id' => $job->id]) }}" title="Buat Booking Confirmation Export" data-tooltip="Buat BC" aria-label="Buat Booking Confirmation Export"><x-icon name="clipboard"/></a>
                    @endif
                @endif
                @can('update',$job)
                    <a class="btn-action" href="{{ route('jobs.edit',$job) }}" title="Edit Job" data-tooltip="Edit" aria-label="Edit Job"><x-icon name="edit"/></a>
                @endcan
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8">
            <div class="empty-state">
                <x-icon name="briefcase"/>
                <h3>Belum ada Job Order yang sesuai</h3>
                <p>Job tersedia setelah quotation disetujui dan dikonversi.</p>
            </div>
        </td>
    </tr>
@endforelse
</tbody>
</table>
</div>
<div class="pagination">{{ $jobs->links() }}</div>
</section>
@endsection