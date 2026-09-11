@extends('layouts.app')
@section('title', 'Dashboard Operational')
@section('content')
<div class="page-heading"><div><p class="eyebrow">WORKSPACE OPERATIONAL</p><h1>Dashboard Operational</h1><p>Pantau job yang masih memiliki biaya belum final.</p></div></div>
<section class="panel"><div class="panel-heading"><div><h2>Job belum final</h2><p>Job yang masih memiliki biaya Draft.</p></div><span class="count-badge">{{ $unfinishedJobs->count() }}</span></div><div class="table-scroll"><table><thead><tr><th>Job</th><th>Subject</th><th>Status biaya</th></tr></thead><tbody>@forelse($unfinishedJobs as $job)<tr><td><a class="text-link" href="{{ route('jobs.show',$job) }}">{{ $job->number }}</a></td><td>{{ $job->subject }}</td><td>Belum final</td></tr>@empty<tr><td colspan="3">Semua biaya job sudah final.</td></tr>@endforelse</tbody></table></div></section>
@endsection
