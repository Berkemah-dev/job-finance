@extends('layouts.app')
@section('title', 'DNP ' . $dnp->number)
@section('content')

@php
    $backUrl = $dnp->job_id ? route('jobs.show', $dnp->job_id) . '#tab-dnp' : route('dnps.index');
    $selectedDocs = $dnp->supporting_documents ?? [];
@endphp

<div class="page-heading">
    <div>
        <p class="eyebrow">KEPABEANAN IMPORT</p>
        <h1>{{ $dnp->number }}</h1>
        <p>📋 Deklarasi Nilai Pabean (DNP) · Tanggal: <strong>{{ $dnp->dnp_date?->format('d/m/Y') }}</strong> @if($dnp->job) · Job: <a class="text-link" href="{{ route('jobs.show', $dnp->job) }}">{{ $dnp->job->number }}</a>@endif</p>
    </div>
    <a class="button button-secondary" href="{{ $backUrl }}">← Kembali</a>
</div>

<div class="quote-actions" style="margin-bottom:20px; display:flex; gap:8px; flex-wrap:wrap;">
    <a class="button button-secondary" href="{{ route('dnps.edit', $dnp) }}">Edit DNP</a>
    <a class="button button-primary" href="{{ route('dnps.pdf', $dnp) }}" target="_blank">🖨 Preview / Cetak PDF DNP</a>
    @if($dnp->job)
        <a class="button button-secondary" href="{{ route('jobs.show', $dnp->job) . '#tab-dnp' }}">Lihat Job Order</a>
    @endif
    <form method="POST" action="{{ route('dnps.destroy', $dnp) }}" data-confirm="Hapus DNP {{ $dnp->number }}?" style="display:inline;">
        @csrf @method('DELETE')
        <button class="button button-danger" style="background:#ef4444;border-color:#ef4444;">Hapus</button>
    </form>
</div>

{{-- PANEL 1: DATA UTAMA DNP --}}
<section class="panel" style="margin-bottom:24px; padding:0; overflow:hidden;">
    <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 24px; background:#f8fafc; border-bottom:1px solid #e2e8f0;">
        <div style="font-weight:700; font-size:15px; color:#1e293b; display:flex; align-items:center; gap:8px;">
            <span>📋</span> Data Deklarasi Nilai Pabean
        </div>
    </div>

    <div style="padding:24px;">
        <div class="report-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">
            <div style="background:#f8fafc; padding:16px; border-radius:8px; border:1px solid #e2e8f0;">
                <h3 style="font-size:13px; font-weight:700; color:#475569; margin:0 0 12px; text-transform:uppercase;">Para Pihak Transaksi</h3>
                <table style="width:100%; font-size:13px; line-height:1.8;">
                    <tr>
                        <td style="color:#64748b; width:140px;">Consignee (Pembeli)</td>
                        <td style="font-weight:600; color:#0f172a;">: {{ $dnp->consignee_name ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;">Shipper (Penjual)</td>
                        <td style="font-weight:600; color:#0f172a;">: {{ $dnp->shipper_name ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;">Importir</td>
                        <td style="font-weight:600; color:#0f172a;">: {{ $dnp->importer_name ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;">Nomor Job Order</td>
                        <td style="font-weight:600; color:#0f172a;">: {{ $dnp->job?->number ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;">Commodity (Barang)</td>
                        <td style="font-weight:600; color:#0f172a;">: {{ $dnp->commodity ?: ($dnp->job?->cargo_description ?: '—') }}</td>
                    </tr>
                </table>
            </div>

            <div style="background:#f8fafc; padding:16px; border-radius:8px; border:1px solid #e2e8f0;">
                <h3 style="font-size:13px; font-weight:700; color:#475569; margin:0 0 12px; text-transform:uppercase;">Rincian Nilai Pabean</h3>
                <table style="width:100%; font-size:13px; line-height:1.8;">
                    <tr>
                        <td style="color:#64748b; width:150px;">Mata Uang (Currency)</td>
                        <td style="font-weight:700; color:#2563eb;">: {{ $dnp->currency }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;">Harga Dalam Invoice</td>
                        <td style="font-weight:600; color:#0f172a;">: {{ $dnp->currency }} {{ number_format((float)$dnp->invoice_value, 2, '.', ',') }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;">Biaya Transportasi</td>
                        <td style="font-weight:600; color:#0f172a;">: {{ $dnp->currency }} {{ number_format((float)$dnp->freight, 2, '.', ',') }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;">Asuransi</td>
                        <td style="font-weight:600; color:#0f172a;">: {{ $dnp->currency }} {{ number_format((float)$dnp->insurance, 2, '.', ',') }}</td>
                    </tr>
                    <tr style="border-top:1px dashed #cbd5e1;">
                        <td style="font-weight:700; color:#0f172a; padding-top:6px;">Total Nilai Pabean</td>
                        <td style="font-weight:800; font-size:14px; color:#16a34a; padding-top:6px;">: {{ $dnp->currency }} {{ number_format((float)$dnp->total_value, 2, '.', ',') }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;">Trans Pengulangan (F)</td>
                        <td style="font-weight:600; color:#0f172a;">: {{ $dnp->is_repeated_transaction ? 'YA' : 'TIDAK' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</section>

{{-- PANEL 2: DOKUMEN PENDUKUNG TERPILIH --}}
<section class="panel" style="margin-bottom:24px; padding:0; overflow:hidden;">
    <div style="padding:16px 24px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:700; font-size:15px; color:#1e293b; display:flex; align-items:center; gap:8px;">
        <span>📑</span> Checklist Dokumen Pendukung Kepabeanan
    </div>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th style="width:60px; text-align:center;">NO</th>
                    <th>KETERANGAN DOKUMEN</th>
                    <th style="width:120px; text-align:center;">STATUS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($supportingDocsList as $num => $docLabel)
                    @php
                        $hasDoc = in_array((string)$num, array_map('strval', $selectedDocs)) || in_array($num, $selectedDocs);
                    @endphp
                    <tr>
                        <td style="text-align:center; font-weight:600; color:#64748b;">{{ $num }}.</td>
                        <td style="font-size:13px; {{ $hasDoc ? 'font-weight:600; color:#0f172a;' : 'color:#94a3b8;' }}">
                            {{ $docLabel }}
                        </td>
                        <td style="text-align:center;">
                            @if($hasDoc)
                                <span style="display:inline-block; padding:3px 10px; border-radius:9999px; background:#dcfce7; color:#166534; font-size:12px; font-weight:700;">✔ Ada</span>
                            @else
                                <span style="color:#cbd5e1; font-size:12px;">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@endsection
