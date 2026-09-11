@extends('layouts.app')
@section('title', 'Master Data')
@section('content')
<div class="page-heading">
    <div>
        <div class="coa-header-title">
            <x-icon name="database"/>
            <h1>Master Data</h1>
        </div>
        <p>Kelola data master (COA, Port, Jenis Biaya, Satuan) untuk sistem operasional.</p>
    </div>
    <div class="action-group">
        <span class="date-chip"><x-icon name="calendar"/>{{ now()->locale('id')->translatedFormat('d F Y') }}</span>
        @if(($tab ?? 'coa') === 'coa')
        <a class="button button-secondary" href="{{ route('accounts.mappings') }}">
            <x-icon name="file"/> Mapping Akun
        </a>
        <a class="button button-primary" href="{{ route('accounts.create') }}">
            <x-icon name="plus"/> Tambah Akun
        </a>
        @endif
    </div>
</div>

<x-menu-banner
    tag="AKUNTANSI"
    title="Master Data & Chart of Accounts"
    description="Struktur akun buku besar standar akuntansi, data pelabuhan, jenis biaya, dan satuan operasional."
    icon="database"
    art-title="Master Data,"
    art-subtitle="terstruktur rapi."
>
    @if(($tab ?? 'coa') === 'coa')
    <a class="button button-white" href="{{ route('accounts.mappings') }}" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.25);">
        <x-icon name="file"/> Mapping Akun
    </a>
    <a class="button button-white" href="{{ route('accounts.create') }}">
        <x-icon name="plus"/> Tambah Akun
    </a>
    @endif
</x-menu-banner>

<div class="tabs-container" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; border-bottom: 1px solid #e1e7f1;">
    <a href="{{ route('accounts.index', ['tab' => 'coa']) }}" style="padding: 0.5rem 1rem; border-bottom: 2px solid {{ ($tab ?? 'coa') === 'coa' ? '#0f1f3d' : 'transparent' }}; color: {{ ($tab ?? 'coa') === 'coa' ? '#0f1f3d' : '#64748b' }}; font-weight: {{ ($tab ?? 'coa') === 'coa' ? '600' : '400' }}; text-decoration: none;">Data COA</a>
    <a href="{{ route('accounts.index', ['tab' => 'charge']) }}" style="padding: 0.5rem 1rem; border-bottom: 2px solid {{ ($tab ?? 'coa') === 'charge' ? '#0f1f3d' : 'transparent' }}; color: {{ ($tab ?? 'coa') === 'charge' ? '#0f1f3d' : '#64748b' }}; font-weight: {{ ($tab ?? 'coa') === 'charge' ? '600' : '400' }}; text-decoration: none;">Data Cost</a>
    <a href="{{ route('accounts.index', ['tab' => 'unit']) }}" style="padding: 0.5rem 1rem; border-bottom: 2px solid {{ ($tab ?? 'coa') === 'unit' ? '#0f1f3d' : 'transparent' }}; color: {{ ($tab ?? 'coa') === 'unit' ? '#0f1f3d' : '#64748b' }}; font-weight: {{ ($tab ?? 'coa') === 'unit' ? '600' : '400' }}; text-decoration: none;">Data Unit</a>
    <a href="{{ route('accounts.index', ['tab' => 'port']) }}" style="padding: 0.5rem 1rem; border-bottom: 2px solid {{ ($tab ?? 'coa') === 'port' ? '#0f1f3d' : 'transparent' }}; color: {{ ($tab ?? 'coa') === 'port' ? '#0f1f3d' : '#64748b' }}; font-weight: {{ ($tab ?? 'coa') === 'port' ? '600' : '400' }}; text-decoration: none;">Data Port</a>
</div>

@if(($tab ?? 'coa') === 'coa')
    @include('accounts.partials.coa')
@elseif(($tab ?? 'coa') === 'port')
    @include('accounts.partials.port')
@elseif(($tab ?? 'coa') === 'charge')
    @include('accounts.partials.charge')
@elseif(($tab ?? 'coa') === 'unit')
    @include('accounts.partials.unit')
@endif

<style>
    /* Override pagination styling specifically for tabs */
    .pagination nav p {
        color: #000000 !important;
    }
    
    .pagination nav span[aria-current="page"] > span {
        background-color: #ffffff !important;
        color: #000000 !important;
        border-color: #000000 !important;
        font-weight: 800 !important;
    }
    
    .pagination nav a,
    .pagination nav button,
    .pagination nav span[aria-disabled="true"] > span {
        background-color: #ffffff !important;
        border-color: #000000 !important;
        color: #000000 !important;
    }
    
    .pagination nav svg {
        color: #000000 !important;
    }
</style>
@endsection
