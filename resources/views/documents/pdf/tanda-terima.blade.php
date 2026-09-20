@php
    $customer = $job->customer ?? $quotation?->customer;
    $snapshot = $quotation?->customer_snapshot ?? [];

    $customerName = $job->consignee_name ?: ($customer?->name ?? ($snapshot['name'] ?? ''));
    $customerAddress = $job->consignee_address ?: ($customer?->address ?? ($snapshot['address'] ?? ''));
    $customerPhone = $customer?->phone ?? ($snapshot['phone'] ?? '');

    $dateText = $job->job_date?->format('d/m/Y') ?? now()->format('d/m/Y');
    $docNumber = $job->number;

    $type = strtolower($type ?? request('type', 'barang'));
    $isDokumen = $type === 'dokumen';

    $items = $job->quotation_snapshot['items'] ?? [];

    // Pre-populate document names if type == dokumen
    $docNames = [];
    if ($job->relationLoaded('documents') && $job->documents->count() > 0) {
        foreach ($job->documents as $d) {
            $name = $d->documentType?->name ?: $d->original_name;
            if (!in_array($name, $docNames, true)) {
                $docNames[] = $name;
            }
        }
    }
    if (empty($docNames)) {
        $docNames = [
            'BILL OF LADING / AIR WAYBILL',
            'COMMERCIAL INVOICE',
            'PACKING LIST',
            'DOKUMEN PABEAN (PIB / PEB / SPPB / NPE)',
            'SURAT KUASA & DELIVERY ORDER',
        ];
    }
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $isDokumen ? 'TANDA TERIMA DOKUMEN' : 'TANDA TERIMA BARANG' }} - {{ $job->number }}</title>
    <style>
        @page {
            margin: 15px 25px 15px 25px;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            font-size: 8.5px;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }
        .half-sheet {
            height: 380px;
            position: relative;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo-img {
            max-height: 38px;
            max-width: 185px;
        }
        .doc-title {
            font-size: 15px;
            font-weight: bold;
            color: #0b2356;
            letter-spacing: 0.3px;
        }
        .doc-sub {
            font-size: 8px;
            font-style: italic;
            color: #475569;
            margin-top: 2px;
        }
        .meta-box {
            width: 100%;
            border: 1px solid #cbd5e1;
            background: #f1f5f9;
            padding: 5px 8px;
            margin-bottom: 8px;
            border-collapse: collapse;
        }
        .meta-box td {
            vertical-align: middle;
            padding: 2px 0;
            font-size: 8.5px;
        }
        .dots-line {
            color: #475569;
            font-size: 8px;
            letter-spacing: 0.5px;
        }
        .table-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .table-grid th {
            background: #0b2356;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            padding: 5px 6px;
            border: 1px solid #0b2356;
            text-align: left;
        }
        .table-grid th.center, .table-grid td.center {
            text-align: center;
        }
        .table-grid td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            font-size: 8.5px;
            height: 24px;
            vertical-align: middle;
            background: #ffffff;
        }
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .sign-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 8.5px;
            color: #1e293b;
        }
        .sign-space {
            height: 38px;
        }
        .sign-line {
            font-size: 8.5px;
            color: #1e293b;
            letter-spacing: 0.5px;
        }
        .sign-role {
            font-size: 8px;
            color: #475569;
            margin-top: 3px;
        }
        .cut-divider {
            margin: 10px 0 14px 0;
            width: 100%;
        }
        .copy-second {
            padding-top: 36px;
        }
        .cut-divider-table {
            width: 100%;
            border-collapse: collapse;
        }
        .cut-divider-table td {
            vertical-align: middle;
        }
        .cut-line {
            border-bottom: 1px dashed #94a3b8;
        }
        .cut-text {
            font-size: 7.5px;
            color: #64748b;
            font-weight: bold;
            letter-spacing: 1px;
            text-align: center;
            white-space: nowrap;
            padding: 0 8px;
        }
    </style>
</head>
<body>

    @for($copy = 1; $copy <= 2; $copy++)
        <div class="half-sheet {{ $copy === 2 ? 'copy-second' : '' }}">
            <!-- HEADER -->
            <table class="header-table">
                <tr>
                    <td style="width: 45%;">
                        <img src="{{ public_path('images/rdx-banner.png') }}" class="logo-img" alt="RDX LOGISTICS">
                    </td>
                    <td style="width: 55%; text-align: right;">
                        <div class="doc-title">{{ $isDokumen ? 'TANDA TERIMA DOKUMEN' : 'TANDA TERIMA BARANG' }}</div>
                        <div class="doc-sub">Lembar {{ $copy }}: {{ $copy === 1 ? 'Untuk Customer (Asli)' : 'Arsip Perusahaan / Pengirim' }}</div>
                    </td>
                </tr>
            </table>

            <!-- META BOX -->
            <table class="meta-box">
                <tr>
                    <td style="width: 95px; font-weight: bold; color: #1e293b;">Nama Customer</td>
                    <td style="width: 12px; text-align: center; font-weight: bold;">:</td>
                    <td style="width: 250px;">
                        @if(!empty($customerName) && $customerName !== '—')
                            <span style="font-weight: 600; color: #0f172a;">{{ $customerName }}</span>
                        @else
                            <span class="dots-line">...........................................................................</span>
                        @endif
                    </td>
                    <td style="width: 65px; font-weight: bold; color: #1e293b;">Tanggal</td>
                    <td style="width: 12px; text-align: center; font-weight: bold;">:</td>
                    <td>
                        @if(!empty($dateText))
                            <span style="color: #0f172a;">{{ $dateText }}</span>
                        @else
                            <span class="dots-line">................................................</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: bold; color: #1e293b;">Alamat / Telp</td>
                    <td style="text-align: center; font-weight: bold;">:</td>
                    <td>
                        @if(!empty($customerAddress) && $customerAddress !== '—')
                            <span style="color: #0f172a;">{{ $customerAddress }}{{ $customerPhone ? ' / ' . $customerPhone : '' }}</span>
                        @else
                            <span class="dots-line">...........................................................................</span>
                        @endif
                    </td>
                    <td style="font-weight: bold; color: #1e293b;">No.</td>
                    <td style="text-align: center; font-weight: bold;">:</td>
                    <td>
                        @if(!empty($docNumber))
                            <span style="font-weight: 600; color: #0f172a;">{{ $docNumber }}</span>
                        @else
                            <span class="dots-line">................................................</span>
                        @endif
                    </td>
                </tr>
            </table>

            <!-- TABLE -->
            <table class="table-grid">
                <thead>
                    @if($isDokumen)
                        <tr>
                            <th class="center" style="width: 7%;">NO</th>
                            <th style="width: 93%; padding-left: 8px;">NAMA DOKUMEN</th>
                        </tr>
                    @else
                        <tr>
                            <th class="center" style="width: 7%;">NO</th>
                            <th style="width: 63%; padding-left: 8px;">NAMA BARANG</th>
                            <th class="center" style="width: 15%;">QTY</th>
                            <th class="center" style="width: 15%;">SATUAN</th>
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @if($isDokumen)
                        @for($i = 0; $i < 5; $i++)
                            <tr>
                                <td class="center">{{ $i + 1 }}</td>
                                <td style="padding-left: 8px;">{{ $docNames[$i] ?? '' }}</td>
                            </tr>
                        @endfor
                    @else
                        @php
                            $rowList = [];
                            if (!empty($items)) {
                                foreach ($items as $it) {
                                    $rowList[] = [
                                        'name' => $it['description'] ?? 'Barang / Kargo',
                                        'qty' => \App\Support\Money::format($it['quantity'] ?? 1),
                                        'unit' => $it['unit'] ?? 'Package',
                                    ];
                                }
                            } else {
                                $rowList[] = [
                                    'name' => $job->cargo_description ?? '',
                                    'qty' => $job->package_count ? \App\Support\Money::format($job->package_count) : '',
                                    'unit' => $job->package_unit ?: ($job->container_type ? strtoupper($job->container_type) : ''),
                                ];
                            }
                        @endphp
                        @for($i = 0; $i < 5; $i++)
                            <tr>
                                <td class="center">{{ $i + 1 }}</td>
                                <td style="padding-left: 8px;">{{ $rowList[$i]['name'] ?? '' }}</td>
                                <td class="center">{{ $rowList[$i]['qty'] ?? '' }}</td>
                                <td class="center">{{ $rowList[$i]['unit'] ?? '' }}</td>
                            </tr>
                        @endfor
                    @endif
                </tbody>
            </table>

            <!-- SIGNATURE -->
            <table class="sign-table">
                <tr>
                    <td>Diserahkan Oleh:</td>
                    <td>Diterima Oleh:</td>
                </tr>
                <tr>
                    <td class="sign-space"></td>
                    <td class="sign-space"></td>
                </tr>
                <tr>
                    <td>
                        <div class="sign-line">( ___________________________ )</div>
                        <div class="sign-role">Pengirim / Kurir</div>
                    </td>
                    <td>
                        <div class="sign-line">( ___________________________ )</div>
                        <div class="sign-role">Customer / Penerima</div>
                    </td>
                </tr>
            </table>
        </div>

        @if($copy === 1)
            <!-- CUT DIVIDER -->
            <div class="cut-divider">
                <table class="cut-divider-table">
                    <tr>
                        <td class="cut-line" style="width: 40%;"></td>
                        <td class="cut-text" style="width: 20%;">✂ &nbsp;POTONG DI SINI&nbsp; ✂</td>
                        <td class="cut-line" style="width: 40%;"></td>
                    </tr>
                </table>
            </div>
        @endif
    @endfor

</body>
</html>
