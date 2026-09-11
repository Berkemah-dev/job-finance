@extends('layouts.app')
@section('title','Master Satuan')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">MASTER DATA</p><h1>Satuan (Unit)</h1><p>Kelola daftar satuan yang digunakan dalam penawaran harga dan operasional.</p></div>
</div>
<section class="panel">
    <div style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;margin-bottom:1rem;">
        <form class="filter-bar" method="GET" style="flex:1;min-width:260px;">
            <input name="search" value="{{ $search }}" placeholder="Cari nama satuan..." aria-label="Cari satuan">
            <button class="button button-primary" id="btn-search-unit">Cari</button>
            <a class="text-link" href="{{ route('container-units.index') }}">Reset</a>
        </form>
        <form method="POST" action="{{ route('container-units.store') }}" style="display:flex;gap:0.5rem;align-items:center;">
            @csrf
            <input name="name" placeholder="Nama Satuan (mis: 40GP)" required maxlength="50" style="padding:0.5rem 0.75rem;border:1px solid var(--border);border-radius:6px;min-width:260px;background:var(--surface);color:var(--text);">
            <button class="button button-primary" id="btn-add-unit">+ Tambah</button>
        </form>
    </div>
    <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:0.75rem;">Menampilkan {{ $units->total() }} satuan</p>
    <div class="table-scroll">
        <table>
            <thead><tr><th>#</th><th>Nama Satuan</th><th>Status</th><th>Aksi</th></tr></thead>
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
                    <td style="display:flex;gap:0.75rem;align-items:center;">
                        <a class="text-link" href="{{ route('container-units.edit', $unit) }}" id="btn-edit-unit-{{ $unit->id }}">Edit</a>
                        <form method="POST" action="{{ route('container-units.toggle',$unit) }}">
                            @csrf @method('PATCH')
                            <button class="text-link" id="btn-toggle-unit-{{ $unit->id }}">{{ $unit->is_active?'Nonaktifkan':'Aktifkan' }}</button>
                        </form>
                        <form method="POST" action="{{ route('container-units.destroy',$unit) }}" onsubmit="return confirm('Hapus satuan ini?')">
                            @csrf @method('DELETE')
                            <button class="text-link" style="color:var(--danger)" id="btn-del-unit-{{ $unit->id }}">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4"><div class="empty-state"><h3>Belum ada satuan</h3><p>Tambahkan satuan di atas.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $units->links() }}</div>
</section>
@endsection