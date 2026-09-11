@extends('layouts.app')
@section('title','Pembayaran')
@section('content')
<div class="page-heading"><div><p class="eyebrow">PENERIMAAN KAS</p><h1>Pembayaran</h1><p>Pilih invoice untuk mencatat pembayaran dan memperbarui piutang.</p></div><span class="date-chip"><x-icon name="calendar"/>{{ now()->locale('id')->translatedFormat('d F Y') }}</span></div>
<x-menu-banner
    tag="PENERIMAAN KAS"
    title="Pembayaran & Pelunasan Piutang"
    description="Catat penerimaan kas & bank dari customer dan pantau sisa piutang dagang secara real-time."
    icon="wallet"
    art-title="Penerimaan dana,"
    art-subtitle="tercatat akurat."
/>
<section class="panel"><div class="panel-heading"><div><h2>Invoice belum lunas</h2><p>Diurutkan dari jatuh tempo terdekat.</p></div><span class="count-badge">{{ $invoices->total() }} tagihan</span></div><div class="table-scroll"><table><thead><tr><th>Invoice</th><th>Customer</th><th>Jatuh tempo</th><th>Status</th><th class="money">Sisa tagihan</th><th></th></tr></thead><tbody>@forelse($invoices as $invoice)<tr><td><a class="text-link" href="{{ route('invoices.show',$invoice) }}">{{ $invoice->number }}</a></td><td>{{ $invoice->customer_snapshot['name'] }}</td><td>{{ $invoice->due_date->format('d/m/Y') }}</td><td><span class="status-badge status-{{ $invoice->status }}">{{ str_replace('_',' ',ucwords($invoice->status,'_')) }}</span></td><td class="money">Rp {{ \App\Support\Money::format($invoice->balance) }}</td><td><a class="button button-secondary button-small" href="{{ route('payments.create',$invoice) }}">Catat</a></td></tr>@empty<tr><td colspan="6">Semua invoice sudah lunas.</td></tr>@endforelse</tbody></table></div>{{ $invoices->links() }}</section>
@endsection
