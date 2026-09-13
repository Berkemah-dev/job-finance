@extends('layouts.app')
@section('title','Closing Job')
@section('content')
<x-menu-banner
    tag="KEUANGAN & CLOSING"
    title="Closing Pekerjaan"
    description="Finalisasi biaya modal, periksa pendapatan jual, dan kunci profit margin sebelum generate invoice."
    icon="check"
    art-title="Profit final,"
    art-subtitle="terkunci aman."
/>
<section class="panel">
    <form class="filter-bar" method="GET">
        <input name="search" value="{{ $search ?? request('search') }}" placeholder="Cari nomor job, customer, atau pekerjaan" aria-label="Cari closing job">
        <button class="button button-primary">Cari</button>
        <a class="text-link" href="{{ route('closing.index') }}">Reset</a>
    </form>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Job / Pekerjaan</th><th>Customer</th><th>Biaya</th><th>Draft</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($jobs as $job)
                    <tr>
                        <td><strong>{{ $job->number }}</strong><br><small>{{ $job->subject }}</small></td>
                        <td>{{ $job->quotation_snapshot['customer']['name'] ?? '—' }}</td>
                        <td>{{ $job->costs_count }}</td>
                        <td>{{ $job->draft_costs_count }}</td>
                        <td><span class="status-badge status-{{ $job->status }}">{{ config('operations.job_statuses.'.$job->status) }}</span></td>
                        <td><div class="table-actions"><a class="btn-action btn-action-primary" href="{{ route('closing.create',$job) }}" title="Periksa Closing Job" data-tooltip="Closing" aria-label="Periksa Closing Job"><x-icon name="clipboard-check"/></a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state"><x-icon name="check"/><h3>Tidak ada job siap closing</h3><p>Job open yang sesuai filter akan muncul di sini.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $jobs->links() }}</div>
</section>
@endsection
