@extends('layouts.app')
@section('title','Detail Quotation')
@section('content')
<div class="page-heading"><div><p class="eyebrow">QUOTATION</p><h1>{{ $quotation->number }}</h1><p>{{ $quotation->subject }}</p></div><a class="button button-secondary" href="{{ route('quotations.index') }}">← Kembali</a></div>
<section class="panel"><div class="panel-heading"><h2>Informasi penawaran</h2><span class="status-badge status-{{ $quotation->status->value }}">{{ $quotation->status->label() }}</span></div>
<dl class="detail-grid"><div><dt>Customer</dt><dd><strong>{{ $quotation->customer_snapshot['name'] }}</strong><br>{{ $quotation->customer_snapshot['code'] }} @if($quotation->customer->trashed())<span class="count-badge">Diarsipkan</span>@endif</dd></div><div><dt>Tanggal quotation</dt><dd>{{ $quotation->quotation_date->format('d/m/Y') }}</dd></div><div><dt>Berlaku sampai</dt><dd>{{ $quotation->valid_until->format('d/m/Y') }}</dd></div><div><dt>Kontak</dt><dd>{{ $quotation->customer_snapshot['contact_name'] ?? '—' }}<br>{{ $quotation->customer_snapshot['email'] ?? '' }}<br>{{ $quotation->customer_snapshot['phone'] ?? '' }}</dd></div><div><dt>Alamat customer</dt><dd>{{ $quotation->customer_snapshot['address'] ?? '—' }}</dd></div><div><dt>Dibuat oleh</dt><dd>{{ $quotation->creator?->name ?? '—' }}<br>{{ $quotation->created_at->format('d/m/Y H:i') }}</dd></div>
@if($quotation->sales)<div><dt>Sales PIC</dt><dd>{{ $quotation->sales->name }}</dd></div>@endif
@if($quotation->service_type)<div><dt>Services (Layanan)</dt><dd>{{ \App\Models\ServiceType::label($quotation->service_type) }}</dd></div>@endif
@if($quotation->terms_of_delivery)<div><dt>Terms of Delivery</dt><dd>{{ config('operations.terms_of_delivery.'.$quotation->terms_of_delivery) ?? $quotation->terms_of_delivery }}</dd></div>@endif
@if($quotation->cargo_qty)<div><dt>Quantity (Shipment)</dt><dd>{{ $quotation->cargo_qty }}</dd></div>@endif
@if($quotation->weight_meas)<div><dt>Weight / Meas</dt><dd>{{ $quotation->weight_meas }}</dd></div>@endif
@if($quotation->commodity)<div><dt>Commodity</dt><dd>{{ $quotation->commodity }}</dd></div>@endif
@if($quotation->origin || $quotation->destination)<div><dt>Port of Loading → Discharge</dt><dd>{{ $quotation->origin ?? '—' }} → {{ $quotation->destination ?? '—' }}</dd></div>@endif
@if($quotation->payment_terms)<div><dt>Ketentuan pembayaran</dt><dd>{{ config('operations.customer_payment_terms.'.$quotation->payment_terms) ?? '—' }}</dd></div>@endif
@if($quotation->shipper_name)<div><dt>Shipper</dt><dd>{{ $quotation->shipper_name }}<br><small>{{ $quotation->shipper_address ?? '' }}</small></dd></div>@endif
@if($quotation->consignee_name)<div><dt>Consignee</dt><dd>{{ $quotation->consignee_name }}<br><small>{{ $quotation->consignee_address ?? '' }}</small></dd></div>@endif
</dl>
@php
    $canSeeFinancial = Gate::allows('financial.view') || Gate::allows('quotations.approve') || (auth()->user() && auth()->user()->hasRole(['sales-manager', 'super-admin', 'admin']));
@endphp
@if($canSeeFinancial)
<div class="table-scroll"><table class="quote-detail-table"><thead><tr><th>Uraian</th><th>Note</th><th>Mata Uang</th><th>Jenis</th><th>Jumlah / Satuan</th><th class="money">Modal / unit</th><th class="money">Jual / unit</th><th class="money">Total tagihan (IDR)</th></tr></thead><tbody>@foreach($quotation->items as $item)@php $snap = $item->pricing_snapshot ?? []; $fc = strtoupper($snap['currency'] ?? ($item->currency ?? 'IDR')); @endphp<tr><td>{{ $item->description }}@if($item->pricing_source === 'trucking')<br><small class="muted-cell">Tarif trucking · {{ $snap['port_origin'] ?? '' }} → {{ $snap['destination'] ?? '' }} · {{ strtoupper($item->container_type) }} @if($item->overweight) · OVERWEIGHT @endif @if($fc !== 'IDR') · {{ $fc }} {{ \App\Support\Money::format($snap['price'] ?? 0) }} @if(!empty($snap['vendor_name'])) · {{ $snap['vendor_name'] }} @endif @endif</small> @elseif($item->container_type)<br><small class="muted-cell">{{ strtoupper($item->container_type) }} @if($item->overweight) · OVERWEIGHT @endif @if($item->gross_weight !== null) · BW {{ \App\Support\Money::format($item->gross_weight) }} kg @endif @if($item->volume !== null) · {{ $item->volume }} m³ @endif</small> @endif</td><td>{{ $item->note ?? '—' }}</td><td><span class="status-badge" style="font-size:11px;">{{ $item->currency ?? 'IDR' }}</span>@if(($item->currency ?? 'IDR') !== 'IDR' && $item->exchange_rate)<br><small class="subtle">Kurs {{ \App\Support\Money::format($item->exchange_rate) }}</small>@endif</td><td>{{ ucfirst($item->type) }}</td><td>{{ \App\Support\Money::format($item->quantity) }} {{ $item->unit }}</td><td class="money">@if($item->unit_cost !== null){{ ($item->currency ?? 'IDR') !== 'IDR' ? ($item->currency.' '.\App\Support\Money::format($item->unit_cost)) : ('Rp '.\App\Support\Money::format($item->unit_cost)) }}@else — @endif</td><td class="money">{{ ($item->currency ?? 'IDR') !== 'IDR' ? ($item->currency.' '.\App\Support\Money::format($item->unit_price)) : ('Rp '.\App\Support\Money::format($item->unit_price)) }}</td><td class="money">Rp {{ \App\Support\Money::format($item->total_price) }}</td></tr>@endforeach</tbody></table></div>
@if($quotation->items->contains(fn ($item) => $item->pricing_source === 'trucking' || $item->currency !== 'IDR'))
<p class="form-help" style="margin: 12px 24px 0;">Nilai item valuta asing dikonversikan ke IDR sesuai kurs yang ditentukan pada penawaran.</p>
@endif
<div class="summary-box">@foreach(['total_temporary'=>'Temporary','total_provision_cost'=>'Modal provision','total_provision_sell'=>'Nilai jual provision','profit'=>'Estimasi profit'] as $field=>$label)<div class="summary-row"><span>{{ $label }}</span><strong>Rp {{ \App\Support\Money::format($quotation->$field) }}</strong></div>@endforeach<div class="summary-row"><span>Margin provision</span><strong>{{ \App\Support\Money::format($quotation->margin) }}%</strong></div><div class="summary-row summary-total"><span>Total sebelum pajak</span><strong>Rp {{ \App\Support\Money::format($quotation->subtotal) }}</strong></div></div>
@else
<div class="table-scroll"><table class="quote-detail-table"><thead><tr><th>Uraian</th><th>Note</th><th>Mata Uang</th><th>Jenis</th><th>Jumlah / Satuan</th><th class="money">Jual / unit</th><th class="money">Total tagihan (IDR)</th></tr></thead><tbody>@foreach($quotation->items as $item)<tr><td>{{ $item->description }}</td><td>{{ $item->note ?? '—' }}</td><td><span class="status-badge" style="font-size:11px;">{{ $item->currency ?? 'IDR' }}</span>@if(($item->currency ?? 'IDR') !== 'IDR' && $item->exchange_rate)<br><small class="subtle">Kurs {{ \App\Support\Money::format($item->exchange_rate) }}</small>@endif</td><td>{{ ucfirst($item->type) }}</td><td>{{ \App\Support\Money::format($item->quantity) }} {{ $item->unit }}</td><td class="money">{{ ($item->currency ?? 'IDR') !== 'IDR' ? ($item->currency.' '.\App\Support\Money::format($item->unit_price)) : ('Rp '.\App\Support\Money::format($item->unit_price)) }}</td><td class="money">Rp {{ \App\Support\Money::format($item->total_price) }}</td></tr>@endforeach</tbody></table></div>
<div class="summary-box"><div class="summary-row summary-total"><span>Total tagihan</span><strong>Rp {{ \App\Support\Money::format($quotation->subtotal) }}</strong></div></div>
@endif
@if($quotation->discount != 0 || $quotation->tax_rate != 0)
<div class="summary-box"><div class="summary-row"><span>Diskon</span><strong>− Rp {{ \App\Support\Money::format($quotation->discount) }}</strong></div><div class="summary-row"><span>Pajak ({{ \App\Support\Money::format($quotation->tax_rate) }}%)</span><strong>Rp {{ \App\Support\Money::format($quotation->tax_amount) }}</strong></div><div class="summary-row summary-total"><span>Grand total</span><strong>Rp {{ \App\Support\Money::format($quotation->grand_total) }}</strong></div></div>
@endif
@if($quotation->notes)<div class="detail-notes"><strong>Catatan</strong><br>{{ $quotation->notes }}</div>@endif
@if($quotation->approved_at)<div class="detail-notes">Disetujui {{ $quotation->approver?->name }} · {{ $quotation->approved_at->format('d/m/Y H:i') }}</div>@endif
@if($quotation->revised_at)<div class="detail-notes"><strong>Direvisi</strong><br>{{ $quotation->revisedBy?->name }} · {{ $quotation->revised_at->format('d/m/Y H:i') }}<br>{{ $quotation->revision_reason }}</div>@endif
@if($quotation->rejection_reason)<div class="detail-notes"><strong>Alasan penolakan</strong><br>{{ $quotation->rejection_reason }}</div>@endif
</section>
<div class="quote-actions">
@can('update',$quotation)<a class="button button-secondary" href="{{ route('quotations.edit',$quotation) }}">{{ in_array($quotation->status, [\App\Enums\QuotationStatus::Approved, \App\Enums\QuotationStatus::Converted, \App\Enums\QuotationStatus::Submitted], true) ? 'Edit Quotation' : 'Edit draft' }}</a>@endcan
<form method="POST" action="{{ route('quotations.duplicate',$quotation) }}" data-confirm="Buat salinan draft baru dari quotation ini?">@csrf<button class="button button-secondary">Duplikat draft</button></form>
<a class="button button-secondary" href="{{ route('quotations.preview',$quotation) }}" target="_blank">🖨 Preview PDF</a>
@can('submit',$quotation)<form method="POST" action="{{ route('quotations.submit',$quotation) }}" data-confirm="Ajukan quotation ini? Draft tidak dapat diedit setelah diajukan.">@csrf<input type="hidden" name="lock_version" value="{{ $quotation->lock_version }}"><button class="button button-primary">Ajukan quotation</button></form>@endcan
@can('approve',$quotation)
<button type="button" class="button button-primary" onclick="document.getElementById('modal-approve-quotation').showModal()">Setujui Quotation</button>
@endcan
@can('convert',$quotation)
<form method="POST" action="{{ route('quotations.convert',$quotation) }}" data-confirm="Konversi quotation ini ke Job Order? Job akan langsung berstatus Open dan siap untuk pengisian biaya oleh Finance.">@csrf<input type="hidden" name="lock_version" value="{{ $quotation->lock_version }}"><button class="button button-primary">Konversi ke Job Order <x-icon name="arrow"/></button></form>
@endcan
@if($quotation->status === \App\Enums\QuotationStatus::Approved && !$quotation->job && !Gate::allows('convert', $quotation))
<span class="badge-pill" style="background:#e0f2fe;color:#0369a1;padding:8px 14px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;"><x-icon name="clock" style="width:14px;height:14px;"/> Quotation disetujui · Siap diproses Job Order oleh Customer Service (CS)</span>
@endif
@if($quotation->job)
    @if($quotation->job->status === 'cancelled')
        <span class="badge-pill" style="background:#fee2e2;color:#991b1b;padding:8px 14px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;"><x-icon name="x" style="width:14px;height:14px;"/> Job Order {{ $quotation->job->number }} telah Dibatalkan</span>
    @else
        <a class="button button-primary" href="{{ route('jobs.show',$quotation->job) }}">Lihat {{ $quotation->job->number }} <x-icon name="arrow"/></a>
        @can('cancel', $quotation->job)
            <button type="button" class="button button-danger" onclick="document.getElementById('modal-cancel-job-quote').showModal()">
                <x-icon name="x"/> Batalkan Job Order
            </button>
        @endcan
    @endif
@endif
</div>

@can('approve',$quotation)
{{-- MODAL APPROVAL & INPUT MODAL SALES MANAGER --}}
<dialog id="modal-approve-quotation" class="modal-dialog" style="max-width: 820px !important;">
    <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-top-left-radius: 18px; border-top-right-radius: 18px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: #eff6ff; color: #2563eb; font-size: 20px; box-shadow: 0 2px 6px rgba(37,99,235,0.15);">
                💰
            </div>
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Persetujuan Quotation & Penginputan Modal</h3>
                <p style="margin: 2px 0 0; font-size: 12px; color: #64748b;">Periksa & masukkan nilai modal untuk setiap item penawaran sebelum menyetujui.</p>
            </div>
        </div>
        <button type="button" onclick="document.getElementById('modal-approve-quotation').close()" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; display: grid; place-items: center; cursor: pointer; font-size: 14px; transition: all .15s ease;" onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444';" onmouseout="this.style.background='#fff';this.style.color='#64748b';">✕</button>
    </div>

    <form method="POST" action="{{ route('quotations.approve', $quotation) }}" id="form-approve-cost" style="padding: 20px 24px;">
        @csrf
        <input type="hidden" name="lock_version" value="{{ $quotation->lock_version }}">

        <div style="max-height: 360px; overflow-y: auto; margin-bottom: 18px; border: 1px solid #e2e8f0; border-radius: 10px;">
            <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; text-align: left; border-bottom: 1.5px solid #cbd5e1; color: #475569; font-weight: 700;">
                        <th style="padding: 10px 12px;">Uraian Biaya</th>
                        <th style="padding: 10px 12px;">Qty / Satuan</th>
                        <th style="padding: 10px 12px; text-align: right;">Harga Jual (IDR)</th>
                        <th style="padding: 10px 12px; text-align: right; width: 170px;">Modal / Unit (IDR) <span style="color: #e11d48;">*</span></th>
                        <th style="padding: 10px 12px; text-align: right;">Subtotal Modal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quotation->items as $idx => $item)
                    @php
                        $rate = (float)($item->exchange_rate ?? 1);
                        $qty = (float)$item->quantity;
                        $sellIdr = (float)$item->unit_price * $rate;
                        $currentCost = (float)($item->unit_cost ?? 0);
                        $formattedCost = $currentCost > 0 ? number_format($currentCost, 0, ',', '.') : '0';
                    @endphp
                    <tr style="border-bottom: 1px solid #f1f5f9;" data-item-row data-qty="{{ $qty }}" data-rate="{{ $rate }}" data-sell="{{ $sellIdr }}">
                        <td style="padding: 12px;">
                            <strong style="color: #1e293b;">{{ $item->description }}</strong>
                            @if($item->note)<br><small class="subtle" style="color: #64748b;">{{ $item->note }}</small>@endif
                        </td>
                        <td style="padding: 12px; color: #334155;">{{ \App\Support\Money::format($item->quantity) }} {{ $item->unit }}</td>
                        <td style="padding: 12px; text-align: right; font-weight: 600; color: #1e293b;">
                            Rp {{ \App\Support\Money::format($sellIdr) }}
                        </td>
                        <td style="padding: 12px; text-align: right;">
                            <input type="text" inputmode="numeric" name="items[{{ $item->id }}][unit_cost]" value="{{ $formattedCost }}" required class="approve-cost-input" style="width: 100%; text-align: right; padding: 7px 10px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-weight: 700; color: #1e3a8a; font-size: 13.5px;" placeholder="0" autocomplete="off">
                        </td>
                        <td style="padding: 12px; text-align: right; font-weight: 700; color: #475569;" data-row-cost-total>
                            Rp {{ \App\Support\Money::format($currentCost * $qty * $rate) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- SUMMARY LIVE PREVIEW --}}
        <div style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;">
            <div>
                <span style="font-size: 11px; color: #64748b; font-weight: 600; display: block; margin-bottom: 3px;">Total Penawaran (Jual)</span>
                <strong style="font-size: 15px; color: #0f172a;" id="preview-total-sell">Rp {{ \App\Support\Money::format($quotation->total_provision_sell) }}</strong>
            </div>
            <div>
                <span style="font-size: 11px; color: #64748b; font-weight: 600; display: block; margin-bottom: 3px;">Total Modal</span>
                <strong style="font-size: 15px; color: #b91c1c;" id="preview-total-cost">Rp {{ \App\Support\Money::format($quotation->total_provision_cost) }}</strong>
            </div>
            <div>
                <span style="font-size: 11px; color: #64748b; font-weight: 600; display: block; margin-bottom: 3px;">Estimasi Profit</span>
                <strong style="font-size: 15px; color: #16a34a;" id="preview-total-profit">Rp {{ \App\Support\Money::format($quotation->profit) }}</strong>
            </div>
            <div>
                <span style="font-size: 11px; color: #64748b; font-weight: 600; display: block; margin-bottom: 3px;">Margin</span>
                <strong style="font-size: 15px; color: #2563eb;" id="preview-margin">{{ \App\Support\Money::format($quotation->margin) }}%</strong>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
            <button type="button" class="button button-secondary" onclick="document.getElementById('modal-approve-quotation').close()">Batal</button>
            <button type="submit" class="button button-primary" style="background: #16a34a; border-color: #16a34a;">✓ Setujui & Simpan Modal</button>
        </div>
    </form>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dialog = document.getElementById('modal-approve-quotation');
    if (!dialog) return;

    dialog.addEventListener('click', function(e) {
        const rect = dialog.getBoundingClientRect();
        const isInDialog = (rect.top <= e.clientY && e.clientY <= rect.top + rect.height && rect.left <= e.clientX && e.clientX <= rect.left + rect.width);
        if (!isInDialog) dialog.close();
    });

    const rows = dialog.querySelectorAll('[data-item-row]');
    const totalSellEl = document.getElementById('preview-total-sell');
    const totalCostEl = document.getElementById('preview-total-cost');
    const totalProfitEl = document.getElementById('preview-total-profit');
    const marginEl = document.getElementById('preview-margin');

    function parseNum(val) {
        if (!val) return 0;
        let s = String(val).trim().replace(/\s/g, '');
        if (s.includes('.') && s.includes(',')) s = s.replace(/\./g, '').replace(',', '.');
        else if (s.match(/^\d{1,3}(\.\d{3})+$/)) s = s.replace(/\./g, '');
        else if (s.includes(',')) s = s.replace(',', '.');
        return parseFloat(s) || 0;
    }

    function fmt(n) {
        return 'Rp ' + Number(n).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function recalc() {
        let sumSell = 0;
        let sumCost = 0;
        rows.forEach(r => {
            const qty = parseFloat(r.dataset.qty) || 0;
            const rate = parseFloat(r.dataset.rate) || 1;
            const sell = parseFloat(r.dataset.sell) || 0;
            const input = r.querySelector('.approve-cost-input');
            const costVal = parseNum(input.value);
            const rowCostTotal = costVal * qty * rate;
            sumSell += sell * qty;
            sumCost += rowCostTotal;
            const rowCostEl = r.querySelector('[data-row-cost-total]');
            if (rowCostEl) rowCostEl.textContent = fmt(rowCostTotal);
        });
        const profit = sumSell - sumCost;
        const margin = sumSell > 0 ? ((profit / sumSell) * 100) : 0;
        if (totalCostEl) totalCostEl.textContent = fmt(sumCost);
        if (totalProfitEl) totalProfitEl.textContent = fmt(profit);
        if (marginEl) marginEl.textContent = margin.toFixed(2).replace('.', ',') + '%';
    }

    dialog.querySelectorAll('.approve-cost-input').forEach(inp => {
        inp.addEventListener('input', function() {
            let raw = inp.value.replace(/\D/g, '');
            if (raw === '') {
                inp.value = '';
            } else {
                let num = parseInt(raw, 10);
                inp.value = num.toLocaleString('id-ID');
            }
            recalc();
        });
        inp.addEventListener('focus', function() {
            if (inp.value === '0') inp.value = '';
        });
        inp.addEventListener('blur', function() {
            if (inp.value === '') inp.value = '0';
            recalc();
        });
    });
});
</script>
@endcan

@can('revise',$quotation)<section class="panel"><form class="transition-form" method="POST" action="{{ route('quotations.revise',$quotation) }}" data-confirm="Minta revisi quotation? Sales dapat mengedit ulang quotation setelah direvisi.">@csrf<input type="hidden" name="lock_version" value="{{ $quotation->lock_version }}"><label for="revision">Catatan revisi</label><textarea id="revision" name="reason" required maxlength="1000" rows="2" placeholder="Jelaskan bagian yang perlu diperbaiki">{{ old('reason') }}</textarea><button class="button button-secondary">Minta revisi</button></form></section>@endcan
@can('reject',$quotation)<section class="panel"><form class="transition-form" method="POST" action="{{ route('quotations.reject',$quotation) }}" data-confirm="Tolak quotation ini? Quotation yang ditolak tidak dapat dikonversi.">@csrf<input type="hidden" name="lock_version" value="{{ $quotation->lock_version }}"><label for="reason">Alasan penolakan</label><textarea id="reason" name="reason" required maxlength="1000" rows="2" placeholder="Jelaskan alasan penawaran ditolak">{{ old('reason') }}</textarea><button class="button button-danger">Tolak quotation</button></form></section>@endcan
@if($quotation->statusHistory->isNotEmpty())
<section class="panel"><div class="panel-heading"><h2>Riwayat status</h2></div><ol class="approval-timeline">@foreach($quotation->statusHistory as $event)<li><span></span><div><strong>@if($event->from_status){{ \App\Enums\QuotationStatus::tryFrom($event->from_status)?->label() ?? $event->from_status }} → {{ \App\Enums\QuotationStatus::tryFrom($event->to_status)?->label() ?? $event->to_status }}@else Dibuat → {{ \App\Enums\QuotationStatus::tryFrom($event->to_status)?->label() ?? $event->to_status }}@endif</strong><p>{{ $event->user?->name ?? 'System' }} · {{ $event->created_at->format('d/m/Y H:i') }}@if($event->note)<br>{{ $event->note }}@endif</p></div></li>@endforeach</ol></section>
@endif

@if($quotation->job && $quotation->job->status !== 'cancelled')
@can('cancel', $quotation->job)
<dialog id="modal-cancel-job-quote" class="modal-dialog" style="max-width: 520px !important;">
    <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border-top-left-radius: 18px; border-top-right-radius: 18px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: #fee2e2; color: #e11d48; font-size: 20px; box-shadow: 0 2px 6px rgba(225,29,72,0.15);">
                ⚠️
            </div>
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #9f1239;">Pembatalan Job Order</h3>
                <p style="margin: 2px 0 0; font-size: 12px; color: #be123c;">Batalkan pekerjaan ini jika terjadi revisi penawaran.</p>
            </div>
        </div>
        <button type="button" onclick="document.getElementById('modal-cancel-job-quote').close()" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid #fecdd3; background: #fff; color: #be123c; display: grid; place-items: center; cursor: pointer; font-size: 14px; transition: all .15s ease;">✕</button>
    </div>

    <form method="POST" action="{{ route('jobs.cancel', $quotation->job) }}" style="padding: 20px 24px;">
        @csrf
        <input type="hidden" name="lock_version" value="{{ $quotation->job->lock_version }}">
        <p style="color: #475569; font-size: 13.5px; line-height: 1.5; margin-top: 0;">
            Apakah Anda yakin ingin membatalkan Job Order <strong>{{ $quotation->job->number }}</strong>?
            Setelah dibatalkan, Sales Manager dapat mengedit kembali Quotation terkait.
        </p>
        <div style="margin: 16px 0;">
            <label for="cancel_reason_quote" style="display: block; font-weight: 700; font-size: 12.5px; margin-bottom: 6px; color: #334155;">Alasan Pembatalan <span style="color: #e11d48;">*</span></label>
            <textarea id="cancel_reason_quote" name="reason" rows="3" required placeholder="Jelaskan alasan pembatalan Job Order..." style="width: 100%; padding: 10px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 13.5px; resize: vertical; box-sizing: border-box;"></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
            <button type="button" class="button button-secondary" onclick="document.getElementById('modal-cancel-job-quote').close()">Batal</button>
            <button type="submit" class="button button-danger">Ya, Batalkan Job</button>
        </div>
    </form>
</dialog>
@endcan
@endif
@endsection
