@extends('layouts.app')
@section('title', 'Dashboard Customer Service')
@section('content')
<x-menu-banner
    tag="WORKSPACE CUSTOMER SERVICE"
    title="Siap menyambut kedatangan."
    description="Ikuti ETA kargo dan hubungi customer sebelum barang tiba."
    action-url="{{ route('jobs.index') }}"
    action-label="Monitoring Kargo"
    action-icon="arrow"
    icon="calendar"
    art-title="Kepuasan customer,"
    art-subtitle="prioritas utama."
    :show-date="true"
/>

<div class="metric-grid">
    <article class="metric-card"><div class="metric-label"><span>ETA 3 Hari</span><x-icon name="calendar"/></div><strong class="metric-value">{{ $arrivalSoon->count() }}</strong><span class="metric-caption">Perlu dikonfirmasi segera</span></article>
    <article class="metric-card"><div class="metric-label"><span>ETA Hari Ini</span><x-icon name="calendar"/></div><strong class="metric-value">{{ $arrivalSoon->filter(fn($j) => $j->eta?->isToday())->count() }}</strong><span class="metric-caption">Tiba hari ini</span></article>
    <article class="metric-card"><div class="metric-label"><span>Job Ditangani CS</span><x-icon name="briefcase"/></div><strong class="metric-value">{{ $widgets['myOpenJobs'] ?? 0 }}</strong><span class="metric-caption">CS menangani {{ $widgets['myOpenJobs'] ?? 0 }} job terbuka</span></article>
</div>

<section class="panel">
    <div class="panel-heading"><div><h2>Job mendekati tiba</h2><p>ETA dalam H-3 (3 hari ke depan).</p></div><span class="count-badge">{{ $arrivalSoon->count() }}</span></div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>NO JOB</th><th>EXPORT/IMPORT</th><th>ETA</th><th>NAMA CUSTOMER</th><th>KONFIRMASI DO</th></tr></thead>
            <tbody>
                @forelse($arrivalSoon as $job)
                <tr>
                    <td><a class="text-link" href="{{ route('jobs.show',$job) }}"><strong>{{ $job->number }}</strong></a></td>
                    <td><span class="badge-pill">{{ \App\Models\ServiceType::label($job->service_type) }}</span></td>
                    <td>{{ $job->eta ? $job->eta->format('d/m/Y') : '—' }}</td>
                    <td>{{ $job->customer?->name ?? $job->quotation_snapshot['customer']['name'] ?? '—' }}</td>
                    <td>
                        @if($job->do_confirmed_at)
                            <span class="status-badge" style="background:#ddf8ec;color:#1e9d75">DO Selesai</span>
                        @else
                            <form method="POST" action="{{ route('jobs.confirm-do', $job) }}" style="display:inline">
                                @csrf
                                <input type="hidden" name="lock_version" value="{{ $job->lock_version }}">
                                <button type="submit" class="button button-primary" style="padding:4px 9px;font-size:10px">Konfirmasi DO Selesai</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5">Tidak ada job yang mendekati tiba dalam 3 hari ke depan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
