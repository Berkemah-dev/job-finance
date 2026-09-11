@extends('layouts.app')
@section('title','Invoice')
@section('content')
<div class="page-heading"><div><p class="eyebrow">KEUANGAN & PENAGIHAN</p><h1>Invoice</h1><p>Pantau tagihan dan pembayaran customer.</p></div><span class="date-chip"><x-icon name="calendar"/>{{ now()->locale('id')->translatedFormat('d F Y') }}</span></div>
<x-menu-banner
    tag="KEUANGAN & PENAGIHAN"
    title="Invoice & Tagihan Customer"
    description="Kelola penagihan penjualan, cetak invoice komersial, pantau jatuh tempo, dan ekspor Coretax."
    :action-url="route('invoices.coretax.index')"
    action-label="Ekspor Coretax"
    action-icon="arrow"
    icon="file"
    art-title="Penagihan lancar,"
    art-subtitle="cashflow terjaga."
/>
<section class="panel"><form class="filter-bar"><select name="status"><option value="">Semua status</option>@foreach(['issued'=>'Issued','partially_paid'=>'Partially Paid','paid'=>'Paid'] as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach</select><button class="button button-primary">Terapkan</button></form><div class="table-scroll"><table><thead><tr><th>Invoice</th><th>Customer</th><th>Total</th><th>Dibayar</th><th>Sisa</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($invoices as $invoice)<tr><td><strong>{{ $invoice->number }}</strong><br>{{ $invoice->invoice_date->format('d/m/Y') }}</td><td>{{ $invoice->customer_snapshot['name'] }}</td><td>@if($invoice->currency!=='IDR'){{ $invoice->currency }} {{ \App\Support\Money::format((string) $invoice->inInvoiceCurrency('total')) }}<br>@endif <span class="muted">Rp {{ \App\Support\Money::format($invoice->total) }}</span></td><td>Rp {{ \App\Support\Money::format($invoice->paid_amount) }}</td><td>Rp {{ \App\Support\Money::format($invoice->balance) }}</td><td><span class="status-badge status-{{ $invoice->status }}">{{ str_replace('_',' ',ucwords($invoice->status,'_')) }}</span></td><td><a class="text-link" href="{{ route('invoices.show',$invoice) }}">Lihat detail</a></td></tr>@empty<tr><td colspan="7">Belum ada invoice.</td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $invoices->links() }}</div></section>
@endsection
