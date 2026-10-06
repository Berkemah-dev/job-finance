@extends('layouts.app')
@section('title', 'Buat Invoice' . ($job ? ' - ' . $job->number : ''))
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">INVOICE PENAGIHAN</p>
        <h1>Buat Invoice Pra-Closing</h1>
        <p>Pembuatan invoice penagihan ke customer saat Job masih berstatus <strong>Open</strong></p>
    </div>
    <div class="action-group">
        <a class="button button-secondary" href="{{ $job ? route('jobs.show', $job) : route('invoices.index') }}">← Kembali</a>
    </div>
</div>

@if(!$job)
    {{-- PILIH JOB JIKA BELUM DIPILIH --}}
    <section class="panel form-panel" style="max-width: 650px; margin: 0 auto; padding: 24px;">
        <div class="panel-heading" style="margin-bottom: 16px;">
            <h2>Pilih Job Order (Status Open)</h2>
            <span class="subtle">Hanya job Open yang belum memiliki invoice</span>
        </div>
        <form method="GET" action="{{ route('invoices.create') }}">
            <div class="field">
                <label for="job_select">Pilih Job Order <span class="required">*</span></label>
                <select id="job_select" name="job_id" required onchange="this.form.submit()">
                    <option value="">-- Pilih Job Order --</option>
                    @foreach($openJobs as $item)
                        <option value="{{ $item->id }}" @selected(request('job_id') == $item->id)>
                            {{ $item->number }} — {{ $item->customer?->name ?? 'Customer' }} ({{ $item->subject }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-actions" style="margin-top: 16px;">
                <button type="submit" class="button button-primary">Lanjutkan Pengisian Invoice</button>
            </div>
        </form>
    </section>
@else
    {{-- FORM PEMBUATAN INVOICE UNTUK JOB TERPILIH --}}
    <section class="panel form-panel" style="max-width: 900px; margin: 0 auto; padding: 24px;">
        <div class="panel-heading" style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h2>Informasi Job: {{ $job->number }}</h2>
                <p style="color: #64748b; font-size: 13px; margin: 4px 0 0 0;">Customer: <strong>{{ $job->quotation_snapshot['customer']['name'] ?? ($job->customer?->name ?? '—') }}</strong> · {{ $job->subject }}</p>
            </div>
            <span class="status-badge status-open" style="font-size: 12px; padding: 6px 12px;">Job Open</span>
        </div>

        {{-- Ringkasan Biaya --}}
        <div class="detail-grid" style="margin-bottom: 24px; background: #f8fafc; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0;">
            <div><dt>Temporary (Reimbursement)</dt><dd id="sum-temp">Rp {{ \App\Support\Money::format($summary['temporary']) }}</dd></div>
            <div><dt>Modal Provision</dt><dd>Rp {{ \App\Support\Money::format($summary['provision_cost']) }}</dd></div>
            <div><dt>Jual Provision</dt><dd id="sum-provision">Rp {{ \App\Support\Money::format($summary['provision_sell']) }}</dd></div>
            <div><dt>Subtotal Invoice</dt><dd id="sum-subtotal" style="font-weight: 800; color: #0f172a;">Rp {{ \App\Support\Money::format($summary['subtotal']) }}</dd></div>
            <div><dt>Estimasi Profit</dt><dd style="color: #16a34a; font-weight: 700;">Rp {{ \App\Support\Money::format($summary['profit']) }} ({{ $summary['margin'] }}%)</dd></div>
        </div>

        @if(count($costs) > 0)
            <div class="section-heading" style="margin-bottom: 8px;">
                <h3>Item Tagihan yang Akan Dicantumkan</h3>
            </div>
            <div class="table-scroll" style="margin-bottom: 24px;">
                <table>
                    <thead>
                        <tr>
                            <th>Nomor / Tanggal</th>
                            <th>Uraian / Tipe</th>
                            <th>Jumlah</th>
                            <th class="money">Total Jual</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($costs as $cost)
                            <tr>
                                <td><strong>{{ $cost->number }}</strong><br><small class="muted-cell">{{ $cost->cost_date->format('d/m/Y') }}</small></td>
                                <td><strong>{{ $cost->description }}</strong><br><small class="badge-pill">{{ ucfirst($cost->type) }}</small></td>
                                <td>{{ \App\Support\Money::format($cost->quantity) }} {{ $cost->unit }}</td>
                                <td class="money">Rp {{ \App\Support\Money::format($cost->type === 'temporary' ? $cost->total_cost : $cost->total_price) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <form class="data-form" method="POST" action="{{ route('invoices.store') }}" data-confirm="Terbitkan invoice sekarang? Job akan tetap berstatus OPEN.">
            @csrf
            <input type="hidden" name="job_id" value="{{ $job->id }}">
            <input type="hidden" name="lock_version" value="{{ $job->lock_version }}">

            <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="field">
                    <label>Tanggal Invoice <span class="required">*</span></label>
                    <input type="date" name="invoice_date" value="{{ old('invoice_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required>
                </div>
                <div class="field">
                    <label>Jatuh Tempo Invoice <span class="required">*</span></label>
                    <input type="date" name="due_date" value="{{ old('due_date', today()->addDays(30)->toDateString()) }}" required>
                </div>

                {{-- KOLOM INPUT PPN --}}
                <div class="field span-2">
                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                        <label for="tax_input">Nominal PPN (Rp) <span class="required">*</span></label>
                        <span style="font-size: 11.5px; color: #64748b;">Akun: Piutang Cust (D) · PPN Keluaran (K)</span>
                    </div>
                    <input type="number" id="tax_input" name="tax" min="0" max="999999999.99" step="0.01" value="{{ old('tax', '0') }}" required placeholder="0.00">
                    
                    <div style="display: flex; gap: 8px; margin-top: 8px; align-items: center; flex-wrap: wrap;">
                        <span style="font-size: 11.5px; color: #64748b;">Shortcut hitung:</span>
                        <button type="button" class="button button-secondary" style="font-size: 11.5px; padding: 3px 10px;" onclick="calculatePpn(0.11)">
                            11% (Jual Provision)
                        </button>
                        <button type="button" class="button button-secondary" style="font-size: 11.5px; padding: 3px 10px;" onclick="calculatePpn(0.011)">
                            1.1% (Forwarding)
                        </button>
                        <button type="button" class="button button-secondary" style="font-size: 11.5px; padding: 3px 10px;" onclick="setPpnZero()">
                            Tanpa PPN (0)
                        </button>
                    </div>
                    <span style="color: #64748b; font-size: 11.5px; margin-top: 6px; display: block;">
                        PPN Keluaran akan otomatis diposting ke COA 28000 dan menambah total piutang invoice ke COA 1103.
                    </span>
                </div>

                @if(($job->quotation_snapshot['currency'] ?? 'IDR') !== 'IDR')
                    <div class="field span-2">
                        <label>Kurs Override Valas (opsional)</label>
                        <input type="number" name="exchange_rate_override" min="0.0001" step="0.0001" value="{{ old('exchange_rate_override') }}" placeholder="Default {{ $job->quotation_snapshot['exchange_rate'] ?? 1 }}">
                    </div>
                @endif
            </div>

            <div class="form-actions" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 12.5px; color: #64748b;">Job order <strong>{{ $job->number }}</strong> akan tetap berstatus Open.</span>
                <button type="submit" class="button button-primary">
                    <x-icon name="check"/> Terbitkan Invoice Pra-Closing
                </button>
            </div>
        </form>
    </section>

    <script>
    function calculatePpn(rate) {
        const provisionSell = parseFloat("{{ $summary['provision_sell'] }}") || 0;
        const ppn = Math.round(provisionSell * rate * 100) / 100;
        document.getElementById('tax_input').value = ppn.toFixed(2);
    }
    function setPpnZero() {
        document.getElementById('tax_input').value = "0.00";
    }
    </script>
@endif
@endsection
