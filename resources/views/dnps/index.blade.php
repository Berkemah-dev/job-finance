@extends('layouts.app')
@section('title', 'Deklarasi Nilai Pabean (DNP)')
@section('content')

<div class="page-heading">
    <div>
        <p class="eyebrow">KEPABEANAN IMPORT</p>
        <h1>Deklarasi Nilai Pabean (DNP)</h1>
        <p>Kelola dokumen deklarasi nilai pabean untuk pengiriman Import Sea dan Import Air.</p>
    </div>
    <a class="button button-primary" href="{{ route('dnps.create') }}">+ Buat DNP</a>
</div>

<section class="panel">
    <form class="filter-bar" method="GET" action="{{ route('dnps.index') }}">
        <input type="search" name="search" value="{{ $search }}" placeholder="Cari nomor DNP, pembeli, penjual, job..." aria-label="Cari DNP">
        <select name="status" aria-label="Status DNP">
            <option value="">Semua Status</option>
            <option value="draft" @selected($status === 'draft')>Draft</option>
            <option value="submitted" @selected($status === 'submitted')>Submitted</option>
            <option value="approved" @selected($status === 'approved')>Approved</option>
            <option value="completed" @selected($status === 'completed')>Completed</option>
            <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
        </select>
        <input type="date" name="date_from" value="{{ $dateFrom }}" aria-label="Dari Tanggal">
        <input type="date" name="date_to" value="{{ $dateTo }}" aria-label="Sampai Tanggal">
        <button class="button button-primary" type="submit">Filter</button>
        <a class="text-link" href="{{ route('dnps.index') }}">Reset</a>
    </form>

    @if($dnps->isNotEmpty())
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Nomor DNP</th>
                        <th>Tanggal</th>
                        <th>#Job No</th>
                        <th>Consignee (Pembeli)</th>
                        <th>Shipper (Penjual)</th>
                        <th>Currency</th>
                        <th>Harga Invoice</th>
                        <th>Biaya Angkut</th>
                        <th>Total CIF</th>
                        <th>Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dnps as $dnp)
                        <tr>
                            <td><strong>{{ $dnp->number }}</strong></td>
                            <td>{{ $dnp->dnp_date?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                @if($dnp->job)
                                    <a class="text-link" href="{{ route('jobs.show', $dnp->job) }}">{{ $dnp->job->number }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $dnp->consignee_name ?: '—' }}</td>
                            <td>{{ $dnp->shipper_name ?: '—' }}</td>
                            <td><span class="badge-pill">{{ $dnp->currency }}</span></td>
                            <td>{{ number_format((float)$dnp->invoice_value, 2) }}</td>
                            <td>{{ number_format((float)$dnp->freight, 2) }}</td>
                            <td><strong>{{ number_format((float)$dnp->total_value, 2) }}</strong></td>
                            <td>
                                <span class="status-badge status-{{ in_array($dnp->status, ['approved', 'completed']) ? 'approved' : ($dnp->status === 'cancelled' ? 'rejected' : 'draft') }}">
                                    {{ ucfirst($dnp->status) }}
                                </span>
                            </td>
                            <td>
                                <div style="display:flex; gap:6px; justify-content:center;">
                                    <a class="button button-secondary button-sm" href="{{ route('dnps.show', $dnp) }}">Detail</a>
                                    <a class="button button-secondary button-sm" href="{{ route('dnps.edit', $dnp) }}">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $dnps->links() }}</div>
    @else
        <div class="empty-state">
            <x-icon name="file"/>
            <h3>Belum ada Deklarasi Nilai Pabean</h3>
            <p>Klik tombol di bawah untuk membuat dokumen DNP pertama.</p>
            <a class="button button-primary" href="{{ route('dnps.create') }}">+ Buat DNP</a>
        </div>
    @endif
</section>

@endsection
