@php
    $customer = $job->customer ?? $quotation?->customer;
    $snapshot = $quotation?->customer_snapshot ?? [];

    $customerName = $job->consignee_name ?: ($customer?->name ?? ($snapshot['name'] ?? '—'));
    $customerAddress = $job->consignee_address ?: ($customer?->address ?? ($snapshot['address'] ?? '—'));
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
            font-family: Arial, Helvetica, DejaVu Sans, sans-serif;
            color: #000;
            font-size: 8.5px;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }
        .half-sheet {
            height: 480px;
            position: relative;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo-img {
            max-height: 38px;
            max-width: 180px;
        }
        .doc-title {
            font-size: 13.5px;
            font-weight: bold;
            color: #002060;
            letter-spacing: 0.5px;
        }
        .doc-sub {
            font-size: 7.5px;
            font-style: italic;
            color: #475569;
            margin-top: 1px;
        }
        .meta-box {
            width: 100%;
            border: 1px solid #cbd5e1;
            background: #fafbfc;
            padding: 5px 8px;
            margin-bottom: 8px;
            border-collapse: collapse;
        }
        .meta-box td {
            vertical-align: top;
            padding: 1.5px 0;
            font-size: 8px;
        }
        .dots-line {
            display: inline-block;
            border-bottom: 1px dotted #94a3b8;
            min-width: 180px;
        }
        .table-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .table-grid th {
            background: #0B2265;
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            padding: 3.5px 6px;
            border: 1px solid #0B2265;
            text-align: left;
        }
        .table-grid th.center, .table-grid td.center {
            text-align: center;
        }
        .table-grid td {
            border: 1px solid #cbd5e1;
            padding: 3.5px 6px;
            font-size: 8px;
            height: 18px;
            vertical-align: middle;
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
            font-size: 8px;
        }
        .sign-space {
            height: 38px;
        }
        .sign-name-dots {
            font-size: 8px;
            color: #1e293b;
        }
        .sign-role {
            font-size: 7.5px;
            color: #475569;
            margin-top: 2px;
        }
        .cut-divider {
            text-align: center;
            margin: 10px 0;
            position: relative;
        }
        .cut-divider-line {
            border-top: 1px dashed #94a3b8;
            margin-top: -6px;
        }
        .cut-divider-badge {
            display: inline-block;
            background: #fff;
            padding: 0 10px;
            font-size: 7.5px;
            color: #475569;
            letter-spacing: 1px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    @for($copy = 1; $copy <= 2; $copy++)
        <div class="half-sheet">
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
                    <td style="width: 90px; font-weight: 600;">Nama Customer</td>
                    <td style="width: 10px; text-align: center;">:</td>
                    <td style="width: 250px;">
                        <span style="font-weight: bold;">{{ $customerName }}</span>
                    </td>
                    <td style="width: 70px; font-weight: 600;">Tanggal</td>
                    <td style="width: 10px; text-align: center;">:</td>
                    <td>{{ $dateText }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">Alamat / Telp</td>
                    <td style="text-align: center;">:</td>
                    <td>
                        {{ $customerAddress }}{{ $customerPhone ? ' / ' . $customerPhone : '' }}
                    </td>
                    <td style="font-weight: 600;">No.</td>
                    <td style="text-align: center;">:</td>
                    <td style="font-weight: bold;">{{ $docNumber }}</td>
                </tr>
            </table>

            <!-- TABLE -->
            <table class="table-grid">
                <thead>
                    @if($isDokumen)
                        <tr>
                            <th class="center" style="width: 40px;">NO</th>
                            <th>NAMA DOKUMEN</th>
                        </tr>
                    @else
                        <tr>
                            <th class="center" style="width: 40px;">NO</th>
                            <th>NAMA BARANG</th>
                            <th class="center" style="width: 75px;">QTY</th>
                            <th class="center" style="width: 85px;">SATUAN</th>
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @if($isDokumen)
                        @for($i = 0; $i < 5; $i++)
                            <tr>
                                <td class="center">{{ $i + 1 }}</td>
                                <td>{{ $docNames[$i] ?? '' }}</td>
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
                                    'name' => $job->cargo_description ?? 'Barang / Muatan Kargo',
                                    'qty' => $job->package_count ? \App\Support\Money::format($job->package_count) : '1',
                                    'unit' => $job->package_unit ?: ($job->container_type ? strtoupper($job->container_type) : 'Package'),
                                ];
                            }
                        @endphp
                        @for($i = 0; $i < 5; $i++)
                            <tr>
                                <td class="center">{{ $i + 1 }}</td>
                                <td>{{ $rowList[$i]['name'] ?? '' }}</td>
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
                    <td style="font-weight: 600;">Diserahkan Oleh:</td>
                    <td style="font-weight: 600;">Diterima Oleh:</td>
                </tr>
                <tr>
                    <td class="sign-space"></td>
                    <td class="sign-space"></td>
                </tr>
                <tr>
                    <td>
                        <div class="sign-name-dots">( ......................................... )</div>
                        <div class="sign-role">Pengirim / Kurir</div>
                    </td>
                    <td>
                        <div class="sign-name-dots">( ......................................... )</div>
                        <div class="sign-role">Customer / Penerima</div>
                    </td>
                </tr>
            </table>
        </div>

        @if($copy === 1)
            <!-- CUT DIVIDER -->
            <div class="cut-divider">
                <div class="cut-divider-line"></div>
                <div class="cut-divider-badge">✂ &nbsp;POTONG DI SINI&nbsp; ✂</div>
            </div>
        @endif
    @endfor

</body>
</html>
