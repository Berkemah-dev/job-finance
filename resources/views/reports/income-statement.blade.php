@extends('layouts.app')
@section('title','Laba Rugi')
@section('content')
<x-menu-banner
    tag="LAPORAN KEUANGAN"
    title="Laporan Laba Rugi (Income Statement)"
    description="Performa pendapatan jasa, beban pokok penjualan (HPP), biaya operasional, dan laba bersih."
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
    $revenueValue = abs((float) $revenue);
    $cogsValue = abs((float) $cogs);
    $expenseValue = abs((float) $expense);
    $grossValue = abs((float) $gross);
    $netValue = abs((float) $net);
    $maxValue = max(1, $revenueValue, $cogsValue, $expenseValue, $grossValue, $netValue);
@endphp

<div class="report-grid report-grid-4">
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon green"><x-icon name="wallet"/></span><div><h2>Pendapatan Jasa</h2><small>Total omzet periode</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($revenue) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon amber"><x-icon name="briefcase"/></span><div><h2>HPP Job</h2><small>Modal pekerjaan</small></div></div></div><strong class="report-value">Rp {{ \App\Support\Money::format($cogs) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon blue"><x-icon name="chart"/></span><div><h2>Laba Kotor</h2><small>Sebelum beban operasional</small></div></div></div><strong class="report-value {{ (float)$gross >= 0 ? 'positive' : 'negative' }}">Rp {{ \App\Support\Money::format($gross) }}</strong></section>
    <section class="report-card"><div class="report-card-head"><div class="report-card-title"><span class="report-icon purple"><x-icon name="check"/></span><div><h2>Laba Bersih</h2><small>Hasil akhir periode</small></div></div></div><strong class="report-value {{ (float)$net >= 0 ? 'positive' : 'negative' }}">Rp {{ \App\Support\Money::format($net) }}</strong></section>
</div>

<section class="panel report-chart-card report-section">
    <div class="report-chart-head"><div><h2>Ringkasan Laba Rugi</h2><p>Komposisi pendapatan, modal, beban, dan laba bersih.</p></div></div>
    <div class="report-bars">
        <div class="report-bar-row"><span>Pendapatan jasa</span><div class="report-bar-track"><span class="report-bar-fill green" style="--bar: {{ round(($revenueValue / $maxValue) * 100, 2) }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($revenue) }}</strong></div>
        <div class="report-bar-row"><span>HPP job</span><div class="report-bar-track"><span class="report-bar-fill amber" style="--bar: {{ round(($cogsValue / $maxValue) * 100, 2) }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($cogs) }}</strong></div>
        <div class="report-bar-row"><span>Beban operasional</span><div class="report-bar-track"><span class="report-bar-fill" style="--bar: {{ round(($expenseValue / $maxValue) * 100, 2) }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($expense) }}</strong></div>
        <div class="report-bar-row"><span>Laba bersih</span><div class="report-bar-track"><span class="report-bar-fill blue" style="--bar: {{ round(($netValue / $maxValue) * 100, 2) }}%"></span></div><strong class="report-bar-value">Rp {{ \App\Support\Money::format($net) }}</strong></div>
    </div>
</section>

@php
    $ledgerUrl = fn ($account) => route('reports.ledger', ['account_id' => $account->id ?? $account->account_id, 'from' => $from, 'to' => $to]);
@endphp

<section class="panel report-table-panel" style="margin-top: 24px;">
    <div class="panel-heading" style="margin-bottom: 16px;">
        <div>
            <h2>Breakdown Laba Rugi per Akun</h2>
            <span class="subtle">Rincian mutasi setiap akun Chart of Account (COA) pendapatan, HPP, dan beban</span>
        </div>
    </div>
    <p class="balance-detail-hint" style="margin-bottom: 16px;"><x-icon name="chart"/> Klik kode atau nama COA untuk melihat mutasi jurnal dan saldo berjalan akun tersebut di Buku Besar.</p>

    {{-- 1. PENDAPATAN JASA --}}
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 16px; border-radius: 6px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
        <span style="color: #0f172a; font-size: 13.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.025em;">Pendapatan Jasa</span>
        <strong style="color: #0f172a; font-size: 14.5px;">Rp {{ \App\Support\Money::format($revenue) }}</strong>
    </div>
    <div class="table-scroll" style="margin-bottom: 20px;">
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Kode Akun</th>
                    <th style="width: 45%;">Nama Akun</th>
                    <th class="money" style="width: 20%;">Debet</th>
                    <th class="money" style="width: 20%;">Kredit / Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($revenueAccounts ?? [] as $acc)
                    @if($acc->has_activity)
                        <tr>
                            <td><a href="{{ $ledgerUrl($acc) }}" style="color: #0284c7; font-weight: 700; text-decoration: none;" title="Lihat mutasi jurnal {{ $acc->code }} — {{ $acc->name }}">{{ $acc->code }}</a></td>
                            <td><a href="{{ $ledgerUrl($acc) }}" style="color: #0f172a; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" title="Lihat mutasi jurnal {{ $acc->code }} — {{ $acc->name }}">{{ $acc->name }} <x-icon name="arrow" style="width: 12px; height: 12px; color: #94a3b8;"/></a></td>
                            <td class="money">Rp {{ \App\Support\Money::format($acc->debit) }}</td>
                            <td class="money" style="font-weight: 700; color: #0f172a;">Rp {{ \App\Support\Money::format($acc->balance) }}</td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="4" class="text-center" style="color: #64748b;">Belum ada mutasi akun pendapatan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 2. HPP JOB --}}
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 16px; border-radius: 6px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
        <span style="color: #0f172a; font-size: 13.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.025em;">Harga Pokok Penjualan (HPP)</span>
        <strong style="color: #0f172a; font-size: 14.5px;">Rp {{ \App\Support\Money::format($cogs) }}</strong>
    </div>
    <div class="table-scroll" style="margin-bottom: 20px;">
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Kode Akun</th>
                    <th style="width: 45%;">Nama Akun</th>
                    <th class="money" style="width: 20%;">Debet / Modal</th>
                    <th class="money" style="width: 20%;">Kredit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cogsAccounts ?? [] as $acc)
                    @if($acc->has_activity)
                        <tr>
                            <td><a href="{{ $ledgerUrl($acc) }}" style="color: #0284c7; font-weight: 700; text-decoration: none;" title="Lihat mutasi jurnal {{ $acc->code }} — {{ $acc->name }}">{{ $acc->code }}</a></td>
                            <td><a href="{{ $ledgerUrl($acc) }}" style="color: #0f172a; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" title="Lihat mutasi jurnal {{ $acc->code }} — {{ $acc->name }}">{{ $acc->name }} <x-icon name="arrow" style="width: 12px; height: 12px; color: #94a3b8;"/></a></td>
                            <td class="money" style="font-weight: 700; color: #0f172a;">Rp {{ \App\Support\Money::format($acc->balance) }}</td>
                            <td class="money">Rp {{ \App\Support\Money::format($acc->credit) }}</td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="4" class="text-center" style="color: #64748b;">Belum ada mutasi akun HPP</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- LABA KOTOR SUB-CARD --}}
    <div style="background: #f1f5f9; border-top: 2px solid #cbd5e1; border-bottom: 2px solid #cbd5e1; padding: 12px 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-size: 14px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.025em;">Laba Kotor (Gross Profit)</span>
        <span style="font-size: 15px; font-weight: 800; color: {{ (float)$gross >= 0 ? '#16a34a' : '#dc2626' }};">Rp {{ \App\Support\Money::format($gross) }}</span>
    </div>

    {{-- 3. BEBAN OPERASIONAL --}}
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 16px; border-radius: 6px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
        <span style="color: #0f172a; font-size: 13.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.025em;">Beban Operasional</span>
        <strong style="color: #0f172a; font-size: 14.5px;">Rp {{ \App\Support\Money::format($expense) }}</strong>
    </div>
    <div class="table-scroll" style="margin-bottom: 20px;">
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Kode Akun</th>
                    <th style="width: 45%;">Nama Akun</th>
                    <th class="money" style="width: 20%;">Debet / Beban</th>
                    <th class="money" style="width: 20%;">Kredit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenseAccounts ?? [] as $acc)
                    @if($acc->has_activity)
                        <tr>
                            <td><a href="{{ $ledgerUrl($acc) }}" style="color: #0284c7; font-weight: 700; text-decoration: none;" title="Lihat mutasi jurnal {{ $acc->code }} — {{ $acc->name }}">{{ $acc->code }}</a></td>
                            <td><a href="{{ $ledgerUrl($acc) }}" style="color: #0f172a; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" title="Lihat mutasi jurnal {{ $acc->code }} — {{ $acc->name }}">{{ $acc->name }} <x-icon name="arrow" style="width: 12px; height: 12px; color: #94a3b8;"/></a></td>
                            <td class="money" style="font-weight: 700; color: #0f172a;">Rp {{ \App\Support\Money::format($acc->balance) }}</td>
                            <td class="money">Rp {{ \App\Support\Money::format($acc->credit) }}</td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="4" class="text-center" style="color: #64748b;">Belum ada mutasi beban operasional</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- LABA BERSIH TOTAL CARD --}}
    <div style="background: #0f172a; color: #ffffff; padding: 14px 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <span style="font-size: 14.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #ffffff;">Laba Bersih (Net Profit)</span>
            <p style="margin: 2px 0 0 0; font-size: 12px; color: #94a3b8;">Pendapatan – HPP – Beban Operasional</p>
        </div>
        <span style="font-size: 18px; font-weight: 900; color: {{ (float)$net >= 0 ? '#4ade80' : '#f87171' }};">Rp {{ \App\Support\Money::format($net) }}</span>
    </div>
</section>
@endsection
