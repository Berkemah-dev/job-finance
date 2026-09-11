@extends('layouts.app')
@section('title', 'Shipping Instruction')
@section('content')

<x-menu-banner
    tag="OPERASIONAL"
    title="Shipping Instruction (SI)"
    description="Instruksi pengapalan resmi kepada Shipping Lines / Carrier untuk penerbitan Bill of Lading (B/L)."
    :action-url="route('shipping-instructions.create')"
    action-label="+ Buat Shipping Instruction"
    action-icon="plus"
    icon="file"
    art-title="Instruksi kapal,"
    art-subtitle="terbit cepat."
/>

<section class="panel">
    <form class="filter-bar" method="GET">
        <input name="search" value="{{ $search }}" placeholder="Cari nomor SI, carrier, shipper, consignee, kapal, atau Job No" aria-label="Cari Shipping Instruction">
        <div class="date-filter-group">
            <x-icon name="calendar"/>
            <input name="date_from" type="date" value="{{ $dateFrom }}" aria-label="Dari tanggal" title="Dari tanggal">
            <span class="date-sep">→</span>
            <input name="date_to" type="date" value="{{ $dateTo }}" aria-label="Sampai tanggal" title="Sampai tanggal">
        </div>
        <select name="status" aria-label="Status">
            <option value="">Semua status</option>
            <option value="submitted" @selected($status === 'submitted')>Submitted</option>
            <option value="completed" @selected($status === 'completed')>Completed</option>
            <option value="draft" @selected($status === 'draft')>Draft</option>
            <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
        </select>
        <button class="button button-primary">Cari</button>
        <a class="text-link" href="{{ route('shipping-instructions.index') }}">Reset</a>
    </form>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Nomor SI / Tanggal</th>
                    <th>Job Order / Customer</th>
                    <th>Carrier (To:)</th>
                    <th>Shipper & Consignee</th>
                    <th>Vessel & Voyage</th>
                    <th>Rute (POL → POD)</th>
                    <th>Term</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shippingInstructions as $si)
                    <tr>
                        <td>
                            <strong>{{ $si->number }}</strong><br>
                            <small>{{ $si->si_date->format('d/m/Y') }}</small>
                        </td>
                        <td>
                            @if($si->job)
                                <a class="text-link" href="{{ route('jobs.show', $si->job) }}">{{ $si->job->number }}</a>
                            @else
                                —
                            @endif
                            <br><small class="muted-cell">{{ $si->customer?->name ?? '—' }}</small>
                        </td>
                        <td>
                            <strong>{{ $si->to_carrier }}</strong>
                            @if($si->carrier_attn)
                                <br><small class="muted-cell">Attn: {{ $si->carrier_attn }}</small>
                            @endif
                        </td>
                        <td>
                            <strong>{{ Str::limit($si->shipper_name, 25) }}</strong><br>
                            <small class="muted-cell">Cnee: {{ Str::limit($si->consignee_name, 25) }}</small>
                        </td>
                        <td>
                            {{ $si->vessel_voyage ?? '—' }}
                            @if($si->etd)
                                <br><small class="muted-cell">ETD: {{ $si->etd->format('d/m/Y') }}</small>
                            @endif
                        </td>
                        <td>{{ $si->pol }} → {{ $si->pod }}</td>
                        <td>
                            <span class="badge-pill" style="font-weight: 700;">{{ $si->shipment_term }}</span>
                        </td>
                        <td>
                            <span class="status-badge status-{{ $si->status === 'submitted' || $si->status === 'completed' ? 'approved' : ($si->status === 'draft' ? 'draft' : 'rejected') }}">
                                {{ ucfirst($si->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a class="btn-action btn-action-primary" href="{{ route('shipping-instructions.show', $si) }}" title="Detail Shipping Instruction" data-tooltip="Detail" aria-label="Detail Shipping Instruction"><x-icon name="eye"/></a>
                                <a class="btn-action btn-action-purple" href="{{ route('shipping-instructions.preview', $si) }}" target="_blank" title="Cetak PDF SI" data-tooltip="PDF" aria-label="Cetak PDF SI"><x-icon name="printer"/></a>
                                <a class="btn-action" href="{{ route('shipping-instructions.edit', $si) }}" title="Edit Shipping Instruction" data-tooltip="Edit" aria-label="Edit Shipping Instruction"><x-icon name="edit"/></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <x-icon name="file"/>
                                <h3>Belum ada Shipping Instruction</h3>
                                <p>Terbitkan dokumen Shipping Instruction kepada Shipping Line / Carrier.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $shippingInstructions->links() }}</div>
</section>

@endsection
