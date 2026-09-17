@extends('layouts.app')
@section('title', 'AWB — Air Waybill (Export Air)')
@section('content')

<div class="page-heading">
    <div>
        <p class="eyebrow">CUSTOMER SERVICE / OPERASIONAL — EXPORT AIR</p>
        <h1>Air Waybill (AWB)</h1>
        <p>Daftar dokumen pengapalan udara untuk export via maskapai penerbangan.</p>
    </div>
    <a class="button button-primary" href="{{ route('awbs.create') }}">+ Buat AWB Baru</a>
</div>

{{-- FILTER BAR --}}
<section class="panel" style="padding: 16px 20px; margin-bottom: 20px;">
    <form method="GET" action="{{ route('awbs.index') }}" class="filter-bar">
        <input type="text" name="search" value="{{ $search }}" placeholder="Cari no AWB, airline, shipper..." style="flex:1;">
        <select name="status">
            <option value="">Semua Status</option>
            <option value="draft" @selected($status === 'draft')>Draft</option>
            <option value="issued" @selected($status === 'issued')>Issued</option>
            <option value="completed" @selected($status === 'completed')>Completed</option>
            <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
        </select>
        <input type="date" name="date_from" value="{{ $dateFrom }}" title="Dari Tanggal">
        <input type="date" name="date_to" value="{{ $dateTo }}" title="Sampai Tanggal">
        <button class="button button-primary" type="submit">Filter</button>
        @if($search || $status || $dateFrom || $dateTo)
            <a class="button button-secondary" href="{{ route('awbs.index') }}">Reset</a>
        @endif
    </form>
</section>

<section class="panel" style="overflow:hidden;">
    @if($awbs->isEmpty())
        <div style="text-align:center;padding:60px 20px;color:#64748b;">
            <div style="font-size:40px;margin-bottom:12px;">✈️</div>
            <p style="font-size:16px;font-weight:600;">Belum ada AWB</p>
            <p>Klik tombol <strong>+ Buat AWB Baru</strong> untuk membuat Air Waybill pertama.</p>
        </div>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>No. AWB</th>
                    <th>Tanggal</th>
                    <th>Airline</th>
                    <th>Shipper</th>
                    <th>Consignee</th>
                    <th>From → To</th>
                    <th>ETD</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($awbs as $awb)
                <tr>
                    <td><a class="text-link" href="{{ route('awbs.show', $awb) }}"><strong>{{ $awb->number }}</strong></a></td>
                    <td>{{ $awb->awb_date->format('d/m/Y') }}</td>
                    <td>{{ $awb->airline ?: '—' }}@if($awb->airline_code) <small class="badge-pill">{{ $awb->airline_code }}</small>@endif</td>
                    <td>{{ Str::limit($awb->shipper_name, 25) ?: '—' }}</td>
                    <td>{{ Str::limit($awb->consignee_name, 25) ?: '—' }}</td>
                    <td style="font-size:12px;">{{ $awb->airport_of_departure ?: '—' }} → {{ $awb->airport_of_destination ?: '—' }}</td>
                    <td>{{ $awb->etd?->format('d/m/Y') ?: '—' }}</td>
                    <td><span class="status-badge status-{{ in_array($awb->status, ['issued','completed']) ? 'approved' : ($awb->status === 'cancelled' ? 'rejected' : 'draft') }}">{{ ucfirst($awb->status) }}</span></td>
                    <td>
                        <a class="button button-secondary" href="{{ route('awbs.edit', $awb) }}" style="padding:3px 10px;font-size:12px;">Edit</a>
                        <form method="POST" action="{{ route('awbs.destroy', $awb) }}" style="display:inline;" data-confirm="Hapus AWB {{ $awb->number }}?">
                            @csrf @method('DELETE')
                            <button class="button button-danger" style="padding:3px 10px;font-size:12px;background:#ef4444;border-color:#ef4444;">Hapus</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:16px 20px;">{{ $awbs->links() }}</div>
    @endif
</section>

@endsection
