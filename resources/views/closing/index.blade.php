@extends('layouts.app')
@section('title','Closing Job')
@section('content')
<div class="page-heading"><div><p class="eyebrow">KEUANGAN & CLOSING</p><h1>Closing Job</h1><p>Finalisasi biaya, tagihan, dan pencatatan akuntansi.</p></div><span class="date-chip"><x-icon name="calendar"/>{{ now()->locale('id')->translatedFormat('d F Y') }}</span></div>
<x-menu-banner
    tag="KEUANGAN & CLOSING"
    title="Closing Pekerjaan"
    description="Finalisasi biaya modal, periksa pendapatan jual, dan kunci profit margin sebelum generate invoice."
    icon="check"
    art-title="Profit final,"
    art-subtitle="terkunci aman."
/>
<section class="panel"><div class="table-scroll"><table><thead><tr><th>Job</th><th>Customer</th><th>Biaya</th><th>Draft</th><th>Aksi</th></tr></thead><tbody>@forelse($jobs as $job)<tr><td><strong>{{ $job->number }}</strong><br>{{ $job->subject }}</td><td>{{ $job->quotation_snapshot['customer']['name'] }}</td><td>{{ $job->costs_count }}</td><td>{{ $job->draft_costs_count }}</td><td><a class="text-link" href="{{ route('closing.create',$job) }}">Periksa closing</a></td></tr>@empty<tr><td colspan="5"><div class="empty-state"><h3>Tidak ada job Open</h3><p>Job yang siap diproses akan muncul di sini.</p></div></td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $jobs->links() }}</div></section>
@endsection
