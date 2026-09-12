@extends('layouts.app')
@section('title', 'Edit Jenis Biaya')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">MASTER DATA &rsaquo; JENIS BIAYA</p>
        <h1>Edit Jenis Biaya</h1>
    </div>
    <a class="button button-secondary" href="{{ route('charge-types.index') }}">Kembali</a>
</div>

<section class="panel form-panel" style="max-width: 560px;">
    <form class="data-form" method="POST" action="{{ route('charge-types.update', $chargeType) }}">
        @csrf
        @method('PUT')

        <div class="field" style="margin-bottom: 24px;">
            <label for="name">Nama Jenis Biaya <span class="required">*</span></label>
            <input id="name" name="name" type="text" value="{{ old('name', $chargeType->name) }}" required maxlength="150" placeholder="Nama Jenis Biaya (mis: TRUCKING / THC)" style="text-transform: uppercase;">
            @error('name')<span style="color:var(--danger, #d54d5f); font-size: 11px; margin-top: 4px; display: block;">{{ $message }}</span>@enderror
        </div>

        <div class="form-actions" style="margin-top: 0;">
            <a class="button button-secondary" href="{{ route('charge-types.index') }}">Batal</a>
            <button class="button button-primary" type="submit">Simpan Perubahan</button>
        </div>
    </form>
</section>
@endsection