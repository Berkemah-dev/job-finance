@extends('layouts.app')
@section('title','Master Tipe Dokumen')
@section('content')
<x-menu-banner
    tag="MASTER DATA"
    title="Master Tipe Dokumen"
    description="Kelola kategori dan jenis berkas pendukung operasional, perizinan, dan perpajakan."
    :action-url="route('document-types.create')"
    action-label="+ Tambah Tipe Dokumen"
    action-icon="plus"
    icon="file"
    art-title="Standar berkas,"
    art-subtitle="terorganisir."
/>
<section class="panel">
<form class="filter-bar" method="GET"><input name="search" value="{{ $search }}" placeholder="Cari kode atau nama dokumen" aria-label="Cari tipe dokumen"><button class="button button-primary">Cari</button><a class="text-link" href="{{ route('document-types.index') }}">Reset</a></form>
<div class="table-scroll"><table><thead><tr><th>Kode</th><th>Nama Dokumen</th><th>Kategori</th><th>Wajib?</th><th>Status</th><th>Urutan</th><th>Aksi</th></tr></thead><tbody>@forelse($types as $type)<tr><td><strong>{{ $type->code }}</strong></td><td>{{ $type->name }}<br><small>{{ $type->description }}</small></td><td>{{ $type->category_label }}</td><td>{!! $type->is_required ? '<span class="status-badge status-open">Wajib</span>' : '<span class="status-badge">Opsional</span>' !!}</td><td>{!! $type->is_active ? '<span class="status-badge status-paid">Aktif</span>' : '<span class="status-badge status-cancelled">Nonaktif</span>' !!}</td><td>{{ $type->sort_order }}</td><td><div class="table-actions"><a class="btn-action" href="{{ route('document-types.edit',$type) }}" title="Edit Tipe Dokumen" data-tooltip="Edit" aria-label="Edit Tipe Dokumen"><x-icon name="edit"/></a></div></td></tr>@empty<tr><td colspan="7"><div class="empty-state"><x-icon name="file"/><h3>Belum ada tipe dokumen</h3><p>Tambahkan tipe dokumen untuk memulai.</p></div></td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $types->links() }}</div></section>
@endsection
