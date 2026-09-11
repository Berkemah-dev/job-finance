@extends('layouts.app')
@section('title','Master Data Port')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">MASTER DATA</p><h1>Data Port</h1><p>Kelola daftar pelabuhan asal dan tujuan pengiriman.</p></div>
</div>
<section class="panel">
    <div style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;margin-bottom:1rem;">
        <form class="filter-bar" method="GET" style="flex:1;min-width:260px;">
            <input name="search" value="{{ $search }}" placeholder="Cari nama atau kode port..." aria-label="Cari port">
            <select name="status" style="padding:0.5rem 0.75rem;border:1px solid var(--border);border-radius:6px;background:var(--surface);color:var(--text);">
                <option value="all" {{ $status==='all'?'selected':'' }}>Semua Status</option>
                <option value="active" {{ $status==='active'?'selected':'' }}>Aktif</option>
                <option value="inactive" {{ $status==='inactive'?'selected':'' }}>Nonaktif</option>
            </select>
            <button class="button button-primary" id="btn-search-port">Cari</button>
            <a class="text-link" href="{{ route('ports.index') }}">Reset</a>
        </form>
        <form method="POST" action="{{ route('ports.store') }}" style="display:flex;gap:0.5rem;align-items:center;">
            @csrf
            <input name="name" placeholder="Nama Port (mis: SURABAYA, INDONESIA)" required maxlength="150" style="padding:0.5rem 0.75rem;border:1px solid var(--border);border-radius:6px;min-width:340px;background:var(--surface);color:var(--text);">
            <input name="code" placeholder="Kode (mis: IDSUB)" required maxlength="20" style="padding:0.5rem 0.75rem;border:1px solid var(--border);border-radius:6px;width:150px;background:var(--surface);color:var(--text);">
            <button class="button button-primary" id="btn-add-port">+ Tambah</button>
        </form>
    </div>
    <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:0.75rem;">Menampilkan {{ $ports->total() }} port {{ $status!=='all'?'('.($status==='active'?'aktif':'nonaktif').')':'' }}</p>
    <div class="table-scroll">
        <table>
            <thead><tr><th>#</th><th>Nama Port</th><th>Kode</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($ports as $i => $port)
                <tr>
                    <td style="color:var(--text-muted);font-size:0.8rem;">{{ $ports->firstItem()+$i }}</td>
                    <td><strong>{{ $port->name }}</strong></td>
                    <td><code style="background:var(--surface-2);padding:0.1rem 0.4rem;border-radius:4px;font-size:0.85rem;">{{ $port->code }}</code></td>
                    <td>
                        @if($port->is_active)
                            <span class="status-badge status-paid">Aktif</span>
                        @else
                            <span class="status-badge status-cancelled">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        <div class="table-actions">
                            <a class="btn-action" href="{{ route('ports.edit', $port) }}" id="btn-edit-port-{{ $port->id }}" title="Edit Port" data-tooltip="Edit" aria-label="Edit Port"><x-icon name="edit"/></a>
                            <form method="POST" action="{{ route('ports.toggle',$port) }}">
                                @csrf @method('PATCH')
                                <button class="btn-action {{ $port->is_active ? 'btn-action-warning' : 'btn-action-success' }}" id="btn-toggle-port-{{ $port->id }}" title="{{ $port->is_active?'Nonaktifkan':'Aktifkan' }}" data-tooltip="{{ $port->is_active?'Nonaktifkan':'Aktifkan' }}" aria-label="{{ $port->is_active?'Nonaktifkan':'Aktifkan' }}"><x-icon name="{{ $port->is_active ? 'power' : 'check' }}"/></button>
                            </form>
                            <form method="POST" action="{{ route('ports.destroy',$port) }}" onsubmit="return confirm('Hapus port ini?')">
                                @csrf @method('DELETE')
                                <button class="btn-action btn-action-danger" id="btn-del-port-{{ $port->id }}" title="Hapus Port" data-tooltip="Hapus" aria-label="Hapus Port"><x-icon name="trash"/></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="empty-state"><h3>Belum ada data port</h3><p>Tambahkan port di atas.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $ports->links() }}</div>
</section>
@endsection