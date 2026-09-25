@php
    $bannerFile = public_path('images/rdx-banner.png');
    $bannerBase64 = file_exists($bannerFile) ? 'data:image/png;base64,' . base64_encode(file_get_contents($bannerFile)) : '';

    $rows = $statement['all_rows'] ?? ($statement['rows'] instanceof \Illuminate\Contracts\Pagination\Paginator ? $statement['rows']->items() : $statement['rows']);
    $openingDecimal = \App\Support\Money::decimal($statement['opening']);
    $customerContact = $customer->contact_name ?: ($customer->authorizer_name ?? '—');
    $aged = $statement['aged'] ?? [];
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>STATEMENT OF ACCOUNT - {{ $customer->code }}</title>
    <style>
        @page {
            margin: 25pt 35pt 20pt 35pt;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            font-size: 9pt;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        table.header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8pt;
        }
        table.header-table td {
            vertical-align: top;
        }
        .company-name {
            font-weight: bold;
            font-size: 10.5pt;
            margin-bottom: 2pt;
            color: #0f172a;
        }
        .company-address {
            font-size: 8.5pt;
            line-height: 1.35;
            color: #334155;
        }
        .doc-title-container {
            text-align: center;
            margin-top: 2pt;
            margin-bottom: 10pt;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 4pt;
        }
        .doc-title-main {
            font-weight: bold;
            font-size: 14pt;
            letter-spacing: 1px;
            color: #0f172a;
        }
        table.meta-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8pt;
        }
        table.meta-container td {
            vertical-align: top;
            padding: 0;
        }
        .section-header {
            font-weight: bold;
            font-size: 9.5pt;
            margin-top: 8pt;
            margin-bottom: 4pt;
            text-transform: uppercase;
            color: #0f172a;
            background-color: #f1f5f9;
            padding: 3pt 6pt;
            border-left: 3px solid #2563eb;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4pt;
        }
        table.data-table td {
            vertical-align: top;
            padding: 2pt 3pt;
            font-size: 8.5pt;
        }
        .col-label {
            font-weight: 600;
            color: #334155;
        }
        .col-sep {
            width: 10pt;
            text-align: center;
        }
        table.items-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3pt;
            margin-bottom: 6pt;
        }
        table.items-grid th {
            border: 1px solid #94a3b8;
            padding: 4pt 5pt;
            font-weight: bold;
            font-size: 8.5pt;
            text-align: center;
            background-color: #f1f5f9;
            color: #0f172a;
            text-transform: uppercase;
        }
        table.items-grid td {
            border: 1px solid #cbd5e1;
            padding: 4pt 5pt;
            font-size: 8.5pt;
            vertical-align: middle;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .disclaimer {
            font-size: 8pt;
            font-style: italic;
            margin-top: 4pt;
            margin-bottom: 8pt;
            color: #475569;
            line-height: 1.3;
            text-align: center;
        }
        .bottom-container {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6pt;
        }
        .bottom-container td {
            vertical-align: top;
        }
        .payment-box {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            padding: 6pt 8pt;
            font-size: 8pt;
            line-height: 1.45;
        }
        .payment-title {
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 3pt;
            text-transform: uppercase;
            font-size: 8.5pt;
        }
        .signature-box {
            text-align: center;
            font-size: 8.5pt;
        }
        .signature-img-space {
            height: 42pt;
            text-align: center;
            vertical-align: middle;
            margin: 2pt 0;
        }
        .signature-img {
            max-height: 40pt;
            max-width: 130pt;
            display: inline-block;
        }
        .sign-line {
            border-top: 1px solid #0f172a;
            width: 140pt;
            margin: 0 auto 3pt auto;
        }
        .footer {
            position: fixed;
            bottom: -10px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7.5pt;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 2pt;
        }
    </style>
</head>
<body>

    <!-- TOP HEADER -->
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <img src="{{ $bannerBase64 ?: public_path('images/rdx-banner.png') }}" style="max-height: 40pt; max-width: 200pt;" alt="RDX LOGISTICS">
            </td>
            <td style="width: 50%; text-align: right;">
                <div class="company-name">PT.RADIX INTERNATIONAL LOGISTICS</div>
                <div class="company-address">Jl.Teh No.3C Jakarta Barat 11110 Indonesia</div>
                <div class="company-address">Telp : 021-38873060 | Email : operations@jobfinance.test</div>
            </td>
        </tr>
    </table>

    <!-- DOCUMENT TITLE -->
    <div class="doc-title-container">
        <div class="doc-title-main">STATEMENT OF ACCOUNT</div>
    </div>

    <!-- METADATA & CUSTOMER INFO -->
    <table class="meta-container">
        <tr>
            <td style="width: 54%; padding-right: 10pt;">
                <div class="section-header" style="margin-top: 0;">DATA CUSTOMER</div>
                <table class="data-table">
                    <tr>
                        <td class="col-label" style="width: 95pt;">Nama Customer</td>
                        <td class="col-sep">:</td>
                        <td style="font-weight: bold; color: #0f172a;">{{ $customer->name }}</td>
                    </tr>
                    <tr>
                        <td class="col-label">Alamat</td>
                        <td class="col-sep">:</td>
                        <td>{{ $customer->address ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="col-label">NPWP</td>
                        <td class="col-sep">:</td>
                        <td>{{ $customer->tax_number ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="col-label">Attn / Kontak</td>
                        <td class="col-sep">:</td>
                        <td>{{ $customerContact }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 46%; padding-left: 6pt;">
                <div class="section-header" style="margin-top: 0;">INFORMASI DOKUMEN</div>
                <table class="data-table">
                    <tr>
                        <td class="col-label" style="width: 90pt;">Nomor Dokumen</td>
                        <td class="col-sep">:</td>
                        <td style="font-weight: bold; color: #0f172a;">SOA/{{ $customer->code }}/{{ $from->format('Ym') }}</td>
                    </tr>
                    <tr>
                        <td class="col-label">Kode Customer</td>
                        <td class="col-sep">:</td>
                        <td style="font-weight: bold; color: #0f172a;">{{ $customer->code }}</td>
                    </tr>
                    <tr>
                        <td class="col-label">Periode Transaksi</td>
                        <td class="col-sep">:</td>
                        <td style="font-weight: bold;">{{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="col-label">Tanggal Cetak</td>
                        <td class="col-sep">:</td>
                        <td>{{ now()->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="col-label">Mata Uang</td>
                        <td class="col-sep">:</td>
                        <td>IDR (Rupiah)</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- RINGKASAN SALDO -->
    <div class="section-header">RINGKASAN SALDO</div>
    <table class="items-grid">
        <thead>
            <tr>
                <th style="width: 25%;">SALDO AWAL</th>
                <th style="width: 25%;">TAGIHAN PERIODE</th>
                <th style="width: 25%;">PEMBAYARAN PERIODE</th>
                <th style="width: 25%;">SALDO AKHIR</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-right" style="font-weight: bold;">Rp {{ \App\Support\Money::format($statement['opening']) }}</td>
                <td class="text-right" style="font-weight: bold;">Rp {{ \App\Support\Money::format($statement['invoiced']) }}</td>
                <td class="text-right" style="font-weight: bold;">Rp {{ \App\Support\Money::format($statement['paid']) }}</td>
                <td class="text-right" style="font-weight: bold; background-color: #f1f5f9; color: #0f172a;">Rp {{ \App\Support\Money::format($statement['closing']) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- UMUR PIUTANG (AGING) JIKA ADA INVOICE TERTUNGGAK -->
    @if(!empty($aged['invoices']) && $aged['invoices'] > 0)
        <div class="section-header">UMUR PIUTANG (AGING)</div>
        <table class="items-grid">
            <thead>
                <tr>
                    <th style="width: 20%;">SAAT INI</th>
                    <th style="width: 20%;">1 – 30 HARI</th>
                    <th style="width: 20%;">31 – 60 HARI</th>
                    <th style="width: 20%;">61 – 90 HARI</th>
                    <th style="width: 20%;">> 90 HARI</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-right">{{ isset($aged['current']) && !\App\Support\Money::decimal($aged['current'])->isZero() ? 'Rp ' . \App\Support\Money::format($aged['current']) : '—' }}</td>
                    <td class="text-right">{{ isset($aged['aging_1_30']) && !\App\Support\Money::decimal($aged['aging_1_30'])->isZero() ? 'Rp ' . \App\Support\Money::format($aged['aging_1_30']) : '—' }}</td>
                    <td class="text-right">{{ isset($aged['aging_31_60']) && !\App\Support\Money::decimal($aged['aging_31_60'])->isZero() ? 'Rp ' . \App\Support\Money::format($aged['aging_31_60']) : '—' }}</td>
                    <td class="text-right">{{ isset($aged['aging_61_90']) && !\App\Support\Money::decimal($aged['aging_61_90'])->isZero() ? 'Rp ' . \App\Support\Money::format($aged['aging_61_90']) : '—' }}</td>
                    <td class="text-right">{{ isset($aged['aging_90_plus']) && !\App\Support\Money::decimal($aged['aging_90_plus'])->isZero() ? 'Rp ' . \App\Support\Money::format($aged['aging_90_plus']) : '—' }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <!-- DETAIL TRANSAKSI -->
    <div class="section-header">DETAIL TRANSAKSI</div>
    <table class="items-grid">
        <thead>
            <tr>
                <th style="width: 4%;">NO.</th>
                <th style="width: 11%;">TANGGAL</th>
                <th style="width: 17%;">NO. DOKUMEN</th>
                <th style="width: 32%;">KETERANGAN TRANSAKSI</th>
                <th style="width: 12%; text-align: right;">DEBET (BAYAR)</th>
                <th style="width: 12%; text-align: right;">KREDIT (TAGIHAN)</th>
                <th style="width: 12%; text-align: right;">SALDO (IDR)</th>
            </tr>
        </thead>
        <tbody>
            @if(! $openingDecimal->isZero())
                <tr style="background-color: #fefce8; font-weight: bold;">
                    <td class="text-center">—</td>
                    <td class="text-center">{{ $from->format('d/m/Y') }}</td>
                    <td>SALDO-AWAL</td>
                    <td>Saldo Awal sebelum {{ $from->format('d/m/Y') }}</td>
                    <td class="text-right">—</td>
                    <td class="text-right">—</td>
                    <td class="text-right">Rp {{ \App\Support\Money::format($statement['opening']) }}</td>
                </tr>
            @endif

            @forelse($rows as $row)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-center">{{ $row['date'] instanceof \Carbon\Carbon ? $row['date']->format('d/m/Y') : \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                    <td style="font-weight: 600;">{{ $row['number'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td class="text-right">
                        {{ \App\Support\Money::decimal($row['debit'])->isPositive() ? \App\Support\Money::format($row['debit']) : '—' }}
                    </td>
                    <td class="text-right">
                        {{ \App\Support\Money::decimal($row['credit'])->isPositive() ? \App\Support\Money::format($row['credit']) : '—' }}
                    </td>
                    <td class="text-right" style="font-weight: 600;">
                        Rp {{ \App\Support\Money::format($row['balance'] ?? $row['running']) }}
                    </td>
                </tr>
            @empty
                @if($openingDecimal->isZero())
                    <tr>
                        <td colspan="7" class="text-center" style="color: #64748b; padding: 12pt;">
                            Tidak ada catatan transaksi pada periode {{ $from->format('d/m/Y') }} s.d. {{ $to->format('d/m/Y') }}
                        </td>
                    </tr>
                @endif
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold; border-top: 2px solid #0f172a;">
                <td colspan="4" class="text-right" style="padding-right: 8pt; text-transform: uppercase;">TOTAL MUTASI & SALDO AKHIR</td>
                <td class="text-right">Rp {{ \App\Support\Money::format($statement['paid'] ?? '0.00') }}</td>
                <td class="text-right">Rp {{ \App\Support\Money::format($statement['invoiced'] ?? '0.00') }}</td>
                <td class="text-right" style="color: #0f172a;">Rp {{ \App\Support\Money::format($statement['closing']) }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- DISCLAIMER NOTE -->
    <div class="disclaimer">
        ** Dokumen ini dicetak otomatis oleh sistem JobFinance. Mohon segera lakukan konfirmasi jika terdapat perbedaan catatan transaksi **
    </div>

    <!-- INFORMASI REKENING & TANDA TANGAN -->
    <table class="bottom-container">
        <tr>
            <td style="width: 56%; padding-right: 12pt;">
                <div class="payment-box">
                    <div class="payment-title">INFORMASI PEMBAYARAN / PAYMENT DETAILS:</div>
                    <div>NAMA AKUN / ACCOUNT NAME : <strong>PT RADIX INTERNATIONAL LOGISTICS</strong></div>
                    <div style="margin-top: 2pt;">1. Bank BCA - CENGKEH : <strong>240-0375-758</strong></div>
                    <div>2. Bank Mandiri - JAKARTA KOTA : <strong>115-00-1053704-3</strong></div>
                    <div style="margin-top: 4pt; font-style: italic; color: #64748b;">
                        * Harap mencantumkan nomor invoice pada berita transfer dan mengirimkan bukti pembayaran.
                    </div>
                </div>
            </td>
            <td style="width: 44%; text-align: center;">
                <div class="signature-box">
                    <div>Jakarta, {{ now()->format('d/m/Y') }}</div>
                    <div style="font-weight: bold; margin-bottom: 2pt;">PT RADIX INTERNATIONAL LOGISTICS</div>
                    <div class="signature-img-space"></div>
                    <div class="sign-line"></div>
                    <div style="font-weight: bold;">Finance & Accounting Department</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- FOOTER PAGE -->
    <div class="footer">
        Halaman <script type="text/php">if(isset($pdf)){echo $PAGE_NUM.' dari '.$PAGE_COUNT;}</script>
    </div>

</body>
</html>
