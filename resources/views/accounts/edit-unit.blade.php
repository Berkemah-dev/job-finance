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

<section class="panel" style="max-width: 600px;">
    <form method="POST" action="{{ route('container-units.update', $containerUnit) }}">
        @csrf @method('PUT')
        
        <div class="form-group" style="margin-bottom: 2rem;">
            <label style="display:block;margin-bottom:0.5rem;font-weight:500;">Nama Satuan</label>
            <input name="name" value="{{ old('name', $containerUnit->name) }}" required maxlength="50" style="width:100%;padding:0.75rem;border:1px solid var(--border);border-radius:6px;background:var(--surface);color:var(--text);">
            @error('name')<span style="color:var(--danger);font-size:0.85rem;">{{ $message }}</span>@enderror
        </div>
        
        <button class="button button-primary" type="submit">Simpan Perubahan</button>
    </form>
</section>
@endsection