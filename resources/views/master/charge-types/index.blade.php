@extends('layouts.app')
@section('title','Master Jenis Biaya')
@section('content')
<x-menu-banner
    tag="MASTER DATA"
    title="Jenis Biaya (Charge Type)"
    description="Kelola daftar jenis biaya / cost operational dan penagihan freight forwarding."
    icon="database"
    art-title="Jenis Biaya,"
    art-subtitle="tercatat rapi."
/>

<section class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; padding: 18px 20px; border-bottom: 1px solid #edf1f7; background: #fafcff;">
        <form class="filter-bar" method="GET" style="padding: 0; border: none; flex: 1; min-width: 280px; gap: 10px;">
            <input name="search" value="{{ $search }}" placeholder="Cari nama biaya..." aria-label="Cari jenis biaya">
            <select name="status" aria-label="Filter status">
                <option value="all" {{ $status==='all'?'selected':'' }}>Semua Status</option>
                <option value="active" {{ $status==='active'?'selected':'' }}>Aktif</option>
                <option value="inactive" {{ $status==='inactive'?'selected':'' }}>Nonaktif</option>
            </select>
            <button class="button button-primary" id="btn-search-charge">Cari</button>
            <a class="text-link" href="{{ route('charge-types.index') }}">Reset</a>
        </form>

        <form method="POST" action="{{ route('charge-types.store') }}" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @csrf
            <input name="name" placeholder="Nama Jenis Biaya (mis: TRUCKING)" required maxlength="150" style="padding: 10px 12px; border: 1px solid #dce4ef; border-radius: 8px; font-size: 12px; min-width: 280px; background: #fff; color: #354967;">
            <button class="button button-primary" id="btn-add-charge" style="white-space: nowrap;">+ Tambah Biaya</button>
        </form>
    </div>

    <div class="filter-count" style="padding: 12px 20px 0;">
        Menampilkan <strong>{{ $charges->total() }}</strong> jenis biaya {{ $status!=='all' ? '('.($status==='active'?'aktif':'nonaktif').')' : '' }}
    </div>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Nama Jenis Biaya</th>
                    <th>Status</th>
                    <th style="width: 130px; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($charges as $i => $charge)
                <tr>
                    <td style="color:var(--text-muted);font-size:0.8rem;">{{ $charges->firstItem()+$i }}</td>
                    <td><strong>{{ $charge->name }}</strong></td>
                    <td>
                        @if($charge->is_active)
                            <span class="status-badge status-paid">Aktif</span>
                        @else
                            <span class="status-badge status-cancelled">Nonaktif</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <div class="table-actions" style="justify-content: flex-end;">
                            <a class="btn-action" href="{{ route('charge-types.edit', $charge) }}" id="btn-edit-charge-{{ $charge->id }}" title="Edit Jenis Biaya" data-tooltip="Edit" aria-label="Edit Jenis Biaya"><x-icon name="edit"/></a>
                            <form method="POST" action="{{ route('charge-types.toggle',$charge) }}">
                                @csrf @method('PATCH')
                                <button class="btn-action {{ $charge->is_active ? 'btn-action-warning' : 'btn-action-success' }}" id="btn-toggle-charge-{{ $charge->id }}" title="{{ $charge->is_active?'Nonaktifkan':'Aktifkan' }}" data-tooltip="{{ $charge->is_active?'Nonaktifkan':'Aktifkan' }}" aria-label="{{ $charge->is_active?'Nonaktifkan':'Aktifkan' }}"><x-icon name="{{ $charge->is_active ? 'power' : 'check' }}"/></button>
                            </form>
                            <form method="POST" action="{{ route('charge-types.destroy',$charge) }}" onsubmit="return confirm('Hapus jenis biaya ini?')">
                                @csrf @method('DELETE')
                                <button class="btn-action btn-action-danger" id="btn-del-charge-{{ $charge->id }}" title="Hapus Jenis Biaya" data-tooltip="Hapus" aria-label="Hapus Jenis Biaya"><x-icon name="trash"/></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4">
                        <div class="empty-state">
                            <x-icon name="database"/>
                            <h3>Belum ada jenis biaya</h3>
                            <p>Tambahkan jenis biaya pada form di atas.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $charges->links() }}</div>
</section>
@endsection