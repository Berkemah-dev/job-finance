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
    <a class="button button-white" href="{{ route('accounts.template') }}" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.25);">
        ↓ Template Excel
    </a>
    <button class="button button-white" type="button" onclick="document.getElementById('coa-import-dialog').showModal()" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.25);">
        ↑ Import Excel
    </button>
    <a class="button button-white" href="{{ route('accounts.export') }}" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.25);">
        ↓ Export Excel
    </a>
    <a class="button button-white" href="{{ route('accounts.mappings') }}" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.25);">
        <x-icon name="file"/> Mapping Akun
    </a>
    <a class="button button-white" href="{{ route('accounts.create') }}">
        <x-icon name="plus"/> Tambah Akun
    </a>
</x-menu-banner>

<dialog id="coa-import-dialog" class="modal-dialog" style="max-width:560px">
    <form method="dialog" class="modal-close"><button aria-label="Tutup">×</button></form>
    <h2>Import Chart of Accounts</h2>
    <p>Upload file Excel untuk menambah atau memperbarui COA. Kode akun yang sudah ada akan diperbarui, sementara akun lain tidak dihapus.</p>
    <div class="info-note"><strong>Kolom wajib:</strong> Kode Akun, Nama Akun, Tipe. Gunakan <strong>Kode Induk</strong> untuk menentukan kepala akun laporan. Sheet <strong>Referensi Kepala Akun</strong> pada template memuat kelompok 10, 11, 12, 20, 21, 22, dan seterusnya.</div>
    <form method="POST" action="{{ route('accounts.import') }}" enctype="multipart/form-data" class="data-form" style="margin-top:16px">
        @csrf
        <div class="field"><label for="coa-import-file">File Excel</label><input id="coa-import-file" name="file" type="file" accept=".xls,.xlsx,.csv" required><small>Mendukung XLS, XLSX, dan CSV. Maksimal 5 MB.</small>@error('file')<small class="input-error">{{ $message }}</small>@enderror</div>
        <div class="form-actions"><a class="button button-secondary" href="{{ route('accounts.template') }}">Download Template</a><button class="button button-primary" type="submit">Import COA</button></div>
    </form>
</dialog>

@include('accounts.partials.coa')

@endsection
