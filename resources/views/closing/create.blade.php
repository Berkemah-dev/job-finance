@extends('layouts.app')
@section('title', 'Closing Job')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">{{ $job->number }}</p>
        <h1>Konfirmasi Closing Job</h1>
        <p>{{ $job->subject }} · Customer: <strong>{{ $job->quotation_snapshot['customer']['name'] ?? '—' }}</strong></p>
    </div>
    <div class="action-group">
        <a class="button button-secondary" href="{{ route('closing.index') }}">← Kembali</a>
        <a class="button button-secondary" href="{{ route('jobs.costs.index', $job) }}" target="_blank"><x-icon name="wallet"/> Kelola Biaya Job</a>
    </div>
</div>

@if($job->costs()->count() === 0 && empty($job->quotation_snapshot['items']))
    <div class="flash-error">Closing belum siap: job harus memiliki rincian biaya aktual atau item penawaran.</div>
@elseif($draftCount > 0)
    <div class="info-note" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; margin-bottom: 16px; border-radius: 8px; padding: 12px 16px;">
        <x-icon name="check" style="width: 16px; height: 16px; display: inline-block; vertical-align: text-bottom; margin-right: 4px;"/>
        Terdapat <strong>{{ $draftCount }}</strong> biaya job berstatus <em>Draft</em>. Seluruh biaya Draft akan <strong>otomatis difinalisasi</strong> saat closing dilakukan.
    </div>
@endif

<section class="panel form-panel">
    <div class="panel-heading" style="margin-bottom: 12px;">
        <h2>Ringkasan Biaya & Pendapatan Job</h2>
        <span class="subtle">Total {{ count($costs) }} item biaya aktual</span>
    </div>
    <div class="detail-grid">
        <div><dt>Temporary</dt><dd>Rp {{ \App\Support\Money::format($summary['temporary']) }}</dd></div>
        <div><dt>Modal provision</dt><dd>Rp {{ \App\Support\Money::format($summary['provision_cost']) }}</dd></div>
        <div><dt>Jual provision</dt><dd>Rp {{ \App\Support\Money::format($summary['provision_sell']) }}</dd></div>
        <div><dt>Profit</dt><dd>Rp {{ \App\Support\Money::format($summary['profit']) }}</dd></div>
        <div><dt>Subtotal</dt><dd>Rp {{ \App\Support\Money::format($summary['subtotal']) }}</dd></div>
    </div>

    @if(count($costs) > 0)
        <div class="section-heading" style="margin-top: 24px;">
            <h3>Rincian Biaya Job Terkait</h3>
            <span class="subtle">Biaya yang masuk ke dalam kalkulasi invoice & jurnal</span>
        </div>
        <div class="table-scroll" style="margin-bottom: 24px;">
            <table>
                <thead>
                    <tr>
                        <th>Nomor / Tanggal</th>
                        <th>Uraian / Tipe</th>
                        <th>Jumlah</th>
                        <th class="money">Total Modal</th>
                        <th class="money">Total Jual</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($costs as $cost)
                        <tr>
                            <td>
                                <strong>{{ $cost->number }}</strong><br>
                                <small class="muted-cell">{{ $cost->cost_date->format('d/m/Y') }}</small>
                            </td>
                            <td>
                                <strong>{{ $cost->description }}</strong><br>
                                <small class="badge-pill">{{ ucfirst($cost->type) }}</small>
                            </td>
                            <td>{{ \App\Support\Money::format($cost->quantity) }} {{ $cost->unit }}</td>
                            <td class="money">Rp {{ \App\Support\Money::format($cost->total_cost) }}</td>
                            <td class="money">Rp {{ \App\Support\Money::format($cost->total_price) }}</td>
                            <td>
                                <span class="status-badge status-{{ $cost->status }}">{{ config('operations.cost_statuses.'.$cost->status) ?? ucfirst($cost->status) }}</span>
                                @if($cost->status === 'draft')
                                    <br><small style="color: #0284c7; font-size: 10px;">Auto-finalisasi</small>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <form class="data-form" method="POST" action="{{ route('closing.store', $job) }}" data-confirm="Tutup job ini? Invoice Issued dan jurnal akuntansi seimbang akan langsung dibuat.">
        @csrf
        <input type="hidden" name="lock_version" value="{{ $job->lock_version }}">
        <div class="info-note">
            Closing membuat snapshot historis, invoice Issued, jurnal kapitalisasi biaya, dan jurnal pengakuan pendapatan/HPP.
            @if(($job->quotation_snapshot['currency'] ?? 'IDR') !== 'IDR')
                Mata uang invoice: <strong>{{ $job->quotation_snapshot['currency'] }} (kurs {{ $job->quotation_snapshot['exchange_rate'] ?? '1' }})</strong>. Nilai buku tetap dalam Rupiah.
            @endif
        </div>
        <div class="form-grid">
            <div class="field">
                <label>Tanggal closing</label>
                <input type="date" name="closing_date" value="{{ old('closing_date', today()->toDateString()) }}" min="{{ $job->job_date->format('Y-m-d') }}" max="{{ today()->toDateString() }}" required>
            </div>
            <div class="field">
                <label>Jatuh tempo invoice</label>
                <input type="date" name="due_date" value="{{ old('due_date', today()->addDays(30)->toDateString()) }}" required>
            </div>
            <div class="field">
                <label>Sumber dana biaya</label>
                <select name="funding_account" required>
                    <option value="bank">Bank</option>
                    <option value="cash" @selected(old('funding_account') === 'cash')>Kas</option>
                </select>
            </div>
            <div class="field">
                <label>Pajak (Rp)</label>
                <input type="number" name="tax" min="0" max="999999999.99" step=".01" value="{{ old('tax', '0') }}" required>
            </div>
            @if(($job->quotation_snapshot['currency'] ?? 'IDR') !== 'IDR')
                <div class="field">
                    <label>Kurs override (opsional)</label>
                    <input type="number" name="exchange_rate_override" min="0.000001" max="999999999.99" step=".01" value="{{ old('exchange_rate_override') }}" placeholder="Default {{ $job->quotation_snapshot['exchange_rate'] ?? 1 }}" title="Ganti kurs quotation untuk invoice ini; nilai buku tetap dalam Rupiah.">
                </div>
            @endif
        </div>
        <div class="form-actions">
            <button class="button button-primary" @disabled($job->costs()->count() === 0 && empty($job->quotation_snapshot['items']))>
                <x-icon name="check"/> Close Job dan Buat Invoice
            </button>
        </div>
    </form>
</section>
@endsection
