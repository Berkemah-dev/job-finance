@extends('layouts.app')
@section('title', 'Bill of Lading (B/L) — Export Sea')
@section('content')

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL — EXPORT SEA</p>
        <h1>Bill of Lading (B/L)</h1>
        <p>Daftar dokumen B/L untuk pengiriman export via laut.</p>
    </div>
    <a class="button button-primary" href="{{ route('bills-of-lading.create') }}">+ Buat B/L Baru</a>
</div>

<section class="panel" style="padding:16px 20px;margin-bottom:20px;">
    <form method="GET" action="{{ route('bills-of-lading.index') }}" class="filter-bar">
        <input type="text" name="search" value="{{ $search }}" placeholder="Cari no B/L, carrier, shipper..." style="flex:1;">
        <select name="status">
            <option value="">Semua Status</option>
            <option value="draft" @selected($status === 'draft')>Draft</option>
            <option value="issued" @selected($status === 'issued')>Issued</option>
            <option value="released" @selected($status === 'released')>Released</option>
            <option value="completed" @selected($status === 'completed')>Completed</option>
            <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
        </select>
        <input type="date" name="date_from" value="{{ $dateFrom }}" title="Dari Tanggal">
        <input type="date" name="date_to" value="{{ $dateTo }}" title="Sampai Tanggal">
        <button class="button button-primary" type="submit">Filter</button>
        @if($search || $status || $dateFrom || $dateTo)
            <a class="button button-secondary" href="{{ route('bills-of-lading.index') }}">Reset</a>
        @endif
    </form>
</section>

<section class="panel" style="overflow:hidden;">
    @if($bls->isEmpty())
        <div style="text-align:center;padding:60px 20px;color:#64748b;">
            <div style="font-size:40px;margin-bottom:12px;">🚢</div>
            <p style="font-size:16px;font-weight:600;">Belum ada B/L</p>
            <p>Klik tombol <strong>+ Buat B/L Baru</strong> untuk membuat Bill of Lading pertama.</p>
        </div>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>No. B/L</th>
                    <th>Tanggal</th>
                    <th>Tipe B/L</th>
                    <th>Carrier</th>
                    <th>Shipper</th>
                    <th>POL → POD</th>
                    <th>ETD</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bls as $bl)
                <tr>
                    <td><a class="text-link" href="{{ route('bills-of-lading.show', $bl) }}"><strong>{{ $bl->number }}</strong></a></td>
                    <td>{{ $bl->bl_date->format('d/m/Y') }}</td>
                    <td><span class="badge-pill">{{ strtoupper($bl->bl_type) }}</span></td>
                    <td>{{ Str::limit($bl->carrier, 20) ?: '—' }}</td>
                    <td>{{ Str::limit($bl->shipper_name, 22) ?: '—' }}</td>
                    <td style="font-size:12px;">{{ $bl->pol ?: '—' }} → {{ $bl->pod ?: '—' }}</td>
                    <td>{{ $bl->etd?->format('d/m/Y') ?: '—' }}</td>
                    <td><span class="status-badge status-{{ in_array($bl->status, ['issued','released','completed']) ? 'approved' : ($bl->status === 'cancelled' ? 'rejected' : 'draft') }}">{{ ucfirst($bl->status) }}</span></td>
                    <td>
                        <a class="button button-secondary" href="{{ route('bills-of-lading.edit', $bl) }}" style="padding:3px 10px;font-size:12px;">Edit</a>
                        <form method="POST" action="{{ route('bills-of-lading.destroy', $bl) }}" style="display:inline;" data-confirm="Hapus B/L {{ $bl->number }}?">
                            @csrf @method('DELETE')
                            <button class="button button-danger" style="padding:3px 10px;font-size:12px;background:#ef4444;border-color:#ef4444;">Hapus</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:16px 20px;">{{ $bls->links() }}</div>
    @endif
</section>

@endsection
