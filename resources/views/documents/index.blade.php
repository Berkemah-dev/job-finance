@extends('layouts.app')
@section('title','Dokumen Job')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">OPERASIONAL</p>
        <h1>Dokumen Job</h1>
        <p>Gabungan quotation dan Job Order dalam satu alur kerja.</p>
    </div>
    @can('quotations.manage')
    <a class="button button-primary" href="{{ route('quotations.create') }}">+ Buat quotation</a>
    @endcan
</div>

<section class="panel document-workspace">
    <form class="filter-bar" method="GET">
        <input name="search" value="{{ $search }}" placeholder="Cari nomor, customer, atau pekerjaan" aria-label="Cari dokumen job">
        <button class="button button-primary">Cari</button>
        <a class="text-link" href="{{ route('documents.index') }}">Reset</a>
    </form>
    <div class="document-columns">
        @can('quotations.manage')
        <div class="document-column">
            <div class="panel-heading">
                <div>
                    <h2>Quotation</h2>
                    <p>Penawaran yang sedang disusun, disetujui, atau sudah menjadi job.</p>
                </div>
                <a class="text-link" href="{{ route('quotations.index') }}">Lihat semua</a>
            </div>
            <div class="table-scroll">
                <table>
                    <thead><tr><th>Nomor</th><th>Customer</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @forelse($quotations as $quotation)
                    <tr>
                        <td><strong>{{ $quotation->number }}</strong><br><small>{{ $quotation->quotation_date->format('d/m/Y') }}</small></td>
                        <td>{{ $quotation->customer_snapshot['name'] }}<br><small>{{ Str::limit($quotation->subject, 38) }}</small></td>
                        <td><span class="status-badge status-{{ $quotation->status->value }}">{{ $quotation->status->label() }}</span></td>
                        <td>
                            @if($quotation->job)
                            <a class="text-link" href="{{ route('jobs.show', $quotation->job) }}">Buka job</a>
                            @else
                            <a class="text-link" href="{{ route('quotations.show', $quotation) }}">Lihat</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4"><div class="empty-state"><h3>Belum ada quotation</h3><p>Buat quotation untuk memulai dokumen pekerjaan.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endcan

        <div class="document-column">
            <div class="panel-heading">
                <div>
                    <h2>Job Order</h2>
                    <p>Pekerjaan hasil konversi quotation dan status operasionalnya.</p>
                </div>
                <a class="text-link" href="{{ route('jobs.index') }}">Lihat semua</a>
            </div>
            <div class="table-scroll">
                <table>
                    <thead><tr><th>Nomor</th><th>Customer</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @forelse($jobs as $job)
                    <tr>
                        <td><strong>{{ $job->number }}</strong><br><small>{{ optional($job->job_date)->format('d/m/Y') ?? '-' }}</small></td>
                        <td>{{ $job->quotation_snapshot['customer']['name'] ?? $job->customer?->name }}<br><small>{{ Str::limit($job->subject, 38) }}</small></td>
                        <td><span class="status-badge status-{{ $job->status }}">{{ config('operations.job_statuses.'.$job->status) }}</span></td>
                        <td><a class="text-link" href="{{ route('jobs.show', $job) }}">Lihat</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="4"><div class="empty-state"><h3>Belum ada Job Order</h3><p>Job akan muncul setelah quotation dikonversi.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
