@php
    $customer = $job->customer ?? $quotation?->customer;
    $snapshot = $quotation?->customer_snapshot ?? [];
    $customerName = $job->consignee_name ?: ($customer?->name ?? ($snapshot['name'] ?? 'CONSIGNEE'));
    $customerAddress = $job->consignee_address ?: ($customer?->address ?? ($snapshot['address'] ?? ''));
    $shipperName = $job->shipper_name ?: ($quotation?->shipper_name ?? 'SHIPPER');
    $shipperAddress = $job->shipper_address ?: ($quotation?->shipper_address ?? '');
    $aju = $job->booking_reference ?: ($job->customs_registration_number ?: '');
    $nopen = $job->nopen ?: '';
    $nopenDate = $job->nopen_date?->format('d-m-Y') ?? ($job->job_date?->format('d-m-Y') ?? now()->format('d-m-Y'));
    $commodity = $job->cargo_description ?: ($quotation?->commodity ?? '');
    $invoiceValue = $quotation?->subtotal ?? $job->quotation_snapshot['totals']['subtotal'] ?? null;
    $freight = $job->quotation_snapshot['freight'] ?? null;
    $insurance = $job->quotation_snapshot['insurance'] ?? null;
    $totalValue = $invoiceValue;
    $signName = $customer?->authorizer_name ?: 'Nama Direktur';
    $signTitle = $customer?->authorizer_title ?: 'Direktur';
    $dateText = $job->job_date?->format('d-m-Y') ?? now()->format('d-m-Y');
    $money = fn($value) => $value !== null && $value !== '' ? 'Rp '.\App\Support\Money::format($value) : 'MANUAL INPUT';
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>DEKLARASI NILAI PABEAN (DNP) - {{ $job->number }}</title>
    <style>
        @page {
            margin: 25px 35px 25px 35px;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #000;
            font-size: 8.5px;
            line-height: 1.3;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-bold {
            font-weight: bold;
        }
        .doc-title {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        .divider {
            border-bottom: 1.5px solid #000;
            margin: 6px 0 8px 0;
            clear: both;
        }
        .divider-thin {
            border-bottom: 1px solid #000;
            margin: 5px 0 6px 0;
            clear: both;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .meta-table td {
            vertical-align: top;
            padding: 1px 0;
            font-size: 8.5px;
        }
        .section-header-box {
            background: #000;
            color: #fff;
            font-weight: bold;
            padding: 2px 6px;
            display: inline-block;
            margin-bottom: 4px;
            font-size: 8.5px;
        }
        .section-header-underline {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 3px;
            font-size: 8.5px;
        }
        .choice-table {
            width: 100%;
            border-collapse: collapse;
        }
        .choice-table td {
            vertical-align: middle;
            padding: 2px 0;
        }
        .box-choice-table {
            border: 1.2px solid #000;
            border-collapse: collapse;
            margin: 0 auto;
            width: 30px;
            height: 16px;
        }
        .box-choice-table td {
            border: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            text-align: center !important;
            vertical-align: middle !important;
            font-weight: bold;
            font-size: 9.5px;
            line-height: 14px;
            height: 16px;
        }
        .sub-desc {
            padding-left: 12px;
            font-size: 8px;
            color: #111;
        }
        .sub-letter {
            padding-left: 14px;
            font-size: 8px;
        }
        .cost-table {
            width: 100%;
            border-collapse: collapse;
        }
        .cost-table td {
            padding: 1.5px 0;
            vertical-align: bottom;
            font-size: 8.5px;
        }
        .cost-line {
            display: inline-block;
            width: 130px;
            border-bottom: 1px solid #000;
            text-align: center;
            font-weight: bold;
            font-size: 8.5px;
            min-height: 13px;
            line-height: 13px;
        }
        .cost-line-empty {
            display: inline-block;
            width: 130px;
            border-bottom: 1px solid #000;
            min-height: 13px;
            height: 13px;
        }
        .page-break {
            page-break-before: always;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
        }
        .grid-table th, .grid-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            text-align: center;
            font-size: 8px;
            height: 13px;
        }
        .grid-table th {
            font-weight: bold;
        }
        .doc-line-full {
            display: inline-block;
            border-bottom: 1px solid #000;
            width: 70%;
            height: 10px;
            vertical-align: bottom;
        }
        .doc-line-fill {
            border-bottom: 1px solid #000;
            width: 100%;
            height: 11px;
            margin-top: 3px;
        }
        .signature-section {
            width: 100%;
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .sign-block {
            float: right;
            width: 220px;
            text-align: center;
        }
        .sign-space {
            height: 48px;
        }
    </style>
</head>
<body>

    <!-- PAGE 1 -->
    <div class="doc-title">DEKLARASI NILAI PABEAN (DNP)</div>
    <div class="divider"></div>

    <!-- META DETAILS -->
    <table class="meta-table">
        <tr>
            <td style="width: 18%;">Nama Pembeli</td>
            <td style="width: 2%;">:</td>
            <td style="width: 42%; font-weight: bold;">{{ $customerName ?: 'CONSIGNEE' }}</td>
            <td style="width: 16%;">No. AJU PIB</td>
            <td style="width: 2%;">:</td>
            <td style="width: 20%; font-weight: bold;">{{ $aju }}</td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td>:</td>
            <td>{{ $customerAddress }}</td>
            <td>NOPEN PIB</td>
            <td>:</td>
            <td style="font-weight: bold;">{{ $nopen }}</td>
        </tr>
        <tr>
            <td colspan="3"></td>
            <td>Tanggal</td>
            <td>:</td>
            <td>{{ $nopenDate }}</td>
        </tr>
        <tr>
            <td colspan="3"></td>
            <td>Jenis Barang</td>
            <td>:</td>
            <td style="font-weight: bold;">{{ $commodity }}</td>
        </tr>
        <tr>
            <td>Nama Penjual</td>
            <td>:</td>
            <td style="font-weight: bold;">{{ $shipperName ?: 'SHIPPER' }}</td>
            <td colspan="3"></td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td>:</td>
            <td>{{ $shipperAddress }}</td>
            <td colspan="3"></td>
        </tr>
        <tr>
            <td>Nama Importir</td>
            <td>:</td>
            <td style="font-weight: bold;">{{ $customerName ?: 'CONSIGNEE' }}</td>
            <td colspan="3"></td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td>:</td>
            <td>{{ $customerAddress }}</td>
            <td colspan="3"></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>JAKARTA</td>
            <td colspan="3"></td>
        </tr>
        <tr>
            <td>Nama Pemberitahu</td>
            <td>:</td>
            <td colspan="4" style="font-weight: bold;">PT.RADIX INTERNATIONAL LOGISTICS</td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td>:</td>
            <td colspan="4">JL. TEH NO 3C 008 007 PINANGSIA TAMAN SARI KOTA ADM.<br>JAKARTA BARAT DKI JAKARTA</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- SECTION A -->
    <table class="choice-table">
        <tr>
            <td style="width: 78%; font-weight: bold; text-decoration: underline;">A. &nbsp;Obyek penjualan ke dalam Daerah Pabean</td>
            <td style="width: 11%; text-align: center; font-weight: bold;">YA</td>
            <td style="width: 11%; text-align: center; font-weight: bold;">TIDAK</td>
        </tr>
        <tr>
            <td style="padding-top: 4px;">Apakah barang impor saudara merupakan obyek suatu penjualan kedalam<br>Daerah Pabean ?</td>
            <td style="text-align: center; vertical-align: middle;">
                <table class="box-choice-table"><tr><td>&nbsp;</td></tr></table>
            </td>
            <td style="text-align: center; vertical-align: middle;">
                <table class="box-choice-table"><tr><td>X</td></tr></table>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- SECTION B -->
    <div class="section-header-box">B. &nbsp;Persyaratan Nilai Transaksi</div>

    <table class="choice-table" style="margin-top: 4px;">
        <tr>
            <td style="width: 78%; vertical-align: top;">
                1. Apakah terdapat persyaratan / pertimbangan atas pembelian barang Impor saudara yang mempengaruhi harga barang impor tersebut, sehingga mengakibatkan harga barang tidak ditentukan ?
            </td>
            <td style="width: 11%; text-align: center; vertical-align: middle;">
                <table class="box-choice-table"><tr><td>&nbsp;</td></tr></table>
            </td>
            <td style="width: 11%; text-align: center; vertical-align: middle;">
                <table class="box-choice-table"><tr><td>X</td></tr></table>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; padding-top: 4px;">
                2. Apakah terdapat keharusan saudara mengirim procceds atas transaksi jual-beli barang Impor saudara kepada penjual ?<br>
                <span class="sub-desc">Apabila jawaban saudara YA, Procceds harus dicantumkan pada huruf D.6</span>
            </td>
            <td style="text-align: center; vertical-align: middle;">
                <table class="box-choice-table"><tr><td>&nbsp;</td></tr></table>
            </td>
            <td style="text-align: center; vertical-align: middle;">
                <table class="box-choice-table"><tr><td>X</td></tr></table>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; padding-top: 4px;">
                3. Apakah antara saudara dengan penjual saling berhubungan ?<br>
                <span class="sub-letter">a. Apabila jawaban saudara YA, apakah hubungan tersebut mempengaruhi harga barang impor saudara ?</span><br>
                <span class="sub-letter">b. Apabila hubungan tersebut TIDAK mempengaruhi harga, lampirkan (Test Value) pada DNP ini.</span>
            </td>
            <td style="text-align: center; vertical-align: middle;">
                <table class="box-choice-table"><tr><td>&nbsp;</td></tr></table>
            </td>
            <td style="text-align: center; vertical-align: middle;">
                <table class="box-choice-table"><tr><td>X</td></tr></table>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; padding-top: 4px;">
                4. Apakah terdapat pembatasan atas pemakaian / pemanfaatan barang impor yang :<br>
                <span class="sub-letter">a. tidak diatur oleh peraturan perundang-undangan yang berlaku di Daerah Pabean ;</span><br>
                <span class="sub-letter">b. tidak membatasi wilayah geografis tempat penjualan kembali barang impor saudara ; dan/atau</span><br>
                <span class="sub-letter">c. mempengaruhi harga barang impor Saudara secara substansial.</span>
            </td>
            <td style="text-align: center; vertical-align: middle;">
                <table class="box-choice-table"><tr><td>&nbsp;</td></tr></table>
            </td>
            <td style="text-align: center; vertical-align: middle;">
                <table class="box-choice-table"><tr><td>X</td></tr></table>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- SECTION C -->
    <div class="section-header-underline">C. &nbsp;Harga yang sebenarnya atau yang seharusnya dibayar.</div>
    <table class="cost-table">
        <tr>
            <td style="width: 22px; text-align: right; padding-right: 6px;">1.</td>
            <td>harga yang tercantum dalam invoice</td>
            <td style="width: 140px; text-align: right;"><span class="cost-line">{{ $money($invoiceValue) }}</span></td>
        </tr>
        <tr>
            <td style="text-align: right; padding-right: 6px;">2.</td>
            <td>pembayaran tidak langsung</td>
            <td style="text-align: right;"><span class="cost-line-empty"></span></td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- SECTION D -->
    <div class="section-header-underline">D. &nbsp;Biaya-biaya yang ditambahkan pada harga yang sebenarnya atau yang seharusnya Dibayar sepanjang biaya-biaya tersebut belum termasuk dalam harga yang sebenarnya Atau seharusnya dibayar.</div>
    <table class="cost-table">
        <tr>
            <td style="width: 22px; text-align: right; padding-right: 6px;">1.</td>
            <td>Komisi dan jasa prantara, kecuali komisi pembelian</td>
            <td style="width: 140px; text-align: right;"><span class="cost-line-empty"></span></td>
        </tr>
        <tr>
            <td style="text-align: right; padding-right: 6px;">2.</td>
            <td>Biaya pengemasan</td>
            <td style="text-align: right;"><span class="cost-line-empty"></span></td>
        </tr>
        <tr>
            <td style="text-align: right; padding-right: 6px;">3.</td>
            <td>Biaya pengepakan</td>
            <td style="text-align: right;"><span class="cost-line-empty"></span></td>
        </tr>
        <tr>
            <td style="text-align: right; padding-right: 6px;">4.</td>
            <td>Biaya bantuan (assist)</td>
            <td style="text-align: right;"><span class="cost-line-empty"></span></td>
        </tr>
        <tr>
            <td style="text-align: right; padding-right: 6px;">5.</td>
            <td>Royalty dan biaya lisensi</td>
            <td style="text-align: right;"><span class="cost-line-empty"></span></td>
        </tr>
        <tr>
            <td style="text-align: right; padding-right: 6px;">6.</td>
            <td>Procceds</td>
            <td style="text-align: right;"><span class="cost-line-empty"></span></td>
        </tr>
        <tr>
            <td style="text-align: right; padding-right: 6px;">7.</td>
            <td>Biaya transportasi</td>
            <td style="text-align: right;"><span class="cost-line">{{ $money($freight) }}</span></td>
        </tr>
        <tr>
            <td style="text-align: right; padding-right: 6px;">8.</td>
            <td>Biaya pemuatan, pembongkaran dan penanganan (handling charges) berkaitan dengan Pengangkutan barang impor yang belum termasuk dalam biaya transportasi</td>
            <td style="text-align: right;"><span class="cost-line-empty"></span></td>
        </tr>
        <tr>
            <td style="text-align: right; padding-right: 6px;">9.</td>
            <td>Asuransi</td>
            <td style="text-align: right;"><span class="cost-line">{{ $money($insurance) }}</span></td>
        </tr>
        <tr>
            <td style="text-align: right; padding-right: 6px; font-weight: bold;">10.</td>
            <td style="font-weight: bold;">Jumlah C dan D</td>
            <td style="text-align: right;"><span class="cost-line">{{ $totalValue ? 'Rp '.\App\Support\Money::format($totalValue) : 'TOTAL' }}</span></td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- SECTION E (First Part on Page 1) -->
    <div class="section-header-underline">E. &nbsp;Biaya-biaya yang dikurangkan dari harga yang sebenarnya atau yang seharusnya dibayar sepanjang biaya tersebut termasuk dalam harga yang sebenarnya atau yang seharusnya dibayar :</div>
    <table class="cost-table">
        <tr>
            <td style="width: 22px; text-align: right; padding-right: 6px;">1.</td>
            <td>Biaya pengangkutan dan/ atau asuransi setelah pengimporan</td>
            <td style="width: 140px; text-align: right;"><span class="cost-line-empty"></span></td>
        </tr>
        <tr>
            <td style="text-align: right; padding-right: 6px;">2.</td>
            <td>Biaya konstruksi, pembangunan, perakitan, perawatan atau bantuan Teknis setelah pengimporan.</td>
            <td style="text-align: right;"><span class="cost-line-empty"></span></td>
        </tr>
    </table>

    <!-- PAGE BREAK -->
    <div class="page-break"></div>

    <!-- PAGE 2 -->
    <!-- SECTION E (Second Part on Page 2) -->
    <table class="cost-table" style="margin-top: 4px;">
        <tr>
            <td style="width: 22px; text-align: right; padding-right: 6px;">3.</td>
            <td>Biaya lainnya setelah pengimporan</td>
            <td style="width: 140px; text-align: right;"><span class="cost-line-empty"></span></td>
        </tr>
        <tr>
            <td style="text-align: right; padding-right: 6px;">4.</td>
            <td>Bea Masuk, Cukai dan pajak dalam rangka impor</td>
            <td style="text-align: right;"><span class="cost-line-empty"></span></td>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold; padding-top: 6px;">Jumlah E</td>
            <td style="text-align: right; padding-top: 6px;"><span class="cost-line-empty"></span></td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- NILAI TRANSAKSI -->
    <table class="cost-table">
        <tr>
            <td style="font-weight: bold;">Nilai Transaksi, jumlah C ditambah D dikurang E</td>
            <td style="width: 140px; text-align: right;"><span class="cost-line">{{ $totalValue ? 'Rp '.\App\Support\Money::format($totalValue) : 'TOTAL' }}</span></td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- SECTION F -->
    <div style="font-weight: bold; font-size: 8.5px; margin: 4px 0;">
        F. &nbsp;Apakah Transaksi ini merupakan pengulangan transaksi yang pernah dilakukan sebelumnya atas barang dan terhadap Supplier yang sama? &nbsp;&nbsp;TIDAK
    </div>

    <div class="divider"></div>

    <!-- SECTION G -->
    <div style="font-weight: bold; font-size: 8.5px; margin-bottom: 2px;">G. &nbsp;Dokumen pendukung jawaban A, B, C, D, E, dan F</div>
    <div style="font-size: 8px; margin-bottom: 1px;"><strong>X*</strong> ( diisi importir dengan memberi tanda ✔ jika ada)</div>
    <div style="font-size: 8px; margin-bottom: 6px;"><strong>Y*</strong> ( divalidasi Pejabat dengan memberi tanda ✔ jika ada)</div>

    @php
        $supportDocs = [
            ['Invoice', true],
            ['Packing List', true],
            ["Kontrak Penjualan (Sale's Contract)", false],
            ['Purchase Order/Confirmation Order', true],
            ['L/C', true],
            ['Rekening Koran yang terkait dengan transaksi tersebu', false],
            ['Rekening Koran yang terdapat pelunasan transaksi sebelum nya', false],
            ['Bukti Transfer', true],
            ['Bukti hutang kepada supplier dalam hal barang belum jatuh tempo', false],
            ['Bukti negosiasi harga', false],
            ['Bukti pembayaran atas barang yang sama pada supplier yang sama untuk transaksi yang sama untuk transaksi sebelumnya', false],
            ['Sales contract untuk transaksi yang telah lalu atas barang yang sama', false],
            ['Perjanjian penunjukan agen penjual/pembelian/broker', false],
            ['Kontrak pembuatan pengemasan dan/atau pengepakan', false],
            ['Kontrak pembuatan barang impor dengan material yang dipasok oleh pembeli dariDaerah Pabean atau dari luar Daerah Pabean (assist', false],
            ['Perjanjian pembayaran royalty atau lisensi', false],
            ['Bukti bayar ongkos angkutan dalam hal FOB/exwork/....', false],
            ['Perjanjian pembayaran Procceds', false],
            ['Kontrak pengangkutan', false],
            ['Kontrak Asuransi', false],
            ['Laporan hasil audit kepabeanan 2 (dua) tahun terakhir', false],
        ];
    @endphp

    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <!-- LEFT COLUMN: BULLET LIST -->
            <td style="width: 80%; vertical-align: top; padding-right: 10px;">
                <table style="width: 100%; border-collapse: collapse;">
                    @foreach($supportDocs as [$docName, $isChecked])
                        <tr>
                            <td style="vertical-align: middle; padding: 1px 0; font-size: 8px; height: 13px; line-height: 1.1;">
                                • {{ $docName }}
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
            <!-- RIGHT COLUMN: YA / TIDAK GRID -->
            <td style="width: 20%; vertical-align: top;">
                <table class="grid-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">YA</th>
                            <th style="width: 50%;">TIDAK</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($supportDocs as [$docName, $isChecked])
                            <tr>
                                <td style="font-weight: bold; font-size: 9px; vertical-align: middle;">{{ $isChecked ? '✔' : '' }}</td>
                                <td></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- FILL-IN LINES -->
    <div style="font-size: 8px; margin-top: 8px; line-height: 1.4;">
        <div>• Dokumen pembayaran transaksi lainnya yang berkaitan dengan barang impor yang bersangkutan, antara lain :<span class="doc-line-full"></span></div>
        <div class="doc-line-fill"></div>
        <div class="doc-line-fill"></div>
        <div style="margin-top: 4px;">• Dokumen lainnya :<span class="doc-line-full"></span></div>
        <div class="doc-line-fill"></div>
        <div class="doc-line-fill"></div>
        <div style="margin-top: 4px;">• Perjanjian/agreement/kontrak maupun bukti pembayaran atas biaya-biaya yang dikurangkan pada harga yang sebenarnya atau yang seharusnya dibayar, antara lain :<span style="display:inline-block; border-bottom: 1px solid #000; width: 40%; height: 10px;"></span></div>
        <div class="doc-line-fill"></div>
        <div style="margin-top: 4px;">• Test Value :<span class="doc-line-full"></span></div>
        <div>• Dokumen lainnya :<span class="doc-line-full"></span></div>
        <div class="doc-line-fill"></div>
    </div>

    <!-- SIGNATURE BLOCK -->
    <div class="signature-section">
        <div class="sign-block">
            <div style="font-size: 8.5px;">Jakarta, {{ $nopenDate }}</div>
            <div style="font-size: 8px; font-style: italic; margin-top: 4px;">(Cap+TTD+Materai)</div>
            <div class="sign-space"></div>
            <div style="font-weight: bold; font-size: 9px;">{{ $signName }}</div>
            <div style="font-size: 8.5px;">{{ $signTitle }}</div>
        </div>
        <div style="clear: both;"></div>
    </div>

</body>
</html>