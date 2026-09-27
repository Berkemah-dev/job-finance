@extends('layouts.app')
@section('title', 'Tarif LCL')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">MASTER DATA</p><h1>Tarif Estimasi LCL</h1><p>Unggah daftar tarif Excel agar dipakai oleh Kalkulator Estimasi Biaya LCL.</p></div>
    <a class="button button-secondary" href="{{ route('calculators.lcl') }}">Buka Kalkulator LCL</a>
</div>

<section class="panel form-panel">
    <div class="section-heading"><div><h2>Upload daftar tarif</h2><p>Gunakan sheet pertama dengan baris header.</p></div></div>
    <form class="data-form" method="POST" action="{{ route('lcl-rates.import') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-grid">
            <div class="field"><label for="file">File Excel</label><input id="file" name="file" type="file" accept=".xlsx,.csv" required>@error('file')<small class="input-error">{{ $message }}</small>@enderror</div>
        </div>
        <p class="form-help">Kolom yang dibaca: <strong>Country, FOB Port, Subject, Customer, TT, O/F, GRI, CFS, Others, Mekanik Charges, Adm</strong>. Nilai seperti <em>USD 23/W/M</em> dan <em>IDR 250,000/W/M (Min 2)</em> akan dibaca otomatis.</p>
        <div class="form-actions"><button class="button button-primary">Upload tarif LCL</button></div>
    </form>
</section>

<section class="panel table-panel">
    <div class="section-heading"><div><h2>Daftar tarif aktif</h2><p>{{ $rates->total() }} tarif tersedia untuk pilihan POD pada kalkulator.</p></div></div>
    <div class="table-wrap"><table><thead><tr><th>Country</th><th>FOB Port / POD</th><th>Transit</th><th>Customer</th><th>TT</th><th>O/F</th><th>GRI</th><th>CFS</th><th>Others</th><th>Mekanik</th><th>Adm</th></tr></thead><tbody>
    @forelse($rates as $rate)<tr><td>{{ $rate->country ?: '—' }}</td><td><strong>{{ $rate->fob_port }}</strong></td><td>{{ $rate->subject ?: '—' }}</td><td>{{ $rate->customer ?: '—' }}</td><td>{{ rtrim(rtrim($rate->lead_time_days, '0'), '.') }} hari</td><td>USD {{ number_format($rate->ocean_freight_rate, 2) }}</td><td>USD {{ number_format($rate->gri_rate, 2) }}</td><td>USD {{ number_format($rate->cfs_rate, 2) }}</td><td>USD {{ number_format($rate->others_per_set, 2) }}</td><td>IDR {{ number_format($rate->mechanic_rate, 0) }}</td><td>IDR {{ number_format($rate->administration, 0) }}</td></tr>
    @empty<tr><td colspan="11" class="empty-state">Belum ada tarif LCL. Upload file Excel untuk mulai menggunakan kalkulator.</td></tr>@endforelse
    </tbody></table></div>
    @if($rates->hasPages())<div class="pagination">{{ $rates->links() }}</div>@endif
</section>
@endsection
