@extends('layouts.app')
@section('title','Log Aktivitas')
@section('content')
<x-menu-banner
    tag="AUDIT & KEAMANAN"
    title="Log Aktivitas Sistem"
    description="Rekam jejak audit trail seluruh tindakan pengguna untuk menjaga transparansi dan integritas data."
    icon="clock"
    art-title="Audit trail,"
    art-subtitle="terekam detail."
/>
<section class="panel"><form method="GET" class="filter-bar">
<div class="date-filter-group">
    <x-icon name="calendar"/>
    <input name="date_from" type="date" value="{{ $filters['date_from'] }}" aria-label="Dari tanggal" title="Dari tanggal">
    <span class="date-sep">→</span>
    <input name="date_to" type="date" value="{{ $filters['date_to'] }}" aria-label="Sampai tanggal" title="Sampai tanggal">
</div>
<select name="user_id" aria-label="Pengguna"><option value="">Semua pengguna</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected($filters['user_id']===$user->id)>{{ $user->name }}</option>@endforeach</select>
<select name="role_id" aria-label="Role"><option value="">Semua role</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected($filters['role_id']===$role->id)>{{ $role->label }}</option>@endforeach</select>
<select name="module" aria-label="Modul"><option value="">Semua modul</option>@foreach($modules as $module)<option value="{{ $module }}" @selected($filters['module']===$module)>{{ $module }}</option>@endforeach</select>
<select name="action" aria-label="Aksi"><option value="">Semua aksi</option>@foreach(['auth.login','auth.logout','quotation.created','quotation.updated','quotation.submitted','quotation.approved','quotation.rejected','quotation.converted','job.created','job.updated','job.opened','job.closed','job.cancelled','customer.created','customer.updated','customer.archived','vendor.created','vendor.updated','vendor.archived','job_cost.created','job_cost.updated','job_cost.finalized','job_cost.deleted','payment.created','journal.adjustment_created','journal.reversed','user.created','user.updated','document.generated'] as $action)<option value="{{ $action }}" @selected($filters['action']===$action)>{{ $action }}</option>@endforeach</select>
<button class="button button-primary">Terapkan</button><a class="text-link" href="{{ route('activity.index') }}">Reset</a>
</form>
@if($logs->total())
<p class="filter-count">Menampilkan {{ $logs->firstItem() }}–{{ $logs->lastItem() }} dari {{ $logs->total() }} aktivitas.</p>
@endif
<div class="table-scroll"><table><thead><tr><th>Waktu</th><th>Pengguna</th><th>Role</th><th>Modul</th><th>Aksi</th><th>Deskripsi</th></tr></thead><tbody>@forelse($logs as $log)<tr><td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td><td>{{ $log->user?->name ?? 'Pengguna dihapus' }}</td><td>{{ $log->role?->label ?? '—' }}</td><td>{{ $log->module ?? '—' }}</td><td><code>{{ $log->action }}</code></td><td>{{ $log->description }}</td></tr>@empty<tr><td colspan="6"><div class="empty-state"><h3>Belum ada aktivitas yang cocok</h3><p>Kurangi filter atau coba rentang tanggal lain.</p></div></td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $logs->links() }}</div></section>
@endsection