@extends('layouts.app')
@section('title','Pengguna & Akses')
@section('content')
<x-menu-banner
    tag="ADMINISTRASI SISTEM"
    title="Pengguna & Hak Akses"
    description="Kelola data pengguna, penetapan peran (Role & Permission), dan keamanan akun workspace."
    :action-url="auth()->user()->can('users.manage') ? route('users.create') : null"
    action-label="+ Tambah Pengguna"
    action-icon="plus"
    icon="users"
    art-title="Hak akses peran,"
    art-subtitle="aman & tertib."
/>
<section class="panel"><div class="panel-heading"><h2>Pengguna terdaftar</h2><span class="count-badge">Direktori pengguna</span></div><div class="table-scroll"><table><thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Bergabung</th><th>Aksi</th></tr></thead><tbody>@foreach($users as $user)<tr><td><strong>{{ $user->name }}</strong></td><td>{{ $user->email }}</td><td><span class="role-badge">{{ $user->role?->label ?? 'Tanpa role' }}</span></td><td>{{ $user->created_at->format('d/m/Y') }}</td><td>@can('users.manage')<div class="table-actions"><a class="btn-action" href="{{ route('users.edit',$user) }}" title="Edit Pengguna" data-tooltip="Edit" aria-label="Edit Pengguna"><x-icon name="edit"/></a></div>@endcan</td></tr>@endforeach</tbody></table></div><div class="pagination">{{ $users->links() }}</div></section>
<div class="section-heading"><h2>Pembagian hak akses</h2><span class="subtle">4 peran workspace</span></div><div class="role-grid">@foreach($roles as $role)<article class="panel role-card"><span class="stat-icon blue"><x-icon name="lock"/></span><h3>{{ $role->label }}</h3><p>{{ ['super-admin'=>'Seluruh modul dan pengaturan aplikasi.','operational'=>'Customer, quotation, dan operasional job.','finance'=>'Biaya, closing, penagihan, dan akuntansi.','management'=>'Dashboard dan laporan tanpa mengubah transaksi.'][$role->name] ?? 'Akses sesuai permission yang diberikan.' }}</p><span class="count-badge">{{ $role->permissions->count() }} permission</span></article>@endforeach</div>
@endsection
