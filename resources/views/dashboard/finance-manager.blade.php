@extends('layouts.app')
@section('title', 'Dashboard Finance Manager')
@section('content')
<div class="page-heading"><div><p class="eyebrow">WORKSPACE FINANCE MANAGER</p><h1>Dashboard Finance Manager</h1><p>Pantau pendapatan, profit job, dan kurs mingguan.</p></div></div>
<div class="stats-grid"><article class="stat-card"><span>Profit Job</span><strong class="stat-number"><small>Rp</small> {{ \App\Support\Money::format($profitBalance) }}</strong><p>Total profit dari job closing.</p></article><article class="stat-card"><span>Pendapatan</span><strong class="stat-number"><small>Rp</small> {{ \App\Support\Money::format($revenueBalance) }}</strong><p>Total pendapatan dari closing.</p></article><article class="stat-card"><span>Kurs Mingguan</span><strong class="stat-number">{{ $weeklyPricing?->currency ?? '—' }}</strong><p>{{ $weeklyPricing ? \App\Support\Money::format($weeklyPricing->exchange_rate) : 'Belum tersedia' }}</p></article></div>
<section class="panel"><div class="panel-heading"><div><h2>Akses laporan</h2><p>Gunakan laporan ini untuk analisis keuangan.</p></div></div><div class="form-actions"><a class="button button-primary" href="{{ route('reports.profit-per-job') }}">Profit per Job</a><a class="button button-secondary" href="{{ route('reports.profit-monthly') }}">Profit Bulanan</a></div></section>
@endsection
