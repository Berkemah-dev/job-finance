@extends('layouts.app')
@section('title', 'Edit DNP ' . $dnp->number)
@section('content')

@php
    $backUrl = route('dnps.show', $dnp);
    $formatNum = function($val, $curr = 'IDR') {
        if ($val === null || $val === '') return '';
        $num = (float) $val;
        if ($num == 0) return '';
        if ($curr === 'IDR' || $num == (int)$num) {
            return number_format($num, 0, ',', '.');
        }
        return rtrim(rtrim(number_format($num, 2, ',', '.'), '0'), ',');
    };
@endphp

<style>
.form-grid .field {
    display: flex;
    flex-direction: column;
}
.form-grid .field label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.4;
}
.dnp-doc-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}
.dnp-doc-table th, .dnp-doc-table td {
    padding: 10px 14px;
    font-size: 13px;
    border-bottom: 1px solid #f1f5f9;
}
.dnp-doc-table th {
    background: #f8fafc;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    font-size: 11.5px;
    letter-spacing: 0.5px;
}
.dnp-doc-table tr:hover {
    background: #f8fafc;
}
.dnp-doc-table td.checkbox-cell {
    text-align: center;
    width: 60px;
}
.dnp-doc-table td.no-cell {
    text-align: center;
    width: 50px;
    font-weight: 600;
    color: #64748b;
}
</style>

<div class="page-heading">
    <div>
        <p class="eyebrow">KEPABEANAN IMPORT</p>
        <h1>Edit Deklarasi Nilai Pabean ({{ $dnp->number }})</h1>
        <p>Perbarui rincian nilai transaksi dan dokumen pendukung DNP.</p>
    </div>
    <a class="button button-secondary" href="{{ $backUrl }}">← Kembali</a>
</div>

<section class="panel form-panel">
    @if($errors->any())
        <div style="margin-bottom: 20px; padding: 14px 18px; border-radius: 8px; background: #fef2f2; border: 1px solid #f87171; color: #991b1b; font-size: 13px;">
            <strong style="display: block; margin-bottom: 6px; font-size: 14px;">⚠️ Periksa kembali isian form Anda:</strong>
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="data-form" method="POST" action="{{ route('dnps.update', $dnp) }}" id="dnpForm">
        @csrf
        @method('PUT')

        {{-- 1. DATA DNP --}}
        <div class="panel-heading" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 20px;">
            <div>
                <h2 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0 0 4px; display: flex; align-items: center; gap: 8px;">
                    <x-icon name="file" style="width: 16px; height: 16px; color: #2563eb;"/> Data Deklarasi Nilai Pabean
                </h2>
                <span class="subtle" style="font-size: 12.5px;">Rincian pihak transaksi, mata uang, dan nilai pabean (CIF)</span>
            </div>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="dnp_date">Tanggal <span class="required">*</span></label>
                <input id="dnp_date" name="dnp_date" type="date" value="{{ old('dnp_date', $dnp->dnp_date?->format('Y-m-d')) }}" required>
            </div>

            <div class="field">
                <label for="job_id">#Job No <span class="required">*</span></label>
                <select id="job_id" name="job_id">
                    <option value="">-- Pilih Job Order --</option>
                    @foreach($jobs as $j)
                        <option value="{{ $j->id }}"
                            @selected(old('job_id', $dnp->job_id) == $j->id)
                            data-customer-id="{{ $j->customer_id }}"
                            data-consignee="{{ $j->consignee_name ?? $j->customer?->name }}"
                            data-shipper="{{ $j->shipper_name }}"
                            data-importer="{{ $j->customer?->name ?? $j->consignee_name }}"
                            data-commodity="{{ $j->cargo_description }}"
                        >
                            {{ $j->number }} — {{ $j->customer?->name }} ({{ Str::limit($j->cargo_description, 30) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field span-2">
                <label for="consignee_name">Consignee (Pembeli)</label>
                <input id="consignee_name" name="consignee_name" maxlength="160"
                    value="{{ old('consignee_name', $dnp->consignee_name) }}"
                    placeholder="Nama Pembeli / Consignee">
            </div>

            <div class="field span-2">
                <label for="shipper_name">Shipper (Penjual)</label>
                <input id="shipper_name" name="shipper_name" maxlength="160"
                    value="{{ old('shipper_name', $dnp->shipper_name) }}"
                    placeholder="Nama Penjual / Shipper">
            </div>

            <div class="field span-2">
                <label for="importer_name">Importir</label>
                <input id="importer_name" name="importer_name" maxlength="160"
                    value="{{ old('importer_name', $dnp->importer_name) }}"
                    placeholder="Nama Importir">
            </div>

            <div class="field span-2">
                <label for="commodity">
                    <span style="font-weight: 700; color: #1e293b;">Commodity (Nama Barang)</span>
                    <span style="font-size: 11.5px; color: #64748b; font-weight: normal; margin-left: 6px;">— Otomatis terisi dari Job Order</span>
                </label>
                <input id="commodity" name="commodity" maxlength="500"
                    value="{{ old('commodity', $dnp->commodity ?: $dnp->job?->cargo_description) }}"
                    placeholder="Nama barang / komoditas deklarasi pabean">
            </div>

            <div class="field">
                <label for="currency">Currency <span class="required">*</span></label>
                <select id="currency" name="currency" required>
                    @foreach(['USD', 'IDR', 'EUR', 'SGD', 'CNY', 'JPY', 'GBP', 'AUD'] as $curr)
                        <option value="{{ $curr }}" @selected(old('currency', $dnp->currency) === $curr)>{{ $curr }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="invoice_value">Harga Dalam Invoice <span class="dnp-curr-label" style="font-weight: 700; color: #2563eb;">({{ old('currency', $dnp->currency ?? 'IDR') }})</span></label>
                <input id="invoice_value" name="invoice_value" inputmode="decimal"
                    value="{{ old('invoice_value', $formatNum($dnp->invoice_value, $dnp->currency)) }}" placeholder="0">
            </div>

            <div class="field">
                <label for="freight">Biaya Transportasi <span class="dnp-curr-label" style="font-weight: 700; color: #2563eb;">({{ old('currency', $dnp->currency ?? 'IDR') }})</span></label>
                <input id="freight" name="freight" inputmode="decimal"
                    value="{{ old('freight', $formatNum($dnp->freight, $dnp->currency)) }}" placeholder="0">
            </div>

            <div class="field">
                <label for="insurance">Asuransi <span class="dnp-curr-label" style="font-weight: 700; color: #2563eb;">({{ old('currency', $dnp->currency ?? 'IDR') }})</span></label>
                <input id="insurance" name="insurance" inputmode="decimal"
                    value="{{ old('insurance', $formatNum($dnp->insurance, $dnp->currency)) }}" placeholder="0">
            </div>

            <div class="field">
                <label for="is_repeated_transaction">Trans Pengulangan (F)</label>
                <select id="is_repeated_transaction" name="is_repeated_transaction">
                    <option value="0" @selected(!old('is_repeated_transaction', $dnp->is_repeated_transaction))>Tidak</option>
                    <option value="1" @selected(old('is_repeated_transaction', $dnp->is_repeated_transaction) == 1)>Ya</option>
                </select>
            </div>

            <input type="hidden" name="number" value="{{ old('number', $dnp->number) }}">
            <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id', $dnp->customer_id) }}">
        </div>

        {{-- 2. DOKUMEN PENDUKUNG --}}
        <div class="panel-heading" style="border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding-top: 20px; padding-bottom: 12px; margin-top: 32px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0 0 4px; display: flex; align-items: center; gap: 8px;">
                    <x-icon name="file-text" style="width: 16px; height: 16px; color: #2563eb;"/> Dokumen Pendukung Pabean
                </h2>
                <span class="subtle" style="font-size: 12.5px;">Centang dokumen bukti transaksi yang dilampirkan bersama DNP</span>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="button button-secondary button-sm" onclick="selectStandardDocs()" style="font-size: 12px;">
                    ✓ Dokumen Standar (1–4)
                </button>
                <button type="button" class="button button-secondary button-sm" onclick="toggleAllDocs(true)" style="font-size: 12px;">
                    Pilih Semua
                </button>
                <button type="button" class="button button-secondary button-sm" onclick="toggleAllDocs(false)" style="font-size: 12px;">
                    Kosongkan
                </button>
            </div>
        </div>

        @php
            $oldDocs = old('supporting_documents', $dnp->supporting_documents ?? []);
        @endphp

        <div class="table-scroll">
            <table class="dnp-doc-table">
                <thead>
                    <tr>
                        <th class="no-cell">NO</th>
                        <th class="checkbox-cell">PILIH</th>
                        <th>KETERANGAN DOKUMEN PENDUKUNG</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($supportingDocsList as $num => $docLabel)
                        @php
                            $isChecked = in_array((string)$num, array_map('strval', $oldDocs)) || in_array($num, $oldDocs);
                        @endphp
                        <tr>
                            <td class="no-cell">{{ $num }}.</td>
                            <td class="checkbox-cell">
                                <input type="checkbox" id="doc_{{ $num }}" name="supporting_documents[]" value="{{ $num }}"
                                    class="dnp-checkbox"
                                    @checked($isChecked)
                                    style="width: 17px; height: 17px; cursor: pointer; accent-color: #2563eb;">
                            </td>
                            <td>
                                <label for="doc_{{ $num }}" style="cursor: pointer; display: block; margin: 0; font-weight: 500; font-size: 13px; color: #1e293b;">
                                    {{ $docLabel }}
                                </label>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="form-actions" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
            <a class="button button-secondary" href="{{ $backUrl }}">Batal</a>
            <button class="button button-primary">Simpan Perubahan DNP</button>
        </div>
    </form>
</section>

<script>
document.getElementById('job_id')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (!opt || !opt.value) return;

    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el && val !== undefined && val !== null) el.value = val;
    };

    if (opt.dataset.customerId) setVal('customer_id', opt.dataset.customerId);
    if (opt.dataset.consignee) setVal('consignee_name', opt.dataset.consignee);
    if (opt.dataset.shipper) setVal('shipper_name', opt.dataset.shipper);
    if (opt.dataset.importer) setVal('importer_name', opt.dataset.importer);
    if (opt.dataset.commodity) setVal('commodity', opt.dataset.commodity);
});

function selectStandardDocs() {
    const standard = ['1', '2', '3', '4'];
    document.querySelectorAll('.dnp-checkbox').forEach(cb => {
        cb.checked = standard.includes(cb.value);
    });
}

function toggleAllDocs(state) {
    document.querySelectorAll('.dnp-checkbox').forEach(cb => {
        cb.checked = state;
    });
}

const currSelect = document.getElementById('currency');
const updateCurrencyLabels = () => {
    const val = currSelect?.value || 'IDR';
    document.querySelectorAll('.dnp-curr-label').forEach(el => {
        el.textContent = `(${val})`;
    });
};
currSelect?.addEventListener('change', updateCurrencyLabels);
updateCurrencyLabels();

function formatRibuan(input) {
    let val = input.value.replace(/[^\d]/g, '');
    if (val) {
        input.value = new Intl.NumberFormat('id-ID').format(val);
    }
}
const priceInputs = [
    document.getElementById('invoice_value'),
    document.getElementById('freight'),
    document.getElementById('insurance')
];
priceInputs.forEach(inp => {
    if (!inp) return;
    inp.addEventListener('input', function() {
        const curr = currSelect?.value || 'IDR';
        if (curr === 'IDR') {
            formatRibuan(this);
        }
    });
});
</script>

@endsection
