@extends('layouts.app')
@section('title', $account->exists ? 'Edit Akun' : ($parent ? 'Tambah Sub Akun' : 'Tambah Akun'))
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">AKUNTANSI</p>
        <h1>{{ $account->exists ? 'Edit Akun' : ($parent ? 'Tambah Sub Akun: '.$parent->code.' · '.$parent->name : 'Tambah Akun') }}</h1>
        <p>{{ $parent ? 'Sub-akun baru akan berada di bawah '.$parent->code.' - '.$parent->name : 'Gunakan kode unik dan tipe akun yang sesuai.' }}</p>
    </div>
    <a class="text-link" href="{{ route('accounts.index') }}">← Kembali ke Data COA</a>
</div>

@if($parent && !$account->exists)
    <div class="info-note">
        <strong>Menambahkan Sub Akun</strong> untuk <strong>{{ $parent->code }} - {{ $parent->name }}</strong>.
        Tipe akun otomatis mengikuti tipe akun induk ({{ config('accounting.types.'.$parent->type) }}).
    </div>
@endif

<section class="panel form-panel">
    <form class="data-form" method="POST" action="{{ $account->exists ? route('accounts.update', $account) : route('accounts.store') }}">
        @csrf
        @if($account->exists)
            @method('PUT')
        @endif
        <input type="hidden" name="lock_version" value="{{ old('lock_version', $account->lock_version ?? 0) }}">

        <div class="form-grid">
            <div class="field span-2">
                <label for="parent_id">Akun Induk (Parent Account)</label>
                <select id="parent_id" name="parent_id">
                    <option value="">-- Tanpa Induk (Akun Utama / Root) --</option>
                    @foreach($parents ?? [] as $p)
                        <option value="{{ $p->id }}" @selected((string)old('parent_id', $account->parent_id ?? $parent?->id) === (string)$p->id)>
                            {{ str_repeat('— ', max(0, ($p->level ?? 1) - 1)) }}{{ $p->code }} - {{ $p->name }} ({{ config('accounting.types.'.$p->type) }})
                        </option>
                    @endforeach
                </select>
                <small>Pilih akun induk jika akun ini merupakan sub-akun.</small>
            </div>

            <div class="field">
                <label for="code">Kode akun <span class="required">*</span></label>
                <input id="code" name="code" value="{{ old('code', $account->code ?? ($parent ? $parent->code : '')) }}" required maxlength="20" placeholder="Contoh: 11101 atau 62100">
                <small>Kode unik angka/huruf tanpa spasi.</small>
            </div>

            <div class="field">
                <label for="name">Nama akun <span class="required">*</span></label>
                <input id="name" name="name" value="{{ old('name', $account->name) }}" required maxlength="255" placeholder="Contoh: PETTY CASH (-)">
                <small>Nama deskriptif untuk akun pencatatan.</small>
            </div>

            <div class="field span-2">
                <label for="type">Tipe akun <span class="required">*</span></label>
                <select id="type" name="type" required>
                    @foreach(config('accounting.types') as $key => $label)
                        <option value="{{ $key }}" @selected(old('type', $account->type ?? $parent?->type) === $key)>
                            {{ $label }} ({{ ucfirst($key) }})
                        </option>
                    @endforeach
                </select>
                <small>Tipe akun akuntansi standar (Asset, Liabilitas, Ekuitas, Pendapatan, HPP, Beban).</small>
            </div>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ route('accounts.index') }}">Batal</a>
            <button class="button button-primary" type="submit">Simpan Akun</button>
        </div>
    </form>
</section>

@if($account->exists)
    <section class="archive-panel">
        <div>
            <h3>Arsipkan akun</h3>
            <p>Akun yang memiliki sub-akun atau digunakan pada mapping sistem harus dipindahkan terlebih dahulu.</p>
        </div>
        <form method="POST" action="{{ route('accounts.destroy', $account) }}" data-confirm="Apakah Anda yakin ingin mengarsipkan akun {{ $account->code }} - {{ $account->name }}?">
            @csrf
            @method('DELETE')
            <input type="hidden" name="lock_version" value="{{ $account->lock_version }}">
            <button class="button button-danger" type="submit">Arsipkan Akun</button>
        </form>
    </section>
@endif
@endsection
