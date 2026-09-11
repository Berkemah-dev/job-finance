@extends('layouts.app')
@section('title', $roleTitle ?? 'Dashboard')
@section('content')

@if(auth()->user()->role?->name === 'super-admin')
<div class="panel" style="margin-bottom:22px;border:1px solid #dce4f0;background:#fff;padding:12px 18px;border-radius:12px">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <span style="font-size:10px;font-weight:600;letter-spacing:1px;color:#8090a6;text-transform:uppercase">Lihat Dashboard Role:</span>
        <a class="badge-pill" href="{{ route('dashboard.finance') }}" style="text-decoration:none;padding:6px 12px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $role === 'finance' ? '#0f1f3d' : '#f1f5fa' }};color:{{ $role === 'finance' ? '#fff' : '#475569' }}">Finance</a>
        <a class="badge-pill" href="{{ route('dashboard.finance-manager') }}" style="text-decoration:none;padding:6px 12px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $role === 'finance-manager' ? '#0f1f3d' : '#f1f5fa' }};color:{{ $role === 'finance-manager' ? '#fff' : '#475569' }}">Finance Manager</a>
        <a class="badge-pill" href="{{ route('dashboard.sales-manager') }}" style="text-decoration:none;padding:6px 12px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $role === 'sales-manager' ? '#0f1f3d' : '#f1f5fa' }};color:{{ $role === 'sales-manager' ? '#fff' : '#475569' }}">Sales Manager</a>
        <a class="badge-pill" href="{{ route('dashboard.sales') }}" style="text-decoration:none;padding:6px 12px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $role === 'sales' ? '#0f1f3d' : '#f1f5fa' }};color:{{ $role === 'sales' ? '#fff' : '#475569' }}">Sales</a>
        <a class="badge-pill" href="{{ route('dashboard.operational') }}" style="text-decoration:none;padding:6px 12px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $role === 'operational' ? '#0f1f3d' : '#f1f5fa' }};color:{{ $role === 'operational' ? '#fff' : '#475569' }}">Operational</a>
        <a class="badge-pill" href="{{ route('dashboard.customer-service') }}" style="text-decoration:none;padding:6px 12px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $role === 'customer-service' ? '#0f1f3d' : '#f1f5fa' }};color:{{ $role === 'customer-service' ? '#fff' : '#475569' }}">Customer Service</a>
    </div>
</div>
@endif

<div class="page-heading">
    <div>
        <p class="eyebrow">{{ $dashboardMeta['eyebrow'] ?? 'RINGKASAN WORKSPACE' }}</p>
        <h1>{{ $roleTitle ?? 'Dashboard' }}</h1>
        <p>{{ $roleDescription ?? 'Pantau pekerjaan dan performa operasional dalam satu tempat.' }}</p>
    </div>
    <span class="date-chip"><x-icon name="calendar"/>{{ now()->locale('id')->translatedFormat('d F Y') }}</span>
</div>

@include('dashboard.partials.charts')

<x-menu-banner
    :tag="strtoupper($roleTitle ?? 'WORKSPACE')"
    :title="'Halo, ' . auth()->user()->name . '!'"
    :description="$roleDescription ?? 'Kelola pekerjaan dengan rapi. Kenali biaya, tagihan, dan profit setiap job.'"
    action-url="#workflow"
    action-label="Lihat alur pekerjaan"
    action-icon="arrow"
    icon="briefcase"
    art-title="Setiap pekerjaan,"
    art-subtitle="lebih terukur."
/>

{{-- STATS UTAMA PER ROLE --}}
<div class="section-heading"><h2>Ringkasan pekerjaan</h2><span class="subtle">Seluruh periode</span></div>
<div class="stats-grid">
    <article class="stat-card"><div class="stat-top"><span>Job Open</span><span class="stat-icon blue"><x-icon name="briefcase"/></span></div><strong class="stat-number">{{ $openJobs }}</strong><p>Pekerjaan yang sedang berjalan</p></article>
    <article class="stat-card"><div class="stat-top"><span>Job Closed</span><span class="stat-icon green"><x-icon name="check"/></span></div><strong class="stat-number">{{ $closedJobs }}</strong><p>Pekerjaan yang telah diselesaikan</p></article>

    @if(in_array($role, ['finance', 'finance-manager', 'super-admin']))
        <article class="stat-card"><div class="stat-top"><span>Piutang Customer</span><span class="stat-icon amber"><x-icon name="wallet"/></span></div><strong class="stat-number"><small>Rp</small> {{ \App\Support\Money::format($receivableBalance) }}</strong><p>Total sisa tagihan belum lunas</p></article>
        <article class="stat-card"><div class="stat-top"><span>Profit Job</span><span class="stat-icon purple"><x-icon name="chart"/></span></div><strong class="stat-number"><small>Rp</small> {{ \App\Support\Money::format($profitBalance) }}</strong><p>Nilai jual dikurangi modal provision</p></article>
    @elseif($role === 'operational')
        <article class="stat-card"><div class="stat-top"><span>Job Biaya Draft</span><span class="stat-icon amber"><x-icon name="wallet"/></span></div><strong class="stat-number">{{ $costProgress['draft'] ?? 0 }}</strong><p>Job dengan biaya yang belum final</p></article>
        <article class="stat-card"><div class="stat-top"><span>Biaya Siap Closing</span><span class="stat-icon green"><x-icon name="check"/></span></div><strong class="stat-number">{{ $costProgress['final'] ?? 0 }}</strong><p>Job yang semua biayanya sudah final</p></article>
    @elseif($role === 'customer-service')
        <article class="stat-card"><div class="stat-top"><span>Job Mendekat Tiba</span><span class="stat-icon amber"><x-icon name="clock"/></span></div><strong class="stat-number">{{ $arrivalSoon->count() }}</strong><p>ETA dalam 14 hari ke depan</p></article>
        <article class="stat-card"><div class="stat-top"><span>Job Terbuka Saya</span><span class="stat-icon purple"><x-icon name="briefcase"/></span></div><strong class="stat-number">{{ $widgets['myOpenJobs'] ?? 0 }}</strong><p>Job aktif yang ditangani CS</p></article>
    @else
        <article class="stat-card"><div class="stat-top"><span>Quotation Aktif</span><span class="stat-icon amber"><x-icon name="file"/></span></div><strong class="stat-number">{{ $widgets['quotes30d'] ?? 0 }}</strong><p>Penawaran 30 hari terakhir</p></article>
        <article class="stat-card"><div class="stat-top"><span>Quotation Disetujui</span><span class="stat-icon green"><x-icon name="check"/></span></div><strong class="stat-number">{{ $widgets['quotes']['approved'] ?? 0 }}</strong><p>Siap dikonversi ke Job Order</p></article>
    @endif
</div>

@if(in_array($role, ['finance', 'finance-manager', 'super-admin']))
<div class="finance-strip">
    @foreach(['Temporary Final (Job Open)'=>$temporaryBalance, 'Provision Final (Job Open)'=>$provisionBalance, 'Pendapatan'=>$revenueBalance, 'HPP'=>$cogsBalance] as $label=>$amount)
    <div><span>{{ $label }}</span><strong>Rp {{ \App\Support\Money::format($amount) }}</strong></div>
    @endforeach
</div>
@endif

{{-- KURS MINGGUAN WIDGET (Finance, Finance Manager, Sales Manager, Sales, Super Admin) --}}
@if(in_array($role, ['finance', 'finance-manager', 'sales-manager', 'sales', 'super-admin']))
<div class="section-heading">
    <h2>Kurs Mingguan Aktif</h2>
    @if(isset($weeklyPricing) && $weeklyPricing)
    <span class="subtle">Periode {{ $weeklyPricing->effective_date?->format('d/m/Y') }} s/d {{ $weeklyPricing->effective_until?->format('d/m/Y') }}</span>
    @else
    <span class="subtle">Acuan konversi mata uang asing</span>
    @endif
</div>
<section class="panel" style="margin-bottom:20px">
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Mata Uang</th>
                    <th>Kurs (IDR)</th>
                    <th>Layanan / Keterangan</th>
                    <th>Periode Berlaku</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($weeklyRates ?? collect() as $rate)
                <tr>
                    <td><strong>{{ $rate->currency }}</strong></td>
                    <td class="money"><strong>Rp {{ \App\Support\Money::format((string) $rate->exchange_rate) }}</strong></td>
                    <td>{{ $rate->service ?? 'Semua Layanan' }}</td>
                    <td>{{ $rate->effective_date?->format('d/m/Y') }} - {{ $rate->effective_until?->format('d/m/Y') }}</td>
                    <td><span class="status-badge" style="background:#ddf8ec;color:#1e9d75">Aktif</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:18px;color:#8a98b0">Belum ada kurs mingguan aktif yang ditetapkan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif

{{-- PIPELINE QUOTATION (Sales Manager, Sales, Super Admin) --}}
@if(in_array($role, ['sales', 'sales-manager', 'super-admin']) && isset($widgets['quotes']))
<div class="section-heading">
    <h2>Pipeline quotation</h2>
    <div style="display:flex;gap:12px;align-items:center">
        <span class="subtle">{{ $widgets['quotes30d'] ?? 0 }} penawaran dalam 30 hari terakhir</span>
        @can('quotations.manage')
        <a class="button button-primary" href="{{ route('quotations.create') }}" style="padding:6px 12px;font-size:10px">+ Buat Quote Baru</a>
        @endcan
    </div>
</div>
<div class="stats-grid">
@foreach([['draft','Draft','blue','Belum diajukan'],['submitted','Diajukan','amber','Menunggu persetujuan'],['revision','Revisi','amber','Perlu perbaikan'],['approved','Disetujui','green','Siap dikonversi'],['converted','Dikonversi','purple','Sudah jadi Job Order'],['rejected','Ditolak','red','Perlu tindak lanjut']] as [$value,$label,$color,$caption])
<article class="stat-card"><div class="stat-top"><span>{{ $label }}</span><span class="stat-icon {{ $color }}"><x-icon name="file"/></span></div><strong class="stat-number">{{ $widgets['quotes'][$value] ?? 0 }}</strong><p>{{ $caption }}</p></article>
@endforeach
</div>

{{-- QUOTATION MENUNGGU PERSETUJUAN (Khusus Sales Manager & Super Admin) --}}
@if(in_array($role, ['sales-manager', 'super-admin']) && isset($widgets['submittedQuotes']))
<section class="panel" style="margin-bottom:20px;margin-top:16px">
    <div class="panel-heading">
        <div>
            <h2>Draft Quote Menunggu Persetujuan</h2>
            <p>Quotation yang diajukan oleh Sales dan perlu diapprove.</p>
        </div>
        <span class="count-badge">{{ $widgets['submittedQuotes']->count() }}</span>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Nomor</th>
                    <th>Subject</th>
                    <th>Customer</th>
                    <th>Sales</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($widgets['submittedQuotes'] as $quote)
                <tr>
                    <td><a class="text-link" href="{{ route('quotations.show', $quote) }}"><strong>{{ $quote->number }}</strong></a></td>
                    <td>{{ Str::limit($quote->subject, 35) }}</td>
                    <td>{{ $quote->customer_snapshot['name'] ?? $quote->customer?->name ?? '—' }}</td>
                    <td>{{ $quote->creator?->name ?? '—' }}</td>
                    <td>{{ $quote->quotation_date?->format('d/m/Y') }}</td>
                    <td><a class="text-link" href="{{ route('quotations.show', $quote) }}">Review & Approve →</a></td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:18px">Tidak ada draft quote yang menunggu persetujuan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif

{{-- QUOTATION SAYA (Khusus Sales) --}}
@if(in_array($role, ['sales', 'super-admin']) && isset($widgets['myQuotes']))
<section class="panel" style="margin-bottom:20px;margin-top:16px">
    <div class="panel-heading">
        <div><h2>Quotation saya</h2><p>Tawaran terbaru yang Anda buat.</p></div>
        <a class="text-link" href="{{ route('quotations.index') }}">Semua quotation</a>
    </div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Nomor</th><th>Subject</th><th>Customer</th><th>Status</th><th>Dibuat</th></tr></thead>
            <tbody>
                @forelse($widgets['myQuotes'] as $quote)
                <tr>
                    <td><a class="text-link" href="{{ route('quotations.show', $quote) }}">{{ $quote->number }}</a></td>
                    <td>{{ Str::limit($quote->subject,35) }}</td>
                    <td>{{ $quote->customer_snapshot['name'] ?? '—' }}</td>
                    <td><span class="status-badge status-{{ $quote->status->value }}">{{ $quote->status->label() }}</span></td>
                    <td>{{ $quote->created_at->format('d/m/Y') }}</td>
                </tr>
                @empty<tr><td colspan="5">Belum ada quotation.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif
@endif

{{-- JOB BELUM FINAL (Operational & Super Admin) --}}
@if(in_array($role, ['operational', 'super-admin']) && isset($unfinishedJobs))
<section class="panel" style="margin-bottom:20px">
    <div class="panel-heading">
        <div><h2>Job belum final</h2><p>Job yang masih memiliki biaya berstatus Draft.</p></div>
        <span class="count-badge">{{ $unfinishedJobs->count() }}</span>
    </div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Job</th><th>Subject</th><th>Status biaya</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($unfinishedJobs as $job)
                <tr>
                    <td><a class="text-link" href="{{ route('jobs.show',$job) }}"><strong>{{ $job->number }}</strong></a></td>
                    <td>{{ $job->subject }}</td>
                    <td><span class="status-badge status-draft">Biaya Draft</span></td>
                    <td><a class="text-link" href="{{ route('jobs.costs.index', $job) }}">Input / Finalisasi Biaya →</a></td>
                </tr>
                @empty
                <tr><td colspan="4">Semua biaya job sudah final.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif

{{-- JOB MENDEKATI TIBA (Customer Service & Super Admin) --}}
@if(in_array($role, ['customer-service', 'super-admin']) && isset($arrivalSoon))
<section class="panel" style="margin-bottom:20px">
    <div class="panel-heading">
        <div><h2>Job mendekati tiba</h2><p>Perkiraan kedatangan (ETA) dalam 14 hari ke depan.</p></div>
        <span class="count-badge">{{ $arrivalSoon->count() }}</span>
    </div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Job</th><th>Customer</th><th>Subject</th><th>ETA</th><th>Konfirmasi DO</th></tr></thead>
            <tbody>
                @forelse($arrivalSoon as $job)
                <tr>
                    <td><a class="text-link" href="{{ route('jobs.show',$job) }}"><strong>{{ $job->number }}</strong></a></td>
                    <td>{{ $job->quotation_snapshot['customer']['name'] ?? $job->customer?->name ?? '—' }}</td>
                    <td>{{ $job->subject }}</td>
                    <td><strong>{{ $job->eta ? $job->eta->format('d/m/Y') : '—' }}</strong></td>
                    <td>
                        @if($job->do_confirmed_at)
                            <span class="status-badge" style="background:#ddf8ec;color:#1e9d75">DO Selesai</span>
                        @else
                            <form method="POST" action="{{ route('jobs.confirm-do', $job) }}" style="display:inline">
                                @csrf
                                <input type="hidden" name="lock_version" value="{{ $job->lock_version }}">
                                <button type="submit" class="button button-primary" style="padding:4px 9px;font-size:10px">Konfirmasi DO Selesai</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5">Tidak ada job yang mendekati tiba.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif

{{-- PENGIRIMAN BERJALAN & ESTIMASI (Operational, CS, Finance, Super Admin) --}}
@if(isset($widgets['shipment']))
<div class="section-heading">
    <h2>Pengiriman berjalan</h2>
    <span class="subtle">Job Order berstatus open menurut status pengiriman
    @if(isset($widgets['myOpenJobs']) && $widgets['myOpenJobs'] > 0)
     · CS menangani {{ $widgets['myOpenJobs'] }} job terbuka
    @endif
    </span>
</div>
<section class="panel" style="margin-bottom:20px">
    <div class="filter-bar" style="justify-content:flex-start;border:none;padding:14px 23px;display:flex;flex-wrap:wrap;gap:8px">
        @foreach(config('operations.shipment_statuses') as $value=>$label)
        <span class="badge-pill status-{{ $value }}">{{ $label }} · {{ $widgets['shipment'][$value] ?? 0 }}</span>
        @if(!$loop->last)<span class="text-link">→</span>@endif
        @endforeach
    </div>
</section>

<div class="dashboard-grid" style="margin-bottom:20px">
    <section class="panel">
        <div class="panel-heading"><div><h2>ETD mendatang</h2><p>Estimasi keberangkatan 14 hari ke depan.</p></div><span class="count-badge">{{ $widgets['etdSoon']->count() }}</span></div>
        <div class="table-scroll">
            <table><thead><tr><th>Job</th><th>Estimasi</th><th>Subject</th></tr></thead>
            <tbody>
                @forelse($widgets['etdSoon'] as $job)
                <tr><td><a class="text-link" href="{{ route('jobs.show',$job) }}">{{ $job->number }}</a></td><td>{{ $job->etd ? $job->etd->format('d/m/Y') : '—' }}</td><td>{{ Str::limit($job->subject,35) }}</td></tr>
                @empty<tr><td colspan="3">Tidak ada ETD dalam 14 hari ke depan.</td></tr>
                @endforelse
            </tbody>
            </table>
        </div>
    </section>
    <section class="panel">
        <div class="panel-heading"><div><h2>ETA tiba</h2><p>Estimasi kedatangan 7 hari ke depan.</p></div><span class="count-badge">{{ $widgets['etaSoon']->count() }}</span></div>
        <div class="table-scroll">
            <table><thead><tr><th>Job</th><th>Estimasi</th><th>Subject</th></tr></thead>
            <tbody>
                @forelse($widgets['etaSoon'] as $job)
                <tr><td><a class="text-link" href="{{ route('jobs.show',$job) }}">{{ $job->number }}</a></td><td>{{ $job->eta ? $job->eta->format('d/m/Y') : '—' }}</td><td>{{ Str::limit($job->subject,35) }}</td></tr>
                @empty<tr><td colspan="3">Tidak ada ETA dalam 7 hari ke depan.</td></tr>
                @endforelse
            </tbody>
            </table>
        </div>
    </section>
</div>
@endif

{{-- JOB TERBARU & ALUR PEKERJAAN --}}
<div class="dashboard-grid" style="margin-bottom:20px">
    <section class="panel">
        <div class="panel-heading"><div><h2>Job terbaru</h2><p>{{ $draftJobs }} pekerjaan masih berstatus Draft.</p></div><span class="count-badge">{{ $recentJobs->count() }} terbaru</span></div>
        <div class="table-scroll">
            <table><thead><tr><th>Nomor Job</th><th>Customer</th><th>Status</th><th>Dibuat</th></tr></thead>
            <tbody>
                @foreach($recentJobs as $job)
                <tr><td>@can('jobs.manage')<a class="text-link" href="{{ route('jobs.show',$job) }}">{{ $job->number }}</a>@else{{ $job->number }}@endcan</td><td>{{ $job->quotation_snapshot['customer']['name'] ?? $job->customer?->name ?? '—' }}</td><td><span class="status-badge">{{ ucfirst($job->status) }}</span></td><td>{{ $job->created_at->format('d/m/Y') }}</td></tr>
                @endforeach
            </tbody>
            </table>
        </div>
        @if($recentJobs->isEmpty())
        <div class="empty-state"><span class="empty-icon"><x-icon name="briefcase"/></span><h3>Belum ada pekerjaan</h3><p>Job akan muncul setelah quotation<br>dikonversi menjadi Job Order.</p></div>
        @endif
    </section>
    <section class="panel" id="workflow">
        <div class="panel-heading"><div><h2>Alur pekerjaan</h2><p>Dari penawaran hingga profit.</p></div><x-icon name="arrow"/></div>
        <ol class="workflow">
            @foreach([['Quotation','Susun penawaran untuk customer.'],['Job Order & Biaya','Catat temporary dan provision.'],['Closing & Invoice','Finalisasi job dan tagihan.'],['Pembayaran & Laporan','Pantau piutang dan profit job.']] as [$title,$text])
            <li><span>{{ $loop->iteration }}</span><div><h3>{{ $title }}</h3><p>{{ $text }}</p></div></li>
            @endforeach
        </ol>
    </section>
</div>

{{-- INFORMASI KEUANGAN & INVOICE JATUH TEMPO (Finance, Finance Manager, Super Admin) --}}
@if(in_array($role, ['finance', 'finance-manager', 'super-admin']))
    @if(isset($widgets['overdueReceivables']) && ($widgets['overdueReceivables']['count'] > 0 || $widgets['pendingReimbursements'] > 0 || $widgets['journalsThisMonth'] > 0))
    <div class="section-heading"><h2>Informasi Invoice & Jatuh Tempo</h2><span class="subtle">Tagihan dan rekonsiliasi</span></div>
    <div class="stats-grid">
        <article class="stat-card"><div class="stat-top"><span>Piutang jatuh tempo</span><span class="stat-icon amber"><x-icon name="wallet"/></span></div><strong class="stat-number"><small>Rp</small> {{ \App\Support\Money::format($widgets['overdueReceivables']['amount']) }}</strong><p>{{ $widgets['overdueReceivables']['count'] }} invoice melewati jatuh tempo</p></article>
        <article class="stat-card"><div class="stat-top"><span>Reimbursement menunggu</span><span class="stat-icon blue"><x-icon name="file"/></span></div><strong class="stat-number">{{ $widgets['pendingReimbursements'] }}</strong><p>Permintaan reimbursement pending yang perlu diproses</p></article>
        <article class="stat-card"><div class="stat-top"><span>Jurnal bulan ini</span><span class="stat-icon green"><x-icon name="chart"/></span></div><strong class="stat-number">{{ $widgets['journalsThisMonth'] }}</strong><p>Jurnal ter-posting sejak awal bulan</p></article>
    </div>
    @endif

    @if(isset($widgets['topJobs']) && $widgets['topJobs']->isNotEmpty())
    <section class="panel" style="margin-bottom:20px;margin-top:20px">
        <div class="panel-heading"><div><h2>Job paling menguntungkan</h2><p>Berdasarkan profit closing.</p></div><a class="text-link" href="{{ route('reports.profit-per-job') }}">Profit per Job</a></div>
        <div class="table-scroll">
            <table><thead><tr><th>Job</th><th>Subject</th><th>Closing</th><th class="money">Profit</th></tr></thead>
            <tbody>
                @foreach($widgets['topJobs'] as $job)
                <tr><td><a class="text-link" href="{{ route('jobs.show', $job) }}"><strong>{{ $job->number }}</strong></a></td><td>{{ Str::limit($job->subject,35) }}</td><td>{{ $job->closingSnapshot->closing_date->format('d/m/Y') }}</td><td class="money">Rp {{ \App\Support\Money::format((string) $job->closingSnapshot->profit) }}</td></tr>
                @endforeach
            </tbody>
            </table>
        </div>
    </section>
    @endif

    <div class="dashboard-grid bottom-grid">
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
            <div class="panel-heading"><div><h2>Invoice belum lunas</h2><p>Tagihan yang perlu ditindaklanjuti.</p></div><span class="count-badge">{{ $unpaidInvoices->count() }}</span></div>
            <div class="table-scroll">
                <table><thead><tr><th>Invoice</th><th>Jatuh tempo</th><th class="money">Sisa</th></tr></thead>
                <tbody>
                    @forelse($unpaidInvoices as $invoice)
                    <tr><td><a class="text-link" href="{{ route('invoices.show',$invoice) }}">{{ $invoice->number }}</a></td><td>{{ $invoice->due_date->format('d/m/Y') }}</td><td class="money">Rp {{ \App\Support\Money::format($invoice->balance) }}</td></tr>
                    @empty<tr><td colspan="3">Belum ada invoice terbuka.</td></tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </section>
    </div>
@endif

{{-- WIDGET ADMIN SISTEM (Super Admin) --}}
@if($role === 'super-admin' && isset($widgets['admin']))
<div class="section-heading"><h2>Administrasi & Pengguna</h2><span class="subtle">Pengawasan sistem</span></div>
<div class="stats-grid">
    <article class="stat-card"><div class="stat-top"><span>Pengguna sistem</span><span class="stat-icon purple"><x-icon name="users"/></span></div><strong class="stat-number">{{ $widgets['admin']['users'] }}</strong><p>Akun aktif beserta perannya</p></article>
    <article class="stat-card"><div class="stat-top"><span>Aktivitas 7 hari</span><span class="stat-icon blue"><x-icon name="file"/></span></div><strong class="stat-number">{{ $widgets['admin']['activity7d'] }}</strong><p>Aktivitas tercatat pada audit log</p></article>
</div>
@endif

<p class="demo-note">Ringkasan dashboard menampilkan informasi sesuai role dan tanggung jawab pengguna.</p>
@endsection
