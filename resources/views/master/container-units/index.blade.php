@extends('layouts.app')
@section('title','Master Satuan')
@section('content')
<x-menu-banner
    tag="MASTER DATA"
    title="Satuan (Unit)"
    description="Kelola daftar satuan muatan dan tipe kontainer yang digunakan dalam penawaran harga & operasional."
    icon="database"
    art-title="Satuan Muatan,"
    art-subtitle="terstandarisasi."
/>

<section class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; padding: 18px 20px; border-bottom: 1px solid #edf1f7; background: #fafcff;">
        <form class="filter-bar" method="GET" style="padding: 0; border: none; flex: 1; min-width: 280px; gap: 10px;">
            <input name="search" value="{{ $search }}" placeholder="Cari nama satuan..." aria-label="Cari satuan">
            <button class="button button-primary" id="btn-search-unit">Cari</button>
            <a class="text-link" href="{{ route('container-units.index') }}">Reset</a>
        </form>

        <form method="POST" action="{{ route('container-units.store') }}" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @csrf
            <input name="name" placeholder="Nama Satuan (mis: 40GP / CBM)" required maxlength="50" style="padding: 10px 12px; border: 1px solid #dce4ef; border-radius: 8px; font-size: 12px; min-width: 240px; background: #fff; color: #354967;">
            <button class="button button-primary" id="btn-add-unit" style="white-space: nowrap;">+ Tambah Satuan</button>
        </form>
    </div>

    <div class="filter-count" style="padding: 12px 20px 0;">
        Menampilkan <strong>{{ $units->total() }}</strong> satuan
    </div>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Nama Satuan</th>
                    <th>Status</th>
                    <th style="width: 130px; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($units as $i => $unit)
                <tr>
                    <td style="color:var(--text-muted);font-size:0.8rem;">{{ $units->firstItem()+$i }}</td>
                    <td><strong>{{ $unit->name }}</strong></td>
                    <td>
                        @if($unit->is_active)
                            <span class="status-badge status-paid">Aktif</span>
                        @else
                            <span class="status-badge status-cancelled">Nonaktif</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <div class="table-actions" style="justify-content: flex-end;">
                            <a class="btn-action" href="{{ route('container-units.edit', $unit) }}" id="btn-edit-unit-{{ $unit->id }}" title="Edit Satuan" data-tooltip="Edit" aria-label="Edit Satuan"><x-icon name="edit"/></a>
                            <form method="POST" action="{{ route('container-units.toggle',$unit) }}">
                                @csrf @method('PATCH')
                                <button class="btn-action {{ $unit->is_active ? 'btn-action-warning' : 'btn-action-success' }}" id="btn-toggle-unit-{{ $unit->id }}" title="{{ $unit->is_active?'Nonaktifkan':'Aktifkan' }}" data-tooltip="{{ $unit->is_active?'Nonaktifkan':'Aktifkan' }}" aria-label="{{ $unit->is_active?'Nonaktifkan':'Aktifkan' }}"><x-icon name="{{ $unit->is_active ? 'power' : 'check' }}"/></button>
                            </form>
                            <form method="POST" action="{{ route('container-units.destroy',$unit) }}" onsubmit="return confirm('Hapus satuan ini?')">
                                @csrf @method('DELETE')
                                <button class="btn-action btn-action-danger" id="btn-del-unit-{{ $unit->id }}" title="Hapus Satuan" data-tooltip="Hapus" aria-label="Hapus Satuan"><x-icon name="trash"/></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4">
                        <div class="empty-state">
                            <x-icon name="database"/>
                            <h3>Belum ada satuan</h3>
                            <p>Tambahkan satuan pada form di atas.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $units->links() }}</div>
</section>
@endsection