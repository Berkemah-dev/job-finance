@extends('layouts.app')
@section('title','Detail Customer')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">SALES & CUSTOMER</p><h1>{{ $customer->code }}</h1><p>{{ $customer->name }}</p></div>
    <div class="action-group">
        <a class="button button-secondary" href="{{ route('customers.index') }}">← Kembali</a>
        @if(!$customer->trashed())<a class="button button-primary" href="{{ route('customers.edit',$customer) }}">Edit Customer</a>@endif
    </div>
</div>
@if($customer->trashed())<div class="info-note"><strong>Customer nonaktif.</strong> Tidak dapat dipilih untuk transaksi baru. <form method="POST" action="{{ route('customers.restore',$customer) }}" data-confirm="Aktifkan kembali customer ini?" class="inline-form">@csrf<input type="hidden" name="lock_version" value="{{ $customer->lock_version }}"><button class="text-link">Aktifkan kembali</button></form></div>@endif

<section class="panel" style="margin-bottom: 20px;">
    <div class="panel-heading"><h2>👤 Informasi Customer</h2>@if($customer->trashed())<span class="status-badge status-inactive">Nonaktif</span>@else<span class="status-badge status-active">Aktif</span>@endif</div>
    <dl class="detail-grid"><div><dt>Kode customer</dt><dd>{{ $customer->code }}</dd></div><div><dt>Nama</dt><dd>{{ $customer->name }}</dd></div><div><dt>Kontak</dt><dd>{{ $customer->contact_name ?? '—' }}</dd></div><div><dt>Email</dt><dd>{{ $customer->email ?? '—' }}</dd></div><div><dt>Telepon</dt><dd>{{ $customer->phone ?? '—' }}</dd></div><div><dt>NPWP</dt><dd>{{ $customer->tax_number ?? '—' }}</dd></div><div><dt>Syarat pembayaran default</dt><dd>{{ config('operations.customer_payment_terms.'.$customer->default_payment_terms) ?? $customer->default_payment_terms ?? '—' }}</dd></div><div><dt>Alamat</dt><dd>{{ $customer->address ?? '—' }}</dd></div><div><dt>Dibuat</dt><dd>{{ $customer->created_at?->format('d/m/Y H:i') }} · Diperbarui {{ $customer->updated_at?->format('d/m/Y H:i') }}</dd></div></dl>
</section>

<section class="panel" style="margin-bottom: 20px;">
    <div class="panel-heading"><h2>📄 Document Details</h2><span class="subtle">Informasi pencetakan dokumen operasional & pabean</span></div>
    <dl class="detail-grid">
        <div><dt>Nama Customer</dt><dd><strong>{{ $customer->name }}</strong></dd></div>
        <div><dt>Nama Pemberi Kuasa (DNP/SK)</dt><dd>{{ $customer->authorizer_name ?? '—' }}</dd></div>
        <div><dt>Jabatan Pemberi Kuasa (DNP/SK)</dt><dd>{{ $customer->authorizer_title ?? '—' }}</dd></div>
        <div class="span-2"><dt>Alamat Resmi Perusahaan (BL/AWB/SI/Booking/SK/DNP)</dt><dd>{{ $customer->address ?? '—' }}</dd></div>
    </dl>
</section>
<section class="panel"><div class="panel-heading"><h2>Dokumen</h2><span class="subtle">NPWP & NIB</span></div>
@if($customer->documents->isNotEmpty())
<div class="table-scroll"><table><thead><tr><th>Jenis</th><th>Nama file</th><th>Ukuran</th><th>Tipe</th><th>Diunggah oleh</th><th>Diunggah pada</th><th>Aksi</th></tr></thead><tbody>@foreach($customer->documents as $document)<tr><td><span class="status-badge status-approved">{{ \Illuminate\Support\Str::upper($document->type) }}</span></td><td>{{ $document->original_name ?? $document->filename }}</td><td>@if($document->size){{ number_format($document->size / 1024, 0, ',', '.') }} KB@else—@endif</td><td>{{ $document->mime ?? '—' }}</td><td>{{ $document->uploader?->name ?? '—' }}</td><td>{{ $document->created_at?->format('d/m/Y H:i') }}</td><td><div class="table-actions"><a class="btn-action btn-action-primary" href="{{ route('customers.documents.download', [$customer, $document]) }}" title="Unduh Dokumen" data-tooltip="Unduh" aria-label="Unduh Dokumen"><x-icon name="download"/></a>@can('customers.manage')<form method="POST" action="{{ route('customers.documents.destroy', [$customer, $document]) }}" data-confirm="Hapus dokumen ini?">@csrf @method('DELETE')<button class="btn-action btn-action-danger" title="Hapus Dokumen" data-tooltip="Hapus" aria-label="Hapus Dokumen"><x-icon name="trash"/></button></form>@endcan</div></td></tr>@endforeach</tbody></table></div>
@else
<div class="empty-state"><x-icon name="file"/><h3>Belum ada dokumen</h3><p>Unggah NPWP/NIB pada form edit customer.</p></div>
@endif
</section>
@endsection
