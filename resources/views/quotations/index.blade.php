@extends('layouts.app')
@section('title','Quotation')
@section('content')
<x-menu-banner
    tag="SALES & CUSTOMER"
    title="Quotation & Penawaran"
    description="Susun penawaran harga, pantau status persetujuan, dan konversi ke Job Order dengan rapi."
    :action-url="route('quotations.create')"
    action-label="+ Buat Quotation"
    action-icon="plus"
    icon="file"
    art-title="Penawaran akurat,"
    art-subtitle="margin terkontrol."
/>
<section class="panel"><form class="filter-bar" method="GET">
<input name="search" value="{{ $search }}" placeholder="Cari nomor, judul, atau customer" aria-label="Cari quotation">
<div class="date-filter-group">
    <x-icon name="calendar"/>
    <input name="date_from" type="date" value="{{ $dateFrom }}" aria-label="Dari tanggal" title="Dari tanggal">
    <span class="date-sep">→</span>
    <input name="date_to" type="date" value="{{ $dateTo }}" aria-label="Sampai tanggal" title="Sampai tanggal">
</div>
<select name="customer_id" aria-label="Customer"><option value="">Semua customer</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected($customerId===$customer->id)>{{ $customer->name }}</option>@endforeach</select>
<select name="sales_id" aria-label="Sales"><option value="">Semua sales</option>@foreach($sales as $user)<option value="{{ $user->id }}" @selected($salesId===$user->id)>{{ $user->name }}</option>@endforeach</select>
<select name="service_type" aria-label="Layanan"><option value="">Semua layanan</option>@foreach(config('operations.service_types') as $key=>$label)<option value="{{ $key }}" @selected($serviceType===$key)>{{ $label }}</option>@endforeach</select>
<select name="status" aria-label="Status quotation"><option value="">Semua status</option>@foreach(\App\Enums\QuotationStatus::cases() as $option)<option value="{{ $option->value }}" @selected($status?->value===$option->value)>{{ $option->label() }}</option>@endforeach</select>
<button class="button button-primary">Cari</button><a class="text-link" href="{{ route('quotations.index') }}">Reset</a>
</form>
<div class="table-scroll"><table><thead><tr><th>Nomor / Tanggal</th><th>Customer / Penawaran</th><th>Layanan</th><th>Asal → Tujuan</th><th>Sales</th><th>Mata uang</th><th class="money">Total sebelum pajak</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($quotations as $quotation)<tr><td><strong>{{ $quotation->number }}</strong><br><small>{{ $quotation->quotation_date->format('d/m/Y') }}</small></td><td>{{ $quotation->customer_snapshot['name'] }}<br><small>{{ Str::limit($quotation->subject,45) }}</small></td><td>{{ config('operations.service_types.'.$quotation->service_type) ?? '—' }}</td><td>@if($quotation->origin || $quotation->destination){{ $quotation->origin ?? '—' }} → {{ $quotation->destination ?? '—' }}@else—@endif</td><td>{{ $quotation->sales?->name ?? $quotation->creator?->name ?? '—' }}</td><td>{{ $quotation->currency }}</td><td class="money">Rp {{ \App\Support\Money::format($quotation->subtotal) }}</td><td><span class="status-badge status-{{ $quotation->status->value }}">{{ $quotation->status->label() }}</span></td><td><div class="table-actions"><a class="btn-action btn-action-primary" href="{{ route('quotations.show',$quotation) }}">Detail</a></div></td></tr>@empty<tr><td colspan="9"><div class="empty-state"><x-icon name="file"/><h3>Belum ada quotation yang sesuai</h3><p>Buat penawaran pertama untuk customer Anda.</p></div></td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $quotations->links() }}</div></section>
@endsection