@extends('layouts.app')
@section('title','Edit Service')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">MASTER DATA</p>
        <h1>Edit Service</h1>
    </div>
    <a class="button button-secondary" href="{{ route('service-types.index') }}">Kembali</a>
</div>

<section class="panel form-panel" style="max-width: 640px;">
    <form class="data-form" method="POST" action="{{ route('service-types.update', $serviceType) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="field">
                <label for="code">Kode Service</label>
                <input id="code" name="code" value="{{ old('code', $serviceType->code) }}" required maxlength="40">
            </div>
            <div class="field">
                <label for="name">Nama Service</label>
                <input id="name" name="name" value="{{ old('name', $serviceType->name) }}" required maxlength="100">
            </div>
            <div class="field">
                <label for="sort_order">Urutan</label>
                <input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $serviceType->sort_order) }}">
            </div>
        </div>
        <div class="form-actions">
            <a class="button button-secondary" href="{{ route('service-types.index') }}">Batal</a>
            <button class="button button-primary">Simpan</button>
        </div>
    </form>
</section>
@endsection
