@extends('layouts.app')
@section('title','Quotation')
@section('content')
<x-menu-banner
    tag="SALES & CUSTOMER"
    title="Quotation & Penawaran"
    description="Susun penawaran harga, pantau status persetujuan, dan konversi ke Job Order dengan rapi."
    :action-url="route('quotations.create')"
    action-label="Buat Quotation"
    action-icon="plus"
    icon="file"
    art-title="Penawaran akurat,"
    art-subtitle="margin terkontrol."
/>
<section class="panel">
    <form class="filter-bar" method="GET">
        <input name="search" value="{{ $search }}" placeholder="Cari nomor, judul, atau customer" aria-label="Cari quotation">
        <div class="date-filter-group">
            <x-icon name="calendar"/>
            <input name="date_from" type="date" value="{{ $dateFrom }}" aria-label="Dari tanggal" title="Dari tanggal">
            <span class="date-sep">→</span>
            <input name="date_to" type="date" value="{{ $dateTo }}" aria-label="Sampai tanggal" title="Sampai tanggal">
        </div>
        <select name="customer_id" aria-label="Customer">
            <option value="">Semua customer</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" @selected($customerId===$customer->id)>{{ $customer->name }}</option>
            @endforeach
        </select>
        <select name="sales_id" aria-label="Sales">
            <option value="">Semua sales</option>
            @foreach($sales as $user)
                <option value="{{ $user->id }}" @selected($salesId===$user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        <select name="service_type" aria-label="Layanan">
            <option value="">Semua layanan</option>
            @foreach($serviceTypes ?? \App\Models\ServiceType::options() as $key=>$label)
                <option value="{{ $key }}" @selected($serviceType===$key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" aria-label="Status quotation">
            <option value="">Semua status</option>
            @foreach(\App\Enums\QuotationStatus::cases() as $option)
                <option value="{{ $option->value }}" @selected($status?->value===$option->value)>{{ $option->label() }}</option>
            @endforeach
        </select>
        <button class="button button-primary">Cari</button>
        <a class="text-link" href="{{ route('quotations.index') }}">Reset</a>
    </form>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Nomor / Tanggal</th>
                    <th>Customer + Remarks</th>
                    <th>Layanan</th>
                    <th>POL</th>
                    <th>POD</th>
                    <th>Sales</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quotations as $quotation)
                    <tr>
                        <td>
                            <strong>{{ $quotation->number }}</strong><br>
                            <small class="muted-cell">{{ $quotation->quotation_date->format('d/m/Y') }}</small>
                        </td>
                        <td>
                            <strong>{{ $quotation->customer_snapshot['name'] ?? $quotation->customer?->name ?? '—' }}</strong>
                            @if($quotation->notes || $quotation->subject)
                                <br><small class="muted-cell" title="{{ $quotation->notes ?? $quotation->subject }}">{{ Str::limit($quotation->notes ?? $quotation->subject, 45) }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge-pill" style="font-weight: 600;">
                                {{ \App\Models\ServiceType::label($quotation->service_type) }}
                            </span>
                        </td>
                        <td>{{ $quotation->origin ?? '—' }}</td>
                        <td>{{ $quotation->destination ?? '—' }}</td>
                        <td>{{ $quotation->sales?->name ?? $quotation->creator?->name ?? '—' }}</td>
                        <td>
                            <span class="status-badge status-{{ $quotation->status->value }}">{{ $quotation->status->label() }}</span>
                            @if($quotation->status === \App\Enums\QuotationStatus::Approved && !$quotation->job)
                                <br><small class="subtle" style="color: #16a34a; font-weight: 600;">Siap dibuat Job</small>
                            @endif
                        </td>
                        <td>
                            <div class="table-actions">
                                <a class="btn-action btn-action-primary" href="{{ route('quotations.show', $quotation) }}" title="Detail Quotation" data-tooltip="Detail" aria-label="Detail Quotation">
                                    <x-icon name="eye"/>
                                </a>
                                @can('quotations.manage')
                                    <form method="POST" action="{{ route('quotations.duplicate', $quotation) }}" data-confirm="Buat salinan draft baru dari quotation {{ $quotation->number }}?">
                                        @csrf
                                        <button type="submit" class="btn-action" title="Duplikat Quote" data-tooltip="Duplikat" aria-label="Duplikat Quote">
                                            <x-icon name="copy"/>
                                        </button>
                                    </form>
                                @endcan
                                @can('convert', $quotation)
                                    @if(!$quotation->job)
                                        <form method="POST" action="{{ route('quotations.convert', $quotation) }}" data-confirm="Konversi quotation ini ke Job Order? Status job akan langsung Open.">
                                            @csrf
                                            <input type="hidden" name="lock_version" value="{{ $quotation->lock_version }}">
                                            <button class="btn-action btn-action-success" title="Buat Job" data-tooltip="Buat Job" aria-label="Buat Job">
                                                <x-icon name="arrow"/>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <x-icon name="file"/>
                                <h3>Belum ada quotation yang sesuai</h3>
                                <p>Buat penawaran pertama untuk customer Anda.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $quotations->links() }}</div>
</section>
@endsection
