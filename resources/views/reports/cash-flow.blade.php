@extends('layouts.app')
@section('title', 'Laporan Arus Kas (Cash Flow)')
@section('content')
<x-menu-banner
    tag="LAPORAN KEUANGAN"
    title="Laporan Arus Kas (Cash Flow)"
    description="Pergerakan arus kas masuk dari customer dan arus kas keluar untuk biaya operasional & investasi, serta rincian saldo per akun kas & bank."
    icon="wallet"
    art-title="Likuiditas kas,"
    art-subtitle="terjaga optimal."
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
    $paymentValue = abs((float) $customer_payment);
    $costValue = abs((float) $job_cost_capitalization);
    $costPaidValue = abs((float) ($job_cost_payment ?? 0));
    $adjustmentValue = abs((float) $adjustment);
    $otherValue = abs((float) $other);
    $inflowValue = (float) $inflow;
    $outflowValue = (float) $outflow;
    $netMovement = (float) $net;
    $openingVal = (float) $opening;
    $closingVal = (float) $closing;
    $maxValue = max(1, $paymentValue, $costValue, $costPaidValue, $adjustmentValue, $otherValue, abs($netMovement));
@endphp

{{-- 4 KEY METRIC CARDS --}}
<div class="report-grid report-grid-4">
    <section class="report-card">
        <div class="report-card-head">
            <div class="report-card-title">
                <span class="report-icon blue"><x-icon name="file"/></span>
                <div>
                    <h2>Saldo Awal</h2>
                    <small>Sebelum {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}</small>
                </div>
            </div>
        </div>
        <strong class="report-value">Rp {{ \App\Support\Money::format($opening) }}</strong>
    </section>

    <section class="report-card">
        <div class="report-card-head">
            <div class="report-card-title">
                <span class="report-icon green"><x-icon name="wallet"/></span>
                <div>
                    <h2>Total Kas Masuk</h2>
                    <small>Inflow periode ini</small>
                </div>
            </div>
        </div>
        <strong class="report-value" style="color: #16a34a;">Rp {{ \App\Support\Money::format($inflow) }}</strong>
    </section>

    <section class="report-card">
        <div class="report-card-head">
            <div class="report-card-title">
                <span class="report-icon amber"><x-icon name="briefcase"/></span>
                <div>
                    <h2>Total Kas Keluar</h2>
                    <small>Outflow periode ini</small>
                </div>
            </div>
        </div>
        <strong class="report-value" style="color: #dc2626;">Rp {{ \App\Support\Money::format($outflow) }}</strong>
    </section>

    <section class="report-card">
        <div class="report-card-head">
            <div class="report-card-title">
                <span class="report-icon purple"><x-icon name="chart"/></span>
                <div>
                    <h2>Saldo Akhir</h2>
                    <small>Per {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</small>
                </div>
            </div>
        </div>
        <strong class="report-value {{ $closingVal >= 0 ? 'positive' : 'negative' }}">{{ \App\Support\Money::rupiah($closing) }}</strong>
    </section>
</div>

{{-- BREAKDOWN KATEGORI ARUS KAS --}}
<section class="panel report-chart-card report-section">
    <div class="report-chart-head">
        <div>
            <h2>Arus Kas Bersih per Aktivitas</h2>
            <p>Rincian mutasi kas menurut kategori transaksi pada periode yang dipilih.</p>
        </div>
        <div style="text-align: right;">
            <small style="color: #64748b;">Pergerakan Kas Bersih (Net Movement)</small>
            <div style="font-size: 18px; font-weight: 800; color: {{ $netMovement >= 0 ? '#16a34a' : '#dc2626' }};">
                {{ \App\Support\Money::rupiah($net) }}
            </div>
        </div>
    </div>
    <div class="report-bars">
        <div class="report-bar-row">
            <span>Penerimaan Customer</span>
            <div class="report-bar-track">
                <span class="report-bar-fill green" style="--bar: {{ round(($paymentValue / $maxValue) * 100, 2) }}%"></span>
            </div>
            <strong class="report-bar-value" style="color: #16a34a;">Rp {{ \App\Support\Money::format($customer_payment) }}</strong>
        </div>
        <div class="report-bar-row">
            <span>Pembayaran Biaya Job / Vendor</span>
            <div class="report-bar-track">
                <span class="report-bar-fill amber" style="--bar: {{ round(($costPaidValue / $maxValue) * 100, 2) }}%"></span>
            </div>
            <strong class="report-bar-value" style="color: #d97706;">Rp {{ \App\Support\Money::format($job_cost_payment) }}</strong>
        </div>
        <div class="report-bar-row">
            <span>Kapitalisasi Biaya Operasional</span>
            <div class="report-bar-track">
                <span class="report-bar-fill amber" style="--bar: {{ round(($costValue / $maxValue) * 100, 2) }}%"></span>
            </div>
            <strong class="report-bar-value">Rp {{ \App\Support\Money::format($job_cost_capitalization) }}</strong>
        </div>
        <div class="report-bar-row">
            <span>Penyesuaian Kas / Bank</span>
            <div class="report-bar-track">
                <span class="report-bar-fill blue" style="--bar: {{ round(($adjustmentValue / $maxValue) * 100, 2) }}%"></span>
            </div>
            <strong class="report-bar-value">Rp {{ \App\Support\Money::format($adjustment) }}</strong>
        </div>
        <div class="report-bar-row">
            <span>Transaksi Lainnya</span>
            <div class="report-bar-track">
                <span class="report-bar-fill" style="--bar: {{ round(($otherValue / $maxValue) * 100, 2) }}%"></span>
            </div>
            <strong class="report-bar-value">Rp {{ \App\Support\Money::format($other) }}</strong>
        </div>
    </div>
</section>

{{-- BREAKDOWN PER AKUN KAS & BANK --}}
<section class="panel report-table-panel" style="margin-top: 24px;">
    <div class="panel-heading" style="padding: 16px 20px 12px; border-bottom: 1px solid #edf1f7;">
        <h2 style="font-size: 15px; font-weight: 700; margin: 0; color: #0f172a;">Rincian Saldo Kas & Bank per Akun</h2>
        <span class="subtle" style="font-size: 12px; color: #64748b; margin-top: 2px; display: block;">Posisi saldo awal, pergerakan masuk/keluar, dan saldo akhir masing-masing rekening kas & bank.</span>
    </div>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">Kode Akun</th>
                    <th style="width: 28%;">Nama Akun</th>
                    <th class="money" style="width: 15%;">Saldo Awal</th>
                    <th class="money" style="width: 15%;">Kas Masuk (D)</th>
                    <th class="money" style="width: 15%;">Kas Keluar (K)</th>
                    <th class="money" style="width: 15%;">Saldo Akhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $acc)
                    @php
                        $accInflow = (float) $acc->inflow;
                        $accOutflow = (float) $acc->outflow;
                        $accOpening = (float) $acc->opening;
                        $accClosing = (float) $acc->closing;
                        $hasActivity = $accInflow > 0 || $accOutflow > 0 || $accOpening != 0 || $accClosing != 0;
                    @endphp
                    <tr style="{{ !$hasActivity ? 'opacity: 0.6;' : '' }}">
                        <td>
                            <strong style="color: #0f172a;">{{ $acc->code }}</strong>
                        </td>
                        <td style="color: #334155;">
                            {{ $acc->name }}
                        </td>
                        <td class="money" style="color: {{ $accOpening != 0 ? '#0f172a' : '#94a3b8' }};">
                            {{ \App\Support\Money::rupiah($acc->opening) }}
                        </td>
                        <td class="money">
                            @if($accInflow > 0)
                                <span style="color: #16a34a; font-weight: 600;">Rp {{ \App\Support\Money::format($acc->inflow) }}</span>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td class="money">
                            @if($accOutflow > 0)
                                <span style="color: #dc2626; font-weight: 600;">Rp {{ \App\Support\Money::format($acc->outflow) }}</span>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td class="money" style="font-weight: 700; color: {{ $accClosing > 0 ? '#0f172a' : ($accClosing < 0 ? '#dc2626' : '#94a3b8') }};">
                            {{ \App\Support\Money::rupiah($acc->closing) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 24px; color: #64748b;">
                            Tidak ditemukan akun kas & bank.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background: #f8fafc;">
                    <th colspan="2" style="background: #f8fafc; font-size: 11px; font-weight: 700; color: #0f172a; padding: 14px 23px; border-top: 1.5px solid #cbd5e1; border-bottom: 2px solid #94a3b8; letter-spacing: 0.03em;">
                        TOTAL KAS & BANK
                    </th>
                    <td class="money" style="background: #f8fafc; font-weight: 700; color: #0f172a; padding: 14px 23px; border-top: 1.5px solid #cbd5e1; border-bottom: 2px solid #94a3b8;">
                        {{ \App\Support\Money::rupiah($opening) }}
                    </td>
                    <td class="money" style="background: #f8fafc; font-weight: 700; color: #16a34a; padding: 14px 23px; border-top: 1.5px solid #cbd5e1; border-bottom: 2px solid #94a3b8;">
                        Rp {{ \App\Support\Money::format($inflow) }}
                    </td>
                    <td class="money" style="background: #f8fafc; font-weight: 700; color: #dc2626; padding: 14px 23px; border-top: 1.5px solid #cbd5e1; border-bottom: 2px solid #94a3b8;">
                        Rp {{ \App\Support\Money::format($outflow) }}
                    </td>
                    <td class="money" style="background: #f8fafc; font-weight: 800; color: {{ $closingVal >= 0 ? '#0f172a' : '#dc2626' }}; padding: 14px 23px; border-top: 1.5px solid #cbd5e1; border-bottom: 2px solid #94a3b8; font-size: 12.5px;">
                        {{ \App\Support\Money::rupiah($closing) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</section>

{{-- DETAIL MUTASI KAS & BANK PERIODE INI --}}
<section class="panel report-table-panel" style="margin-top: 24px;">
    <div class="panel-heading" style="padding: 16px 20px 12px; border-bottom: 1px solid #edf1f7;">
        <h2 style="font-size: 15px; font-weight: 700; margin: 0; color: #0f172a;">Riwayat Mutasi Kas & Bank</h2>
        <span class="subtle" style="font-size: 12px; color: #64748b; margin-top: 2px; display: block;">Daftar jurnal transaksi yang mempengaruhi kas & bank pada periode ini.</span>
    </div>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th style="width: 11%;">Tanggal</th>
                    <th style="width: 16%;">No. Jurnal</th>
                    <th style="width: 22%;">Akun Kas / Bank</th>
                    <th style="width: 25%;">Keterangan</th>
                    <th class="money" style="width: 13%;">Kas Masuk (D)</th>
                    <th class="money" style="width: 13%;">Kas Keluar (K)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    @php
                        $debit = (float) $entry->debit;
                        $credit = (float) $entry->credit;
                    @endphp
                    <tr>
                        <td style="color: #64748b; font-size: 12.5px;">
                            {{ \Carbon\Carbon::parse($entry->journal->journal_date)->format('d/m/Y') }}
                        </td>
                        <td>
                            <a href="{{ route('journals.show', $entry->journal_id) }}" style="display: inline-block; padding: 2px 8px; border-radius: 4px; background: #eff6ff; color: #1d4ed8; font-weight: 600; font-size: 12px; text-decoration: none; border: 1px solid #dbeafe;">
                                {{ $entry->journal->number }}
                            </a>
                        </td>
                        <td>
                            <strong style="color: #0f172a; font-size: 12.5px;">{{ $entry->account->code }}</strong>
                            <div style="font-size: 12px; color: #64748b;">{{ $entry->account->name }}</div>
                        </td>
                        <td style="color: #334155; font-size: 12.5px;">
                            {{ $entry->description ?: ($entry->journal->description ?: '—') }}
                        </td>
                        <td class="money">
                            @if($debit > 0)
                                <span style="color: #16a34a; font-weight: 600;">Rp {{ \App\Support\Money::format($entry->debit) }}</span>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td class="money">
                            @if($credit > 0)
                                <span style="color: #dc2626; font-weight: 600;">Rp {{ \App\Support\Money::format($entry->credit) }}</span>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 24px; color: #64748b;">
                            Tidak ada transaksi mutasi kas & bank pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
