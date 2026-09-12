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

<section class="panel form-panel" style="max-width: 640px;">
    <form class="data-form" method="POST" action="{{ route('ports.update', $port) }}">
        @csrf
        @method('PUT')

        <div class="form-grid">
            <div class="field">
                <label for="name">Nama Port <span class="required">*</span></label>
                <input id="name" name="name" type="text" value="{{ old('name', $port->name) }}" required maxlength="150" placeholder="Nama Port">
                @error('name')<span style="color:var(--danger, #d54d5f); font-size: 11px; margin-top: 4px; display: block;">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="code">Kode Port <span class="required">*</span></label>
                <input id="code" name="code" type="text" value="{{ old('code', $port->code) }}" required maxlength="20" placeholder="Kode Port" style="text-transform: uppercase;">
                @error('code')<span style="color:var(--danger, #d54d5f); font-size: 11px; margin-top: 4px; display: block;">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ route('ports.index') }}">Batal</a>
            <button class="button button-primary" type="submit">Simpan Perubahan</button>
        </div>
    </form>
</section>
@endsection