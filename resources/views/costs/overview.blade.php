@extends('layouts.app')
@section('title','Biaya Job')
@section('content')
<div class="page-heading"><div><p class="eyebrow">KEUANGAN & HPP</p><h1>Biaya Job</h1><p>Pilih pekerjaan untuk mencatat dan memeriksa biaya aktual.</p></div><span class="date-chip"><x-icon name="calendar"/>{{ now()->locale('id')->translatedFormat('d F Y') }}</span></div>
<x-menu-banner
    tag="KEUANGAN & HPP"
    title="Biaya Job & Modal Operasional"
    description="Catat pengeluaran uang muka temporary, biaya final vendor, dan modal per nomor job."
    icon="wallet"
    art-title="Modal pekerjaan,"
    art-subtitle="terkontrol ketat."
/>
<section class="panel"><form class="filter-bar" method="GET"><input name="search" value="{{ $search }}" placeholder="Cari nomor atau nama pekerjaan" aria-label="Cari job"><select name="status" aria-label="Status job"><option value="">Semua status job</option>@foreach(config('operations.job_statuses') as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select><button class="button button-primary">Cari</button><a class="text-link" href="{{ route('costs.overview') }}">Reset</a></form>
<div class="table-scroll"><table><thead><tr><th>Job / Customer</th><th>Pekerjaan</th><th>Status job</th><th>Biaya Draft</th><th>Biaya Final</th><th>Aksi</th></tr></thead><tbody>@forelse($jobs as $job)<tr><td><strong>{{ $job->number }}</strong><br><small>{{ $job->quotation_snapshot['customer']['name'] }}</small></td><td>{{ Str::limit($job->subject,40) }}</td><td><span class="status-badge status-{{ $job->status }}">{{ config('operations.job_statuses.'.$job->status) }}</span></td><td>{{ $job->draft_costs_count }}</td><td>{{ $job->final_costs_count }}</td><td><a class="text-link" href="{{ route('jobs.costs.index',$job) }}">Lihat biaya</a></td></tr>@empty<tr><td colspan="6"><div class="empty-state"><x-icon name="wallet"/><h3>Belum ada job yang sesuai</h3><p>Job tersedia setelah quotation dikonversi.</p></div></td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $jobs->links() }}</div></section>
@endsection
