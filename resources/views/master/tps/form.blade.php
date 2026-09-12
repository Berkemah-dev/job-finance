@extends('layouts.app')
@section('title', $tps->exists ? 'Edit TPS' : 'Tambah TPS')
@section('content')
<div class="page-heading"><div><p class="eyebrow">MASTER DATA</p><h1>{{ $tps->exists ? 'Edit' : 'Tambah' }} TPS</h1></div><a class="text-link" href="{{ route('tps.index') }}">Kembali ke daftar</a></div>
<section class="panel form-panel"><form class="data-form" method="POST" action="{{ $tps->exists ? route('tps.update',$tps) : route('tps.store') }}">@csrf @if($tps->exists) @method('PUT') @endif
<div class="form-grid">
<div class="field"><label for="code">Kode TPS <span class="required">*</span></label><input id="code" name="code" maxlength="20" value="{{ old('code',$tps->code) }}" required placeholder="e.g. TPS001"></div>
<div class="field"><label for="mode">Moda <span class="required">*</span></label><select id="mode" name="mode" required><option value="sea" @selected(old('mode',$tps->mode)==='sea')>Sea (Laut)</option><option value="air" @selected(old('mode',$tps->mode)==='air')>Air (Udara)</option></select></div>
<div class="field span-2"><label for="name">Nama TPS <span class="required">*</span></label><input id="name" name="name" maxlength="150" value="{{ old('name',$tps->name) }}" required></div>
<div class="field"><label for="city">Kota <span class="required">*</span></label><input id="city" name="city" maxlength="100" value="{{ old('city',$tps->city) }}" required></div>
<div class="field span-2"><label for="address">Alamat</label><textarea id="address" name="address" rows="2" maxlength="500">{{ old('address',$tps->address) }}</textarea></div>
<div class="field span-2 checkbox-field" style="margin-top: 4px;">
    <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 500; color: #1e293b; cursor: pointer; user-select: none; margin: 0;">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active',$tps->exists ? $tps->is_active : true)) style="width: 18px; height: 18px; accent-color: #0f1f3d; margin: 0; cursor: pointer; flex-shrink: 0;">
        <span>TPS aktif dan dapat digunakan</span>
    </label>
</div>
</div><div class="form-actions">@if($tps->exists)<button type="submit" form="delete-form" class="button button-danger">Hapus</button>@endif<a class="button button-secondary" href="{{ route('tps.index') }}">Batal</a><button class="button button-primary">Simpan</button></div></form></section>
@if($tps->exists)<form id="delete-form" action="{{ route('tps.destroy',$tps) }}" method="POST" data-confirm="Hapus TPS ini?">@csrf @method('DELETE')</form>@endif
@endsection