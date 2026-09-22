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
    $signSyanneFile = public_path('images/signature-syanne.jpeg');
    $signSyanneBase64 = file_exists($signSyanneFile) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($signSyanneFile)) : '';
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>SURAT KUASA PENGAJUAN PEMBERITAHUAN PABEAN - {{ $job->number }}</title>
    <style>
        @page {
            margin: 28pt 45pt 20pt 45pt;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000000;
            font-size: 9pt;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .header-title {
            text-align: center;
            margin-top: 10pt;
            margin-bottom: 20pt;
        }
        .title-main {
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 2pt;
        }
        .title-sub {
            font-size: 11pt;
            letter-spacing: 0.5px;
            margin-bottom: 2pt;
        }
        .title-no {
            font-size: 9.5pt;
        }
        .paragraph {
            margin: 8pt 0 3pt 0;
            text-align: justify;
            line-height: 1.35;
        }
        table.info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1pt 0 3pt 0;
        }
        table.info-table td {
            vertical-align: top;
            padding: 1pt 0;
            font-size: 9pt;
        }
        .col-label {
            width: 150pt;
        }
        .col-label-doc {
            width: 170pt;
        }
        .col-sep {
            width: 15pt;
            text-align: center;
        }
        .col-val {
            text-align: left;
        }
        .legal-notice {
            margin: 10pt 0 5pt 0;
            text-align: justify;
            line-height: 1.35;
        }
        table.signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25pt;
        }
        table.signature-table td {
            vertical-align: top;
        }
        .sign-left {
            text-align: left;
            width: 50%;
        }
        .sign-right {
            text-align: center;
            width: 50%;
            padding-left: 50pt;
        }
        .sign-logo-img {
            height: 42pt;
            width: auto;
            margin: 6pt 0 4pt 0;
            display: block;
        }
        .sign-name-bold {
            font-weight: bold;
            font-size: 9pt;
        }
        .sign-role-title {
            font-weight: bold;
            font-size: 9pt;
        }
        .sign-space-empty {
            height: 48pt;
        }
    </style>
</head>
<body>

    <div class="header-title">
        <div class="title-main">SURAT KUASA</div>
        <div class="title-sub">PENGAJUAN PEMBERITAHUAN PABEAN</div>
        <div class="title-no">No. {{ $job->sk_pabean_number ?: '—' }}</div>
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
            <td class="col-label-doc">Nomor/Tanggal B/L atau AWB</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $hblNumber }}{{ $hblNumber && $blDate ? ' / ' : '' }}{{ $blDate }}</td>
        </tr>
        <tr>
            <td class="col-label-doc">Nomor/Tanggal Invoice</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $invoiceText ?: '—' }}</td>
        </tr>
        <tr>
            <td class="col-label-doc">Harga ( FOB / C&amp;F / CIF )</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $priceText }}</td>
        </tr>
        <tr>
            <td class="col-label-doc">Nomor/Tanggal Packing List</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $plText ?: '—' }}</td>
        </tr>
        <tr>
            <td class="col-label-doc">Dokumen Pelengkap lainnya</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $job->notes ?: '—' }}</td>
        </tr>
    </table>

    <table class="info-table" style="margin-top: 4pt;">
        <tr>
            <td class="col-label-doc">Nama Penerbit Invoice</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $shipperName ?: '—' }}</td>
        </tr>
    </table>

    <div class="legal-notice">
        Atas penyerahan dokumen tersebut, kami bertanggung jawab penuh atas kebenaran mengenai isi, jumlah, jenis serta kualitas barang yang tercantum dalam dokumen. Kami bertanggung jawab sepenuhnya atas segala kewajiban Kepabeanan sebagaimana dimaksud dalam Undang - undang No. 17 Tahun 2006 tentang Kepabeanan.
    </div>

    <div class="paragraph" style="margin-top: 8pt;">
        Demikian Surat Kuasa ini kami buat untuk dipergunakan sebagaimana mestinya.
    </div>

    <table class="signature-table">
        <tr>
            <td class="sign-left">
                <div>Penerima Kuasa</div>
                <div>
                    <img src="{{ $signSyanneBase64 ?: public_path('images/signature-syanne.jpeg') }}" class="sign-logo-img" alt="RDX LOGISTICS">
                </div>
                <div class="sign-name-bold">SYANNE</div>
                <div class="sign-role-title">PPJK</div>
            </td>
            <td class="sign-right">
                <div>Jakarta, <strong>{{ strtoupper($signDate) }}</strong></div>
                <div style="margin-top: 2pt;">Pemberi Kuasa,</div>
                <div class="sign-space-empty"></div>
                <div class="sign-name-bold">{{ strtoupper($authorizerName ?: 'BUDI SANTOSO') }}</div>
                <div class="sign-role-title">{{ strtoupper($authorizerTitle ?: 'DIREKTUR') }}</div>
            </td>
        </tr>
    </table>

</body>
</html>
