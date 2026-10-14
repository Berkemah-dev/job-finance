@extends('layouts.app')
@section('title','Detail Biaya')
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ $job->number }} · {{ config('operations.job_statuses.'.$job->status) }}</p><h1>{{ $cost->number }}</h1><p>{{ $cost->description }}</p></div><a class="button button-secondary" href="{{ route('jobs.costs.index',$job) }}">← Kembali</a></div>
@if($job->invoice && ($job->invoice->payments()->exists() || (float) $job->invoice->paid_amount > 0))<div class="info-note"><strong>Biaya terkunci karena invoice sudah memiliki pembayaran.</strong> Perubahan nominal tidak tersedia agar saldo invoice dan jurnal pembayaran tetap konsisten.</div>@elseif($job->invoice)<div class="info-note"><strong>Invoice sudah dibuat dan belum dibayar.</strong> Biaya tetap dapat disesuaikan; sistem memperbarui rincian serta jurnal invoice secara otomatis.</div>@elseif($cost->status==='final')<div class="info-note"><strong>Biaya sudah dikonfirmasi Finance.</strong> Finance Confirm hanya menandai biaya telah diperiksa; biaya tetap dapat disesuaikan sebelum maupun sesudah Finalisasi Invoice selama belum ada pembayaran.</div>@endif
<section class="panel"><div class="panel-heading"><h2>Rincian biaya aktual</h2><span class="status-badge status-{{ $cost->status }}">{{ config('operations.cost_statuses.'.$cost->status) }}</span></div>
<dl class="detail-grid">
<div><dt>Jenis transaksi</dt><dd><strong>{{ match($cost->cost_category) { 'reimbursement' => 'Reimbursement (Temporary Payment)', 'debit_note' => 'Debit Note (Piutang Agent)', 'credit_note' => 'Credit Note (Hutang Agent)', default => 'Payment Request (Provisional Payment)' } }}</strong></dd></div><div><dt>Tanggal biaya</dt><dd>{{ $cost->cost_date->format('d/m/Y') }}</dd></div><div><dt>Jumlah / Satuan</dt><dd>{{ \App\Support\Money::format($cost->quantity) }} {{ $cost->unit }}</dd></div>
<div><dt>Modal per unit</dt><dd>{{ $cost->currency ?? 'IDR' }} {{ \App\Support\Money::format($cost->foreign_unit_cost ?? $cost->unit_cost) }}@if(($cost->currency ?? 'IDR') !== 'IDR') <small>(Rp {{ \App\Support\Money::format($cost->unit_cost) }})</small>@endif</dd></div><div><dt>Nilai jual per unit</dt><dd>{{ $cost->currency ?? 'IDR' }} {{ \App\Support\Money::format($cost->foreign_unit_price ?? $cost->unit_price) }}@if(($cost->currency ?? 'IDR') !== 'IDR') <small>(Rp {{ \App\Support\Money::format($cost->unit_price) }})</small>@endif</dd></div>@if(($cost->currency ?? 'IDR') !== 'IDR')<div><dt>Kurs Modal ke IDR</dt><dd>Rp {{ \App\Support\Money::format($cost->cost_exchange_rate ?? $cost->exchange_rate) }}</dd></div><div><dt>Kurs Tagih ke IDR</dt><dd>Rp {{ \App\Support\Money::format($cost->price_exchange_rate ?? $cost->exchange_rate) }}</dd></div>@endif<div><dt>Penerima / vendor</dt><dd>{{ $cost->payee ?? '—' }}</dd></div>
<div><dt>Nomor bukti / referensi</dt><dd>{{ $cost->reference ?? '—' }}</dd></div>@if($cost->quotation_id)<div><dt>Referensi quotation</dt><dd><a class="text-link" href="{{ route('quotations.show', $cost->quotation_id) }}">{{ $cost->quotation->number }}</a>@if($cost->quotation_item_id)<br>Item ke-{{ $cost->quotationItem->position }} · {{ $cost->quotationItem->description }}@endif</dd></div>@endif<div><dt>Dibuat oleh</dt><dd>{{ $cost->creator?->name }}<br>{{ $cost->created_at->format('d/m/Y H:i') }}</dd></div><div><dt>Finalisasi</dt><dd>{{ $cost->finalizer?->name ?? 'Belum difinalisasi' }}<br>{{ $cost->finalized_at?->format('d/m/Y H:i') }}</dd></div>
</dl>
@if($cost->notes)<div class="detail-notes"><strong>Catatan</strong><br>{{ $cost->notes }}</div>@endif
@php
    $isForeign = ($cost->currency ?? 'IDR') !== 'IDR';
    $costExchangeRate = (float)($cost->cost_exchange_rate ?? $cost->exchange_rate ?: 1);
    $priceExchangeRate = (float)($cost->price_exchange_rate ?? $cost->exchange_rate ?: 1);
    $foreignTotalCost = $isForeign ? (float)($cost->foreign_total_cost ?? ($cost->total_cost / $costExchangeRate)) : (float)($cost->total_cost ?? 0);
    $foreignTotalPrice = $isForeign ? (float)($cost->foreign_total_price ?? ($cost->total_price / $priceExchangeRate)) : (float)($cost->total_price ?? 0);
    $foreignProfit = $foreignTotalPrice - $foreignTotalCost;
@endphp
<div class="summary-box">
    <div class="summary-row"><span>Total modal</span><strong>@if($isForeign) {{ $cost->currency }} {{ \App\Support\Money::format($foreignTotalCost) }} (Rp {{ \App\Support\Money::format($cost->total_cost) }}) @else Rp {{ \App\Support\Money::format($cost->total_cost) }} @endif</strong></div>
    <div class="summary-row summary-total"><span>Total jual</span><strong>@if($isForeign) {{ $cost->currency }} {{ \App\Support\Money::format($foreignTotalPrice) }} (Rp {{ \App\Support\Money::format($cost->total_price) }}) @else Rp {{ \App\Support\Money::format($cost->total_price) }} @endif</strong></div>
    @if($cost->type !== 'temporary')
    <div class="summary-row"><span>Estimasi profit</span><strong>@if($isForeign) {{ $cost->currency }} {{ \App\Support\Money::format($foreignProfit) }} (Rp {{ \App\Support\Money::format(\App\Support\Money::decimal($cost->total_price)->minus(\App\Support\Money::decimal($cost->total_cost))) }}) @else Rp {{ \App\Support\Money::format(\App\Support\Money::decimal($cost->total_price)->minus(\App\Support\Money::decimal($cost->total_cost))) }} @endif</strong></div>
    @endif
</div>
<div class="info-note" style="margin-top:16px;"><strong>Status Pembayaran Vendor:</strong> @if($cost->paid_at) <span class="status-badge status-paid" style="vertical-align:middle;margin:0 4px;">PAID</span> {{ $cost->paid_date?->format('d/m/Y') }} · {{ $cost->paymentAccount?->name }} @else <span class="status-badge status-draft" style="vertical-align:middle;margin:0 4px;">Belum PAID</span> — biaya dapat dibayar meskipun masih Draft; Finance Confirm tetap diperlukan sebelum Finalisasi Invoice. @endif</div>
</section>
<div class="actions-bar" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; margin-top:20px; margin-bottom:24px;">
    @can('update',$cost)
        <a class="button button-secondary" href="{{ route('jobs.costs.edit',[$job,$cost]) }}">Edit biaya</a>
    @endcan
    @can('finalize',$cost)
        <form method="POST" action="{{ route('jobs.costs.finalize',[$job,$cost]) }}" data-confirm="Konfirmasi biaya ini setelah diperiksa Finance? Biaya masih dapat disesuaikan sampai Finalisasi Invoice.">
            @csrf
            <input type="hidden" name="job_version" value="{{ $job->lock_version }}">
            <input type="hidden" name="lock_version" value="{{ $cost->lock_version }}">
            <button class="button button-primary">Finance Confirm</button>
        </form>
    @endcan
    @can('approve',$cost)
        <form method="POST" action="{{ route('jobs.costs.approve',[$job,$cost]) }}" data-confirm="Setujui transaksi ini untuk Closing Job?">
            @csrf
            <input type="hidden" name="job_version" value="{{ $job->lock_version }}">
            <input type="hidden" name="lock_version" value="{{ $cost->lock_version }}">
            <button class="button button-primary">Setujui untuk Closing</button>
        </form>
    @endcan
    @can('delete',$cost)
        <form method="POST" action="{{ route('jobs.costs.destroy',[$job,$cost]) }}" data-confirm="Hapus biaya Draft ini dari perhitungan job?">
            @csrf
            @method('DELETE')
            <input type="hidden" name="job_version" value="{{ $job->lock_version }}">
            <input type="hidden" name="lock_version" value="{{ $cost->lock_version }}">
            <button class="button button-danger">Hapus biaya Draft</button>
        </form>
    @endcan
</div>

@if(!$cost->paid_at && (float) $cost->total_cost > 0)
<section class="panel form-panel" style="margin-top:24px;">
    <div class="panel-heading">
        <div>
            <h2>Pembayaran Biaya Vendor (Tandai PAID)</h2>
            <p>Catat pengeluaran kas / bank ke vendor. Pembayaran dapat dilakukan saat biaya masih Draft maupun sudah Finance Confirmed.</p>
        </div>
    </div>
    <form method="POST" action="{{ route('jobs.costs.pay',[$job,$cost]) }}" class="data-form">
        @csrf
        <input type="hidden" name="job_version" value="{{ $job->lock_version }}">
        <div class="form-grid">
            <div class="field">
                <label>Tanggal PAID</label>
                <input type="date" name="paid_date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required>
            </div>
            <div class="field">
                <label>Rekening Sumber Dana (Kas / Bank)</label>
                <select name="payment_account_id" required>
                    <option value="">Pilih rekening pembayaran...</option>
                    @foreach($bankAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="pay_pph23_amount">Potong PPh 23 (Jika ada)</label>
                <input id="pay_pph23_amount" name="pph23_amount" type="text" inputmode="decimal" value="{{ $cost->pph23_amount ? preg_replace('/,00$/', '', \App\Support\Money::format($cost->pph23_amount)) : '' }}" placeholder="0" data-currency-input>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:9px;" data-pph-calculator data-base-amount="{{ $cost->total_cost }}">
                    <span style="font-size:11.5px;color:#64748b;">Hitung dari modal Rp {{ \App\Support\Money::format($cost->total_cost) }}:</span>
                    <button type="button" class="button button-secondary" style="font-size:11px;padding:4px 10px;" data-pph-rate="0.02">PPh 2%</button>
                    <button type="button" class="button button-secondary" style="font-size:11px;padding:4px 10px;" data-pph-rate="0.025">PPh 2,5%</button>
                    <button type="button" class="button button-secondary" style="font-size:11px;padding:4px 10px;" data-pph-rate="0">Tanpa PPh</button>
                </div>
                <small class="field-hint" style="color:var(--text-muted, #64748b);font-size:12px;margin-top:4px;display:block;">
                    Jika ada pemotongan pajak PPh 23 jasa (biasanya 2%), masukkan nominal di sini. Jika tidak ada, biarkan 0.
                </small>
                <label for="withholding_document_number" style="margin-top:12px;display:block;">Nomor Bukti Potong (Bupot)</label>
                <input id="withholding_document_number" name="withholding_document_number" value="{{ old('withholding_document_number', $cost->withholding_document_number) }}" maxlength="100" placeholder="Isi bila Bupot sudah tersedia">
            </div>
        </div>
        <div class="form-actions" style="margin-top:20px;">
            <button class="button button-primary">Tandai PAID & Buat Jurnal Pembayaran</button>
        </div>
    </form>
</section>
@endif
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-pph-calculator]').forEach((calculator) => {
        const input = document.getElementById('pay_pph23_amount');
        const base = Number(calculator.dataset.baseAmount || 0);
        calculator.querySelectorAll('[data-pph-rate]').forEach((button) => button.addEventListener('click', () => {
            const value = Math.round(base * Number(button.dataset.pphRate) * 100) / 100;
            input.value = value.toFixed(2);
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();
        }));
    });
});
</script>
@endsection
