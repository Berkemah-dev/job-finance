@extends('layouts.app')
@section('title', 'List Payment')
@section('content')
<x-menu-banner tag="KEUANGAN" title="List Payment" description="Daftar CN, PR, DN, dan reimbursement beserta status pembayarannya." icon="wallet" art-title="Daftar pembayaran," art-subtitle="dalam satu halaman." />
<section class="panel">
    <form method="GET" class="filter-bar">
        <select name="category" aria-label="Jenis transaksi">
            <option value="">Semua jenis transaksi</option>
            @foreach($categories as $value => $label)
                <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" aria-label="Status pembayaran">
            <option value="">Semua status pembayaran</option>
            <option value="paid" @selected(request('status') === 'paid')>Paid</option>
            <option value="unpaid" @selected(request('status') === 'unpaid')>Unpaid (termasuk kurang bayar)</option>
            <option value="partial" @selected(request('status') === 'partial')>Kurang bayar</option>
        </select>
        <input name="number" value="{{ request('number') }}" placeholder="No. CN / PR / DN" aria-label="Nomor dokumen" maxlength="100">
        <input name="job_number" value="{{ request('job_number') }}" placeholder="No. Job" aria-label="Nomor Job" maxlength="100">
        <button class="button button-primary">Terapkan</button>
        <a class="text-link" href="{{ route('payments.list') }}">Reset</a>
    </form>
    <div class="table-scroll">
        <table>
            <thead><tr><th>No. Dokumen / Tanggal</th><th>Jenis</th><th>No. Job / Customer</th><th>Vendor / Penerima</th><th>Uraian</th><th class="money">Nilai (IDR)</th><th class="money">Sudah Dibayar (IDR)</th><th class="money">Sisa (IDR)</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($costs as $cost)
                    @php
                        $paid = \App\Support\Money::decimal($cost->list_paid_amount ?? 0);
                        $remaining = \App\Support\Money::decimal($cost->total_cost)->minus($paid);
                        $remaining = $remaining->isPositive() ? $remaining : \App\Support\Money::decimal(0);
                        $paymentLabel = $remaining->isPositive() && $paid->isPositive() ? 'KURANG BAYAR' : ($cost->paid_at && !$remaining->isPositive() ? 'PAID' : 'UNPAID');
                    @endphp
                    <tr>
                        <td><strong>{{ $cost->number }}</strong><br><small>{{ $cost->cost_date?->format('d/m/Y') }}</small></td>
                        <td>{{ $categories[$cost->cost_category ?: 'payment_request'] ?? $cost->cost_category }}</td>
                        <td>{{ $cost->job?->number ?? '—' }}<br><small>{{ $cost->job?->customer?->name ?? '—' }}</small></td>
                        <td>{{ $cost->vendor?->name ?: ($cost->payee ?: '—') }}</td>
                        <td>{{ $cost->description }}<br><small>{{ $cost->status === 'final' ? 'Final' : 'Draft' }}</small></td>
                        <td class="money">{{ \App\Support\Money::format($cost->total_cost) }}</td>
                        <td class="money">{{ \App\Support\Money::format((string) $paid) }}</td>
                        <td class="money">{{ \App\Support\Money::format((string) $remaining) }}</td>
                        <td><span class="status-badge {{ $paymentLabel === 'PAID' ? 'status-paid' : 'status-draft' }}">{{ $paymentLabel }}</span></td>
                        <td>@if($cost->job)<a class="button button-secondary button-sm" href="{{ route('jobs.costs.show', [$cost->job, $cost]) }}">Detail / Pembayaran</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="10"><div class="empty-state"><h3>Belum ada transaksi yang sesuai filter.</h3></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $costs->links() }}</div>
</section>
@endsection
