@extends('layouts.app')
@section('title','HPP per Jenis Biaya')
@section('content')
<x-menu-banner tag="LAPORAN KEUANGAN" title="HPP per Jenis Biaya" description="Rincian HPP Trucking, Freight, dan biaya lain yang telah diakui saat Closing Job." icon="briefcase" art-title="Modal pekerjaan," art-subtitle="terukur per jenis."/>

<section class="panel report-filter-panel">
    <form class="filter-bar" method="GET">
        <label>Dari <input type="date" name="from" value="{{ $from }}" max="{{ $to }}"></label>
        <label>Sampai <input type="date" name="to" value="{{ $to }}" min="{{ $from }}" max="{{ today()->toDateString() }}"></label>
        <button class="button button-primary">Terapkan</button>
    </form>
</section>

<div class="report-grid report-grid-4">
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon amber"><x-icon name="briefcase"/></span><div><h2>Total HPP</h2><small>Modal final</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($totalCost) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon blue"><x-icon name="file"/></span><div><h2>Jenis Biaya</h2><small>Uraian yang digunakan</small></div></div></div><strong class="report-value">{{ $rows->count() }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon purple"><x-icon name="wallet"/></span><div><h2>PPh 23 Dipotong</h2><small>Rekonsiliasi pajak vendor</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($totalPph23) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon green"><x-icon name="briefcase"/></span><div><h2>Job Tercakup</h2><small>{{ $totalTransactions }} transaksi HPP</small></div></div></div><strong class="report-value">{{ $totalJobs }}</strong></section>
</div>

<section class="panel report-table-panel">
    <div class="panel-heading">
        <div>
            <h2>Rincian HPP</h2>
            <p>{{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} sampai {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}.</p>
        </div>
    </div>
    <p class="balance-detail-hint" style="margin-bottom: 16px;">
        <x-icon name="chart"/> Klik pada nama jenis biaya untuk melihat rincian transaksi per Job Order.
    </p>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Jenis biaya</th>
                    <th class="money">Transaksi</th>
                    <th class="money">Job</th>
                    <th class="money">Total modal / HPP</th>
                    <th class="money">PPh 23</th>
                    <th class="money">Nilai jual</th>
                    <th class="money">Margin estimasi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $index => $row)
                @php
                    $margin = (float) $row->total_price > 0 ? (((float) $row->total_price - (float) $row->total_cost) / (float) $row->total_price) * 100 : 0;
                    $detailId = 'hpp-detail-'.$index;
                @endphp
                <tr style="cursor: pointer;" onclick="toggleHppDetail('{{ $detailId }}')">
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button type="button" class="button button-secondary button-sm" style="padding: 2px 6px; font-size: 10px; line-height: 1;" onclick="event.stopPropagation(); toggleHppDetail('{{ $detailId }}');">
                                <span id="icon-{{ $detailId }}">▶</span>
                            </button>
                            <strong style="color: #0284c7;">{{ $row->description }}</strong>
                        </div>
                    </td>
                    <td class="money">{{ $row->transaction_count }}</td>
                    <td class="money">{{ $row->job_count }}</td>
                    <td class="money"><strong>Rp {{ \App\Support\Money::format($row->total_cost) }}</strong></td>
                    <td class="money">Rp {{ \App\Support\Money::format($row->total_pph23) }}</td>
                    <td class="money">Rp {{ \App\Support\Money::format($row->total_price) }}</td>
                    <td class="money">{{ \App\Support\Money::format($margin) }}%</td>
                </tr>
                <tr id="{{ $detailId }}" style="display: none; background: #f8fafc;">
                    <td colspan="7" style="padding: 12px 16px; border-bottom: 2px solid #e2e8f0;">
                        <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <h4 style="margin: 0 0 8px 0; font-size: 12.5px; font-weight: 700; color: #1e293b; display: flex; justify-content: space-between; align-items: center;">
                                <span>Rincian Biaya: <strong>{{ $row->description }}</strong></span>
                                <small style="color: #64748b;">{{ count($row->items) }} Transaksi</small>
                            </h4>
                            <div class="table-scroll">
                                <table style="font-size: 12px; margin: 0;">
                                    <thead>
                                        <tr style="background: #f1f5f9;">
                                            <th style="padding: 6px 10px;">Job Order</th>
                                            <th style="padding: 6px 10px;">Nomor Biaya</th>
                                            <th style="padding: 6px 10px;">Tanggal</th>
                                            <th style="padding: 6px 10px;">Penerima / Vendor</th>
                                            <th style="padding: 6px 10px;">Jumlah</th>
                                            <th style="padding: 6px 10px;" class="money">Modal (HPP)</th>
                                            <th style="padding: 6px 10px;" class="money">Nilai Jual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($row->items as $item)
                                            <tr>
                                                <td style="padding: 6px 10px;">
                                                    <a href="{{ route('jobs.show', $item['job_id']) }}" target="_blank" style="color: #0284c7; font-weight: 700; text-decoration: none;">
                                                        {{ $item['job_number'] }}
                                                    </a>
                                                </td>
                                                <td style="padding: 6px 10px;">{{ $item['cost_number'] }}</td>
                                                <td style="padding: 6px 10px;">{{ $item['cost_date'] }}</td>
                                                <td style="padding: 6px 10px;">{{ $item['payee'] }}</td>
                                                <td style="padding: 6px 10px;">{{ \App\Support\Money::format($item['quantity']) }} {{ $item['unit'] }}</td>
                                                <td style="padding: 6px 10px;" class="money"><strong>Rp {{ \App\Support\Money::format($item['total_cost']) }}</strong></td>
                                                <td style="padding: 6px 10px;" class="money">Rp {{ \App\Support\Money::format($item['total_price']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><x-icon name="briefcase"/><h3>Belum ada HPP Closing</h3><p>Laporan muncul setelah Job berhasil di-closing.</p></div></td></tr>
            @endforelse
            </tbody>
            <tfoot>
                <tr class="summary-total">
                    <td>Total</td>
                    <td class="money">{{ $totalTransactions }}</td>
                    <td class="money">{{ $totalJobs }}</td>
                    <td class="money">Rp {{ \App\Support\Money::format($totalCost) }}</td>
                    <td class="money">Rp {{ \App\Support\Money::format($totalPph23) }}</td>
                    <td class="money">Rp {{ \App\Support\Money::format($totalSales) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</section>

<script>
function toggleHppDetail(id) {
    const el = document.getElementById(id);
    const icon = document.getElementById('icon-' + id);
    if (!el) return;
    if (el.style.display === 'none') {
        el.style.display = 'table-row';
        if (icon) icon.textContent = '▼';
    } else {
        el.style.display = 'none';
        if (icon) icon.textContent = '▶';
    }
}
</script>
@endsection
