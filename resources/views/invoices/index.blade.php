@extends('layouts.app')
@section('title','Invoice')
@section('content')
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

<section class="panel">
    <form class="filter-bar">
        <select name="status">
            <option value="">Semua status</option>
            @foreach(['issued'=>'Issued','partially_paid'=>'Partially Paid','paid'=>'Paid'] as $v=>$l)
                <option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>
            @endforeach
        </select>
        <button class="button button-primary">Terapkan</button>
    </form>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Dibayar</th>
                    <th>Sisa</th>
                    <th>Status Bayar</th>
                    <th>Pengiriman Fisik</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td><strong>{{ $invoice->number }}</strong><br><small class="muted-cell">{{ $invoice->invoice_date->format('d/m/Y') }}</small></td>
                        <td>{{ $invoice->customer_snapshot['name'] }}</td>
                        <td>
                            @if($invoice->currency!=='IDR')
                                <strong>{{ $invoice->currency }} {{ \App\Support\Money::format((string) $invoice->inInvoiceCurrency('total')) }}</strong><br>
                            @endif
                            <span class="muted-cell">Rp {{ \App\Support\Money::format($invoice->total) }}</span>
                        </td>
                        <td>Rp {{ \App\Support\Money::format($invoice->paid_amount) }}</td>
                        <td>Rp {{ \App\Support\Money::format($invoice->balance) }}</td>
                        <td><span class="status-badge status-{{ $invoice->status }}">{{ str_replace('_',' ',ucwords($invoice->status,'_')) }}</span></td>
                        <td>
                            @php
                                $dStatus = $invoice->delivery_status ?? 'not_sent';
                                $dLabels = ['not_sent' => 'Belum Dikirim', 'sent' => 'Terkirim', 'received' => 'Diterima'];
                                $dColors = ['not_sent' => '#f1f5f9; color:#64748b', 'sent' => '#e0f2fe; color:#0369a1', 'received' => '#dcfce7; color:#15803d'];
                            @endphp
                            <span class="status-badge" style="background: {{ $dColors[$dStatus] ?? '#f1f5f9' }};">
                                {{ $dLabels[$dStatus] ?? ucfirst($dStatus) }}
                            </span>
                            @if($invoice->tracking_number)
                                <br><small class="muted-cell">Resi: {{ $invoice->tracking_number }}</small>
                            @endif
                        </td>
                        <td>
                            <div class="table-actions">
                                <a class="btn-action btn-action-primary" href="{{ route('invoices.show', $invoice) }}" title="Detail Invoice" data-tooltip="Detail" aria-label="Detail Invoice"><x-icon name="eye"/></a>
                                <a class="btn-action btn-action-purple" href="{{ route('invoices.preview', $invoice) }}" target="_blank" title="Cetak PDF Invoice" data-tooltip="PDF" aria-label="Cetak PDF Invoice"><x-icon name="printer"/></a>
                                @if($invoice->balance > 0)
                                <a class="btn-action btn-action-success" href="{{ route('payments.create', $invoice) }}" title="Catat Pembayaran" data-tooltip="Bayar" aria-label="Catat Pembayaran"><x-icon name="wallet"/></a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">Belum ada invoice.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $invoices->links() }}</div>
</section>
@endsection
