@php
    $customer = $job->customer ?? $quotation?->customer;
    $snapshot = $quotation?->customer_snapshot ?? [];
    $customerName = $job->consignee_name ?: ($customer?->name ?? ($snapshot['name'] ?? 'CONSIGNEE'));
    $customerAddress = $job->consignee_address ?: ($customer?->address ?? ($snapshot['address'] ?? ''));
    $shipperName = $job->shipper_name ?: ($quotation?->shipper_name ?? 'SHIPPER');
    $shipperAddress = $job->shipper_address ?: ($quotation?->shipper_address ?? '');
    $aju = $job->booking_reference ?: '';
    $nopen = $job->nopen ?: '';
    $commodity = $job->cargo_description ?: ($quotation?->commodity ?? '');
    $invoiceValue = $quotation?->subtotal ?? $job->quotation_snapshot['totals']['subtotal'] ?? null;
    $freight = $job->quotation_snapshot['freight'] ?? null;
    $insurance = $job->quotation_snapshot['insurance'] ?? null;
    $totalValue = $invoiceValue;
    $signName = $customer?->authorizer_name ?: 'Nama Direktur';
    $signTitle = $customer?->authorizer_title ?: 'Direktur';
    $dateText = now()->format('d-m-Y');
    $money = fn($value) => $value !== null && $value !== '' ? 'Rp '.\App\Support\Money::format($value) : 'MANUAL INPUT';
    $checked = '✓';
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page{margin:18px 22px}*{box-sizing:border-box}body{font-family:DejaVu Sans,sans-serif;color:#111;font-size:8.6px;line-height:1.25}.title{text-align:center;font-weight:700;font-size:15px;text-decoration:underline;margin:0 0 8px}.doc-no{text-align:left;font-size:9px;margin-bottom:8px}.main-table,.section-table,.support-table{width:100%;border-collapse:collapse}.main-table td,.section-table td,.section-table th,.support-table td,.support-table th{border:1px solid #111;padding:3px 4px;vertical-align:top}.no-border td{border:0}.label{font-weight:700;width:19%}.value{font-weight:400}.center{text-align:center}.right{text-align:right}.bold{font-weight:700}.section-title{font-weight:700;background:#f1f1f1}.checkbox{display:inline-block;width:12px;height:12px;border:1px solid #111;text-align:center;line-height:10px;font-weight:700;margin-right:4px}.answer{width:46px;text-align:center}.small{font-size:7.6px}.mt{margin-top:7px}.question{line-height:1.3}.signature{width:100%;margin-top:18px}.signature td{border:0;text-align:center;vertical-align:top}.stamp{height:58px;padding-top:18px}.footer-name{font-weight:700;text-decoration:underline}.indent{padding-left:14px}.nowrap{white-space:nowrap}.support-table th{background:#f1f1f1}.support-table .doc-name{width:72%}.support-table .mark{width:7%;text-align:center}.line-field{display:inline-block;min-width:130px;border-bottom:1px solid #111;height:11px}.amount{font-weight:700}.page-break{page-break-before:always}
    </style>
</head>
<body>
    <div class="title">DEKLARASI NILAI PABEAN (DNP)</div>
    <div class="doc-no">{{ $job->number }}</div>

    <table class="main-table">
        <tr>
            <td class="label">Nama Pembeli</td><td>{{ $customerName ?: 'CONSIGNEE' }}</td>
            <td class="label">No. AJU PIB</td><td>{{ $aju ?: '' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat</td><td rowspan="2">{{ $customerAddress }}</td>
            <td class="label">NOPEN PIB</td><td>{{ $nopen ?: '' }}</td>
        </tr>
        <tr>
            <td class="label"></td>
            <td class="label">Tanggal</td><td>{{ $job->job_date?->format('d-m-Y') ?? $dateText }}</td>
        </tr>
        <tr>
            <td class="label">Jenis Barang</td><td colspan="3">{{ $commodity }}</td>
        </tr>
        <tr>
            <td class="label">Nama Penjual</td><td>{{ $shipperName ?: 'SHIPPER' }}</td>
            <td class="label">Nama Importir</td><td>{{ $customerName ?: 'CONSIGNEE' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat</td><td>{{ $shipperAddress }}</td>
            <td class="label">Alamat</td><td>{{ $customerAddress }}</td>
        </tr>
        <tr>
            <td class="label">Nama Pemberitahu</td><td colspan="3" class="bold">PT.RADIX INTERNATIONAL LOGISTICS</td>
        </tr>
        <tr>
            <td class="label">Alamat</td><td colspan="3">JL. TEH NO 3C 008 007 PINANGSIA TAMAN SARI KOTA ADM. JAKARTA BARAT DKI JAKARTA</td>
        </tr>
    </table>

    <table class="section-table mt">
        <tr><td colspan="4" class="section-title">A. Obyek penjualan ke dalam Daerah Pabean</td></tr>
        <tr>
            <td class="question">Apakah barang impor saudara merupakan obyek suatu penjualan kedalam Daerah Pabean?</td>
            <td class="answer">YA<br><span class="checkbox"></span></td>
            <td class="answer">TIDAK<br><span class="checkbox">{{ $checked }}</span></td>
        </tr>
        <tr><td colspan="4" class="section-title">B. Persyaratan Nilai Transaksi</td></tr>
        <tr>
            <td class="question">Apakah terdapat persyaratan / pertimbangan atas pembelian barang impor saudara yang mempengaruhi harga barang impor tersebut, sehingga mengakibatkan harga barang tidak ditentukan?</td>
            <td class="answer"><span class="checkbox"></span></td><td class="answer"><span class="checkbox">{{ $checked }}</span></td>
        </tr>
        <tr>
            <td class="question">Apakah terdapat keharusan saudara mengirim proceeds atas transaksi jual-beli barang impor saudara kepada penjual? <br><span class="small">Apabila jawaban saudara YA, proceeds harus dicantumkan pada huruf D.6.</span></td>
            <td class="answer"><span class="checkbox"></span></td><td class="answer"><span class="checkbox">{{ $checked }}</span></td>
        </tr>
        <tr>
            <td class="question">Apakah antara saudara dengan penjual saling berhubungan? <br><span class="small">Apabila jawaban saudara YA, apakah hubungan tersebut mempengaruhi harga barang impor saudara? Apabila hubungan tersebut TIDAK mempengaruhi harga, lampirkan Test Value pada DNP ini.</span></td>
            <td class="answer"><span class="checkbox"></span></td><td class="answer"><span class="checkbox">{{ $checked }}</span></td>
        </tr>
        <tr>
            <td class="question">Apakah terdapat pembatasan atas pemakaian / pemanfaatan barang impor yang tidak diatur oleh peraturan perundang-undangan, tidak membatasi wilayah geografis penjualan kembali, dan/atau mempengaruhi harga barang impor secara substansial?</td>
            <td class="answer"><span class="checkbox"></span></td><td class="answer"><span class="checkbox">{{ $checked }}</span></td>
        </tr>
    </table>

    <table class="section-table mt">
        <tr><td colspan="3" class="section-title">C. Harga yang sebenarnya atau yang seharusnya dibayar</td></tr>
        <tr><td>1.</td><td>Harga yang tercantum dalam invoice</td><td class="right amount">{{ $money($invoiceValue) }}</td></tr>
        <tr><td>2.</td><td>Pembayaran tidak langsung</td><td class="right">-</td></tr>
        <tr><td colspan="2" class="bold">Jumlah C</td><td class="right amount">{{ $money($invoiceValue) }}</td></tr>

        <tr><td colspan="3" class="section-title">D. Biaya-biaya yang ditambahkan pada harga yang sebenarnya atau yang seharusnya dibayar</td></tr>
        <tr><td>1.</td><td>Komisi dan jasa perantara, kecuali komisi pembelian</td><td class="right">-</td></tr>
        <tr><td>2.</td><td>Biaya pengemasan</td><td class="right">-</td></tr>
        <tr><td>3.</td><td>Biaya pengepakan</td><td class="right">-</td></tr>
        <tr><td>4.</td><td>Biaya bantuan (assist)</td><td class="right">-</td></tr>
        <tr><td>5.</td><td>Royalty dan biaya lisensi</td><td class="right">-</td></tr>
        <tr><td>6.</td><td>Proceeds</td><td class="right">-</td></tr>
        <tr><td>7.</td><td>Biaya transportasi</td><td class="right amount">{{ $money($freight) }}</td></tr>
        <tr><td>8.</td><td>Biaya pemuatan, pembongkaran dan penanganan (handling charges) yang belum termasuk dalam biaya transportasi</td><td class="right">-</td></tr>
        <tr><td>9.</td><td>Asuransi</td><td class="right amount">{{ $money($insurance) }}</td></tr>
        <tr><td colspan="2" class="bold">Jumlah C dan D</td><td class="right amount">{{ $money($totalValue) }}</td></tr>

        <tr><td colspan="3" class="section-title">E. Biaya-biaya yang dikurangkan dari harga yang sebenarnya atau yang seharusnya dibayar</td></tr>
        <tr><td>1.</td><td>Biaya pengangkutan dan/atau asuransi setelah pengimporan</td><td class="right">-</td></tr>
        <tr><td>2.</td><td>Biaya konstruksi, pembangunan, perakitan, perawatan atau bantuan teknis setelah pengimporan</td><td class="right">-</td></tr>
        <tr><td>3.</td><td>Biaya lainnya setelah pengimporan</td><td class="right">-</td></tr>
        <tr><td>4.</td><td>Bea Masuk, Cukai dan pajak dalam rangka impor</td><td class="right">-</td></tr>
        <tr><td colspan="2" class="bold">Jumlah E</td><td class="right">-</td></tr>

        <tr><td colspan="2" class="section-title">F. Nilai Transaksi, jumlah C ditambah D dikurang E</td><td class="right amount">{{ $money($totalValue) }}</td></tr>
        <tr><td colspan="3">Apakah transaksi ini merupakan pengulangan transaksi yang pernah dilakukan sebelumnya atas barang dan terhadap supplier yang sama? <strong>TIDAK</strong></td></tr>
    </table>

    <table class="support-table mt">
        <tr><th colspan="3">Dokumen pendukung jawaban A, B, C, D, E, dan F</th></tr>
        <tr><th class="doc-name">Dokumen</th><th class="mark">X*</th><th class="mark">Y*</th></tr>
        @foreach([
            ['Invoice', true],
            ['Packing List', true],
            ["Kontrak Penjualan (Sale's Contract)", false],
            ['Purchase Order/Confirmation Order', false],
            ['L/C', false],
            ['Rekening Koran yang terkait dengan transaksi tersebut', false],
            ['Rekening Koran yang terdapat pelunasan transaksi sebelumnya', false],
            ['Bukti Transfer', false],
            ['Bukti hutang kepada supplier dalam hal barang belum jatuh tempo', false],
            ['Bukti negosiasi harga', false],
            ['Bukti pembayaran atas barang yang sama pada supplier yang sama untuk transaksi sebelumnya', false],
            ['Sales contract untuk transaksi yang telah lalu atas barang yang sama', false],
            ['Perjanjian penunjukan agen penjual/pembelian/broker', false],
            ['Kontrak pembuatan pengemasan dan/atau pengepakan', false],
            ['Kontrak pembuatan barang impor dengan material yang dipasok oleh pembeli dari Daerah Pabean atau dari luar Daerah Pabean (assist)', false],
            ['Perjanjian pembayaran royalty atau lisensi', false],
            ['Bukti bayar ongkos angkutan dalam hal FOB/exwork/....', false],
            ['Perjanjian pembayaran Proceeds', false],
            ['Kontrak pengangkutan', false],
            ['Kontrak Asuransi', false],
            ['Laporan hasil audit kepabeanan 2 (dua) tahun terakhir', false],
        ] as [$docName, $isChecked])
            <tr><td>{{ $docName }}</td><td class="mark">{{ $isChecked ? $checked : '' }}</td><td class="mark">{{ $isChecked ? $checked : '' }}</td></tr>
        @endforeach
        <tr><td>Dokumen pembayaran transaksi lainnya yang berkaitan dengan barang impor yang bersangkutan, antara lain: <span class="line-field"></span></td><td></td><td></td></tr>
        <tr><td>Dokumen lainnya: <span class="line-field"></span></td><td></td><td></td></tr>
        <tr><td>Perjanjian/agreement/kontrak maupun bukti pembayaran atas biaya-biaya yang dikurangkan pada harga yang sebenarnya atau yang seharusnya dibayar, antara lain: <span class="line-field"></span></td><td></td><td></td></tr>
        <tr><td>Test Value: <span class="line-field"></span></td><td></td><td></td></tr>
        <tr><td>Dokumen lainnya: <span class="line-field"></span></td><td></td><td></td></tr>
    </table>

    <table class="signature">
        <tr>
            <td style="width:55%"></td>
            <td>Jakarta, {{ $dateText }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="stamp">(Cap+TTD+Materai)</td>
        </tr>
        <tr>
            <td></td>
            <td><div class="footer-name">{{ $signName }}</div><div>{{ $signTitle }}</div></td>
        </tr>
    </table>
</body>
</html>