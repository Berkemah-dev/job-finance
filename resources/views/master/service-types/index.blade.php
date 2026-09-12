@extends('layouts.app')
@section('title','Master Service')
@section('content')
<x-menu-banner
    tag="MASTER DATA"
    title="Service"
    description="Kelola daftar layanan utama untuk quotation, job order, pricing, dan laporan."
    icon="briefcase"
    art-title="Service,"
    art-subtitle="terpusat."
/>

<section class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;padding:18px 20px;border-bottom:1px solid #edf1f7;background:#fafcff;">
        <form class="filter-bar" method="GET" style="padding:0;border:none;flex:1;min-width:280px;gap:10px;">
            <input name="search" value="{{ $search }}" placeholder="Cari kode atau nama service..." aria-label="Cari service">
            <select name="status" aria-label="Status service">
                <option value="all" @selected($status === 'all')>Semua status</option>
                <option value="active" @selected($status === 'active')>Aktif</option>
                <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
            </select>
            <button class="button button-primary">Cari</button>
            <a class="text-link" href="{{ route('service-types.index') }}">Reset</a>
        </form>

        <form method="POST" action="{{ route('service-types.store') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            @csrf
            <input name="code" placeholder="Kode: exp_sea" required maxlength="40" style="padding:10px 12px;border:1px solid #dce4ef;border-radius:8px;font-size:12px;min-width:140px;background:#fff;color:#354967;">
            <input name="name" placeholder="Nama: EXPORT SEA" required maxlength="100" style="padding:10px 12px;border:1px solid #dce4ef;border-radius:8px;font-size:12px;min-width:220px;background:#fff;color:#354967;">
            <input name="sort_order" type="number" min="0" max="9999" placeholder="Urutan" style="padding:10px 12px;border:1px solid #dce4ef;border-radius:8px;font-size:12px;width:90px;background:#fff;color:#354967;">
            <button class="button button-primary" style="white-space:nowrap;">+ Tambah Service</button>
        </form>
    </div>

    <div class="filter-count" style="padding:12px 20px 0;">
        Menampilkan <strong>{{ $services->total() }}</strong> service
    </div>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th style="width:50px;">#</th>
                    <th>Kode</th>
                    <th>Nama Service</th>
                    <th>Urutan</th>
                    <th>Status</th>
                    <th style="width:130px;text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($services as $i => $service)
                    <tr>
                        <td style="color:var(--text-muted);font-size:.8rem;">{{ $services->firstItem() + $i }}</td>
                        <td><span class="badge-pill">{{ $service->code }}</span></td>
                        <td><strong>{{ $service->name }}</strong></td>
                        <td>{{ $service->sort_order }}</td>
                        <td>
                            @if($service->is_active)
                                <span class="status-badge status-paid">Aktif</span>
                            @else
                                <span class="status-badge status-cancelled">Nonaktif</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div class="table-actions" style="justify-content:flex-end;">
                                <a class="btn-action" href="{{ route('service-types.edit', $service) }}" title="Edit Service" data-tooltip="Edit" aria-label="Edit Service"><x-icon name="edit"/></a>
                                <form method="POST" action="{{ route('service-types.toggle', $service) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn-action {{ $service->is_active ? 'btn-action-warning' : 'btn-action-success' }}" title="{{ $service->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" data-tooltip="{{ $service->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $service->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"><x-icon name="{{ $service->is_active ? 'power' : 'check' }}"/></button>
                                </form>
                                <form method="POST" action="{{ route('service-types.destroy', $service) }}" onsubmit="return confirm('Hapus service ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-action btn-action-danger" title="Hapus Service" data-tooltip="Hapus" aria-label="Hapus Service"><x-icon name="trash"/></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <x-icon name="briefcase"/>
                                <h3>Belum ada service</h3>
                                <p>Tambahkan service pada form di atas.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $services->links() }}</div>
</section>
@endsection
