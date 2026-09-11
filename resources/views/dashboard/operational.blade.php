@extends('layouts.app')
@section('title', 'Dashboard Operational')
@section('content')
<div class="role-hero"><div><span class="eyebrow" style="color:#b9c9e6">WORKSPACE OPERATIONAL</span><h2>Job siap diselesaikan.</h2><p>Pastikan biaya setiap job sudah lengkap dan final.</p></div><span class="status-chip">{{ $unfinishedJobs->count() }} perlu dicek</span></div>
<section class="panel"><div class="panel-heading"><div><h2>Job belum final</h2><p>Job yang masih memiliki biaya Draft.</p></div><span class="count-badge">{{ $unfinishedJobs->count() }}</span></div><div class="table-scroll"><table><thead><tr><th>Job</th><th>Subject</th><th>Status biaya</th></tr></thead><tbody>@forelse($unfinishedJobs as $job)<tr><td><a class="text-link" href="{{ route('jobs.show',$job) }}">{{ $job->number }}</a></td><td>{{ $job->subject }}</td><td>Belum final</td></tr>@empty<tr><td colspan="3">Semua biaya job sudah final.</td></tr>@endforelse</tbody></table></div></section>
@endsection
