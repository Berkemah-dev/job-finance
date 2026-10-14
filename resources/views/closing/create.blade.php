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
        Terdapat <strong>{{ $draftCount }}</strong> biaya job berstatus <em>Draft</em>. Finalisasi masing-masing biaya terlebih dahulu dari menu Biaya Job sebelum Closing dilakukan.
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
                                    <br><small style="color: #0284c7; font-size: 10px;">Perlu finalisasi</small>
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
            Closing membuat snapshot historis, invoice Issued, jurnal pengakuan Hutang Biaya, dan jurnal pengakuan pendapatan/HPP. Closing tidak mencatat pengeluaran Kas atau Bank; pembayaran dilakukan kemudian dari menu Biaya Job.
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

            @if(! $job->invoice)
            <div class="field span-2" style="background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 16px;">
                <label style="font-size: 13.5px; font-weight: 700; color: #166534; display: flex; justify-content: space-between; align-items: center;">
                    <span>Input PPN (Rp) <span class="required">*</span></span>
                    <small style="font-weight: 500; color: #15803d;">Akun: Piutang Cust (D) · PPN Keluaran (K)</small>
                </label>
                <input type="number" id="tax_input_closing" name="tax" min="0" max="999999999.99" step="0.01" value="{{ old('tax', '0') }}" required style="font-size: 16px; font-weight: 700; color: #15803d; margin-top: 6px;">
                <div style="display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap;">
                    <button type="button" class="button button-secondary" style="font-size: 11.5px; padding: 4px 10px;" onclick="calculatePpnClosing(0.11)">
                        Hitung 11% (dari Jual Provision)
                    </button>
                    <button type="button" class="button button-secondary" style="font-size: 11.5px; padding: 4px 10px;" onclick="calculatePpnClosing(0.011)">
                        Hitung 1.1% (Freight Forwarding)
                    </button>
                    <button type="button" class="button button-secondary" style="font-size: 11.5px; padding: 4px 10px;" onclick="document.getElementById('tax_input_closing').value='0.00'">
                        Tanpa PPN (0)
                    </button>
                </div>
                <small style="color: #4b5563; font-size: 11.5px; margin-top: 6px; display: block;">
                    💡 <em>PPN Keluaran akan otomatis diposting ke COA 28000 (PPN Keluaran) dan menambah tagihan Piutang Customer (COA 1103).</em>
                </small>
            </div>
            <div class="field span-2" style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 16px;">
                <label for="stamp_duty_closing" style="font-size: 13.5px; font-weight: 700; color: #334155;">Tagihan Materai (Rp)</label>
                <input type="number" id="stamp_duty_closing" name="stamp_duty" min="0" max="999999999.99" step="0.01" value="{{ old('stamp_duty', '0') }}" placeholder="0.00" style="margin-top: 6px;">
                <small style="color: #64748b; font-size: 11.5px; margin-top: 6px; display: block;">Materai akan ditampilkan sebagai baris tersendiri dan ditambahkan ke total invoice.</small>
            </div>
            @else
            <div class="field span-2">
                <div class="info-note" style="margin:0;"><strong>PPN dan Materai mengikuti Invoice {{ $job->invoice->number }}.</strong> Nilai PPN Rp {{ \App\Support\Money::format($job->invoice->tax) }} dan Materai Rp {{ \App\Support\Money::format($job->invoice->stamp_duty) }} sudah tercatat pada invoice dan tidak perlu diinput kembali saat Closing Job.</div>
            </div>
            @endif
            <div class="field span-2">
                <div class="info-note" style="margin:0;"><strong>Kurs Tagih otomatis.</strong> Invoice menggunakan kurs tagih pada setiap Biaya Job. Tidak perlu memasukkan kurs kembali saat Closing Job.</div>
            </div>
        </div>
        <div class="form-actions">
            <button class="button button-primary" @disabled($job->costs()->count() === 0 && empty($job->quotation_snapshot['items']))>
                <x-icon name="check"/> {{ $job->invoice ? 'Close Job' : 'Close Job dan Buat Invoice' }}
            </button>
        </div>
    </form>
</section>

@if(! $job->invoice)
<script>
function calculatePpnClosing(rate) {
    const provisionSell = parseFloat("{{ $summary['provision_sell'] }}") || 0;
    const ppn = Math.round(provisionSell * rate * 100) / 100;
    document.getElementById('tax_input_closing').value = ppn.toFixed(2);
}
</script>
@endif
@endsection
