@extends('layouts.app')
@section('title', 'Role & Permission')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">ADMINISTRASI</p><h1>Role & Permission</h1><p>Buat role tambahan dan tentukan menu yang dapat diakses setiap role.</p></div>
    <a class="button button-primary" href="{{ route('roles.create') }}">+ Buat Role Baru</a>
</div>

<section class="panel">
    <div class="panel-heading"><h2>Daftar role</h2><span class="subtle">{{ $roles->count() }} role tersedia</span></div>
    <div class="role-grid" style="padding:0 20px 20px;">
        @foreach($roles as $role)
            <article class="panel role-card" style="margin:0;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                    <div><h3 style="margin:0 0 4px;">{{ $role->label }}</h3><code>{{ $role->name }}</code></div>
                    @if(in_array($role->name, $builtInRoles, true))
                        <span class="status-badge status-active">Bawaan</span>
                    @else
                        <span class="status-badge status-info">Custom</span>
                    @endif
                </div>
                <p style="margin:14px 0;color:#64748b;font-size:12px;">{{ $role->users->count() }} pengguna · {{ $role->permissions->count() }} permission</p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    @foreach($role->permissions->take(5) as $permission)<span class="count-badge">{{ $permission->name }}</span>@endforeach
                    @if($role->permissions->count() > 5)<span class="count-badge">+{{ $role->permissions->count() - 5 }} lainnya</span>@endif
                </div>
                @unless(in_array($role->name, $builtInRoles, true))
                    <div class="form-actions" style="margin-top:16px;padding-top:12px;border-top:1px solid #e2e8f0;">
                        <a class="button button-secondary button-sm" href="{{ route('roles.edit', $role) }}">Edit Permission</a>
                        <form method="POST" action="{{ route('roles.destroy', $role) }}" data-confirm="Hapus role {{ $role->label }}?">
                            @csrf @method('DELETE')<button class="button button-danger button-sm">Hapus</button>
                        </form>
                    </div>
                @endunless
            </article>
        @endforeach
    </div>
</section>
@endsection
