@extends('layouts.app')
@section('title','Jurnal')
@section('content')
<x-menu-banner
    tag="AKUNTANSI"
    title="Jurnal Umum & Penyesuaian"
    description="Daftar transaksi debit dan kredit otomatis dari operasional serta pencatatan jurnal manual."
    :action-url="route('journals.create')"
    action-label="+ Jurnal Penyesuaian"
    action-icon="plus"
    icon="file"
    art-title="Debit & kredit,"
    art-subtitle="selalu seimbang."
/>
<form class="filter-bar" method="GET"><select name="type"><option value="">Semua jenis</option>@foreach(['job_cost_capitalization'=>'Kapitalisasi biaya','job_closing'=>'Closing job','customer_payment'=>'Pembayaran','adjustment'=>'Penyesuaian','journal_reversal'=>'Reversal'] as $value=>$label)<option value="{{ $value }}" @selected(request('type')===$value)>{{ $label }}</option>@endforeach</select><div class="date-filter-group"><x-icon name="calendar"/><input type="date" name="from" value="{{ request('from') }}" aria-label="Dari tanggal" title="Dari tanggal"><span class="date-sep">→</span><input type="date" name="to" value="{{ request('to') }}" aria-label="Sampai tanggal" title="Sampai tanggal"></div><button class="button button-secondary">Terapkan</button></form>
<section class="panel"><div class="table-scroll"><table><thead><tr><th>Nomor</th><th>Tanggal</th><th>Jenis</th><th>Uraian</th><th>Status</th><th>Baris</th></tr></thead><tbody>@forelse($journals as $journal)<tr><td><a class="text-link" href="{{ route('journals.show',$journal) }}">{{ $journal->number }}</a></td><td>{{ $journal->journal_date->format('d/m/Y') }}</td><td>{{ ucwords(str_replace('_',' ',$journal->type)) }}</td><td>{{ $journal->description }}</td><td><span class="status-badge status-{{ $journal->reversed_at?'cancelled':'final' }}">{{ $journal->reversed_at?'Reversed':'Posted' }}</span></td><td>{{ $journal->entries_count }}</td></tr>@empty<tr><td colspan="6">Belum ada jurnal.</td></tr>@endforelse</tbody></table></div>{{ $journals->links() }}</section>
@endsection
