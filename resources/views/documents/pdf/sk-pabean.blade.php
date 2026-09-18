@php
    $customer = $job->customer ?? $quotation?->customer;
    $snapshot = $quotation?->customer_snapshot ?? [];

    $customerName = $job->consignee_name ?: ($customer?->name ?? ($snapshot['name'] ?? ''));
    $customerTax = $customer?->tax_number ?? ($snapshot['tax_number'] ?? '');
    $customerPhone = $customer?->phone ?? ($snapshot['phone'] ?? '');
    $customerAddress = $job->consignee_address ?: ($customer?->address ?? ($snapshot['address'] ?? ''));
    $authorizerName = $customer?->authorizer_name ?: ($customer?->contact_name ?? ($snapshot['contact_name'] ?? ''));
    $authorizerTitle = $customer?->authorizer_title ?: 'Direktur';

    $blNumber = $job->bl_number ?: ($job->awb_number ?: '');
    $hblNumber = $job->hbl_number ?: ($job->hawb_number ?: ($blNumber ?: ''));
    $blDate = $job->job_date?->format('d/m/Y') ?: '';
    
    $shipperName = $job->invoice_issuer ?: ($job->shipper_name ?: ($quotation?->shipper_name ?? ''));
    $incoterm = $job->incoterm ?: ($quotation?->incoterm ?? 'CIF');
    $priceText = $job->invoice_amount 
        ? ($incoterm . ' / ' . $job->invoice_amount) 
        : (($invoiceAmount = $quotation?->subtotal ?? $job->quotation_snapshot['totals']['subtotal'] ?? null) 
            ? $incoterm . ' / Rp ' . \App\Support\Money::format($invoiceAmount) 
            : $incoterm);

    // Commercial Invoice
    $invoiceNo = $job->commercial_invoice_number;
    $invoiceDate = $job->commercial_invoice_date?->format('d/m/Y');
    if (! $invoiceNo && $job->relationLoaded('documents')) {
        $invoiceDoc = $job->documents->first(fn($doc) => str_contains(strtoupper($doc->documentType?->name ?? ''), 'INVOICE') || str_contains(strtoupper($doc->documentType?->code ?? ''), 'INV'));
        if ($invoiceDoc) {
            $invoiceNo = $invoiceDoc->notes ?: pathinfo($invoiceDoc->original_name, PATHINFO_FILENAME);
            $invoiceDate = $invoiceDoc->created_at?->format('d/m/Y');
        }
    }
    $invoiceText = $invoiceNo ? ($invoiceNo . ($invoiceDate ? ' / ' . $invoiceDate : '')) : '';

    // Packing List
    $plNo = $job->packing_list_number;
    $plDate = $job->packing_list_date?->format('d/m/Y');
    if (! $plNo && $job->relationLoaded('documents')) {
        $plDoc = $job->documents->first(fn($doc) => str_contains(strtoupper($doc->documentType?->name ?? ''), 'PACKING') || str_contains(strtoupper($doc->documentType?->code ?? ''), 'PL'));
        if ($plDoc) {
            $plNo = $plDoc->notes ?: pathinfo($plDoc->original_name, PATHINFO_FILENAME);
            $plDate = $plDoc->created_at?->format('d/m/Y');
        }
    }
    $plText = $plNo ? ($plNo . ($plDate ? ' / ' . $plDate : '')) : '';

    $signDate = $job->job_date?->format('d-m-Y') ?? now()->format('d-m-Y');
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>SURAT KUASA PENGAJUAN PEMBERITAHUAN PABEAN - {{ $job->number }}</title>
    <style>
        @page {
            margin: 35px 50px 30px 50px;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, DejaVu Sans, sans-serif;
            color: #000;
            font-size: 9px;
            line-height: 1.4;
        }
        .kop-placeholder {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 25px;
        }
        .header-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .title-main {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .title-sub {
            font-size: 11.5px;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin: 1px 0;
        }
        .title-no {
            font-size: 10px;
            font-weight: bold;
        }
        .paragraph {
            margin: 10px 0 4px 0;
            text-align: justify;
            line-height: 1.4;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2px 0 4px 0;
        }
        .info-table td {
            vertical-align: top;
            padding: 1.2px 0;
            font-size: 9px;
        }
        .col-label {
            width: 165px;
        }
        .col-sep {
            width: 15px;
            text-align: center;
        }
        .col-val {
            text-align: left;
        }
        .legal-p {
            margin: 10px 0 6px 0;
            text-align: justify;
            line-height: 1.4;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        .signature-table td {
            vertical-align: top;
        }
        .sign-left {
            text-align: left;
            width: 50%;
        }
        .sign-right {
            text-align: center;
            width: 50%;
            padding-left: 60px;
        }
        .sign-logo {
            max-height: 48px;
            max-width: 130px;
            margin: 4px 0 2px 0;
            display: block;
        }
        .sign-name-bold {
            font-weight: bold;
            font-size: 9px;
        }
        .sign-role-title {
            font-weight: bold;
            font-size: 9px;
        }
        .sign-space-empty {
            height: 52px;
        }
    </style>
</head>
<body>

    <div class="kop-placeholder">KOP SURAT</div>

    <div class="header-title">
        <div class="title-main">SURAT KUASA</div>
        <div class="title-sub">PENGAJUAN PEMBERITAHUAN PABEAN</div>
        <div class="title-no">(No. {{ $job->number }} )</div>
    </div>

    <div class="paragraph">Yang bertanda-tangan dibawah ini :</div>

    <table class="info-table">
        <tr>
            <td class="col-label">Nama</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $authorizerName }}</td>
        </tr>
        <tr>
            <td class="col-label">Jabatan</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $authorizerTitle }}</td>
        </tr>
        <tr>
            <td class="col-label">Nama Perusahaan</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $customerName }}</td>
        </tr>
        <tr>
            <td class="col-label">NPWP</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $customerTax }}</td>
        </tr>
        <tr>
            <td class="col-label">Telepon</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $customerPhone }}</td>
        </tr>
        <tr>
            <td class="col-label">Alamat Perusahaan</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $customerAddress }}</td>
        </tr>
    </table>

    <div class="paragraph">
        Selanjutnya dalam Surat Kuasa ini disebut sebagai PEMBERI KUASA, dengan ini memberi kuasa kepada :
    </div>

    <table class="info-table">
        <tr>
            <td class="col-label">Nama PPJK</td>
            <td class="col-sep">:</td>
            <td class="col-val">PT. RADIX INTERNATIONAL LOGISTICS</td>
        </tr>
        <tr>
            <td class="col-label">NPWP</td>
            <td class="col-sep">:</td>
            <td class="col-val">27.018.918.6-032.000</td>
        </tr>
        <tr>
            <td class="col-label">Nama Pimpinan PPJK</td>
            <td class="col-sep">:</td>
            <td class="col-val">SYANNE</td>
        </tr>
        <tr>
            <td class="col-label">Alamat PPJK</td>
            <td class="col-sep">:</td>
            <td class="col-val">
                JL. TEH NO 3C, PINANGSIA, TAMAN SARI<br>
                KOTA ADM. JAKARTA BARAT, DKI JAKARTA
            </td>
        </tr>
        <tr>
            <td class="col-label">Telepon</td>
            <td class="col-sep">:</td>
            <td class="col-val">(021) 38873060</td>
        </tr>
    </table>

    <div class="paragraph">
        Selanjutnya didalam surat kuasa ini disebut sebagai PENERIMA KUASA, guna bertindak untuk dan atas nama Pemberi Kuasa untuk pengajuan Pemberitahuan Pabean pada kantor Pelayanan Bea dan Cukai yang bersangkutan atas import / eksport barang tersebut dibawah ini :
    </div>

    <table class="info-table">
        <tr>
            <td class="col-label">Nomor/Tanggal B/L atau AWB</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $hblNumber }}{{ $hblNumber && $blDate ? ' / ' . $blDate : '' }}</td>
        </tr>
        <tr>
            <td class="col-label">Nomor/Tanggal Invoice</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $invoiceText }}</td>
        </tr>
        <tr>
            <td class="col-label">Harga ( FOB / C&F / CIF )</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $priceText }}</td>
        </tr>
        <tr>
            <td class="col-label">Nomor/Tanggal Packing List</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $plText }}</td>
        </tr>
        <tr>
            <td class="col-label">Dokumen Pelengkap lainnya</td>
            <td class="col-sep">:</td>
            <td class="col-val">1.<br>2.</td>
        </tr>
        <tr>
            <td class="col-label">Nama Penerbit Invoice</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $shipperName }}</td>
        </tr>
    </table>

    <div class="legal-p">
        Atas penyerahan dokumen tersebut, kami bertanggung jawab penuh atas kebenaran mengenai isi, jumlah, jenis serta kualitas barang yang tercantum dalam dokumen. Kami bertanggung jawab sepenuhnya atas segala kewajiban Kepabeanan sebagaimana dimaksud dalam Undang - undang No. 17 Tahun 2006 tentang Kepabeanan.
    </div>

    <div class="paragraph" style="margin-top: 8px;">
        Demikian Surat Kuasa ini kami buat untuk dipergunakan sebagaimana mestinya.
    </div>

    <table class="signature-table">
        <tr>
            <td class="sign-left">
                <div>Penerima Kuasa,</div>
                <div>
                    <img src="{{ public_path('images/signature-syanne.jpeg') }}" class="sign-logo" alt="RDX LOGISTICS">
                </div>
                <div class="sign-name-bold">SYANNE</div>
                <div class="sign-role-title">PPJK</div>
            </td>
            <td class="sign-right">
                <div>Jakarta, {{ $signDate }}</div>
                <div style="margin-top: 2px;">Pemberi Kuasa,</div>
                <div class="sign-space-empty"></div>
                <div class="sign-name-bold">( {{ $authorizerName ?: '.........................................' }} )</div>
                <div class="sign-role-title">{{ $authorizerTitle ?: 'Direktur' }}</div>
            </td>
        </tr>
    </table>

</body>
</html>
