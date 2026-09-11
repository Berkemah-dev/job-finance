@extends('layouts.app')
@section('title', 'Edit Port')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">MASTER DATA &rsaquo; PORT</p>
        <h1>Edit Port</h1>
    </div>
    <a class="button button-secondary" href="{{ route('ports.index') }}">Kembali</a>
</div>

<section class="panel" style="max-width: 600px;">
    <form method="POST" action="{{ route('ports.update', $port) }}">
        @csrf @method('PUT')
        
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label style="display:block;margin-bottom:0.5rem;font-weight:500;">Nama Port</label>
            <input name="name" value="{{ old('name', $port->name) }}" required maxlength="150" style="width:100%;padding:0.75rem;border:1px solid var(--border);border-radius:6px;background:var(--surface);color:var(--text);">
            @error('name')<span style="color:var(--danger);font-size:0.85rem;">{{ $message }}</span>@enderror
        </div>
        
        <div class="form-group" style="margin-bottom: 2rem;">
            <label style="display:block;margin-bottom:0.5rem;font-weight:500;">Kode Port</label>
            <input name="code" value="{{ old('code', $port->code) }}" required maxlength="20" style="width:100%;padding:0.75rem;border:1px solid var(--border);border-radius:6px;background:var(--surface);color:var(--text);">
            @error('code')<span style="color:var(--danger);font-size:0.85rem;">{{ $message }}</span>@enderror
        </div>
        
        <button class="button button-primary" type="submit">Simpan Perubahan</button>
    </form>
</section>
@endsection