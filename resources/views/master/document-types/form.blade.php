@extends('layouts.app')
@section('title', $type->exists ? 'Edit Tipe Dokumen' : 'Tambah Tipe Dokumen')
@section('content')
<div class="page-heading"><div><p class="eyebrow">MASTER DATA</p><h1>{{ $type->exists ? 'Edit' : 'Tambah' }} Tipe Dokumen</h1></div><a class="text-link" href="{{ route('document-types.index') }}">Kembali ke daftar</a></div>
<section class="panel form-panel"><form class="data-form" method="POST" action="{{ $type->exists ? route('document-types.update',$type) : route('document-types.store') }}">@csrf @if($type->exists) @method('PUT') @endif
<div class="form-grid">
<div class="field"><label for="code">Kode Dokumen <span class="required">*</span></label><input id="code" name="code" maxlength="20" value="{{ old('code',$type->code) }}" required placeholder="e.g. BL, AWB, CI"></div>
<div class="field"><label for="category">Kategori <span class="required">*</span></label><select id="category" name="category" required><option value="">Pilih kategori</option>@foreach($categories as $value=>$label)<option value="{{ $value }}" @selected(old('category',$type->category)===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="field span-2"><label for="name">Nama Dokumen <span class="required">*</span></label><input id="name" name="name" maxlength="100" value="{{ old('name',$type->name) }}" required></div>
<div class="field span-2"><label for="description">Deskripsi</label><textarea id="description" name="description" rows="2" maxlength="500">{{ old('description',$type->description) }}</textarea></div>
<div class="field"><label for="sort_order">Urutan Tampil</label><input id="sort_order" name="sort_order" type="number" min="0" max="999" value="{{ old('sort_order',$type->sort_order ?? 0) }}"></div>
<div class="field span-2 checkbox-field">
    <label><input type="checkbox" name="is_required" value="1" @checked(old('is_required',$type->is_required))> Dokumen ini wajib diupload untuk setiap job</label>
    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$type->exists ? $type->is_active : true))> Tipe dokumen aktif dan dapat digunakan</label>
</div>
</div><div class="form-actions">@if($type->exists)<button type="submit" form="delete-form" class="button button-danger">Hapus</button>@endif<a class="button button-secondary" href="{{ route('document-types.index') }}">Batal</a><button class="button button-primary">Simpan</button></div></form></section>
@if($type->exists)<form id="delete-form" action="{{ route('document-types.destroy',$type) }}" method="POST" data-confirm="Hapus tipe dokumen ini?">@csrf @method('DELETE')</form>@endif
@endsection
