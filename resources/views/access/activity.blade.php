@extends('layouts.app')
@section('title','Log Aktivitas')
@section('content')
<div class="page-heading"><div><p class="eyebrow">ADMINISTRASI</p><h1>Log Aktivitas</h1><p>Riwayat akses dan aktivitas penting di workspace.</p></div></div><section class="panel"><div class="table-scroll"><table><thead><tr><th>Pengguna</th><th>Aktivitas</th><th>Waktu</th></tr></thead><tbody>@forelse($logs as $log)<tr><td>{{ $log->user?->name ?? 'Pengguna dihapus' }}</td><td>{{ $log->description }}</td><td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td></tr>@empty<tr><td colspan="3">Belum ada aktivitas tercatat.</td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $logs->links() }}</div></section>
@endsection
