@extends('layouts.app')
@section('title','Laba Rugi')
@section('content')
<x-menu-banner
    tag="PT RADIX INTERNATIONAL LOGISTICS"
    title="Laporan Laba Rugi (Income Statement)"
    description="Untuk Tahun Yang Berakhir {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}"
    icon="chart"
    art-title="Kinerja laba,"
    art-subtitle="tumbuh positif."
/>

<section class="panel report-filter-panel">
    <form class="filter-bar" method="GET">
        <div class="date-filter-group">
            <x-icon name="calendar"/>
            <input type="date" name="from" value="{{ $from }}" aria-label="Dari tanggal" title="Dari tanggal">
            <span class="date-sep">→</span>
            <input type="date" name="to" value="{{ $to }}" aria-label="Sampai tanggal" title="Sampai tanggal">
        </div>
        <button class="button button-primary">Terapkan</button>
    </form>
</section>

@php
    $revenueValue = abs((float) ($totalPendapatan ?? $revenue));
    $cogsValue = abs((float) ($totalHpp ?? $cogs));
    $expenseValue = abs((float) ($totalBiayaOperasional ?? $expense));
    $grossValue = abs((float) ($labaKotor ?? $gross));
    $netValue = abs((float) ($labaBersihSesudahPajak ?? $net));
    $maxValue = max(1, $revenueValue, $cogsValue, $expenseValue, $grossValue, $netValue);
    $money = fn ($amount) => 'Rp '.\App\Support\Money::format($amount);
    $ledgerUrl = fn ($account) => !empty($account->id) ? route('reports.ledger', ['account_id' => $account->id, 'from' => $from, 'to' => $to]) : '#';
    $reportYear = \Carbon\Carbon::parse($to)->format('Y');
@endphp

<div class="report-grid report-grid-4">
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon green"><x-icon name="wallet"/></span><div><h2>Total Pendapatan</h2><small>Closed + Unclosed</small></div></div></div><strong class="report-value">{{ $money($totalPendapatan ?? $revenue) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon amber"><x-icon name="briefcase"/></span><div><h2>Total HPP</h2><small>Closed + Unclosed</small></div></div></div><strong class="report-value">{{ $money($totalHpp ?? $cogs) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon blue"><x-icon name="chart"/></span><div><h2>Laba / Rugi Kotor</h2><small>Margin {{ number_format((float)($grossMarginPct ?? 0), 2, ',', '.') }}%</small></div></div></div><strong class="report-value {{ (float)($labaKotor ?? $gross) >= 0 ? 'positive' : 'negative' }}">{{ $money($labaKotor ?? $gross) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon purple"><x-icon name="check"/></span><div><h2>Laba Bersih</h2><small>Sesudah Pajak</small></div></div></div><strong class="report-value {{ (float)($labaBersihSesudahPajak ?? $net) >= 0 ? 'positive' : 'negative' }}">{{ $money($labaBersihSesudahPajak ?? $net) }}</strong></section>
</div>

<section class="panel report-chart-card report-section">
    <div class="report-chart-head"><div><h2>Ringkasan Laba Rugi</h2><p>Komposisi pendapatan, HPP, biaya operasional, dan laba bersih.</p></div></div>
    <div class="report-bars">
        <div class="report-bar-row"><span>Total Pendapatan</span><div class="report-bar-track"><span class="report-bar-fill green" style="--bar: {{ round(($revenueValue / $maxValue) * 100, 2) }}%"></span></div><strong class="report-bar-value">{{ $money($totalPendapatan ?? $revenue) }}</strong></div>
        <div class="report-bar-row"><span>Total HPP</span><div class="report-bar-track"><span class="report-bar-fill amber" style="--bar: {{ round(($cogsValue / $maxValue) * 100, 2) }}%"></span></div><strong class="report-bar-value">{{ $money($totalHpp ?? $cogs) }}</strong></div>
        <div class="report-bar-row"><span>Biaya Operasional</span><div class="report-bar-track"><span class="report-bar-fill" style="--bar: {{ round(($expenseValue / $maxValue) * 100, 2) }}%"></span></div><strong class="report-bar-value">{{ $money($totalBiayaOperasional ?? $expense) }}</strong></div>
        <div class="report-bar-row"><span>Laba Bersih</span><div class="report-bar-track"><span class="report-bar-fill blue" style="--bar: {{ round(($netValue / $maxValue) * 100, 2) }}%"></span></div><strong class="report-bar-value">{{ $money($labaBersihSesudahPajak ?? $net) }}</strong></div>
    </div>
</section>

<section class="panel report-table-panel" style="margin-top: 24px;">
    <div class="panel-heading" style="margin-bottom: 16px;">
        <div>
            <h2>PT Radix International Logistics — Laporan Laba Rugi</h2>
            <span class="subtle">Untuk Tahun Yang Berakhir {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }} ({{ $from }} s/d {{ $to }})</span>
        </div>
    </div>
    <p class="balance-detail-hint" style="margin-bottom: 16px;"><x-icon name="chart"/> Klik kode atau nama akun untuk melihat mutasi jurnal dan saldo berjalan di Buku Besar.</p>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">ACC NO#</th>
                    <th style="width: 50%;">URAIAN AKUN</th>
                    <th class="money" style="width: 20%;">RINCIAN</th>
                    <th class="money" style="width: 20%;">TOTAL / SUBTOTAL</th>
                </tr>
            </thead>
            <tbody>
                {{-- PENDAPATAN CLOSED --}}
                <tr style="background: #f8fafc;">
                    <td colspan="4" style="font-weight: 800; color: #0f172a;">Pendapatan Closed</td>
                </tr>
                @foreach($pendapatanClosed ?? [] as $acc)
                    <tr>
                        <td><strong>{{ $acc->acc_no }}</strong></td>
                        <td><a href="{{ $ledgerUrl($acc) }}" style="color: #0f172a; font-weight: 600; text-decoration: none;">{{ $acc->name }}</a></td>
                        <td class="money">{{ $money($acc->balance) }}</td>
                        <td class="money"></td>
                    </tr>
                @endforeach
                <tr style="background: #f1f5f9; font-weight: 700;">
                    <td></td>
                    <td>Total Pendapatan</td>
                    <td></td>
                    <td class="money">{{ $money($totalPendapatanClosed ?? 0) }}</td>
                </tr>

                {{-- PENDAPATAN JOB UNCLOSED --}}
                <tr style="background: #f8fafc;">
                    <td colspan="4" style="font-weight: 800; color: #0f172a;">Pendapatan Job Unclosed</td>
                </tr>
                @foreach($pendapatanUnclosed ?? [] as $acc)
                    <tr>
                        <td><strong>{{ $acc->acc_no }}</strong></td>
                        <td><a href="{{ $ledgerUrl($acc) }}" style="color: #0f172a; font-weight: 600; text-decoration: none;">{{ $acc->name }}</a></td>
                        <td class="money">{{ $money($acc->balance) }}</td>
                        <td class="money"></td>
                    </tr>
                @endforeach
                <tr style="background: #f1f5f9; font-weight: 700;">
                    <td></td>
                    <td>Total Pendapatan Unclosed</td>
                    <td></td>
                    <td class="money">{{ $money($totalPendapatanUnclosed ?? 0) }}</td>
                </tr>

                {{-- TOTAL PENDAPATAN --}}
                <tr style="background: #e2e8f0; font-weight: 800; color: #0f172a;">
                    <td></td>
                    <td>TOTAL PENDAPATAN</td>
                    <td></td>
                    <td class="money">{{ $money($totalPendapatan ?? $revenue) }}</td>
                </tr>

                {{-- HARGA POKOK PENJUALAN --}}
                <tr style="background: #f8fafc;">
                    <td colspan="4" style="font-weight: 800; color: #0f172a;">Harga Pokok Penjualan</td>
                </tr>
                @foreach($hppClosed ?? [] as $acc)
                    <tr>
                        <td><strong>{{ $acc->acc_no }}</strong></td>
                        <td><a href="{{ $ledgerUrl($acc) }}" style="color: #0f172a; font-weight: 600; text-decoration: none;">{{ $acc->name }}</a></td>
                        <td class="money">{{ $money($acc->balance) }}</td>
                        <td class="money"></td>
                    </tr>
                @endforeach
                <tr style="background: #f1f5f9; font-weight: 700;">
                    <td></td>
                    <td>
                        Total Harga Pokok Pendapatan
                        @if(!empty($hppBreakdown) && count($hppBreakdown) > 0)
                            <button type="button" onclick="toggleIncomeDetail('hpp-job-detail')" style="margin-left:8px;border:0;background:none;color:#0284c7;font-weight:700;cursor:pointer;font-size:12px;">(Lihat Rincian Charge <span id="hpp-job-detail-icon">▶</span>)</button>
                        @endif
                    </td>
                    <td></td>
                    <td class="money">{{ $money($totalHppClosed ?? 0) }}</td>
                </tr>
                @if(!empty($hppBreakdown) && count($hppBreakdown) > 0)
                    <tr id="hpp-job-detail" style="display:none;background:#f8fafc;">
                        <td colspan="4" style="padding:14px 18px;">
                            <strong style="font-size:12px;color:#334155;">Rincian HPP Closed per uraian charge</strong>
                            <div style="margin-top:8px;border:1px solid #dbe3ee;border-radius:8px;background:#fff;overflow:hidden;">
                                @foreach($hppBreakdown as $index => $charge)
                                    @php $detailId = 'hpp-charge-'.$index; @endphp
                                    <div style="border-bottom:1px solid #eef2f7;">
                                        <button type="button" onclick="toggleIncomeDetail('{{ $detailId }}')" style="width:100%;display:flex;justify-content:space-between;align-items:center;gap:12px;border:0;background:#fff;padding:10px 12px;cursor:pointer;text-align:left;">
                                            <span style="font-weight:700;color:#0f172a;">{{ strtoupper($charge->description) }} <small style="font-weight:500;color:#64748b;">({{ $charge->transaction_count }} charge)</small></span>
                                            <span style="font-family:monospace;font-weight:700;color:#0f172a;">{{ $money($charge->total_cost) }} <span id="{{ $detailId }}-icon" style="color:#94a3b8;">▶</span></span>
                                        </button>
                                        <div id="{{ $detailId }}" style="display:none;padding:0 12px 12px;background:#f8fafc;">
                                            <table style="width:100%;font-size:11px;border-collapse:collapse;">
                                                <thead><tr style="color:#64748b;text-align:left;"><th style="padding:6px;">Job Order</th><th style="padding:6px;">Uraian</th><th style="padding:6px;">Tanggal</th><th style="padding:6px;text-align:right;">Modal / HPP</th></tr></thead>
                                                <tbody>@foreach($charge->items as $item)<tr style="border-top:1px solid #e2e8f0;"><td style="padding:6px;"><a href="{{ route('jobs.show', $item['job_id']) }}" style="color:#0284c7;font-weight:700;text-decoration:none;">{{ $item['job_number'] }}</a></td><td style="padding:6px;">{{ $item['cost_number'] }} · {{ $charge->description }}</td><td style="padding:6px;">{{ $item['cost_date'] }}</td><td style="padding:6px;text-align:right;font-family:monospace;font-weight:700;">{{ $money($item['total_cost']) }}</td></tr>@endforeach</tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endif

                {{-- HPP JOB UNCLOSED --}}
                <tr style="background: #f8fafc;">
                    <td colspan="4" style="font-weight: 800; color: #0f172a;">HPP Job Unclosed</td>
                </tr>
                @foreach($hppUnclosed ?? [] as $acc)
                    <tr>
                        <td><strong>{{ $acc->acc_no }}</strong></td>
                        <td><a href="{{ $ledgerUrl($acc) }}" style="color: #0f172a; font-weight: 600; text-decoration: none;">{{ $acc->name }}</a></td>
                        <td class="money">{{ $money($acc->balance) }}</td>
                        <td class="money"></td>
                    </tr>
                @endforeach
                <tr style="background: #f1f5f9; font-weight: 700;">
                    <td></td>
                    <td>Total Harga Pokok Pendapatan Unclosed</td>
                    <td></td>
                    <td class="money">{{ $money($totalHppUnclosed ?? 0) }}</td>
                </tr>

                {{-- TOTAL HPP --}}
                <tr style="background: #e2e8f0; font-weight: 800; color: #0f172a;">
                    <td></td>
                    <td>TOTAL HPP</td>
                    <td></td>
                    <td class="money">{{ $money($totalHpp ?? $cogs) }}</td>
                </tr>

                {{-- LABA / RUGI KOTOR --}}
                <tr style="background: #dbeafe; font-weight: 800; color: #0f172a;">
                    <td></td>
                    <td>Laba / Rugi Kotor</td>
                    <td class="money">{{ number_format((float)($grossMarginPct ?? 0), 2, ',', '.') }} %</td>
                    <td class="money">{{ $money($labaKotor ?? $gross) }}</td>
                </tr>

                {{-- BIAYA OPERASIONAL --}}
                <tr style="background: #f8fafc;">
                    <td colspan="4" style="font-weight: 800; color: #0f172a;">Biaya Operasional</td>
                </tr>
                @foreach($biayaOperasional ?? [] as $acc)
                    <tr>
                        <td><strong>{{ $acc->acc_no }}</strong></td>
                        <td><a href="{{ $ledgerUrl($acc) }}" style="color: #0f172a; font-weight: 600; text-decoration: none;">{{ $acc->name }}</a></td>
                        <td class="money">{{ $money($acc->balance) }}</td>
                        <td class="money"></td>
                    </tr>
                @endforeach
                <tr style="background: #f1f5f9; font-weight: 700;">
                    <td></td>
                    <td>Jumlah Biaya Operasional</td>
                    <td></td>
                    <td class="money">{{ $money($totalBiayaOperasional ?? 0) }}</td>
                </tr>

                {{-- LABA / RUGI OPERASIONAL --}}
                <tr style="background: #e2e8f0; font-weight: 800; color: #0f172a;">
                    <td></td>
                    <td>Laba / Rugi Operasional</td>
                    <td></td>
                    <td class="money">{{ $money($labaOperasional ?? 0) }}</td>
                </tr>

                {{-- PENDAPATAN DAN BIAYA LAIN-LAIN --}}
                <tr style="background: #f8fafc;">
                    <td colspan="4" style="font-weight: 800; color: #0f172a;">Pendapatan dan Biaya Lain-Lain</td>
                </tr>
                @foreach($pendapatanLain ?? [] as $acc)
                    <tr>
                        <td><strong>{{ $acc->acc_no }}</strong></td>
                        <td><a href="{{ $ledgerUrl($acc) }}" style="color: #0f172a; font-weight: 600; text-decoration: none;">{{ $acc->name }}</a></td>
                        <td class="money">{{ $money($acc->balance) }}</td>
                        <td class="money"></td>
                    </tr>
                @endforeach
                <tr style="background: #f1f5f9; font-weight: 700;">
                    <td></td>
                    <td>Pendapatan Lain Lain</td>
                    <td></td>
                    <td class="money">{{ $money($totalPendapatanLain ?? 0) }}</td>
                </tr>

                @foreach($biayaLain ?? [] as $acc)
                    <tr>
                        <td><strong>{{ $acc->acc_no }}</strong></td>
                        <td><a href="{{ $ledgerUrl($acc) }}" style="color: #0f172a; font-weight: 600; text-decoration: none;">{{ $acc->name }}</a></td>
                        <td class="money">{{ $money($acc->balance) }}</td>
                        <td class="money"></td>
                    </tr>
                @endforeach
                <tr style="background: #f1f5f9; font-weight: 700;">
                    <td></td>
                    <td>Biaya Lain Lain</td>
                    <td></td>
                    <td class="money">{{ $money($totalBiayaLain ?? 0) }}</td>
                </tr>

                <tr style="background: #e2e8f0; font-weight: 700;">
                    <td></td>
                    <td>Jumlah Pendapatan dan Biaya Lain-Lain</td>
                    <td></td>
                    <td class="money">{{ $money($jumlahPendapatanBiayaLain ?? 0) }}</td>
                </tr>

                {{-- LABA / RUGI BERSIH SEBELUM PAJAK --}}
                <tr style="background: #dbeafe; font-weight: 800; color: #0f172a;">
                    <td></td>
                    <td>Laba / Rugi Bersih sebelum Pajak</td>
                    <td class="money">{{ number_format((float)($netBeforeTaxPct ?? 0), 2, ',', '.') }} %</td>
                    <td class="money">{{ $money($labaBersihSebelumPajak ?? $net) }}</td>
                </tr>

                {{-- PAJAK PENGHASILAN --}}
                <tr style="font-weight: 700;">
                    <td></td>
                    <td>
                        @if(!empty($pajakAccount?->id))
                            <a href="{{ $ledgerUrl($pajakAccount) }}" style="color: #0f172a; text-decoration: none;">Pajak Penghasilan Thn {{ $reportYear }}</a>
                        @else
                            Pajak Penghasilan Thn {{ $reportYear }}
                        @endif
                    </td>
                    <td></td>
                    <td class="money">{{ $money($pajakPenghasilan ?? 0) }}</td>
                </tr>

                {{-- LABA / RUGI BERSIH SESUDAH PAJAK --}}
                <tr style="background: #0f172a; color: #ffffff; font-weight: 900;">
                    <td></td>
                    <td style="color: #ffffff;">Laba / Rugi Bersih sesudah Pajak</td>
                    <td></td>
                    <td class="money" style="color: {{ (float)($labaBersihSesudahPajak ?? $net) >= 0 ? '#4ade80' : '#f87171' }};">{{ $money($labaBersihSesudahPajak ?? $net) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<script>
function toggleIncomeDetail(id) {
    const target = document.getElementById(id);
    if (!target) return;
    const expanded = target.style.display !== 'none';
    target.style.display = expanded ? 'none' : (target.tagName === 'TR' ? 'table-row' : 'block');
    const icon = document.getElementById(id + '-icon');
    if (icon) icon.textContent = expanded ? '▶' : '▼';
}
</script>
@endsection
