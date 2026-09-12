@extends('layouts.app')
@section('title','Data Document')
@section('content')
<x-menu-banner
    tag="MASTER DATA"
    title="Data Document"
    description="Kelola checklist dokumen per service: export sea, export air, import sea, dan import air."
    :action-url="route('document-types.create')"
    action-label="+ Tambah Document"
    action-icon="plus"
    icon="file"
    art-title="Standar berkas,"
    art-subtitle="terorganisir."
/>
<section class="panel">
<form class="filter-bar" method="GET"><input name="search" value="{{ $search }}" placeholder="Cari kode atau nama document" aria-label="Cari data document"><button class="button button-primary">Cari</button><a class="text-link" href="{{ route('document-types.index') }}">Reset</a></form>
@if($search === '' && $groupedTypes->isNotEmpty())
    <div style="padding:18px 20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;">
        @foreach($groupedTypes as $serviceName => $rows)
            <div style="border:1px solid #dbe3ef;border-radius:14px;background:#fff;overflow:hidden;">
                <div style="padding:14px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-weight:800;color:#0f1f3d;">{{ $serviceName }}</div>
                <div style="display:flex;flex-direction:column;">
                    @foreach($rows as $type)
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:11px 14px;border-bottom:1px solid #f1f5f9;">
                            <div>
                                <strong>{{ $type->name }}</strong>
                                <br><small>{{ $type->code }}</small>
                            </div>
                            <a class="btn-action" href="{{ route('document-types.edit',$type) }}" title="Edit Data Document" data-tooltip="Edit" aria-label="Edit Data Document"><x-icon name="edit"/></a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="table-scroll"><table><thead><tr><th>Kode</th><th>Nama Document</th><th>Service</th><th>Kategori</th><th>Wajib?</th><th>Status</th><th>Urutan</th><th>Aksi</th></tr></thead><tbody>@forelse($types as $type)<tr><td><strong>{{ $type->code }}</strong></td><td>{{ $type->name }}<br><small>{{ $type->description }}</small></td><td><small>{{ $type->service_labels }}</small></td><td>{{ $type->category_label }}</td><td>{!! $type->is_required ? '<span class="status-badge status-open">Wajib</span>' : '<span class="status-badge">Opsional</span>' !!}</td><td>{!! $type->is_active ? '<span class="status-badge status-paid">Aktif</span>' : '<span class="status-badge status-cancelled">Nonaktif</span>' !!}</td><td>{{ $type->sort_order }}</td><td><div class="table-actions"><a class="btn-action" href="{{ route('document-types.edit',$type) }}" title="Edit Data Document" data-tooltip="Edit" aria-label="Edit Data Document"><x-icon name="edit"/></a></div></td></tr>@empty<tr><td colspan="8"><div class="empty-state"><x-icon name="file"/><h3>Belum ada data document</h3><p>Tambahkan data document untuk memulai.</p></div></td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $types->links() }}</div>
@endif
</section>
@endsection
