@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('content')

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

@endsection
