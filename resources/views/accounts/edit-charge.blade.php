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

<section class="panel" style="max-width: 600px;">
    <form method="POST" action="{{ route('charge-types.update', $chargeType) }}">
        @csrf @method('PUT')
        
        <div class="form-group" style="margin-bottom: 2rem;">
            <label style="display:block;margin-bottom:0.5rem;font-weight:500;">Nama Jenis Biaya</label>
            <input name="name" value="{{ old('name', $chargeType->name) }}" required maxlength="150" style="width:100%;padding:0.75rem;border:1px solid var(--border);border-radius:6px;background:var(--surface);color:var(--text);">
            @error('name')<span style="color:var(--danger);font-size:0.85rem;">{{ $message }}</span>@enderror
        </div>
        
        <button class="button button-primary" type="submit">Simpan Perubahan</button>
    </form>
</section>
@endsection