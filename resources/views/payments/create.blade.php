@extends('layouts.app')
@section('title', 'Add Payment - ' . $invoice->number)
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">INVOICE {{ $invoice->number }}</p>
        <h1>Add Payment</h1>
        <p>Sisa tagihan Rp {{ \App\Support\Money::format($invoice->balance) }}</p>
    </div>
    <a class="button button-secondary" href="{{ route('invoices.show', $invoice) }}">← Kembali</a>
</div>

<section class="panel form-panel" style="max-width: 650px; margin: 0 auto; padding: 24px;">
    {{-- Summary Info Card Invoice --}}
    <div style="display: grid; grid-template-columns: 1.1fr 1.6fr 1.3fr; gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; align-items: center;">
        <div>
            <span style="display: block; font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">No. Invoice</span>
            <strong style="font-size: 13px; color: #0f172a; font-weight: 700;">{{ $invoice->number }}</strong>
        </div>
        <div>
            <span style="display: block; font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Customer</span>
            <strong style="font-size: 12.5px; color: #334155; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $invoice->customer_snapshot['name'] }}">{{ $invoice->customer_snapshot['name'] }}</strong>
        </div>
        <div style="text-align: right;">
            <span style="display: block; font-size: 10px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Sisa Tagihan</span>
            <strong style="font-size: 13.5px; color: #2563eb; font-weight: 800; font-family: monospace;">{{ $invoice->currency }} {{ \App\Support\Money::format($invoice->balance) }}</strong>
        </div>
    </div>

    <form class="data-form" method="POST" action="{{ route('payments.store', $invoice) }}">
        @csrf
        <input type="hidden" name="lock_version" value="{{ $invoice->lock_version }}">

        <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="field">
                <label>Tanggal Pembayaran <span class="required">*</span></label>
                <input type="date" name="payment_date" min="{{ $invoice->invoice_date->format('Y-m-d') }}" max="{{ today()->toDateString() }}" value="{{ old('payment_date', today()->toDateString()) }}" required>
            </div>
            <div class="field">
                <label>Jumlah Bayar (Kas/Bank) <span class="required">*</span></label>
                <input type="number" name="amount" min="0.01" max="{{ $invoice->balance }}" step="0.01" value="{{ old('amount', $invoice->balance) }}" required>
            </div>
            <div class="field">
                <label>Mata Uang <span class="required">*</span></label>
                <select name="currency" required>
                    <option value="IDR" @selected($invoice->currency === 'IDR')>IDR (Rupiah)</option>
                    <option value="USD" @selected($invoice->currency === 'USD')>USD (US Dollar)</option>
                    <option value="SGD" @selected($invoice->currency === 'SGD')>SGD (Singapore Dollar)</option>
                    <option value="EUR" @selected($invoice->currency === 'EUR')>EUR (Euro)</option>
                </select>
            </div>
            <div class="field">
                <label>Kurs / Nilai Tukar <span class="required">*</span></label>
                <input type="number" name="exchange_rate" step="0.0001" min="0.0001" value="{{ old('exchange_rate', $invoice->currency === 'IDR' ? '1' : ($invoice->exchange_rate ?? '1')) }}" placeholder="1" required>
            </div>
            <div class="field">
                <label>Potongan PPH 23 (IDR)</label>
                <input type="number" name="pph23_amount" min="0" step="0.01" value="{{ old('pph23_amount', '0.00') }}" placeholder="0.00">
                <small style="color: #64748b; font-size: 11px;">Jurnal ke 11192 - PPH 23 Dimuka</small>
            </div>
            <div class="field">
                <label>No. Referensi Transfer <span style="font-weight: 400; color: #94a3b8;">(Opsional)</span></label>
                <input name="reference" maxlength="100" value="{{ old('reference') }}" placeholder="cth: Ref No. 892019 / Bukti Transfer">
            </div>
            <div class="field span-2">
                <label>Rekening Penerimaan (Bank / Kas) <span class="required">*</span></label>
                <select name="deposit_account" required>
                    <option value="">-- Pilih Bank / Kas --</option>
                    @foreach($bankAccounts ?? [] as $acc)
                        <option value="{{ $acc->id }}" @selected(old('deposit_account') == $acc->id || (empty(old('deposit_account')) && str_contains($acc->name, 'BCA IDR')))>
                            {{ $acc->name }} ({{ $acc->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Metode Pembayaran</label>
                <select name="method">
                    <option value="transfer">Transfer Bank</option>
                    <option value="cash">Kas Tunai</option>
                    <option value="giro">Bilyet Giro / Cek</option>
                    <option value="other">Lainnya</option>
                </select>
            </div>
            <div class="field span-2">
                <label>Catatan Tambahan <span style="font-weight: 400; color: #94a3b8;">(Opsional)</span></label>
                <textarea name="notes" maxlength="2000" rows="2" placeholder="Catatan internal">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="form-actions" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
            <a class="button button-secondary" href="{{ route('invoices.show', $invoice) }}">Batal</a>
            <button type="submit" class="button button-primary"><x-icon name="check"/> Simpan Pembayaran</button>
        </div>
    </form>
</section>
@endsection
