@extends('layouts.app')
@section('title','Customer')
@section('content')
<x-menu-banner
    tag="SALES & CUSTOMER"
    title="Master Data Customer"
    description="Kelola identitas pelanggan, NPWP, batas kredit, kontak PIC, dan riwayat dokumen."
    :action-url="route('customers.create')"
    action-label="Tambah Customer"
    action-icon="plus"
    icon="users"
    art-title="Data pelanggan,"
    art-subtitle="terkelola rapi."
/>
<section class="panel">
<form class="filter-bar" method="GET"><input name="search" value="{{ $search }}" placeholder="Cari nama, kode, email, NPWP, atau telepon" aria-label="Cari customer"><select name="status" aria-label="Status customer"><option value="active" @selected($status==='active')>Customer aktif</option><option value="pending" @selected($status==='pending')>Menunggu approval</option><option value="inactive" @selected($status==='inactive')>Nonaktif / diarsipkan</option></select><button class="button button-primary">Cari</button><a class="text-link" href="{{ route('customers.index') }}">Reset</a><a class="button button-secondary button-sm" href="{{ route('customer-addresses.index') }}" style="margin-left: auto;"><x-icon name="map-pin"/> Master Alamat Customer</a></form>
<div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Customer</th>
                <th>PIC</th>
                <th>Email / Telepon</th>
                <th>Status</th>
                <th>Approval by</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td><strong>{{ $customer->code }}</strong></td>
                    <td><strong>{{ $customer->name }}</strong></td>
                    <td>{{ $customer->contact_name ?? '—' }}</td>
                    <td>
                        {{ $customer->email ?? '—' }}
                        @if($customer->phone)
                            <br><small class="muted-cell">{{ $customer->phone }}</small>
                        @endif
                    </td>
                    <td>
                        @if($customer->trashed())
                            <span class="status-badge status-inactive">Nonaktif</span>
                        @else
                            <span class="status-badge status-active">Aktif</span>
                        @endif
                    </td>
                    <td>
                        @if(($customer->approval_status ?? 'approved') === 'pending')
                            <span class="status-badge status-draft">Menunggu Finance</span>
                        @else
                            <span class="status-badge status-approved">Approved</span>
                            @if($customer->approver)
                                <br><small class="muted-cell">oleh {{ $customer->approver->name }}</small>
                            @endif
                            @if($customer->approved_at)
                                <br><small class="muted-cell">{{ $customer->approved_at->format('d/m/Y') }}</small>
                            @endif
                        @endif
                    </td>
                    <td>
                        <div class="table-actions">
                            @if(!$customer->trashed())
                                <a class="btn-action btn-action-primary" href="{{ route('customers.show', $customer) }}" title="Detail Customer" data-tooltip="Detail" aria-label="Detail Customer"><x-icon name="eye"/></a>
                                <a class="btn-action" href="{{ route('customers.edit', $customer) }}" title="Edit Customer" data-tooltip="Edit" aria-label="Edit Customer"><x-icon name="edit"/></a>
                            @else
                                <form method="POST" action="{{ route('customers.restore', $customer) }}" data-confirm="Aktifkan kembali customer ini?">
                                    @csrf
                                    <input type="hidden" name="lock_version" value="{{ $customer->lock_version }}">
                                    <button class="btn-action btn-action-success" title="Aktifkan Customer" data-tooltip="Aktifkan" aria-label="Aktifkan Customer"><x-icon name="check"/></button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <x-icon name="users"/>
                            <h3>Belum ada customer yang sesuai</h3>
                            <p>Tambahkan customer atau sesuaikan pencarian.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="pagination">{{ $customers->links() }}</div>
</section>
@endsection
