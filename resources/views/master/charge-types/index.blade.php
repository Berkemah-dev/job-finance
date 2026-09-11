@extends('layouts.app')
@section('title','Master Jenis Biaya')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">MASTER DATA</p><h1>Jenis Biaya (Charge Type)</h1><p>Kelola daftar jenis biaya / cost yang digunakan dalam operasional.</p></div>
</div>
<section class="panel">
    <div style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;margin-bottom:1rem;">
        <form class="filter-bar" method="GET" style="flex:1;min-width:260px;">
            <input name="search" value="{{ $search }}" placeholder="Cari nama biaya..." aria-label="Cari jenis biaya">
            <select name="status" style="padding:0.5rem 0.75rem;border:1px solid var(--border);border-radius:6px;background:var(--surface);color:var(--text);">
                <option value="all" {{ $status==='all'?'selected':'' }}>Semua Status</option>
                <option value="active" {{ $status==='active'?'selected':'' }}>Aktif</option>
                <option value="inactive" {{ $status==='inactive'?'selected':'' }}>Nonaktif</option>
            </select>
            <button class="button button-primary" id="btn-search-charge">Cari</button>
            <a class="text-link" href="{{ route('charge-types.index') }}">Reset</a>
        </form>
        <form method="POST" action="{{ route('charge-types.store') }}" style="display:flex;gap:0.5rem;align-items:center;">
            @csrf
            <input name="name" placeholder="Nama Jenis Biaya (mis: TRUCKING)" required maxlength="150" style="padding:0.5rem 0.75rem;border:1px solid var(--border);border-radius:6px;min-width:320px;background:var(--surface);color:var(--text);">
            <button class="button button-primary" id="btn-add-charge">+ Tambah</button>
        </form>
    </div>
    <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:0.75rem;">Menampilkan {{ $charges->total() }} jenis biaya</p>
    <div class="table-scroll">
        <table>
            <thead><tr><th>#</th><th>Nama Jenis Biaya</th><th>Status</th><th>Aksi</th></tr></thead>
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
                    <td style="display:flex;gap:0.75rem;align-items:center;">
                        <a class="text-link" href="{{ route('charge-types.edit', $charge) }}" id="btn-edit-charge-{{ $charge->id }}">Edit</a>
                        <form method="POST" action="{{ route('charge-types.toggle',$charge) }}">
                            @csrf @method('PATCH')
                            <button class="text-link" id="btn-toggle-charge-{{ $charge->id }}">{{ $charge->is_active?'Nonaktifkan':'Aktifkan' }}</button>
                        </form>
                        <form method="POST" action="{{ route('charge-types.destroy',$charge) }}" onsubmit="return confirm('Hapus jenis biaya ini?')">
                            @csrf @method('DELETE')
                            <button class="text-link" style="color:var(--danger)" id="btn-del-charge-{{ $charge->id }}">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4"><div class="empty-state"><h3>Belum ada jenis biaya</h3><p>Tambahkan jenis biaya di atas.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $charges->links() }}</div>
</section>
@endsection