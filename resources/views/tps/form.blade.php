@extends('layouts.app')
@section('title',$tps->exists?'Edit TPS':'Tambah TPS')
@section('content')
<div class="page-heading"><div><p class="eyebrow">MASTER DATA</p><h1>{{ $tps->exists?'Edit TPS':'Tambah TPS' }}</h1><p>Data TPS digunakan untuk mencatat Tempat Penimbunan Sementara pada proses operasional.</p></div><a class="text-link" href="{{ route('tps.index') }}">Kembali</a></div>
<section class="panel form-panel"><form class="data-form" method="POST" action="{{ $tps->exists?route('tps.update',$tps):route('tps.store') }}">@csrf @if($tps->exists) @method('PUT') @endif
<div class="form-grid">
<div class="field"><label for="code">Kode TPS <span class="required">*</span></label><input id="code" name="code" type="text" value="{{ old('code',$tps->code) }}" placeholder="cth: JKTSP01" maxlength="30" required></div>
<div class="field"><label for="name">Nama TPS <span class="required">*</span></label><input id="name" name="name" type="text" value="{{ old('name',$tps->name) }}" placeholder="cth: TPS Soekarno-Hatta" maxlength="200" required></div>
<div class="field"><label for="city">Kota <span class="required">*</span></label><input id="city" name="city" type="text" value="{{ old('city',$tps->city) }}" placeholder="cth: Jakarta" maxlength="120" required></div>
<div class="field"><label for="mode">Moda <span class="required">*</span></label><select id="mode" name="mode" required><option value="">Pilih moda</option><option value="air" @selected(old('mode',$tps->mode)==='air')>✈ Udara (Air)</option><option value="sea" @selected(old('mode',$tps->mode)==='sea')>🚢 Laut (Sea)</option></select></div>
<div class="field"><label for="is_active">Status</label><select id="is_active" name="is_active"><option value="1" @selected(!$tps->exists || $tps->is_active)>Aktif</option><option value="0" @selected($tps->exists && !$tps->is_active)>Nonaktif</option></select></div>
</div>
<div class="form-actions"><a class="button button-secondary" href="{{ route('tps.index') }}">Batal</a><button class="button button-primary">Simpan TPS</button></div>
</form></section>

@if($tps->exists)
<section class="archive-panel"><div><h3>Hapus TPS</h3><p>Menghapus TPS secara permanen. Lakukan hanya jika TPS tidak pernah dipakai.</p></div><form method="POST" action="{{ route('tps.destroy',$tps) }}" data-confirm="Hapus TPS {{ $tps->code }} secara permanen?">@csrf @method('DELETE')<button class="button button-danger">Hapus TPS</button></form></section>
@endif
@endsection
