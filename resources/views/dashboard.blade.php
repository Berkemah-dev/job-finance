@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="page-heading"><div><p class="eyebrow">RINGKASAN WORKSPACE</p><h1>Dashboard</h1><p>Pantau pekerjaan dan keuangan dalam satu tempat.</p></div><span class="date-chip"><x-icon name="calendar"/>{{ now()->locale('id')->translatedFormat('d F Y') }}</span></div>
<section class="welcome-banner">
    <div><span class="banner-tag"><span class="status-dot"></span> WORKSPACE JOBFINANCE</span><h2>Halo, {{ auth()->user()->name }}!</h2><p>Kelola pekerjaan dengan rapi.<br>Kenali biaya, tagihan, dan profit setiap job.</p><a href="#workflow" class="button button-white">Lihat alur pekerjaan <x-icon name="arrow"/></a></div>
    <div class="banner-art" aria-hidden="true"><div class="art-orbit"></div><div class="art-card"><span class="art-icon"><x-icon name="briefcase"/></span><span>Setiap pekerjaan,<br><strong>lebih terukur.</strong></span><div class="art-bars"><i></i><i></i><i></i><i></i><i></i></div><span class="art-check"><x-icon name="check"/></span></div></div>
</section>
<div class="section-heading"><h2>Ringkasan pekerjaan</h2><span class="subtle">Seluruh periode</span></div>
<div class="stats-grid">
@foreach([['Job Open','briefcase','blue','Pekerjaan yang sedang berjalan'],['Job Closed','check','green','Pekerjaan yang telah diselesaikan']] as [$label,$icon,$color,$caption])
<article class="stat-card"><div class="stat-top"><span>{{ $label }}</span><span class="stat-icon {{ $color }}"><x-icon :name="$icon"/></span></div><strong class="stat-number">{{ $label==='Job Open'?$openJobs:$closedJobs }}</strong><p>{{ $caption }}</p></article>
@endforeach
@can('reports.view')
@foreach([['Piutang Customer','wallet','amber','Tagihan yang belum dibayar',$receivableBalance],['Profit Job','chart','purple','Nilai jual dikurangi modal provision',$profitBalance]] as [$label,$icon,$color,$caption,$amount])
<article class="stat-card"><div class="stat-top"><span>{{ $label }}</span><span class="stat-icon {{ $color }}"><x-icon :name="$icon"/></span></div><strong class="stat-number"><small>Rp</small> {{ \App\Support\Money::format($amount) }}</strong><p>{{ $caption }}</p></article>
@endforeach
@endcan
</div>
@can('reports.view')
<div class="finance-strip">@foreach(['Temporary Final (Job Open)'=>$temporaryBalance,'Provision Final (Job Open)'=>$provisionBalance,'Pendapatan'=>$revenueBalance,'HPP'=>$cogsBalance] as $label=>$amount)<div><span>{{ $label }}</span><strong>Rp {{ \App\Support\Money::format($amount) }}</strong></div>@endforeach</div>
@endcan
<div class="dashboard-grid">
<section class="panel"><div class="panel-heading"><div><h2>Job terbaru</h2><p>{{ $draftJobs }} pekerjaan masih berstatus Draft.</p></div><span class="count-badge">{{ $recentJobs->count() }} terbaru</span></div><div class="table-scroll"><table><thead><tr><th>Nomor Job</th><th>Customer</th><th>Status</th><th>Dibuat</th></tr></thead><tbody>@foreach($recentJobs as $job)<tr><td>@can('jobs.manage')<a class="text-link" href="{{ route('jobs.show',$job) }}">{{ $job->number }}</a>@else{{ $job->number }}@endcan</td><td>{{ $job->quotation_snapshot['customer']['name'] }}</td><td><span class="status-badge">{{ ucfirst($job->status) }}</span></td><td>{{ $job->created_at->format('d/m/Y') }}</td></tr>@endforeach</tbody></table></div>@if($recentJobs->isEmpty())<div class="empty-state"><span class="empty-icon"><x-icon name="briefcase"/></span><h3>Belum ada pekerjaan</h3><p>Job akan muncul setelah quotation<br>dikonversi menjadi Job Order.</p></div>@endif</section>
<section class="panel" id="workflow"><div class="panel-heading"><div><h2>Alur pekerjaan</h2><p>Dari penawaran hingga profit.</p></div><x-icon name="arrow"/></div><ol class="workflow">@foreach([['Quotation','Susun penawaran untuk customer.'],['Job Order & Biaya','Catat temporary dan provision.'],['Closing & Invoice','Finalisasi job dan tagihan.'],['Pembayaran & Laporan','Pantau piutang dan profit job.']] as [$title,$text])<li><span>{{ $loop->iteration }}</span><div><h3>{{ $title }}</h3><p>{{ $text }}</p></div></li>@endforeach</ol></section>
</div>
@can('reports.view')
<div class="dashboard-grid bottom-grid"><section class="panel"><div class="panel-heading"><div><h2>Pendapatan & profit</h2><p>Enam bulan terakhir dari job closed.</p></div><span class="count-badge">6 bulan</span></div>@php $chartMax=max(1,$monthlyPerformance->max(fn($r)=>(float)$r['revenue'])); @endphp<div class="monthly-chart">@foreach($monthlyPerformance as $month)<div class="month-column" title="Pendapatan Rp {{ \App\Support\Money::format($month['revenue']) }} · Profit Rp {{ \App\Support\Money::format($month['profit']) }}"><div class="bar-pair"><i class="bar-revenue" style="height:{{ max(3,((float)$month['revenue']/$chartMax)*100) }}%"></i><i class="bar-profit" style="height:{{ max(3,((float)$month['profit']/$chartMax)*100) }}%"></i></div><span>{{ $month['label'] }}</span></div>@endforeach</div><div class="chart-legend"><span><i class="legend-revenue"></i>Pendapatan</span><span><i class="legend-profit"></i>Profit</span></div></section><section class="panel"><div class="panel-heading"><div><h2>Invoice belum lunas</h2><p>Tagihan yang perlu ditindaklanjuti.</p></div><span class="count-badge">{{ $unpaidInvoices->count() }}</span></div><div class="table-scroll"><table><thead><tr><th>Invoice</th><th>Jatuh tempo</th><th class="money">Sisa</th></tr></thead><tbody>@forelse($unpaidInvoices as $invoice)<tr><td><a class="text-link" href="{{ route('invoices.show',$invoice) }}">{{ $invoice->number }}</a></td><td>{{ $invoice->due_date->format('d/m/Y') }}</td><td class="money">Rp {{ \App\Support\Money::format($invoice->balance) }}</td></tr>@empty<tr><td colspan="3">Belum ada invoice terbuka.</td></tr>@endforelse</tbody></table></div></section></div>
@endcan
<p class="demo-note">Nilai penawaran merupakan estimasi. Ringkasan keuangan memakai biaya final, closing, dan pembayaran aktual.</p>
@endsection
