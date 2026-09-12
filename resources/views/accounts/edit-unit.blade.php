@extends('layouts.app')
@section('title', 'Edit Satuan')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">MASTER DATA &rsaquo; SATUAN</p>
        <h1>Edit Satuan</h1>
    </div>
    <a class="button button-secondary" href="{{ route('container-units.index') }}">Kembali</a>
</div>

<section class="panel form-panel" style="max-width: 560px;">
    <form class="data-form" method="POST" action="{{ route('container-units.update', $containerUnit) }}">
        @csrf
        @method('PUT')

        <div class="field" style="margin-bottom: 24px;">
            <label for="name">Nama Satuan <span class="required">*</span></label>
            <input id="name" name="name" type="text" value="{{ old('name', $containerUnit->name) }}" required maxlength="50" placeholder="Nama Satuan (mis: 40GP / CBM / 20FT)" style="text-transform: uppercase;">
            @error('name')<span style="color:var(--danger, #d54d5f); font-size: 11px; margin-top: 4px; display: block;">{{ $message }}</span>@enderror
        </div>

        <div class="form-actions" style="margin-top: 0;">
            <a class="button button-secondary" href="{{ route('container-units.index') }}">Batal</a>
            <button class="button button-primary" type="submit">Simpan Perubahan</button>
        </div>
    </form>
</section>
@endsection