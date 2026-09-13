@extends('layouts.app')
@section('title','Pengguna & Akses')

@section('content')
<x-menu-banner
    tag="ADMINISTRASI SISTEM"
    title="Pengguna & Hak Akses"
    description="Kelola akun, role, status aktif, dan password pengguna."
    :action-url="auth()->user()->can('users.manage') ? route('users.create') : null"
    action-label="+ Tambah Pengguna"
    action-icon="plus"
    icon="users"
    art-title="Hak akses peran,"
    art-subtitle="aman & tertib."
/>

@php
    $generated = session('generated_password');
@endphp

<div class="stats-grid user-stats-grid">
    <article class="stat-card">
        <div class="stat-top"><span>Total Pengguna</span><span class="stat-icon blue"><x-icon name="users"/></span></div>
        <strong class="stat-number">{{ number_format($stats['total']) }}</strong>
        <p>Semua akun yang terdaftar.</p>
    </article>
    <article class="stat-card">
        <div class="stat-top"><span>Aktif</span><span class="stat-icon green"><x-icon name="check-circle"/></span></div>
        <strong class="stat-number">{{ number_format($stats['active']) }}</strong>
        <p>Bisa login dan memakai aplikasi.</p>
    </article>
    <article class="stat-card">
        <div class="stat-top"><span>Nonaktif</span><span class="stat-icon red"><x-icon name="power"/></span></div>
        <strong class="stat-number">{{ number_format($stats['inactive']) }}</strong>
        <p>Akses login sedang ditutup.</p>
    </article>
    <article class="stat-card">
        <div class="stat-top"><span>Role</span><span class="stat-icon purple"><x-icon name="lock"/></span></div>
        <strong class="stat-number">{{ number_format($stats['roles']) }}</strong>
        <p>Pembagian hak akses workspace.</p>
    </article>
</div>

<section class="panel user-directory-panel">
    <div class="panel-heading">
        <div>
            <h2>Daftar pengguna</h2>
            <p>{{ $users->total() }} data ditemukan</p>
        </div>
        <span class="count-badge">Halaman {{ $users->currentPage() }} dari {{ $users->lastPage() }}</span>
    </div>

    <form class="filter-bar" method="GET">
        <input name="search" value="{{ $filters['search'] }}" placeholder="Cari nama atau email" aria-label="Cari pengguna">
        <select name="role_id" aria-label="Filter role">
            <option value="">Semua role</option>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected((string) $filters['role_id'] === (string) $role->id)>{{ $role->label }}</option>
            @endforeach
        </select>
        <select name="status" aria-label="Filter status">
            <option value="all" @selected($filters['status'] === 'all')>Semua status</option>
            <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
            <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
        </select>
        <button class="button button-primary">Terapkan</button>
        <a class="button button-secondary" href="{{ route('users.index') }}">Reset</a>
    </form>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Bergabung</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <span class="user-avatar">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                <div>
                                    <strong>{{ $user->name }}</strong>
                                    <small>ID #{{ $user->id }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td><span class="role-badge">{{ $user->role?->label ?? 'Tanpa role' }}</span></td>
                        <td>
                            @if($user->is_active)
                                <span class="status-badge status-active">Aktif</span>
                            @else
                                <span class="status-badge status-inactive">Nonaktif</span>
                            @endif
                        </td>
                        <td>{{ $user->created_at?->format('d/m/Y') ?? '-' }}</td>
                        <td>
                            @can('users.manage')
                                <div class="table-actions">
                                    <a class="btn-action" href="{{ route('users.edit',$user) }}" title="Edit Pengguna" data-tooltip="Edit" aria-label="Edit Pengguna"><x-icon name="edit"/></a>
                                    <form method="POST" action="{{ route('users.toggle',$user) }}" data-confirm="{{ $user->is_active ? 'Nonaktifkan akun ini?' : 'Aktifkan akun ini?' }}">
                                        @csrf
                                        <button class="btn-action {{ $user->is_active ? 'btn-action-warning' : 'btn-action-success' }}" title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" data-tooltip="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"><x-icon name="power"/></button>
                                    </form>
                                    <form method="POST" action="{{ route('users.generate-password',$user) }}" data-confirm="Generate password baru untuk {{ $user->name }}? Password lama tidak bisa dipakai lagi.">
                                        @csrf
                                        <button class="btn-action btn-action-primary" title="Generate Password" data-tooltip="Generate Password" aria-label="Generate Password"><x-icon name="refresh"/></button>
                                    </form>
                                </div>
                            @else
                                <span class="subtle">-</span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <span class="empty-icon"><x-icon name="users"/></span>
                                <h3>Data pengguna tidak ditemukan</h3>
                                <p>Coba ubah kata kunci, role, atau status.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination users-pagination">
        <span>Menampilkan {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} pengguna</span>
        {{ $users->links() }}
    </div>
</section>

<div class="section-heading"><h2>Pembagian hak akses</h2><span class="subtle">{{ $roles->count() }} role workspace</span></div>
<div class="role-grid">
    @foreach($roles as $role)
        <article class="panel role-card">
            <span class="stat-icon blue"><x-icon name="lock"/></span>
            <h3>{{ $role->label }}</h3>
            <p>{{ ['super-admin'=>'Seluruh modul dan pengaturan aplikasi.','operational'=>'Customer, quotation, dan operasional job.','finance'=>'Biaya, closing, penagihan, dan akuntansi.','management'=>'Dashboard dan laporan tanpa mengubah transaksi.','finance-manager'=>'Approval finance, laporan, dan kontrol biaya.','sales-manager'=>'Monitoring sales, quotation, dan customer.','sales'=>'Customer dan quotation sesuai area kerja.','customer-service'=>'Quotation yang disetujui, job, dan dokumen customer.'][$role->name] ?? 'Akses sesuai permission yang diberikan.' }}</p>
            <span class="count-badge">{{ $role->permissions->count() }} permission</span>
        </article>
    @endforeach
</div>

@if($generated)
    <div class="modal-backdrop password-modal" data-password-modal>
        <section class="password-card" role="dialog" aria-modal="true" aria-labelledby="generated-password-title">
            <button class="password-close" type="button" data-close-password aria-label="Tutup"><x-icon name="x"/></button>
            <span class="stat-icon green"><x-icon name="lock"/></span>
            <p class="eyebrow">PASSWORD BARU</p>
            <h2 id="generated-password-title">{{ $generated['name'] }}</h2>
            <p class="password-email">{{ $generated['email'] }}</p>
            <div class="password-value" id="generated-password-value">{{ $generated['password'] }}</div>
            <div class="password-actions">
                <button class="button button-primary" type="button" data-copy-password>Copy password</button>
                <button class="button button-secondary" type="button" data-close-password>Tutup</button>
            </div>
            <small>Password hanya tampil sekali di popup ini.</small>
        </section>
    </div>
@endif

<style>
.user-stats-grid{margin-bottom:18px}.user-directory-panel{margin-top:0}.user-cell{display:flex;align-items:center;gap:10px}.user-cell small{display:block;color:#94a3b8;font-size:9px;margin-top:3px}.user-avatar{width:34px;height:34px;border-radius:10px;display:inline-grid;place-items:center;background:#fef2f2;color:#0f1f3d;font-weight:700}.users-pagination{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;color:#8490a4;font-size:11px}.modal-backdrop.password-modal{position:fixed;inset:0;background:rgba(15,31,61,.38);display:grid;place-items:center;z-index:120;padding:24px}.password-card{width:min(430px,100%);background:#fff;border:1px solid #e1e7f1;border-radius:18px;padding:28px;box-shadow:0 24px 80px rgba(15,31,61,.26);position:relative;text-align:left}.password-close{position:absolute;right:16px;top:16px;border:0;background:#f8fafc;color:#64748b;width:34px;height:34px;border-radius:10px;display:grid;place-items:center;cursor:pointer}.password-close .icon{width:16px}.password-card h2{font-size:20px;margin:10px 0 4px}.password-email{color:#71819a;font-size:12px}.password-value{margin:18px 0;padding:15px 16px;background:#0f1f3d;color:#fff;border-radius:12px;font-size:20px;font-weight:700;letter-spacing:1px;text-align:center}.password-actions{display:flex;gap:10px;flex-wrap:wrap}.password-card small{display:block;margin-top:14px;color:#8a96a9}.theme-dark .password-card{background:#172033;border-color:#334155}.theme-dark .password-value{background:#0b1220}@media(max-width:760px){.users-pagination{align-items:flex-start}.password-card{padding:24px}.user-stats-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-close-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            const modal = document.querySelector('[data-password-modal]');
            if (modal) modal.remove();
        });
    });

    const copy = document.querySelector('[data-copy-password]');
    if (copy) {
        copy.addEventListener('click', async function () {
            const value = document.getElementById('generated-password-value')?.textContent?.trim() || '';
            if (! value) return;
            try {
                await navigator.clipboard.writeText(value);
                copy.textContent = 'Sudah dicopy';
            } catch (error) {
                copy.textContent = 'Copy manual';
            }
        });
    }
});
</script>
@endsection
