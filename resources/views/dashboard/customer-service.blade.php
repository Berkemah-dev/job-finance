@extends('layouts.app')
@section('title', 'Dashboard Customer Service')
@section('content')
<div class="role-hero">
    <div>
        <span class="eyebrow" style="color:#b9c9e6">WORKSPACE CUSTOMER SERVICE</span>
        <h2>Siap menyambut kedatangan.</h2>
        <p>Ikuti ETA kargo dan hubungi customer sebelum barang tiba.</p>
    </div>
    <span class="status-chip">{{ $arrivalSoon->count() }} mendekat</span>
</div>

<div class="metric-grid">
    <article class="metric-card"><div class="metric-label"><span>ETA 7 Hari</span><x-icon name="calendar"/></div><strong class="metric-value">{{ $arrivalSoon->where('eta','<=',today()->addDays(7))->count() }}</strong><span class="metric-caption">Perlu dikonfirmasi segera</span></article>
    <article class="metric-card"><div class="metric-label"><span>ETA 14 Hari</span><x-icon name="calendar"/></div><strong class="metric-value">{{ $arrivalSoon->count() }}</strong><span class="metric-caption">Dalam pemantauan</span></article>
    <article class="metric-card"><div class="metric-label"><span>Job Ditangani CS</span><x-icon name="briefcase"/></div><strong class="metric-value">{{ $widgets['myOpenJobs'] ?? 0 }}</strong><span class="metric-caption">CS menangani {{ $widgets['myOpenJobs'] ?? 0 }} job terbuka</span></article>
</div>

@if(isset($widgets['shipment']))
<div class="section-heading"><h2>Pengiriman berjalan</h2><span class="subtle">Job Order berstatus open menurut status pengiriman · CS menangani {{ $widgets['myOpenJobs'] ?? 0 }} job terbuka</span></div>
<section class="panel" style="margin-bottom:20px">
    <div class="filter-bar" style="justify-content:flex-start;border:none;padding:14px 23px;display:flex;flex-wrap:wrap;gap:8px">
        @foreach(config('operations.shipment_statuses') as $value=>$label)
        <span class="badge-pill status-{{ $value }}">{{ $label }} · {{ $widgets['shipment'][$value] ?? 0 }}</span>
        @if(!$loop->last)<span class="text-link">→</span>@endif
        @endforeach
    </div>
</section>
@endif

<section class="panel">
    <div class="panel-heading"><div><h2>Job mendekati tiba</h2><p>ETA dalam 14 hari ke depan.</p></div><span class="count-badge">{{ $arrivalSoon->count() }}</span></div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Job</th><th>Subject</th><th>ETA</th><th>Konfirmasi DO</th></tr></thead>
            <tbody>
                @forelse($arrivalSoon as $job)
                <tr>
                    <td><a class="text-link" href="{{ route('jobs.show',$job) }}">{{ $job->number }}</a></td>
                    <td>{{ $job->subject }}</td>
                    <td>{{ $job->eta ? $job->eta->format('d/m/Y') : '—' }}</td>
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
                <tr><td colspan="4">Tidak ada job yang mendekati tiba.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
