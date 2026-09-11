@extends('layouts.app')
@section('title', 'Dashboard Finance')
@section('content')
<div class="role-hero">
    <div>
        <span class="eyebrow" style="color:#b9c9e6">WORKSPACE FINANCE</span>
        <h2>Keuangan lebih terkontrol.</h2>
        <p>Prioritaskan invoice jatuh tempo, tagihan belum lunas, dan kurs aktif hari ini.</p>
    </div>
    <a class="button button-white" href="{{ route('invoices.index') }}">Buka Invoice <x-icon name="arrow"/></a>
</div>

<div class="stats-grid">
    <article class="stat-card">
        <div class="stat-top"><span>Invoice Belum Lunas</span><span class="stat-icon amber"><x-icon name="wallet"/></span></div>
        <strong class="stat-number">{{ $unpaidInvoices->count() }}</strong>
        <p>Invoice yang perlu ditindaklanjuti.</p>
    </article>
    <article class="stat-card">
        <div class="stat-top"><span>Piutang Customer</span><span class="stat-icon purple"><x-icon name="wallet"/></span></div>
        <strong class="stat-number"><small>Rp</small> {{ \App\Support\Money::format($receivableBalance) }}</strong>
        <p>Total sisa tagihan belum lunas.</p>
    </article>
    <article class="stat-card">
        <div class="stat-top"><span>Reimbursement menunggu</span><span class="stat-icon blue"><x-icon name="file"/></span></div>
        <strong class="stat-number">{{ $widgets['pendingReimbursements'] ?? 0 }}</strong>
        <p>Permintaan pending yang perlu diproses.</p>
    </article>
    <article class="stat-card">
        <div class="stat-top"><span>Kurs Mingguan</span><span class="stat-icon blue"><x-icon name="chart"/></span></div>
        <strong class="stat-number">{{ $weeklyPricing?->currency ?? 'USD' }}</strong>
        <p>{{ $weeklyPricing ? 'Rp ' . \App\Support\Money::format($weeklyPricing->exchange_rate) : 'Belum tersedia' }}</p>
    </article>
</div>

@if(isset($widgets['overdueReceivables']) && ($widgets['overdueReceivables']['count'] > 0 || $widgets['journalsThisMonth'] > 0))
<div class="stats-grid" style="margin-top:20px">
    <article class="stat-card"><div class="stat-top"><span>Piutang jatuh tempo</span><span class="stat-icon amber"><x-icon name="wallet"/></span></div><strong class="stat-number"><small>Rp</small> {{ \App\Support\Money::format($widgets['overdueReceivables']['amount']) }}</strong><p>{{ $widgets['overdueReceivables']['count'] }} invoice melewati jatuh tempo</p></article>
    <article class="stat-card"><div class="stat-top"><span>Jurnal bulan ini</span><span class="stat-icon green"><x-icon name="chart"/></span></div><strong class="stat-number">{{ $widgets['journalsThisMonth'] ?? 0 }}</strong><p>Jurnal ter-posting bulan ini</p></article>
    <article class="stat-card"><div class="stat-top"><span>Job Open</span><span class="stat-icon blue"><x-icon name="briefcase"/></span></div><strong class="stat-number">{{ $openJobs }}</strong><p>Pekerjaan sedang berjalan</p></article>
    <article class="stat-card"><div class="stat-top"><span>Job Closed</span><span class="stat-icon green"><x-icon name="check"/></span></div><strong class="stat-number">{{ $closedJobs }}</strong><p>Pekerjaan selesai</p></article>
</div>
@endif

@if(isset($widgets['topJobs']) && $widgets['topJobs']->isNotEmpty())
<section class="panel" style="margin-bottom:20px;margin-top:20px">
    <div class="panel-heading"><div><h2>Job paling menguntungkan</h2><p>Berdasarkan profit closing.</p></div><a class="text-link" href="{{ route('reports.profit-per-job') }}">Profit per Job</a></div>
    <div class="table-scroll"><table><thead><tr><th>Job</th><th>Subject</th><th>Closing</th><th class="money">Profit</th></tr></thead><tbody>@foreach($widgets['topJobs'] as $job)<tr><td><a class="text-link" href="{{ route('jobs.show',$job) }}"><strong>{{ $job->number }}</strong></a></td><td>{{ Str::limit($job->subject,35) }}</td><td>{{ $job->closingSnapshot->closing_date->format('d/m/Y') }}</td><td class="money">Rp {{ \App\Support\Money::format((string) $job->closingSnapshot->profit) }}</td></tr>@endforeach</tbody></table></div>
</section>
@endif

<div class="finance-strip" style="margin-bottom:20px;margin-top:20px">
    <div><span>Temporary Final (Job Open)</span><strong>Rp {{ \App\Support\Money::format($temporaryBalance) }}</strong></div>
    <div><span>Provision Final (Job Open)</span><strong>Rp {{ \App\Support\Money::format($provisionBalance) }}</strong></div>
    <div><span>Pendapatan</span><strong>Rp {{ \App\Support\Money::format($revenueBalance) }}</strong></div>
    <div><span>HPP</span><strong>Rp {{ \App\Support\Money::format($cogsBalance) }}</strong></div>
</div>

@if(isset($widgets['shipment']))
<div class="section-heading"><h2>Pengiriman berjalan</h2><span class="subtle">Status shipment aktif</span></div>
<section class="panel" style="margin-bottom:20px">
    <div class="filter-bar" style="justify-content:flex-start;border:none;padding:14px 23px;display:flex;flex-wrap:wrap;gap:8px">
        @foreach(config('operations.shipment_statuses') as $value=>$label)
        <span class="badge-pill status-{{ $value }}">{{ $label }} · {{ $widgets['shipment'][$value] ?? 0 }}</span>
        @if(!$loop->last)<span class="text-link">→</span>@endif
        @endforeach
    </div>
</section>
@endif

<div class="dashboard-grid bottom-grid" style="margin-bottom:20px">
    <section class="panel">
        <div class="panel-heading"><div><h2>Pendapatan & profit</h2><p>Enam bulan terakhir dari job closed.</p></div><span class="count-badge">6 bulan</span></div>
        @php $chartMax=max(1,$monthlyPerformance->max(fn($r)=>(float)$r['revenue'])); @endphp
        <div class="monthly-chart">
            @foreach($monthlyPerformance as $month)
            <div class="month-column" title="Pendapatan Rp {{ \App\Support\Money::format($month['revenue']) }} · Profit Rp {{ \App\Support\Money::format($month['profit']) }}">
                <div class="bar-pair">
                    <i class="bar-revenue" style="height:{{ max(3,((float)$month['revenue']/$chartMax)*100) }}%"></i>
                    <i class="bar-profit" style="height:{{ max(3,((float)$month['profit']/$chartMax)*100) }}%"></i>
                </div>
                <span>{{ $month['label'] }}</span>
            </div>
            @endforeach
        </div>
        <div class="chart-legend"><span><i class="legend-revenue"></i>Pendapatan</span><span><i class="legend-profit"></i>Profit</span></div>
    </section>
    <section class="panel">
        <div class="panel-heading"><div><h2>Umur piutang</h2><p>Distribusi invoice berdasarkan jatuh tempo.</p></div></div>
        <div class="mini-chart">
            <div class="bar-item"><div class="bar" style="height:{{ max(7,$invoiceAging['current']*18) }}%;background:#0f1f3d"></div><small>Saat ini<br>{{ $invoiceAging['current'] }}</small></div>
            <div class="bar-item"><div class="bar" style="height:{{ max(7,$invoiceAging['overdue_1_30']*18) }}%;background:#b91c1c"></div><small>1–30 hari<br>{{ $invoiceAging['overdue_1_30'] }}</small></div>
            <div class="bar-item"><div class="bar" style="height:{{ max(7,$invoiceAging['overdue_30_plus']*18) }}%;background:#7f1d1d"></div><small>>30 hari<br>{{ $invoiceAging['overdue_30_plus'] }}</small></div>
        </div>
    </section>
</div>

<section class="panel">
    <div class="panel-heading"><div><h2>Invoice yang perlu dibayar</h2><p>Urut berdasarkan tanggal jatuh tempo.</p></div><a class="text-link" href="{{ route('invoices.index') }}">Lihat semua invoice</a></div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Invoice</th><th>Jatuh tempo</th><th class="money">Sisa</th></tr></thead>
            <tbody>
                @forelse($unpaidInvoices as $invoice)
                <tr><td><a class="text-link" href="{{ route('invoices.show', $invoice) }}"><strong>{{ $invoice->number }}</strong></a></td><td>{{ $invoice->due_date->format('d/m/Y') }}</td><td class="money">Rp {{ \App\Support\Money::format($invoice->balance) }}</td></tr>
                @empty
                <tr><td colspan="3">Tidak ada invoice terbuka.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
