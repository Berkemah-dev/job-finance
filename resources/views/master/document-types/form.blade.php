@extends('layouts.app')
@section('title', $type->exists ? 'Edit Data Document' : 'Tambah Data Document')
@section('content')
<div class="page-heading"><div><p class="eyebrow">MASTER DATA</p><h1>{{ $type->exists ? 'Edit' : 'Tambah' }} Data Document</h1></div><a class="text-link" href="{{ route('document-types.index') }}">Kembali ke daftar</a></div>
<section class="panel form-panel"><form class="data-form" method="POST" action="{{ $type->exists ? route('document-types.update',$type) : route('document-types.store') }}">@csrf @if($type->exists) @method('PUT') @endif
<div class="form-grid">
<div class="field"><label for="code">Kode Document <span class="required">*</span></label><input id="code" name="code" maxlength="20" value="{{ old('code',$type->code) }}" required placeholder="e.g. HBL, MBL, NPE"></div>
<div class="field"><label for="category">Kategori <span class="required">*</span></label><select id="category" name="category" required><option value="">Pilih kategori</option>@foreach($categories as $value=>$label)<option value="{{ $value }}" @selected(old('category',$type->category)===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="field span-2"><label for="name">Nama Document <span class="required">*</span></label><input id="name" name="name" maxlength="100" value="{{ old('name',$type->name) }}" required></div>
<div class="field span-2">
    <label style="font-size: 12px; font-weight: 600; color: #354967; margin-bottom: 8px; display: block;">Berlaku untuk Service</label>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 10px; padding: 14px; border: 1px solid #dce4ef; border-radius: 10px; background: #fafcff;">
        @php $selectedServices = old('service_codes', $type->service_codes ?? []); @endphp
        @foreach($serviceTypes as $code => $label)
            @php $isChecked = in_array($code, $selectedServices, true); @endphp
            <label style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border: 1px solid {{ $isChecked ? '#bfdbfe' : '#e2e8f0' }}; border-radius: 8px; background: {{ $isChecked ? '#eff6ff' : '#ffffff' }}; cursor: pointer; transition: all .15s ease; user-select: none;">
                <input type="checkbox" name="service_codes[]" value="{{ $code }}" @checked($isChecked) style="width: 16px; height: 16px; accent-color: #0f1f3d; margin: 0; cursor: pointer; flex-shrink: 0;">
                <span style="font-size: 12px; font-weight: 600; color: #1e293b; white-space: nowrap;">{{ $label }}</span>
            </label>
        @endforeach
    </div>
    <small class="form-help" style="margin-top: 6px; font-size: 11px; color: #8490a4;">Pilih satu atau lebih service. Kosongkan jika dokumen berlaku untuk semua service.</small>
</div>
<div class="field span-2"><label for="description">Deskripsi</label><textarea id="description" name="description" rows="2" maxlength="500">{{ old('description',$type->description) }}</textarea></div>
<div class="field"><label for="sort_order">Urutan Tampil</label><input id="sort_order" name="sort_order" type="number" min="0" max="999" value="{{ old('sort_order',$type->sort_order ?? 0) }}"></div>
<div class="field span-2 checkbox-field" style="display: flex; flex-direction: column; gap: 10px; margin-top: 4px;">
    <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 500; color: #1e293b; cursor: pointer; user-select: none; margin: 0;">
        <input type="checkbox" name="is_required" value="1" @checked(old('is_required',$type->is_required)) style="width: 18px; height: 18px; accent-color: #0f1f3d; margin: 0; cursor: pointer; flex-shrink: 0;">
        <span>Document ini wajib diupload untuk setiap job</span>
    </label>
    <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 500; color: #1e293b; cursor: pointer; user-select: none; margin: 0;">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active',$type->exists ? $type->is_active : true)) style="width: 18px; height: 18px; accent-color: #0f1f3d; margin: 0; cursor: pointer; flex-shrink: 0;">
        <span>Data document aktif dan dapat digunakan</span>
    </label>
</div>
</div><div class="form-actions">@if($type->exists)<button type="submit" form="delete-form" class="button button-danger">Hapus</button>@endif<a class="button button-secondary" href="{{ route('document-types.index') }}">Batal</a><button class="button button-primary">Simpan</button></div></form></section>
@if($type->exists)<form id="delete-form" action="{{ route('document-types.destroy',$type) }}" method="POST" data-confirm="Hapus data document ini?">@csrf @method('DELETE')</form>@endif
@endsection
