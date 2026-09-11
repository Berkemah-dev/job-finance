@extends('layouts.app')
@section('title','Master Data Port')
@section('content')
<x-menu-banner
    tag="MASTER DATA"
    title="Data Port"
    description="Kelola daftar pelabuhan muat (POL) dan pelabuhan bongkar (POD) beserta kode resmi pelabuhan."
    icon="database"
    art-title="Rute Pelabuhan,"
    art-subtitle="terdata akurat."
/>

<section class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; padding: 18px 20px; border-bottom: 1px solid #edf1f7; background: #fafcff;">
        <form class="filter-bar" method="GET" style="padding: 0; border: none; flex: 1; min-width: 280px; gap: 10px;">
            <input name="search" value="{{ $search }}" placeholder="Cari nama atau kode port..." aria-label="Cari port">
            <select name="status" aria-label="Filter status">
                <option value="all" {{ $status==='all'?'selected':'' }}>Semua Status</option>
                <option value="active" {{ $status==='active'?'selected':'' }}>Aktif</option>
                <option value="inactive" {{ $status==='inactive'?'selected':'' }}>Nonaktif</option>
            </select>
            <button class="button button-primary" id="btn-search-port">Cari</button>
            <a class="text-link" href="{{ route('ports.index') }}">Reset</a>
        </form>

        <form method="POST" action="{{ route('ports.store') }}" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @csrf
            <input name="name" placeholder="Nama Port (mis: SURABAYA, INDONESIA)" required maxlength="150" style="padding: 10px 12px; border: 1px solid #dce4ef; border-radius: 8px; font-size: 12px; min-width: 260px; background: #fff; color: #354967;">
            <input name="code" placeholder="Kode (mis: IDSUB)" required maxlength="20" style="padding: 10px 12px; border: 1px solid #dce4ef; border-radius: 8px; font-size: 12px; width: 130px; text-transform: uppercase; background: #fff; color: #354967;">
            <button class="button button-primary" id="btn-add-port" style="white-space: nowrap;">+ Tambah Port</button>
        </form>
    </div>

    <div class="filter-count" style="padding: 12px 20px 0;">
        Menampilkan <strong>{{ $ports->total() }}</strong> port {{ $status!=='all' ? '('.($status==='active'?'aktif':'nonaktif').')' : '' }}
    </div>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Nama Port</th>
                    <th>Kode</th>
                    <th>Status</th>
                    <th style="width: 130px; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ports as $i => $port)
                <tr>
                    <td style="color:var(--text-muted);font-size:0.8rem;">{{ $ports->firstItem()+$i }}</td>
                    <td><strong>{{ $port->name }}</strong></td>
                    <td><code style="background: #edf2f9; color: #0284c7; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">{{ $port->code }}</code></td>
                    <td>
                        @if($port->is_active)
                            <span class="status-badge status-paid">Aktif</span>
                        @else
                            <span class="status-badge status-cancelled">Nonaktif</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <div class="table-actions" style="justify-content: flex-end;">
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
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <x-icon name="database"/>
                            <h3>Belum ada data port</h3>
                            <p>Tambahkan port pada form di atas.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $ports->links() }}</div>
</section>
@endsection