@extends('layouts.app')
@section('title',$cost->exists?'Edit Biaya':'Tambah Biaya')
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ $job->number }}</p><h1>{{ $cost->exists?'Edit biaya Draft':'Tambah biaya' }}</h1><p>{{ $job->subject }}</p></div><a class="button button-secondary" href="{{ route('jobs.costs.index',$job) }}">← Kembali</a></div>
<section class="panel form-panel"><form class="data-form" data-cost-form method="POST" action="{{ $cost->exists?route('jobs.costs.update',[$job,$cost]):route('jobs.costs.store',$job) }}">@csrf @if($cost->exists) @method('PUT') @endif
<input type="hidden" name="job_version" value="{{ old('job_version',$job->lock_version) }}"><input type="hidden" name="lock_version" value="{{ old('lock_version',$cost->lock_version ?? 0) }}">
<div class="info-note">Reimburse ditagihkan kembali sebesar modal (Temporary Payment). Non Reimburse memiliki modal dan nilai jual; selisihnya menjadi estimasi profit job.</div>
<div class="form-grid">
<div class="field span-2">
    <label for="description">Uraian biaya <span class="required">*</span></label>
    <select id="description" name="description" data-custom-select data-allow-custom="true" placeholder="Pilih dari Master Cost atau ketik uraian biaya..." required>
        <option value="">Pilih dari Master Cost atau ketik uraian biaya...</option>
        @foreach($chargeTypes ?? \App\Models\ChargeType::where('is_active', true)->orderBy('name')->get(['name']) as $charge)
            <option value="{{ $charge->name }}" @selected(old('description',$cost->description) === $charge->name)>{{ $charge->name }}</option>
        @endforeach
        @if($curDesc = old('description', $cost->description))
            @if(!collect($chargeTypes ?? [])->contains('name', $curDesc))
                <option value="{{ $curDesc }}" selected>{{ $curDesc }}</option>
            @endif
        @endif
    </select>
    <p class="form-help">Pilih dari Master Cost (jenis biaya) atau ketik deskripsi.</p>
</div>

<div class="field">
    <label for="type">Jenis biaya</label>
    <select id="type" name="type" data-cost-type>
        <option value="provision" @selected(old('type',$cost->type)==='provision')>Non Reimburse (Modal & Nilai Jual)</option>
        <option value="temporary" @selected(old('type',$cost->type)==='temporary')>Reimburse (Reimbursement)</option>
    </select>
</div>

<div class="field" id="category_field">
    <label for="cost_category">Kategori Transaksi (Alur COA)</label>
    <select id="cost_category" name="cost_category">
        <option value="payment_request" @selected(old('cost_category', $cost->cost_category ?? 'payment_request') === 'payment_request') data-cost-type="provision">Payment Request (Provisional → Piutang Customer)</option>
        <option value="debit_note" @selected(old('cost_category', $cost->cost_category) === 'debit_note') data-cost-type="provision">Debit Note (Provisional → Piutang Agent)</option>
        <option value="credit_note" @selected(old('cost_category', $cost->cost_category) === 'credit_note') data-cost-type="provision">Credit Note (Hutang Agent)</option>
        <option value="reimbursement" @selected(old('cost_category', $cost->cost_category ?? 'reimbursement') === 'reimbursement') data-cost-type="temporary">Reimbursement (Temporary Payment → Piutang Temporary)</option>
    </select>
</div>

<div class="field"><label for="cost_date">Tanggal biaya</label><input id="cost_date" name="cost_date" type="date" value="{{ old('cost_date',$cost->cost_date?->format('Y-m-d') ?? now()->toDateString()) }}" min="{{ $job->job_date->format('Y-m-d') }}" max="{{ now()->toDateString() }}" required></div>
<div class="field"><label for="quantity">Jumlah</label><input id="quantity" name="quantity" type="number" min="0.01" max="999999.99" step="0.01" value="{{ old('quantity',$cost->quantity ?? '1') }}" data-quantity required></div>
<div class="field"><label for="unit">Satuan</label><input id="unit" name="unit" value="{{ old('unit',$cost->unit ?? 'Layanan') }}" maxlength="30" required></div>
<div class="field"><label for="unit_cost">Modal per unit (Rp)</label><input id="unit_cost" name="unit_cost" type="number" min="0" max="999999999.99" step="0.01" value="{{ old('unit_cost',$cost->unit_cost ?? '0') }}" data-unit-cost required></div>
<div class="field"><label for="unit_price">Nilai jual per unit (Rp)</label><input id="unit_price" name="unit_price" type="number" min="0" max="999999999.99" step="0.01" value="{{ old('unit_price',$cost->unit_price ?? '0') }}" data-unit-price required><p class="form-help" data-temporary-help>Untuk Reimburse, nilai jual otomatis mengikuti modal.</p></div>

<div class="field">
    <label for="vendor_id">Penerima / Vendor</label>
    @php
        $rawVendors = $vendors ?? \App\Models\Vendor::where('is_active', true)->with('categories')->orderBy('name')->get();
        $groupedVendors = $rawVendors->groupBy(function($v) {
            $types = config('operations.vendor_types');
            $cats = $v->categories->pluck('category')->all();
            if (empty($cats) && $v->type) { $cats = [$v->type]; }
            $first = $cats[0] ?? 'other';
            return $types[$first] ?? 'Lainnya';
        });
    @endphp
    <select id="vendor_id" name="vendor_id">
        <option value="">— Pilih Vendor (Shipping Lines/Trucking/Agent) —</option>
        @foreach($groupedVendors as $groupLabel => $vList)
            <optgroup label="{{ $groupLabel }}">
                @foreach($vList as $vItem)
                    <option value="{{ $vItem->id }}" data-name="{{ $vItem->name }}" @selected(old('vendor_id', $cost->vendor_id) == $vItem->id || (!old('vendor_id') && old('payee', $cost->payee) === $vItem->name))>
                        {{ $vItem->code ? '['.$vItem->code.'] ' : '' }}{{ $vItem->name }}
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
    <input type="hidden" id="payee" name="payee" value="{{ old('payee',$cost->payee) }}">
</div>

<div class="field"><label for="reference">Nomor bukti / referensi</label><input id="reference" name="reference" value="{{ old('reference',$cost->reference) }}" maxlength="100" placeholder="Nomor kuitansi atau referensi"></div>
<div class="field span-2"><label for="notes">Catatan</label><textarea id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes',$cost->notes) }}</textarea></div>
</div>
<div class="summary-box" aria-live="polite"><div class="summary-row"><span>Total modal</span><strong data-preview-cost>Rp 0,00</strong></div><div class="summary-row"><span>Total jual</span><strong data-preview-total>Rp 0,00</strong></div><div class="summary-row summary-total"><span>Estimasi profit</span><strong data-preview-profit>Rp 0,00</strong></div></div>
<div class="form-actions"><a class="button button-secondary" href="{{ route('jobs.costs.index',$job) }}">Batal</a><button class="button button-primary">Simpan biaya Draft</button></div></form></section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('[data-cost-type]');
    const catSelect = document.getElementById('cost_category');
    const vendorSelect = document.getElementById('vendor_id');
    const payeeInput = document.getElementById('payee');

    if (vendorSelect && payeeInput) {
        vendorSelect.addEventListener('change', function() {
            const opt = vendorSelect.options[vendorSelect.selectedIndex];
            if (opt && opt.dataset.name) {
                payeeInput.value = opt.dataset.name;
            } else if (!vendorSelect.value) {
                payeeInput.value = '';
            }
        });
    }

    if (typeSelect && catSelect) {
        function syncCategory() {
            const isTemp = typeSelect.value === 'temporary';
            Array.from(catSelect.options).forEach(opt => {
                const optType = opt.getAttribute('data-cost-type');
                if (isTemp) {
                    opt.hidden = (optType !== 'temporary');
                } else {
                    opt.hidden = (optType === 'temporary');
                }
            });
            if (isTemp && catSelect.value !== 'reimbursement') {
                catSelect.value = 'reimbursement';
            } else if (!isTemp && catSelect.value === 'reimbursement') {
                catSelect.value = 'payment_request';
            }
        }
        typeSelect.addEventListener('change', syncCategory);
        syncCategory();
    }
});
</script>
@endsection
