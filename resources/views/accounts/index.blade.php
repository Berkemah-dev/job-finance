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

<dialog id="coa-import-dialog" class="modal-dialog coa-import-modal">
    <div class="coa-import-modal__header">
        <div class="coa-import-modal__heading">
            <span class="coa-import-modal__icon"><x-icon name="file"/></span>
            <div>
                <span class="coa-import-modal__eyebrow">IMPORT DATA COA</span>
                <h2>Import Chart of Accounts</h2>
                <p>Tambahkan atau perbarui struktur akun melalui satu file Excel.</p>
            </div>
        </div>
        <button type="button" class="coa-import-modal__close" onclick="document.getElementById('coa-import-dialog').close()" aria-label="Tutup">×</button>
    </div>

    <form method="POST" action="{{ route('accounts.import') }}" enctype="multipart/form-data">
        @csrf
        <div class="coa-import-modal__body">
            <div class="coa-import-modal__notice">
                <strong>Format file</strong>
                <p>Kolom wajib: <b>Kode Akun</b>, <b>Nama Akun</b>, dan <b>Tipe</b>. Kode akun yang sama akan diperbarui; akun lain tetap aman.</p>
            </div>

            <div class="coa-import-modal__steps" aria-label="Langkah import">
                <div><span>1</span><p>Unduh template</p></div>
                <i></i>
                <div><span>2</span><p>Isi data akun</p></div>
                <i></i>
                <div><span>3</span><p>Unggah file</p></div>
            </div>

            <label for="coa-import-file" id="coa-import-dropzone" class="coa-import-dropzone">
                <input id="coa-import-file" name="file" type="file" accept=".xls,.xlsx,.csv" required>
                <span class="coa-import-dropzone__icon"><x-icon name="file"/></span>
                <strong id="coa-import-file-title">Pilih atau tarik file Excel ke sini</strong>
                <small id="coa-import-file-detail">Mendukung XLS, XLSX, atau CSV · Maksimal 5 MB</small>
            </label>
            @error('file')<small class="input-error">{{ $message }}</small>@enderror
        </div>

        <div class="coa-import-modal__footer">
            <a class="button button-secondary" href="{{ route('accounts.template') }}">↓ Download Template</a>
            <div class="coa-import-modal__actions">
                <button class="button button-secondary" type="button" onclick="document.getElementById('coa-import-dialog').close()">Batal</button>
                <button class="button button-primary" type="submit">↑ Import COA</button>
            </div>
        </div>
    </form>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('coa-import-dialog');
    const input = document.getElementById('coa-import-file');
    const dropzone = document.getElementById('coa-import-dropzone');
    const title = document.getElementById('coa-import-file-title');
    const detail = document.getElementById('coa-import-file-detail');

    const showSelectedFile = () => {
        const file = input.files[0];
        if (!file) return;
        title.textContent = file.name;
        detail.textContent = `File siap diimport · ${(file.size / 1024 / 1024).toFixed(2)} MB`;
        dropzone.classList.add('has-file');
    };

    input.addEventListener('change', showSelectedFile);
    ['dragenter', 'dragover'].forEach((eventName) => dropzone.addEventListener(eventName, (event) => {
        event.preventDefault();
        dropzone.classList.add('is-dragging');
    }));
    ['dragleave', 'drop'].forEach((eventName) => dropzone.addEventListener(eventName, (event) => {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');
    }));
    dropzone.addEventListener('drop', (event) => {
        if (!event.dataTransfer.files.length) return;
        input.files = event.dataTransfer.files;
        showSelectedFile();
    });
    dialog.addEventListener('click', (event) => {
        const bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    });
});
</script>

@include('accounts.partials.coa')

@endsection
