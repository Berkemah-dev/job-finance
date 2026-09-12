@extends('layouts.app')
@section('title','Dokumen Job')
@section('content')
@php
$quickFilters = [
    '' => 'Semua',
    'draft' => 'Draft',
    'approval' => 'Menunggu Approval',
    'approved' => 'Quotation Approved',
    'running' => 'Job Berjalan',
    'done' => 'Selesai',
];
@endphp
<x-menu-banner
    tag="OPERASIONAL"
    title="Pusat Dokumen Operasional"
    description="Generate dan cetak Job Order, Surat Jalan, Tanda Terima, dan SK/DO langsung ke format PDF."
    :action-url="auth()->user()->can('quotations.manage') ? route('quotations.create') : null"
    action-label="+ Buat Dokumen Job"
    action-icon="plus"
    icon="file"
    art-title="Dokumen logistik,"
    art-subtitle="siap cetak."
/>

<section class="document-stats">
    <article class="document-stat"><span>Total Job</span><strong>{{ number_format($summary['total']) }}</strong></article>
    <article class="document-stat"><span>Draft</span><strong>{{ number_format($summary['draft']) }}</strong></article>
    <article class="document-stat"><span>Menunggu Persetujuan</span><strong>{{ number_format($summary['approval']) }}</strong></article>
    <article class="document-stat"><span>Approved (Siap JO)</span><strong>{{ number_format($summary['approved']) }}</strong></article>
    <article class="document-stat"><span>Aktif / Berjalan</span><strong>{{ number_format($summary['running']) }}</strong></article>
    <article class="document-stat"><span>Selesai</span><strong>{{ number_format($summary['done']) }}</strong></article>
</section>

<section class="panel document-workspace">
    <form class="document-filter" method="GET">
        <div class="quick-filters">
            @foreach($quickFilters as $value => $label)
            <a class="{{ request('status','') === $value ? 'active' : '' }}" href="{{ route('documents.index', array_filter([...request()->except('page'), 'status' => $value], fn ($item) => $item !== null && $item !== '')) }}">{{ $label }}</a>
            @endforeach
        </div>
        <div class="document-filter-grid">
            <input name="search" value="{{ $search }}" placeholder="Cari nomor job, quotation, customer, atau pekerjaan" aria-label="Cari dokumen job">
            <select name="customer_id" aria-label="Customer">
                <option value="">Semua customer</option>
                @foreach($customers as $customer)
                <option value="{{ $customer->id }}" @selected((string) request('customer_id') === (string) $customer->id)>{{ $customer->code }} - {{ $customer->name }}</option>
                @endforeach
            </select>
            <select name="quotation_status" aria-label="Status quotation">
                <option value="">Semua quotation</option>
                @foreach(\App\Enums\QuotationStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('quotation_status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <select name="job_status" aria-label="Status Job Order">
                <option value="">Semua Job Order</option>
                @foreach(config('operations.job_statuses') as $value => $label)
                <option value="{{ $value }}" @selected(request('job_status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="date-filter-group">
                <x-icon name="calendar"/>
                <input type="date" name="period_from" value="{{ request('period_from') }}" aria-label="Periode mulai" title="Periode mulai">
                <span class="date-sep">→</span>
                <input type="date" name="period_to" value="{{ request('period_to') }}" aria-label="Periode sampai" title="Periode sampai">
            </div>
            <label class="document-filter-check"><input type="checkbox" name="my_jobs" value="1" @checked($myJobs)> <span>Hanya Job Saya (CS/Sales)</span></label>
            <button class="button button-primary">Terapkan</button>
            <a class="button button-secondary" href="{{ route('documents.index') }}">Reset Filter</a>
        </div>
    </form>

    <div class="table-scroll document-table">
        <table>
            <thead><tr><th>No</th><th>Job / Customer</th><th>Quotation</th><th>Job Order</th><th class="money">Nilai</th><th>Status</th><th>Updated</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($documents as $quotation)
                @php
                $job = $quotation->job;
                $workflowStatus = $job ? config('operations.job_statuses.'.$job->status) : ($quotation->status === \App\Enums\QuotationStatus::Approved ? 'Siap dibuat Job Order' : $quotation->status->label());
                @endphp
                <tr>
                    <td>{{ $documents->firstItem() + $loop->index }}</td>
                    <td><strong>{{ $job?->number ?? 'Belum ada Job Order' }}</strong><br><span>{{ $quotation->customer_snapshot['name'] }}</span><br><small>{{ Str::limit($quotation->subject, 48) }}</small></td>
                    <td><strong>{{ $quotation->number }}</strong><br><span class="status-badge status-{{ $quotation->status->value }}">{{ $quotation->status->label() }}</span></td>
                    <td>
                        @if($job)
                        <strong>{{ $job->number }}</strong><br><span class="status-badge status-{{ $job->status }}">{{ config('operations.job_statuses.'.$job->status) }}</span>
                        @elseif($quotation->status === \App\Enums\QuotationStatus::Approved && auth()->user()->can('convert', $quotation))
                        <form method="POST" action="{{ route('quotations.convert', $quotation) }}" data-confirm="Buat Job Order dari quotation ini?">@csrf<input type="hidden" name="lock_version" value="{{ $quotation->lock_version }}"><button class="btn-action btn-action-primary" title="Create JO" data-tooltip="Create JO" aria-label="Create JO"><x-icon name="plus-square"/></button></form>
                        @else
                        <span class="muted-cell">Belum dibuat</span>
                        @endif
                    </td>
                    <td class="money">Rp {{ \App\Support\Money::format($quotation->subtotal) }}</td>
                    <td><span class="document-status">{{ $workflowStatus }}</span></td>
                    <td>{{ $quotation->updated_at->format('d/m/Y H:i') }}</td>
                    <td><div class="table-actions"><a class="btn-action btn-action-primary" href="{{ route('documents.show', $quotation) }}" title="Detail Dokumen Job" data-tooltip="Detail" aria-label="Detail Dokumen Job"><x-icon name="eye"/></a></div></td>
                </tr>
                @empty
                <tr><td colspan="8"><div class="empty-state"><h3>Belum ada Dokumen Job.</h3><p>Buat Dokumen Job untuk memulai alur Quotation sampai Job Order.</p>@can('quotations.manage')<a class="button button-primary" href="{{ route('quotations.create') }}">+ Buat Dokumen Job</a>@endcan</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="document-pagination"><span>Total data: {{ $documents->total() }}</span>{{ $documents->links() }}</div>
</section>
@endsection
