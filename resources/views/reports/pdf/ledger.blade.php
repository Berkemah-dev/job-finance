<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Detail Account {{ $account->code }} — {{ $account->name }}</title>
    <style>
        @page { margin: 18px 20px; }
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1e293b; font-size: 8px; margin: 0; }
        .header { margin-bottom: 8px; }
        .logo { max-width: 180px; max-height: 45px; }
        .title { font-size: 14px; font-weight: 700; color: #0f172a; margin: 6px 0 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        .meta { border-collapse: collapse; margin-bottom: 8px; font-size: 9px; }
        .meta td { padding: 1px 4px; }
        .meta td:first-child { width: 100px; font-weight: 600; color: #475569; }
        .meta td:nth-child(2) { width: 10px; text-align: center; }
        table.ledger-table { width: 100%; border-collapse: collapse; font-size: 8px; }
        table.ledger-table th, table.ledger-table td { border: 1px solid #cbd5e1; padding: 4px 5px; vertical-align: top; }
        table.ledger-table th { background: #f1f5f9; color: #1e293b; text-align: center; font-weight: 700; text-transform: uppercase; font-size: 7.5px; }
        td.right { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        td.center { text-align: center; }
        .opening td { font-weight: 700; background: #f8fafc; color: #334155; }
        .badge { display: inline-block; font-size: 7px; font-weight: 700; padding: 1px 3px; border-radius: 2px; text-transform: uppercase; }
        .badge-import { color: #3730a3; background: #e0e7ff; }
        .badge-export { color: #065f46; background: #ecfdf5; }
        .badge-domestic { color: #334155; background: #f1f5f9; }
        .tax-tag { font-weight: 600; color: #1e40af; }
        .bupot-tag { font-weight: 600; color: #059669; }
        .subtle { color: #64748b; font-size: 7.5px; }
        .footer { margin-top: 8px; font-size: 7.5px; color: #94a3b8; text-align: right; }
    </style>
</head>
<body>
@php
    $logoFile = public_path('images/logo.png');
    $logo = file_exists($logoFile) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoFile)) : '';
@endphp
@include('documents.pdf.partials.letterhead')
<div class="title">DETIL ACCOUNT (BUKU BESAR)</div>
<table class="meta">
    <tr>
        <td>Account Name</td>
        <td>:</td>
        <td><strong>{{ $account->code }} — {{ $account->name }}</strong></td>
    </tr>
    <tr>
        <td>Currency</td>
        <td>:</td>
        <td>IDR</td>
    </tr>
    <tr>
        <td>Period</td>
        <td>:</td>
        <td>{{ \Carbon\Carbon::parse($from)->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($to)->format('d-m-Y') }}</td>
    </tr>
</table>

<table class="ledger-table">
    <thead>
        <tr>
            <th style="width: 3%;">NO</th>
            <th style="width: 7%;">DATE</th>
            <th style="width: 10%;">NO JURNAL</th>
            <th style="width: 16%;">DESCRIPTION</th>
            <th style="width: 10%;">NOMOR JOB</th>
            <th style="width: 12%;">NOMOR INVOICE</th>
            <th style="width: 13%;">CUSTOMER / VENDOR</th>
            <th style="width: 11%;">FAKTUR PAJAK / BUPOT</th>
            <th style="width: 6%;">DEBIT</th>
            <th style="width: 6%;">CREDIT</th>
            <th style="width: 6%;">BALANCE</th>
        </tr>
    </thead>
    <tbody>
        <tr class="opening">
            <td colspan="10" class="right">Beginning Balance</td>
            <td class="right">{{ \App\Support\Money::format($opening) }}</td>
        </tr>
        @forelse($entries as $entry)
            <tr>
                <td class="center">{{ $loop->iteration }}</td>
                <td class="center">{{ $entry->journal->journal_date->format('d-m-Y') }}</td>
                <td>
                    <strong>{{ $entry->journal->number }}</strong>
                    @if(!empty($entry->cost_number))
                        <div class="subtle">PR: {{ $entry->cost_number }}</div>
                    @endif
                </td>
                <td>
                    <div>{{ $entry->description }}</div>
                    @if(!empty($entry->cost_name) && $entry->cost_name !== $entry->description)
                        <div class="subtle">Cost: {{ $entry->cost_name }}</div>
                    @endif
                </td>
                <td>
                    <strong>{{ $entry->job_number ?? '—' }}</strong>
                    @if(!empty($entry->service_type))
                        @php
                            $srv = strtoupper((string) $entry->service_type);
                            $isImp = str_contains($srv, 'IMP');
                            $isExp = str_contains($srv, 'EXP');
                        @endphp
                        <div>
                            <span class="badge {{ $isImp ? 'badge-import' : ($isExp ? 'badge-export' : 'badge-domestic') }}">
                                {{ $isImp ? 'IMPORT' : ($isExp ? 'EKSPOR' : 'DOMESTIK') }}
                            </span>
                        </div>
                    @endif
                </td>
                <td>
                    <strong>{{ $entry->invoice_number ?? '—' }}</strong>
                    @if(!empty($entry->base_amount))
                        <div class="subtle">Nilai: Rp {{ \App\Support\Money::format($entry->base_amount) }}</div>
                    @endif
                    @if(!empty($entry->tax_rate))
                        <div style="color: #0284c7; font-weight: 600;">Tarif: {{ $entry->tax_rate }}</div>
                    @endif
                </td>
                <td>
                    @if(!empty($entry->customer_name))
                        <div><strong>{{ $entry->customer_name }}</strong></div>
                    @endif
                    @if(!empty($entry->vendor_name))
                        <div class="subtle">{{ $entry->vendor_name }}</div>
                    @endif
                    @if(empty($entry->customer_name) && empty($entry->vendor_name))
                        <span style="color: #94a3b8;">—</span>
                    @endif
                </td>
                <td>
                    @if(!empty($entry->tax_invoice_number))
                        <div class="tax-tag">NSFP: {{ $entry->tax_invoice_number }}</div>
                    @endif
                    @if(!empty($entry->withholding_document_number))
                        <div class="bupot-tag">Bupot: {{ $entry->withholding_document_number }}</div>
                    @endif
                    @if(empty($entry->tax_invoice_number) && empty($entry->withholding_document_number))
                        <span style="color: #94a3b8;">—</span>
                    @endif
                </td>
                <td class="right">{{ \App\Support\Money::format($entry->debit) }}</td>
                <td class="right">{{ \App\Support\Money::format($entry->credit) }}</td>
                <td class="right">{{ \App\Support\Money::format($entry->running_balance) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="11" class="center" style="padding: 12px; color: #64748b;">Tidak ada mutasi pada periode ini.</td>
            </tr>
        @endforelse
        <tr class="opening">
            <td colspan="10" class="right">Ending Balance</td>
            <td class="right">{{ \App\Support\Money::format($closing) }}</td>
        </tr>
    </tbody>
</table>

<div class="footer">Dicetak {{ now()->format('d-m-Y H:i') }} &bull; JobFinance System</div>
</body>
</html>
