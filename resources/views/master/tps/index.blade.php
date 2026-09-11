@extends('layouts.app')
@section('title','Master TPS')
@section('content')
<div class="page-heading"><div><p class="eyebrow">MASTER DATA</p><h1>Master TPS</h1><p>Kelola Tempat Penimbunan Sementara (TPS) udara dan laut.</p></div><span class="date-chip"><x-icon name="calendar"/>{{ now()->locale('id')->translatedFormat('d F Y') }}</span></div>
<x-menu-banner
    tag="MASTER DATA"
    title="Master TPS Air & Sea"
    description="Kelola Tempat Penimbunan Sementara (TPS) bandara dan pelabuhan beserta kode kepabeanan."
    :action-url="route('tps.create')"
    action-label="+ Tambah TPS"
    action-icon="plus"
    icon="briefcase"
    art-title="Terminal kargo,"
    art-subtitle="terintegrasi."
/>
<section class="panel">
<form class="filter-bar" method="GET"><input name="search" value="{{ $search }}" placeholder="Cari kode, nama, atau kota" aria-label="Cari TPS"><select name="mode" aria-label="Filter moda"><option value="">Semua moda</option><option value="air" @selected($mode==='air')>Air (Udara)</option><option value="sea" @selected($mode==='sea')>Sea (Laut)</option></select><button class="button button-primary">Cari</button><a class="text-link" href="{{ route('tps.index') }}">Reset</a></form>
<div class="table-scroll"><table><thead><tr><th>Kode</th><th>Nama TPS</th><th>Kota</th><th>Moda</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($tps as $item)<tr><td><strong>{{ $item->code }}</strong></td><td>{{ $item->name }}<br><small>{{ $item->address }}</small></td><td>{{ $item->city }}</td><td>{{ $item->mode_label }}</td><td>{!! $item->is_active ? '<span class="status-badge status-paid">Aktif</span>' : '<span class="status-badge status-cancelled">Nonaktif</span>' !!}</td><td><a class="text-link" href="{{ route('tps.edit',$item) }}">Edit</a></td></tr>@empty<tr><td colspan="6"><div class="empty-state"><x-icon name="file"/><h3>Belum ada TPS</h3><p>Tambahkan TPS untuk memulai.</p></div></td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $tps->links() }}</div></section>
@endsection