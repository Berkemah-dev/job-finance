@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('content')
<div class="page-heading">
    <div>
        <div class="coa-header-title">
            <x-icon name="database"/>
            <h1>Chart of Accounts (COA)</h1>
        </div>
        <p>Kelola struktur akun buku besar dan bagan akun standar akuntansi.</p>
    </div>
    <div class="action-group">
        <span class="date-chip"><x-icon name="calendar"/>{{ now()->locale('id')->translatedFormat('d F Y') }}</span>
        <a class="button button-secondary" href="{{ route('accounts.mappings') }}">
            <x-icon name="file"/> Mapping Akun
        </a>
        <a class="button button-primary" href="{{ route('accounts.create') }}">
            <x-icon name="plus"/> Tambah Akun
        </a>
    </div>
</div>

<x-menu-banner
    tag="AKUNTANSI"
    title="Bagan Akun (Chart of Accounts)"
    description="Struktur akun buku besar standar akuntansi dan mapping akun otomatis untuk pencatatan transaksi jurnal."
    icon="database"
    art-title="Bagan Akun,"
    art-subtitle="terstruktur rapi."
>
    <a class="button button-white" href="{{ route('accounts.mappings') }}" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.25);">
        <x-icon name="file"/> Mapping Akun
    </a>
    <a class="button button-white" href="{{ route('accounts.create') }}">
        <x-icon name="plus"/> Tambah Akun
    </a>
</x-menu-banner>

@include('accounts.partials.coa')

<style>
    /* Pagination styling */
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
