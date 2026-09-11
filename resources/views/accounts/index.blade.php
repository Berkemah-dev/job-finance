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
        @if($tab === 'coa')
        <a class="button button-secondary" href="{{ route('accounts.mappings') }}">
            <x-icon name="file"/> Mapping Akun
        </a>
        <a class="button button-primary" href="{{ route('accounts.create') }}">
            <x-icon name="plus"/> Tambah Akun
        </a>
        @endif
    </div>
</div>

<div class="tabs-container" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; border-bottom: 1px solid var(--border);">
    <a href="{{ route('accounts.index', ['tab' => 'coa']) }}" style="padding: 0.5rem 1rem; border-bottom: 2px solid {{ $tab === 'coa' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'coa' ? 'var(--primary)' : 'var(--text)' }}; font-weight: {{ $tab === 'coa' ? '600' : '400' }}; text-decoration: none;">Data COA</a>
    <a href="{{ route('accounts.index', ['tab' => 'charge']) }}" style="padding: 0.5rem 1rem; border-bottom: 2px solid {{ $tab === 'charge' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'charge' ? 'var(--primary)' : 'var(--text)' }}; font-weight: {{ $tab === 'charge' ? '600' : '400' }}; text-decoration: none;">Data Cost</a>
    <a href="{{ route('accounts.index', ['tab' => 'unit']) }}" style="padding: 0.5rem 1rem; border-bottom: 2px solid {{ $tab === 'unit' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'unit' ? 'var(--primary)' : 'var(--text)' }}; font-weight: {{ $tab === 'unit' ? '600' : '400' }}; text-decoration: none;">Data Unit</a>
    <a href="{{ route('accounts.index', ['tab' => 'port']) }}" style="padding: 0.5rem 1rem; border-bottom: 2px solid {{ $tab === 'port' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'port' ? 'var(--primary)' : 'var(--text)' }}; font-weight: {{ $tab === 'port' ? '600' : '400' }}; text-decoration: none;">Data Port</a>
</div>

@if($tab === 'coa')
    @include('accounts.partials.coa')
@elseif($tab === 'port')
    @include('accounts.partials.port')
@elseif($tab === 'charge')
    @include('accounts.partials.charge')
@elseif($tab === 'unit')
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
