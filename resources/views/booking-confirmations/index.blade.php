@extends('layouts.app')
@section('title', 'Booking Confirmation')
@section('content')

<x-menu-banner
    tag="OPERASIONAL"
    title="Booking Confirmation (BC)"
    description="Konfirmasi alokasi ruang muatan (space booking) pengapalan untuk customer dan shipper."
    :action-url="route('booking-confirmations.create')"
    action-label="+ Buat Booking Confirmation"
    action-icon="plus"
    icon="file"
    art-title="Alokasi space,"
    art-subtitle="terkonfirmasi."
/>

<section class="panel">
    <form class="filter-bar" method="GET">
        <input name="search" value="{{ $search }}" placeholder="Cari nomor BC, customer, shipper, vessel, atau Job No" aria-label="Cari Booking Confirmation">
        <div class="date-filter-group">
            <x-icon name="calendar"/>
            <input name="date_from" type="date" value="{{ $dateFrom }}" aria-label="Dari tanggal" title="Dari tanggal">
            <span class="date-sep">→</span>
            <input name="date_to" type="date" value="{{ $dateTo }}" aria-label="Sampai tanggal" title="Sampai tanggal">
        </div>
        <select name="status" aria-label="Status">
            <option value="">Semua status</option>
            <option value="confirmed" @selected($status === 'confirmed')>Confirmed</option>
            <option value="draft" @selected($status === 'draft')>Draft</option>
            <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
        </select>
        <button class="button button-primary">Cari</button>
        <a class="text-link" href="{{ route('booking-confirmations.index') }}">Reset</a>
    </form>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Nomor BC / Tanggal</th>
                    <th>Customer / Ref</th>
                    <th>Job Order</th>
                    <th>Shipper</th>
                    <th>Carrier / Vessel</th>
                    <th>Rute (POL → POD)</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookingConfirmations as $bc)
                    <tr>
                        <td>
                            <strong>{{ $bc->number }}</strong><br>
                            <small>{{ $bc->booking_date->format('d/m/Y') }}</small>
                        </td>
                        <td>
                            <strong>{{ $bc->customer?->name ?? '—' }}</strong>
                            @if($bc->customer_ref)
                                <br><small class="muted-cell">Ref: {{ $bc->customer_ref }}</small>
                            @endif
                        </td>
                        <td>
                            @if($bc->job)
                                <a class="text-link" href="{{ route('jobs.show', $bc->job) }}">{{ $bc->job->number }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $bc->shipper_name ?? '—' }}</td>
                        <td>
                            <strong>{{ $bc->carrier_name ?? '—' }}</strong>
                            @if($bc->vessel_voyage)
                                <br><small>{{ $bc->vessel_voyage }}</small>
                            @endif
                        </td>
                        <td>
                            {{ $bc->pol ?? '—' }} → {{ $bc->pod ?? '—' }}
                            @if($bc->etd || $bc->eta)
                                <br><small class="muted-cell">ETD: {{ $bc->etd?->format('d/m/Y') ?? '—' }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="status-badge status-{{ $bc->status === 'confirmed' ? 'approved' : ($bc->status === 'draft' ? 'draft' : 'rejected') }}">
                                {{ ucfirst($bc->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="action-group">
                                <a class="text-link" href="{{ route('booking-confirmations.show', $bc) }}">Detail</a>
                                <a class="text-link" href="{{ route('booking-confirmations.preview', $bc) }}" target="_blank">PDF</a>
                                <a class="text-link" href="{{ route('booking-confirmations.edit', $bc) }}">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <x-icon name="file"/>
                                <h3>Belum ada Booking Confirmation</h3>
                                <p>Buat dokumen Booking Confirmation baru untuk mengonfirmasi pengapalan.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $bookingConfirmations->links() }}</div>
</section>

@endsection
