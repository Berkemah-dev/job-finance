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
                @php $hppRoot = collect($cogsAccounts ?? [])->firstWhere('code', '5101'); @endphp
                @if($hppRoot && $hppRoot->has_activity)
                    <tr style="cursor: pointer;" onclick="toggleIncomeDetail('hpp-job-detail')">
                        <td><button type="button" style="border:0;background:none;color:#0284c7;font-weight:700;padding:0;cursor:pointer;">{{ $hppRoot->code }}</button></td>
                        <td><button type="button" style="border:0;background:none;color:#0f172a;font-weight:600;padding:0;cursor:pointer;">{{ $hppRoot->name }} <span id="hpp-job-detail-icon" style="color:#94a3b8;">▶</span></button></td>
                        <td class="money" style="font-weight:700;color:#0f172a;">Rp {{ \App\Support\Money::format($hppRoot->balance) }}</td>
                        <td class="money">Rp {{ \App\Support\Money::format($hppRoot->credit) }}</td>
                    </tr>
                    <tr id="hpp-job-detail" style="display:none;background:#f8fafc;">
                        <td colspan="4" style="padding:14px 18px;">
                            <strong style="font-size:12px;color:#334155;">Rincian HPP per uraian charge</strong>
                            <p style="margin:4px 0 10px;font-size:11px;color:#64748b;">Klik uraian untuk melihat rincian nomor Job dan nilai modal tiap charge.</p>
                            <div style="border:1px solid #dbe3ee;border-radius:8px;background:#fff;overflow:hidden;">
                                @forelse($hppBreakdown ?? [] as $index => $charge)
                                    @php $detailId = 'hpp-charge-'.$index; @endphp
                                    <div style="border-bottom:1px solid #eef2f7;">
                                        <button type="button" onclick="toggleIncomeDetail('{{ $detailId }}')" style="width:100%;display:flex;justify-content:space-between;align-items:center;gap:12px;border:0;background:#fff;padding:10px 12px;cursor:pointer;text-align:left;">
                                            <span style="font-weight:700;color:#0f172a;">{{ strtoupper($charge->description) }} <small style="font-weight:500;color:#64748b;">({{ $charge->transaction_count }} charge)</small></span>
                                            <span style="font-family:monospace;font-weight:700;color:#0f172a;">Rp {{ \App\Support\Money::format($charge->total_cost) }} <span id="{{ $detailId }}-icon" style="color:#94a3b8;">▶</span></span>
                                        </button>
                                        <div id="{{ $detailId }}" style="display:none;padding:0 12px 12px;background:#f8fafc;">
                                            <table style="width:100%;font-size:11px;border-collapse:collapse;">
                                                <thead><tr style="color:#64748b;text-align:left;"><th style="padding:6px;">Job Order</th><th style="padding:6px;">Uraian</th><th style="padding:6px;">Tanggal</th><th style="padding:6px;text-align:right;">Modal / HPP</th></tr></thead>
                                                <tbody>@foreach($charge->items as $item)<tr style="border-top:1px solid #e2e8f0;"><td style="padding:6px;"><a href="{{ route('jobs.show', $item['job_id']) }}" style="color:#0284c7;font-weight:700;text-decoration:none;">{{ $item['job_number'] }}</a></td><td style="padding:6px;">{{ $item['cost_number'] }} · {{ $charge->description }}</td><td style="padding:6px;">{{ $item['cost_date'] }}</td><td style="padding:6px;text-align:right;font-family:monospace;font-weight:700;">Rp {{ \App\Support\Money::format($item['total_cost']) }}</td></tr>@endforeach</tbody>
                                            </table>
                                        </div>
                                    </div>
                                @empty
                                    <div style="padding:12px;color:#64748b;font-size:12px;">Belum ada rincian HPP dari Job Closing pada periode ini.</div>
                                @endforelse
                            </div>
                            <a href="{{ route('reports.ledger', ['account_id' => $hppRoot->id, 'from' => $from, 'to' => $to]) }}" style="display:inline-block;margin-top:10px;color:#0284c7;font-size:12px;font-weight:700;text-decoration:none;">Lihat seluruh Buku Besar HPP →</a>
                        </td>
                    </tr>
                @else
                    <tr><td colspan="4" class="text-center" style="color:#64748b;">Belum ada mutasi akun HPP</td></tr>
                @endif
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
<script>
function toggleIncomeDetail(id) {
    const target = document.getElementById(id);
    if (!target) return;
    const expanded = target.style.display !== 'none';
    target.style.display = expanded ? 'none' : (target.tagName === 'TR' ? 'table-row' : 'block');
    const icon = document.getElementById(id + '-icon');
    if (icon) icon.textContent = expanded ? '▶' : '▼';
    if (id === 'hpp-job-detail') {
        const rootIcon = document.getElementById('hpp-job-detail-icon');
        if (rootIcon) rootIcon.textContent = expanded ? '▶' : '▼';
    }
}
</script>
@endsection
