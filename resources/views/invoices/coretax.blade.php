@extends('layouts.app')
@section('title', 'Ekspor Coretax')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">INVOICE</p>
        <h1>Ekspor faktur PPN ke Coretax</h1>
        <p>Pilih faktur yang memenuhi syarat (memiliki PPN) untuk diunduh langsung sebagai <strong>Bulk XML Coretax</strong> atau diekspor ke file <strong>Excel (.xlsx)</strong>.</p>
    </div>
    <a class="button button-secondary" href="{{ route('invoices.index') }}">← Kembali</a>
</div>

<section class="panel">
    <form class="filter-bar" method="GET">
        <input name="search" value="{{ $search }}" placeholder="Cari nomor invoice atau nama customer" aria-label="Cari invoice">
        <select name="status" aria-label="Status">
            <option value="">Semua status</option>
            @foreach(['issued' => 'Diterbitkan', 'partially_paid' => 'Sebagian dibayar', 'paid' => 'Lunas'] as $key => $label)
                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="button button-primary">Terapkan</button>
        <a class="text-link" href="{{ route('invoices.coretax.index') }}">Reset</a>
    </form>

    <form id="coretax-bulk-form" method="POST">
        @csrf
        <div class="bulk-action-bar" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span id="selected-badge" style="font-size: 13px; font-weight: 600; color: #334155; background: #e2e8f0; padding: 4px 10px; border-radius: 6px;">0 faktur dipilih</span>
                <button type="button" id="btn-select-all" class="text-link" style="font-size: 12px; cursor: pointer; background: none; border: none; padding: 0;">Pilih Semua di Halaman</button>
                <button type="button" id="btn-deselect-all" class="text-link" style="font-size: 12px; cursor: pointer; background: none; border: none; padding: 0; display: none;">Batal Pilih</button>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="submit" formaction="{{ route('invoices.coretax.bulk-xml') }}" class="button button-primary" id="btn-submit-xml" disabled style="display: inline-flex; align-items: center; gap: 6px;">
                    <x-icon name="download"/> Unduh Bulk XML
                </button>
                <button type="submit" formaction="{{ route('invoices.coretax.bulk-excel') }}" class="button button-secondary" id="btn-submit-excel" disabled style="display: inline-flex; align-items: center; gap: 6px;">
                    <x-icon name="file"/> Unduh Excel (.xlsx)
                </button>
            </div>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="check-all" title="Pilih Semua di Halaman" style="cursor: pointer; width: 16px; height: 16px; accent-color: #0f1f3d;" aria-label="Pilih semua faktur">
                        </th>
                        <th>No. Faktur</th>
                        <th>Tanggal</th>
                        <th>Customer</th>
                        <th>Job</th>
                        <th class="money">PPN</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" class="invoice-checkbox" style="cursor: pointer; width: 16px; height: 16px; accent-color: #0f1f3d;" aria-label="Pilih invoice {{ $invoice->number }}">
                            </td>
                            <td><strong>{{ $invoice->number }}</strong></td>
                            <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                            <td>{{ $invoice->customer_snapshot['name'] ?? '—' }}</td>
                            <td>{{ $invoice->job?->number }}</td>
                            <td class="money">Rp {{ \App\Support\Money::format($invoice->tax) }}</td>
                            <td>
                                <span class="status-badge status-{{ $invoice->status }}">
                                    {{ ['issued' => 'Diterbitkan', 'partially_paid' => 'Sebagian dibayar', 'paid' => 'Lunas'][$invoice->status] ?? $invoice->status }}
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn-action btn-action-primary" href="{{ route('invoices.coretax.preview', $invoice) }}" target="_blank" title="Pratinjau Faktur Pajak" data-tooltip="Pratinjau" aria-label="Pratinjau Faktur Pajak"><x-icon name="eye"/></a>
                                    <a class="btn-action" href="{{ route('invoices.coretax', $invoice) }}" title="Unduh XML Coretax" data-tooltip="Unduh XML" aria-label="Unduh XML Coretax"><x-icon name="download"/></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <x-icon name="file"/>
                                    <h3>Belum ada faktur PPN</h3>
                                    <p>Faktur baru muncul di sini setelah closing job dengan pajak lebih besar dari nol.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <div class="pagination">{{ $invoices->links() }}</div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('check-all');
    const checkboxes = document.querySelectorAll('.invoice-checkbox');
    const selectedBadge = document.getElementById('selected-badge');
    const btnSelectAll = document.getElementById('btn-select-all');
    const btnDeselectAll = document.getElementById('btn-deselect-all');
    const btnXml = document.getElementById('btn-submit-xml');
    const btnExcel = document.getElementById('btn-submit-excel');
    const form = document.getElementById('coretax-bulk-form');

    function updateState() {
        const checked = document.querySelectorAll('.invoice-checkbox:checked');
        const count = checked.length;
        const total = checkboxes.length;

        selectedBadge.textContent = count + ' faktur dipilih';
        if (count > 0) {
            btnXml.disabled = false;
            btnExcel.disabled = false;
            btnDeselectAll.style.display = 'inline-block';
        } else {
            btnXml.disabled = true;
            btnExcel.disabled = true;
            btnDeselectAll.style.display = 'none';
        }

        if (checkAll) {
            if (total > 0 && count === total) {
                checkAll.checked = true;
                checkAll.indeterminate = false;
            } else if (count > 0) {
                checkAll.checked = false;
                checkAll.indeterminate = true;
            } else {
                checkAll.checked = false;
                checkAll.indeterminate = false;
            }
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = checkAll.checked);
            updateState();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateState);
    });

    if (btnSelectAll) {
        btnSelectAll.addEventListener('click', function () {
            checkboxes.forEach(cb => cb.checked = true);
            updateState();
        });
    }

    if (btnDeselectAll) {
        btnDeselectAll.addEventListener('click', function () {
            checkboxes.forEach(cb => cb.checked = false);
            updateState();
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            const count = document.querySelectorAll('.invoice-checkbox:checked').length;
            if (count === 0) {
                e.preventDefault();
                alert('Pilih minimal satu faktur terlebih dahulu.');
            }
        });
    }
});
</script>
@endsection
