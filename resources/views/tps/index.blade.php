@extends('layouts.app')
@section('title','Master TPS Air/Sea')
@section('content')
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
<form class="filter-bar" method="GET"><input name="search" value="{{ $search }}" placeholder="Cari nama, kode, atau kota" aria-label="Cari TPS"><select name="mode" aria-label="Moda TPS"><option value="">Semua moda</option><option value="air" @selected($mode==='air')>✈ Udara (Air)</option><option value="sea" @selected($mode==='sea')>🚢 Laut (Sea)</option></select><select name="status" aria-label="Status TPS"><option value="active" @selected($status==='active')>Aktif</option><option value="inactive" @selected($status==='inactive')>Nonaktif</option></select><button class="button button-primary">Cari</button><a class="text-link" href="{{ route('tps.index') }}">Reset</a></form>
<div class="table-scroll"><table><thead><tr><th>Kode TPS</th><th>Nama TPS</th><th>Kota</th><th>Moda</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($tpsList as $tps)
<tr>
    <td><strong>{{ $tps->code }}</strong></td>
    <td>{{ $tps->name }}</td>
    <td>{{ $tps->city }}</td>
    <td>@if($tps->mode==='air')<span class="badge-pill">✈ Udara</span>@else<span class="badge-pill">🚢 Laut</span>@endif</td>
    <td>@if($tps->is_active)<span class="status-badge status-active">Aktif</span>@else<span class="status-badge status-inactive">Nonaktif</span>@endif</td>
    <td>
        <a class="text-link" href="{{ route('tps.edit',$tps) }}">Edit</a>
        ·
        <form method="POST" action="{{ route('tps.toggle',$tps) }}" style="display:inline">@csrf<button class="text-link">{{ $tps->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
        ·
        <form method="POST" action="{{ route('tps.destroy',$tps) }}" style="display:inline" data-confirm="Hapus TPS {{ $tps->code }}?">@csrf @method('DELETE')<button class="text-link" style="color:var(--color-danger)">Hapus</button></form>
    </td>
</tr>
@empty
<tr><td colspan="6"><div class="empty-state"><h3>Belum ada TPS yang sesuai</h3><p>Tambahkan TPS atau sesuaikan pencarian.</p></div></td></tr>
@endforelse
</tbody></table></div>
<div class="pagination">{{ $tpsList->links() }}</div>
</section>
@endsection
