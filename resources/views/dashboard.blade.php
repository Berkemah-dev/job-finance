@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="page-heading"><div><p class="eyebrow">RINGKASAN WORKSPACE</p><h1>Dashboard</h1><p>Pantau pekerjaan dan keuangan dalam satu tempat.</p></div><span class="date-chip"><x-icon name="calendar"/>{{ now()->locale('id')->translatedFormat('d F Y') }}</span></div>
<section class="welcome-banner">
    <div><span class="banner-tag"><span class="status-dot"></span> WORKSPACE JOBFINANCE</span><h2>Halo, {{ auth()->user()->name }}!</h2><p>Kelola pekerjaan dengan rapi.<br>Kenali biaya, tagihan, dan profit setiap job.</p><a href="#workflow" class="button button-white">Lihat alur pekerjaan <x-icon name="arrow"/></a></div>
    <div class="banner-art" aria-hidden="true"><div class="art-orbit"></div><div class="art-card"><span class="art-icon"><x-icon name="briefcase"/></span><span>Setiap pekerjaan,<br><strong>lebih terukur.</strong></span><div class="art-bars"><i></i><i></i><i></i><i></i><i></i></div><span class="art-check"><x-icon name="check"/></span></div></div>
</section>
@if($role === 'super-admin')
<div class="section-heading"><h2>Ringkasan pekerjaan</h2><span class="subtle">Seluruh periode</span></div>
<div class="stats-grid">
@foreach([['Job Open','briefcase','blue','Pekerjaan yang sedang berjalan'],['Job Closed','check','green','Pekerjaan yang telah diselesaikan']] as [$label,$icon,$color,$caption])
<article class="stat-card"><div class="stat-top"><span>{{ $label }}</span><span class="stat-icon {{ $color }}"><x-icon :name="$icon"/></span></div><strong class="stat-number">{{ $label==='Job Open'?$openJobs:$closedJobs }}</strong><p>{{ $caption }}</p></article>
@endforeach
@can('financial.view')
@foreach([['Piutang Customer','wallet','amber','Tagihan yang belum dibayar',$receivableBalance],['Profit Job','chart','purple','Nilai jual dikurangi modal provision',$profitBalance]] as [$label,$icon,$color,$caption,$amount])
<article class="stat-card"><div class="stat-top"><span>{{ $label }}</span><span class="stat-icon {{ $color }}"><x-icon :name="$icon"/></span></div><strong class="stat-number"><small>Rp</small> {{ \App\Support\Money::format($amount) }}</strong><p>{{ $caption }}</p></article>
@endforeach
@endcan
</div>
@can('financial.view')
<div class="finance-strip">@foreach(['Temporary Final (Job Open)'=>$temporaryBalance,'Provision Final (Job Open)'=>$provisionBalance,'Pendapatan'=>$revenueBalance,'HPP'=>$cogsBalance] as $label=>$amount)<div><span>{{ $label }}</span><strong>Rp {{ \App\Support\Money::format($amount) }}</strong></div>@endforeach</div>
@endcan
<div class="dashboard-grid">
<section class="panel"><div class="panel-heading"><div><h2>Job terbaru</h2><p>{{ $draftJobs }} pekerjaan masih berstatus Draft.</p></div><span class="count-badge">{{ $recentJobs->count() }} terbaru</span></div><div class="table-scroll"><table><thead><tr><th>Nomor Job</th><th>Customer</th><th>Status</th><th>Dibuat</th></tr></thead><tbody>@foreach($recentJobs as $job)<tr><td>@can('jobs.manage')<a class="text-link" href="{{ route('jobs.show',$job) }}">{{ $job->number }}</a>@else{{ $job->number }}@endcan</td><td>{{ $job->quotation_snapshot['customer']['name'] }}</td><td><span class="status-badge">{{ ucfirst($job->status) }}</span></td><td>{{ $job->created_at->format('d/m/Y') }}</td></tr>@endforeach</tbody></table></div>@if($recentJobs->isEmpty())<div class="empty-state"><span class="empty-icon"><x-icon name="briefcase"/></span><h3>Belum ada pekerjaan</h3><p>Job akan muncul setelah quotation<br>dikonversi menjadi Job Order.</p></div>@endif</section>
<section class="panel" id="workflow"><div class="panel-heading"><div><h2>Alur pekerjaan</h2><p>Dari penawaran hingga profit.</p></div><x-icon name="arrow"/></div><ol class="workflow">@foreach([['Quotation','Susun penawaran untuk customer.'],['Job Order & Biaya','Catat temporary dan provision.'],['Closing & Invoice','Finalisasi job dan tagihan.'],['Pembayaran & Laporan','Pantau piutang dan profit job.']] as [$title,$text])<li><span>{{ $loop->iteration }}</span><div><h3>{{ $title }}</h3><p>{{ $text }}</p></div></li>@endforeach</ol></section>
</div>
@if(isset($widgets['quotes']))
<div class="section-heading"><h2>Pipeline quotation</h2><span class="subtle">{{ $widgets['quotes30d'] }} penawaran dalam 30 hari terakhir</span></div>
<div class="stats-grid">
@foreach([['draft','Draft','blue','Belum diajukan'],['submitted','Diajukan','amber','Menunggu persetujuan'],['revision','Revisi','amber','Perlu perbaikan'],['approved','Disetujui','green','Siap dikonversi'],['converted','Dikonversi','purple','Sudah jadi Job Order'],['rejected','Ditolak','red','Perlu tindak lanjut']] as [$value,$label,$color,$caption])
<article class="stat-card"><div class="stat-top"><span>{{ $label }}</span><span class="stat-icon {{ $color }}"><x-icon name="file"/></span></div><strong class="stat-number">{{ $widgets['quotes'][$value] ?? 0 }}</strong><p>{{ $caption }}</p></article>
@endforeach
</div>
@if(isset($widgets['myQuotes']))
<section class="panel"><div class="panel-heading"><div><h2>Quotation saya</h2><p>Tawaran terbaru yang Anda buat.</p></div><a class="text-link" href="{{ route('quotations.index') }}">Semua quotation</a></div><div class="table-scroll"><table><thead><tr><th>Nomor</th><th>Subject</th><th>Customer</th><th>Status</th><th>Dibuat</th></tr></thead><tbody>@forelse($widgets['myQuotes'] as $quote)<tr><td>{{ $quote->number }}</td><td>{{ Str::limit($quote->subject,35) }}</td><td>{{ $quote->customer_snapshot['name'] ?? '—' }}</td><td><span class="status-badge status-{{ $quote->status->value }}">{{ $quote->status->label() }}</span></td><td>{{ $quote->created_at->format('d/m/Y') }}</td></tr>@empty<tr><td colspan="5">Belum ada quotation.</td></tr>@endforelse</tbody></table></div></section>
@endif
@endif
@if(isset($widgets['shipment']))
<div class="section-heading"><h2>Pengiriman berjalan</h2><span class="subtle">Job Order berstatus open menurut status pengiriman</span>
@if($widgets['myOpenJobs'] > 0)
<span class="subtle"> · CS menangani {{ $widgets['myOpenJobs'] }} job terbuka</span>
@endif
</div>
<section class="panel"><div class="filter-bar" style="justify-content:flex-start;border:none;padding:14px 23px">@foreach(config('operations.shipment_statuses') as $value=>$label)<span class="badge-pill status-{{ $value }}">{{ $label }} · {{ $widgets['shipment'][$value] ?? 0 }}</span>@if(!$loop->last)<span class="text-link">→</span>@endif @endforeach</div></section>
<div class="dashboard-grid">
<section class="panel"><div class="panel-heading"><div><h2>ETD mendatang</h2><p>Estimasi keberangkatan 14 hari ke depan.</p></div><span class="count-badge">{{ $widgets['etdSoon']->count() }}</span></div><div class="table-scroll"><table><thead><tr><th>Job</th><th>Estimasi</th><th>Subject</th></tr></thead><tbody>@forelse($widgets['etdSoon'] as $job)<tr><td>{{ $job->number }}</td><td>{{ $job->etd->format('d/m/Y') }}</td><td>{{ Str::limit($job->subject,35) }}</td></tr>@empty<tr><td colspan="3">Tidak ada ETD dalam 14 hari ke depan.</td></tr>@endforelse</tbody></table></div></section>
<section class="panel"><div class="panel-heading"><div><h2>ETA tiba</h2><p>Estimasi kedatangan 7 hari ke depan.</p></div><span class="count-badge">{{ $widgets['etaSoon']->count() }}</span></div><div class="table-scroll"><table><thead><tr><th>Job</th><th>Estimasi</th><th>Subject</th></tr></thead><tbody>@forelse($widgets['etaSoon'] as $job)<tr><td>{{ $job->number }}</td><td>{{ $job->eta->format('d/m/Y') }}</td><td>{{ Str::limit($job->subject,35) }}</td></tr>@empty<tr><td colspan="3">Tidak ada ETA dalam 7 hari ke depan.</td></tr>@endforelse</tbody></table></div></section>
</div>
@endif
@can('financial.view')
@if(isset($widgets['overdueReceivables']) && ($widgets['overdueReceivables']['count'] > 0 || $widgets['pendingReimbursements'] > 0 || $widgets['journalsThisMonth'] > 0))
<div class="stats-grid">
<article class="stat-card"><div class="stat-top"><span>Piutang jatuh tempo</span><span class="stat-icon amber"><x-icon name="wallet"/></span></div><strong class="stat-number"><small>Rp</small> {{ \App\Support\Money::format($widgets['overdueReceivables']['amount']) }}</strong><p>{{ $widgets['overdueReceivables']['count'] }} invoice melewati jatuh tempo</p></article>
<article class="stat-card"><div class="stat-top"><span>Reimbursement menunggu</span><span class="stat-icon blue"><x-icon name="file"/></span></div><strong class="stat-number">{{ $widgets['pendingReimbursements'] }}</strong><p>Permintaan reimbursement pending yang perlu diproses</p></article>
<article class="stat-card"><div class="stat-top"><span>Jurnal bulan ini</span><span class="stat-icon green"><x-icon name="chart"/></span></div><strong class="stat-number">{{ $widgets['journalsThisMonth'] }}</strong><p>Jurnal ter-posting sejak awal bulan</p></article>
</div>
@endif
@if(isset($widgets['topJobs']) && $widgets['topJobs']->isNotEmpty())
<section class="panel"><div class="panel-heading"><div><h2>Job paling menguntungkan</h2><p>Berdasarkan profit closing.</p></div><a class="text-link" href="{{ route('reports.profit-per-job') }}">Profit per Job</a></div><div class="table-scroll"><table><thead><tr><th>Job</th><th>Subject</th><th>Closing</th><th class="money">Profit</th></tr></thead><tbody>@foreach($widgets['topJobs'] as $job)<tr><td>{{ $job->number }}</td><td>{{ Str::limit($job->subject,35) }}</td><td>{{ $job->closingSnapshot->closing_date->format('d/m/Y') }}</td><td class="money">Rp {{ \App\Support\Money::format((string) $job->closingSnapshot->profit) }}</td></tr>@endforeach</tbody></table></div></section>
@endif
@endcan
@if(isset($widgets['admin']))
<div class="stats-grid">
<article class="stat-card"><div class="stat-top"><span>Pengguna sistem</span><span class="stat-icon purple"><x-icon name="users"/></span></div><strong class="stat-number">{{ $widgets['admin']['users'] }}</strong><p>Akun aktif beserta perannya</p></article>
<article class="stat-card"><div class="stat-top"><span>Aktivitas 7 hari</span><span class="stat-icon blue"><x-icon name="file"/></span></div><strong class="stat-number">{{ $widgets['admin']['activity7d'] }}</strong><p>Aktivitas tercatat pada audit log</p></article>
</div>
@endif
@can('financial.view')
<div class="dashboard-grid bottom-grid"><section class="panel"><div class="panel-heading"><div><h2>Pendapatan & profit</h2><p>Enam bulan terakhir dari job closed.</p></div><span class="count-badge">6 bulan</span></div>@php $chartMax=max(1,$monthlyPerformance->max(fn($r)=>(float)$r['revenue'])); @endphp<div class="monthly-chart">@foreach($monthlyPerformance as $month)<div class="month-column" title="Pendapatan Rp {{ \App\Support\Money::format($month['revenue']) }} · Profit Rp {{ \App\Support\Money::format($month['profit']) }}"><div class="bar-pair"><i class="bar-revenue" style="height:{{ max(3,((float)$month['revenue']/$chartMax)*100) }}%"></i><i class="bar-profit" style="height:{{ max(3,((float)$month['profit']/$chartMax)*100) }}%"></i></div><span>{{ $month['label'] }}</span></div>@endforeach</div><div class="chart-legend"><span><i class="legend-revenue"></i>Pendapatan</span><span><i class="legend-profit"></i>Profit</span></div></section><section class="panel"><div class="panel-heading"><div><h2>Invoice belum lunas</h2><p>Tagihan yang perlu ditindaklanjuti.</p></div><span class="count-badge">{{ $unpaidInvoices->count() }}</span></div><div class="table-scroll"><table><thead><tr><th>Invoice</th><th>Jatuh tempo</th><th class="money">Sisa</th></tr></thead><tbody>@forelse($unpaidInvoices as $invoice)<tr><td><a class="text-link" href="{{ route('invoices.show',$invoice) }}">{{ $invoice->number }}</a></td><td>{{ $invoice->due_date->format('d/m/Y') }}</td><td class="money">Rp {{ \App\Support\Money::format($invoice->balance) }}</td></tr>@empty<tr><td colspan="3">Belum ada invoice terbuka.</td></tr>@endforelse</tbody></table></div></section></div>
@endcan
@else
<div class="section-heading"><h2>Ringkasan {{ $role === 'finance-manager' ? 'Finance Manager' : ucfirst(str_replace('-', ' ', $role)) }}</h2><span class="subtle">Informasi sesuai tanggung jawab Anda</span></div>
@if(in_array($role, ['finance', 'finance-manager'], true))
<div class="stats-grid">
@if($role === 'finance')
<article class="stat-card"><div class="stat-top"><span>Invoice belum lunas</span><span class="stat-icon amber"><x-icon name="wallet"/></span></div><strong class="stat-number">{{ $unpaidInvoices->count() }}</strong><p>Periksa tanggal jatuh tempo invoice.</p></article>
@else
<article class="stat-card"><div class="stat-top"><span>Profit Job</span><span class="stat-icon purple"><x-icon name="chart"/></span></div><strong class="stat-number"><small>Rp</small> {{ \App\Support\Money::format($profitBalance) }}</strong><p>Profit dari job yang sudah closing.</p></article>
<article class="stat-card"><div class="stat-top"><span>Pendapatan</span><span class="stat-icon green"><x-icon name="chart"/></span></div><strong class="stat-number"><small>Rp</small> {{ \App\Support\Money::format($revenueBalance) }}</strong><p>Total pendapatan dari closing.</p></article>
@endif
<article class="stat-card"><div class="stat-top"><span>Kurs Mingguan</span><span class="stat-icon blue"><x-icon name="chart"/></span></div><strong class="stat-number">{{ $weeklyPricing?->currency ?? '—' }}</strong><p>{{ $weeklyPricing ? \App\Support\Money::format($weeklyPricing->exchange_rate).' · berlaku '.optional($weeklyPricing->effective_date)->format('d/m/Y') : 'Belum tersedia' }}</p></article>
</div>
@elseif(in_array($role, ['sales', 'sales-manager'], true))
<div class="stats-grid"><article class="stat-card"><div class="stat-top"><span>Quotation Draft</span><span class="stat-icon blue"><x-icon name="file"/></span></div><strong class="stat-number">{{ $widgets['quotes']['draft'] ?? 0 }}</strong><p>Quotation yang belum disetujui.</p></article><article class="stat-card"><div class="stat-top"><span>Menunggu Persetujuan</span><span class="stat-icon amber"><x-icon name="file"/></span></div><strong class="stat-number">{{ ($widgets['quotes']['submitted'] ?? 0) + ($widgets['quotes']['revision'] ?? 0) }}</strong><p>Quotation yang perlu ditindaklanjuti.</p></article><article class="stat-card"><div class="stat-top"><span>Kurs Mingguan</span><span class="stat-icon blue"><x-icon name="chart"/></span></div><strong class="stat-number">{{ $weeklyPricing?->currency ?? '—' }}</strong><p>{{ $weeklyPricing ? \App\Support\Money::format($weeklyPricing->exchange_rate) : 'Belum tersedia' }}</p></article></div>
<section class="panel"><div class="panel-heading"><h2>Aksi cepat</h2><a class="button button-primary" href="{{ route('quotations.create') }}">Buat Quotation</a></div></section>
@elseif($role === 'operational')
<section class="panel"><div class="panel-heading"><div><h2>Job belum final</h2><p>Job yang masih memiliki biaya Draft.</p></div><span class="count-badge">{{ $unfinishedJobs->count() }}</span></div><div class="table-scroll"><table><thead><tr><th>Job</th><th>Subject</th><th>Status biaya</th></tr></thead><tbody>@forelse($unfinishedJobs as $job)<tr><td><a class="text-link" href="{{ route('jobs.show',$job) }}">{{ $job->number }}</a></td><td>{{ $job->subject }}</td><td>Belum final</td></tr>@empty<tr><td colspan="3">Semua biaya job sudah final.</td></tr>@endforelse</tbody></table></div></section>
@elseif($role === 'customer-service')
<section class="panel"><div class="panel-heading"><div><h2>Job mendekati tiba</h2><p>ETA dalam 14 hari ke depan.</p></div><span class="count-badge">{{ $arrivalSoon->count() }}</span></div><div class="table-scroll"><table><thead><tr><th>Job</th><th>Subject</th><th>ETA</th></tr></thead><tbody>@forelse($arrivalSoon as $job)<tr><td><a class="text-link" href="{{ route('jobs.show',$job) }}">{{ $job->number }}</a></td><td>{{ $job->subject }}</td><td>{{ $job->eta->format('d/m/Y') }}</td></tr>@empty<tr><td colspan="3">Tidak ada job yang mendekati tiba.</td></tr>@endforelse</tbody></table></div></section>
@endif
@endif
<p class="demo-note">Ringkasan dashboard menampilkan informasi sesuai role dan tanggung jawab pengguna.</p>
@endsection
