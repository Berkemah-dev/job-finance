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
<section class="panel"><div class="table-scroll"><table><thead><tr><th>Job</th><th>Customer</th><th>Biaya</th><th>Draft</th><th>Aksi</th></tr></thead><tbody>@forelse($jobs as $job)<tr><td><strong>{{ $job->number }}</strong><br>{{ $job->subject }}</td><td>{{ $job->quotation_snapshot['customer']['name'] }}</td><td>{{ $job->costs_count }}</td><td>{{ $job->draft_costs_count }}</td><td><div class="table-actions"><a class="btn-action btn-action-primary" href="{{ route('closing.create',$job) }}" title="Periksa Closing Job" data-tooltip="Closing" aria-label="Periksa Closing Job"><x-icon name="clipboard-check"/></a></div></td></tr>@empty<tr><td colspan="5"><div class="empty-state"><h3>Tidak ada job Open</h3><p>Job yang siap diproses akan muncul di sini.</p></div></td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $jobs->links() }}</div></section>
@endsection
