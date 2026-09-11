@extends('layouts.app')
@section('title', 'Dashboard Customer Service')
@section('content')
<div class="role-hero"><div><span class="eyebrow" style="color:#b9c9e6">WORKSPACE CUSTOMER SERVICE</span><h2>Siap menyambut kedatangan.</h2><p>Ikuti ETA dan hubungi customer sebelum barang tiba.</p></div><span class="status-chip">{{ $arrivalSoon->count() }} mendekat</span></div>
<section class="panel"><div class="panel-heading"><div><h2>Job mendekati tiba</h2><p>ETA dalam 14 hari ke depan.</p></div><span class="count-badge">{{ $arrivalSoon->count() }}</span></div><div class="table-scroll"><table><thead><tr><th>Job</th><th>Subject</th><th>ETA</th></tr></thead><tbody>@forelse($arrivalSoon as $job)<tr><td><a class="text-link" href="{{ route('jobs.show',$job) }}">{{ $job->number }}</a></td><td>{{ $job->subject }}</td><td>{{ $job->eta->format('d/m/Y') }}</td></tr>@empty<tr><td colspan="3">Tidak ada job yang mendekati tiba.</td></tr>@endforelse</tbody></table></div></section>
@endsection
