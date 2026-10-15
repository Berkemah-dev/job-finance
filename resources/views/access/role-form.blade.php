@extends('layouts.app')
@section('title', $role->exists ? 'Edit Role' : 'Buat Role Baru')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">ADMINISTRASI › ROLE & PERMISSION</p><h1>{{ $role->exists ? 'Edit Role' : 'Buat Role Baru' }}</h1><p>Centang permission yang boleh digunakan oleh role ini.</p></div>
    <a class="button button-secondary" href="{{ route('roles.index') }}">← Kembali</a>
</div>
<section class="panel form-panel">
    <form class="data-form" method="POST" action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}">
        @csrf @if($role->exists) @method('PUT') @endif
        <div class="form-grid">
            <div class="field"><label>Nama Role <span class="required">*</span></label><input name="label" value="{{ old('label', $role->label) }}" placeholder="Contoh: Supervisor Operasional" required></div>
            <div class="field"><label>Kode Role <span class="required">*</span></label><input name="name" value="{{ old('name', $role->name) }}" pattern="[A-Za-z0-9_-]+" placeholder="supervisor-operasional" required><small class="form-help">Gunakan huruf, angka, tanda minus, atau garis bawah.</small></div>
        </div>
        <div style="margin-top:22px;"><h2 style="font-size:16px;margin-bottom:5px;">Permission</h2><p class="form-help" style="margin-bottom:14px;">Pilih akses yang diperlukan. Permission yang tidak dicentang tidak akan muncul atau bisa digunakan oleh role ini.</p>
            <div class="form-grid" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr));">
                @foreach($permissions as $permission)
                    <label style="display:flex;align-items:center;gap:10px;padding:12px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;cursor:pointer;"><input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, array_map('intval', old('permissions', $role->permissions->pluck('id')->all())), true))><span><strong style="display:block;color:#0f172a;">{{ $permission->name }}</strong><small style="color:#64748b;">{{ str_replace('.', ' · ', $permission->name) }}</small></span></label>
                @endforeach
            </div>
        </div>
        <div class="form-actions"><a class="button button-secondary" href="{{ route('roles.index') }}">Batal</a><button class="button button-primary">Simpan Role</button></div>
    </form>
</section>
@endsection
