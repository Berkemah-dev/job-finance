@extends('layouts.app')
@section('title', 'Dashboard Customer Service')
@section('content')
<div class="page-heading"><div><p class="eyebrow">WORKSPACE CUSTOMER SERVICE</p><h1>Dashboard Customer Service</h1><p>Pantau job yang mendekati jadwal tiba.</p></div></div>
<section class="panel"><div class="panel-heading"><div><h2>Job mendekati tiba</h2><p>ETA dalam 14 hari ke depan.</p></div><span class="count-badge">{{ $arrivalSoon->count() }}</span></div><div class="table-scroll"><table><thead><tr><th>Job</th><th>Subject</th><th>ETA</th></tr></thead><tbody>@forelse($arrivalSoon as $job)<tr><td><a class="text-link" href="{{ route('jobs.show',$job) }}">{{ $job->number }}</a></td><td>{{ $job->subject }}</td><td>{{ $job->eta->format('d/m/Y') }}</td></tr>@empty<tr><td colspan="3">Tidak ada job yang mendekati tiba.</td></tr>@endforelse</tbody></table></div></section>
@endsection
