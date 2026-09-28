@php
    $overview = $balanceOverview ?? [];
    $liabilities = (float) ($overview['liabilities'] ?? 0);
    $equity = (float) ($overview['equity_total'] ?? 0);
    $composition = max(1, abs($liabilities) + abs($equity));
    $liabilityPercent = min(100, (abs($liabilities) / $composition) * 100);
    $equityPercent = min(100, (abs($equity) / $composition) * 100);
@endphp
<section class="balance-dashboard" aria-label="Ringkasan posisi keuangan">
    <div class="balance-dashboard-head">
        <div>
            <p class="eyebrow">POSISI KEUANGAN</p>
            <h2>Ringkasan Neraca</h2>
            <p>Saldo seluruh COA dari jurnal sampai {{ \Carbon\Carbon::parse($overview['as_of'] ?? today())->format('d/m/Y') }}.</p>
        </div>
        <a class="text-link" href="{{ route('reports.balance-sheet') }}">Lihat Neraca Detail</a>
    </div>
    <div class="balance-dashboard-metrics">
        <article><span>Total Aset</span><strong>Rp {{ \App\Support\Money::format($overview['assets'] ?? 0) }}</strong></article>
        <article><span>Total Liabilitas</span><strong>Rp {{ \App\Support\Money::format($overview['liabilities'] ?? 0) }}</strong></article>
        <article><span>Total Ekuitas</span><strong>Rp {{ \App\Support\Money::format($overview['equity_total'] ?? 0) }}</strong><small>Termasuk laba berjalan</small></article>
    </div>
    <div class="balance-dashboard-chart">
        <div class="balance-chart-label"><span>Komposisi sumber dana</span><strong>Liabilitas + Ekuitas</strong></div>
        <div class="balance-chart-track" role="img" aria-label="Liabilitas {{ number_format($liabilityPercent, 1) }} persen dan Ekuitas {{ number_format($equityPercent, 1) }} persen">
            <i class="balance-chart-liability" style="width: {{ $liabilityPercent }}%"></i><i class="balance-chart-equity" style="width: {{ $equityPercent }}%"></i>
        </div>
        <div class="balance-chart-legend"><span><i class="legend-liability"></i>Liabilitas {{ number_format($liabilityPercent, 1) }}%</span><span><i class="legend-equity"></i>Ekuitas {{ number_format($equityPercent, 1) }}%</span></div>
    </div>
</section>
