@extends('layouts.app')
@section('title','Weekly Pricing')
@section('content')
<x-menu-banner
    tag="PRICING & VALUTA"
    title="Weekly Pricing & Kurs"
    description="Kelola kurs mingguan dan tarif dasar yang berlaku sebagai standar quotation & invoice."
    :action-url="auth()->user()->can('pricing.manage') ? route('pricing.weekly.create') : null"
    action-label="+ Tambah Weekly Pricing"
    action-icon="plus"
    icon="chart"
    art-title="Fluktuasi kurs,"
    art-subtitle="terpantau presisi."
/>
<section class="panel">
<form class="filter-bar" method="GET"><input name="week" value="{{ request('week') }}" placeholder="Cari pekan (cth. W36-2026)" aria-label="Cari pekan"><select name="currency" aria-label="Mata uang"><option value="">Semua mata uang</option>@foreach(config('operations.currencies') as $code=>$label)<option value="{{ $code }}" @selected(request('currency')===$code)>{{ $code }}</option>@endforeach</select><select name="status" aria-label="Status"><option value="">Aktif & nonaktif</option><option value="inactive" @selected(request('status')==='inactive')>Nonaktif saja</option></select><button class="button button-primary">Cari</button><a class="text-link" href="{{ route('pricing.weekly.index') }}">Reset</a></form>
<div class="table-scroll"><table><thead><tr><th>Pekan</th><th>Tanggal Berlaku</th><th>Mata Uang</th><th>Kurs</th><th>Service</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($items as $item)<tr><td><strong>{{ $item->week }}</strong></td><td>{{ $item->effective_date?->format('d M Y') }}@if($item->effective_until)<br><small>s.d. {{ $item->effective_until->format('d M Y') }}</small>@endif</td><td>{{ $item->currency }}</td><td>{{ number_format((float) $item->exchange_rate, 2, ',', '.') }}</td><td>{{ $item->service ? \App\Models\ServiceType::label($item->service) : 'Semua' }}</td><td>@if($item->is_active)<span class="status-badge status-active">Aktif</span>@else<span class="status-badge status-inactive">Nonaktif</span>@endif</td><td>@can('pricing.manage')<div class="table-actions"><a class="btn-action" href="{{ route('pricing.weekly.edit',$item) }}" title="Edit Weekly Pricing" data-tooltip="Edit" aria-label="Edit Weekly Pricing"><x-icon name="edit"/></a><form method="POST" action="{{ route('pricing.weekly.toggle',$item) }}" data-confirm="{{ $item->is_active?'Nonaktifkan':'Aktifkan' }} weekly pricing ini?">@csrf<input type="hidden" name="lock_version" value="{{ $item->lock_version }}"><button class="btn-action {{ $item->is_active ? 'btn-action-warning' : 'btn-action-success' }}" title="{{ $item->is_active?'Nonaktifkan':'Aktifkan' }}" data-tooltip="{{ $item->is_active?'Nonaktifkan':'Aktifkan' }}" aria-label="{{ $item->is_active?'Nonaktifkan':'Aktifkan' }}"><x-icon name="{{ $item->is_active ? 'power' : 'check' }}"/></button></form><form method="POST" action="{{ route('pricing.weekly.destroy',$item) }}" data-confirm="Hapus weekly pricing ini?">@csrf @method('DELETE')<input type="hidden" name="lock_version" value="{{ $item->lock_version }}"><button class="btn-action btn-action-danger" title="Hapus Weekly Pricing" data-tooltip="Hapus" aria-label="Hapus Weekly Pricing"><x-icon name="trash"/></button></form></div>@endcan</td></tr>@empty<tr><td colspan="7"><div class="empty-state"><x-icon name="clock"/><h3>Belum ada weekly pricing</h3><p>Tambahkan weekly pricing atau sesuaikan filter.</p></div></td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $items->links() }}</div></section>
@endsection
