<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>JO Profit {{ $job->number }}</title>
    <style>
        @page { margin: 22pt 26pt 20pt 26pt; }
        * { box-sizing: border-box; }
        body { font-family: 'Courier New', Courier, monospace; font-size: 8.5pt; line-height: 1.25; color: #000; margin: 0; padding: 0; }
        h1 { font-size: 16pt; margin: 0 0 4pt; }
        h2 { font-size: 10pt; margin: 12pt 0 6pt; page-break-after: avoid; }
        p { margin: 4pt 0; }
        .muted { color: #000; font-size: 7pt; }
        .meta { width: 100%; margin: 14pt 0; border-collapse: collapse; }
        .meta td { vertical-align: top; padding: 3pt 5pt 3pt 0; }
        .label { color: #000; width: 13%; font-weight: bold; }
        .grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .grid th, .grid td { padding: 2.5pt 6pt; border: 1px solid #000; vertical-align: top; overflow-wrap: break-word; word-wrap: break-word; }
        .grid th { background: #dfdfdf; color: #000; font-size: 7.5pt; font-weight: bold; padding: 3pt 6pt; text-align: left; }
        .grid thead { display: table-header-group; }
        .grid tr { page-break-inside: avoid; }
        .grid .money { text-align: right; }
        .total { font-weight: bold; background: #dfdfdf; }
        .profit { margin-top: 16pt; padding: 10pt; border: 1px solid #000; page-break-inside: avoid; }
        .keep-section { page-break-inside: avoid; }
        .profit strong { font-size: 14pt; }
        .signatures { width: 100%; margin-top: 24pt; text-align: center; }
        .signatures td { width: 50%; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10pt; page-break-inside: avoid; }
        .header-table td { vertical-align: middle; padding: 0; }
        .header-logo { width: 40%; }
        .header-logo img { width: 140pt; height: auto; }
        .header-title { text-align: right; }
    </style>
</head>
<body>
    @php
        $logoFile = public_path('images/logo.png');
        $logoSource = is_file($logoFile) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoFile)) : null;
    @endphp
    <table class="header-table">
        <tr>
            <td class="header-logo">
                @if($logoSource)
                    <img src="{{ $logoSource }}" alt="RDX Logistics">
                @else
                    <strong>RDX LOGISTICS</strong>
                @endif
            </td>
            <td class="header-title">
                <h1>JO PROFIT</h1>
                <p><strong>{{ $job->number }}</strong><br>{{ strtoupper($job->status) }} | {{ $job->job_date?->format('d/m/Y') }}</p>
            </td>
        </tr>
    </table>
    <p class="muted">Rincian biaya final per Job. Seluruh nominal jual dan beli dalam IDR.</p>
    <table class="meta">
        <tr><td class="label">Customer</td><td style="width:43%">{{ $job->customer?->name ?? data_get($job->quotation_snapshot, 'customer.name', '-') }}</td><td class="label">QTY</td><td>{{ $job->package_count ?? $job->quotation?->cargo_qty ?? '-' }} {{ $job->container_type }}</td></tr>
        <tr><td class="label">Loading</td><td>{{ $job->pol ?? $job->origin ?? $job->quotation?->origin ?? '-' }}</td><td class="label">Gross weight</td><td>{{ $job->gross_weight ?? '-' }} KGS</td></tr>
        <tr><td class="label">Discharge</td><td>{{ $job->pod ?? $job->destination ?? $job->quotation?->destination ?? '-' }}</td><td class="label">Volume</td><td>{{ $job->volume ?? '-' }} M3</td></tr>
        <tr><td class="label">Invoice</td><td>{{ $job->invoice?->number ?? '-' }}</td><td class="label">Status invoice</td><td>{{ $job->invoice?->status ?? '-' }}</td></tr>
    </table>

    <h2>CHARGES</h2>
    @include('reports.pdf.partials.job-profit-charges', ['items' => $charges, 'totalSell' => $sell, 'totalBuy' => $buy])

    <div class="keep-section">
    <h2>REIMBURSEMENT</h2>
    @include('reports.pdf.partials.job-profit-charges', ['items' => $reimbursements, 'totalSell' => $reimbursementSell, 'totalBuy' => $reimbursementBuy])
    <p class="muted">Reimbursement dipisahkan dari perhitungan profit charges.</p>
    </div>

    <div class="profit">
        Total jual charges: Rp {{ \App\Support\Money::format($sell) }}<br>
        Total beli charges: Rp {{ \App\Support\Money::format($buy) }}<br>
        <strong>PROFIT: Rp {{ \App\Support\Money::format($profit) }}</strong>
        <p class="muted">Jual dikurangi beli, sebelum pajak dan materai invoice. Biaya tetap dihitung meskipun belum dibayar.</p>
    </div>

    <div class="keep-section">
    <h2>CREDIT NOTE BELUM LUNAS</h2>
    <table class="grid">
        <thead><tr><th style="width:22%">NO. CN</th><th style="width:33%">CHARGES / VENDOR</th><th class="money" style="width:20%">DIBAYAR (IDR)</th><th class="money" style="width:25%">SISA (IDR)</th></tr></thead>
        <tbody>
            @forelse($unpaidCreditNotes as $row)
                <tr><td>{{ $row['cost']->number }}</td><td>{{ $row['cost']->description }}<br><span class="muted">{{ $row['cost']->vendor?->name ?? '-' }}</span></td><td class="money">{{ \App\Support\Money::format($row['paid']) }}</td><td class="money">{{ \App\Support\Money::format($row['outstanding']) }}</td></tr>
            @empty
                <tr><td colspan="4">Tidak ada Credit Note final yang belum lunas.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="muted">Daftar CN hanya informasi sisa pembayaran. Nominal beli sudah tercakup di atas, tidak dikurangkan lagi dari profit.</p>
    </div>
    @if($draftCount > 0)
        <p class="muted">{{ $draftCount }} biaya belum final tidak dimasukkan dalam laporan ini.</p>
    @endif
    <table class="signatures"><tr><td>Disiapkan oleh<br><br><br>____________________</td><td>Disetujui oleh<br><br><br>____________________</td></tr></table>
</body>
</html>
