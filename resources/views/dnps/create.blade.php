@extends('layouts.app')
@section('title', 'Buat Deklarasi Nilai Pabean (DNP)')
@section('content')

@php
    $backUrl = $selectedJob
        ? route('jobs.show', $selectedJob) . '#tab-dnp'
        : route('dnps.index');
@endphp

<style>
.form-grid .field {
    display: flex;
    flex-direction: column;
}
.form-grid .field label {
    min-height: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 11.5px;
    font-weight: 600;
    color: #334155;
    line-height: 1.3;
}
.dnp-doc-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}
.dnp-doc-table th, .dnp-doc-table td {
    padding: 9px 14px;
    font-size: 12.5px;
    border-bottom: 1px solid #f1f5f9;
}
.dnp-doc-table th {
    background: #f8fafc;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
}
.dnp-doc-table tr:hover {
    background: #f8fafc;
}
.dnp-doc-table td.checkbox-cell {
    text-align: center;
    width: 90px;
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
        <h1>Buat Deklarasi Nilai Pabean (DNP)</h1>
        <p>Dokumen deklarasi nilai pabean resmi untuk pengisian dan pendaftaran PIB.</p>
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

    <form class="data-form" method="POST" action="{{ route('dnps.store') }}" id="dnpForm">
        @csrf

        {{-- 1. DATA DNP --}}
        <div class="form-section-heading" style="background: #1e40af; color: #fff; padding: 10px 16px; border-radius: 6px; margin-bottom: 16px;">
            <h2 style="color: #fff; margin: 0; font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <span>📋</span> Data DNP
            </h2>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="dnp_date">Tanggal <span class="required">*</span></label>
                <input id="dnp_date" name="dnp_date" type="date" value="{{ old('dnp_date', date('Y-m-d')) }}" required>
            </div>

            <div class="field">
                <label for="job_id">#Job No <span class="required">*</span></label>
                <select id="job_id" name="job_id">
                    <option value="">-- Pilih Job Order --</option>
                    @foreach($jobs as $j)
                        <option value="{{ $j->id }}"
                            @selected(old('job_id', $selectedJob?->id) == $j->id)
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
                    value="{{ old('consignee_name', $selectedJob?->consignee_name ?? $selectedJob?->customer?->name) }}"
                    placeholder="Nama Pembeli / Consignee">
            </div>

            <div class="field span-2">
                <label for="shipper_name">Shipper (Penjual)</label>
                <input id="shipper_name" name="shipper_name" maxlength="160"
                    value="{{ old('shipper_name', $selectedJob?->shipper_name) }}"
                    placeholder="Nama Penjual / Shipper">
            </div>

            <div class="field span-2">
                <label for="importer_name">Importir</label>
                <input id="importer_name" name="importer_name" maxlength="160"
                    value="{{ old('importer_name', $selectedJob?->customer?->name ?? $selectedJob?->consignee_name) }}"
                    placeholder="Nama Importir">
            </div>

            <div class="field span-2">
                <label for="commodity">
                    <span style="font-weight: 700; color: #1e293b;">Commodity (Nama Barang)</span>
                    <span style="font-size: 11px; color: #64748b; font-weight: normal; margin-left: 6px;">— Otomatis terisi dari Job Order</span>
                </label>
                <input id="commodity" name="commodity" maxlength="500"
                    value="{{ old('commodity', $selectedJob?->cargo_description) }}"
                    placeholder="Nama barang / komoditas deklarasi pabean">
            </div>

            <div class="field">
                <label for="currency">Currency <span class="required">*</span></label>
                <select id="currency" name="currency" required>
                    @foreach(['USD', 'IDR', 'EUR', 'SGD', 'CNY', 'JPY', 'GBP', 'AUD'] as $curr)
                        <option value="{{ $curr }}" @selected(old('currency', 'USD') === $curr)>{{ $curr }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="invoice_value">Harga Dalam Invoice</label>
                <input id="invoice_value" name="invoice_value" inputmode="decimal"
                    value="{{ old('invoice_value', '0.00') }}" placeholder="contoh: 7,420.58">
            </div>

            <div class="field">
                <label for="freight">Biaya Transportasi</label>
                <input id="freight" name="freight" inputmode="decimal"
                    value="{{ old('freight', '0.00') }}" placeholder="contoh: 1,225.00">
            </div>

            <div class="field">
                <label for="insurance">Asuransi</label>
                <input id="insurance" name="insurance" inputmode="decimal"
                    value="{{ old('insurance', '0.00') }}" placeholder="contoh: 30.00">
            </div>

            <div class="field">
                <label for="is_repeated_transaction">Trans Pengulangan (F)</label>
                <select id="is_repeated_transaction" name="is_repeated_transaction">
                    <option value="0" @selected(!old('is_repeated_transaction'))>Tidak</option>
                    <option value="1" @selected(old('is_repeated_transaction') == '1')>Ya</option>
                </select>
            </div>

            <input type="hidden" name="number" value="{{ old('number', $defaultNumber) }}">
            <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id', $selectedJob?->customer_id) }}">
        </div>

        {{-- 2. DOKUMEN PENDUKUNG --}}
        <div class="form-section-heading" style="background: #1e40af; color: #fff; padding: 10px 16px; border-radius: 6px; margin-top: 28px; margin-bottom: 12px;">
            <h2 style="color: #fff; margin: 0; font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <span>📑</span> Dokumen Pendukung
            </h2>
        </div>

        @php
            $oldDocs = old('supporting_documents', [1, 2, 3]);
        @endphp

        <div class="table-scroll">
            <table class="dnp-doc-table">
                <thead>
                    <tr>
                        <th class="no-cell">NO</th>
                        <th>KETERANGAN</th>
                        <th class="checkbox-cell">PILIH</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($supportingDocsList as $num => $docLabel)
                        @php
                            $isChecked = in_array((string)$num, array_map('strval', $oldDocs)) || in_array($num, $oldDocs);
                        @endphp
                        <tr>
                            <td class="no-cell">{{ $num }}.</td>
                            <td>
                                <label for="doc_{{ $num }}" style="cursor: pointer; display: block; margin: 0; font-weight: 500; font-size: 13px; color: #1e293b;">
                                    {{ $docLabel }}
                                </label>
                            </td>
                            <td class="checkbox-cell">
                                <input type="checkbox" id="doc_{{ $num }}" name="supporting_documents[]" value="{{ $num }}"
                                    @checked($isChecked)
                                    style="width: 17px; height: 17px; cursor: pointer; accent-color: #2563eb;">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="form-actions" style="margin-top: 24px;">
            <a class="button button-secondary" href="{{ $backUrl }}">Batal</a>
            <button class="button button-primary">Simpan Deklarasi Nilai Pabean</button>
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
</script>

@endsection
