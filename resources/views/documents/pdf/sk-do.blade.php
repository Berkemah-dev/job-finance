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
    $vesselVoyage = $job->vessel_voyage ?: ($job->flight_number ?: '');
    $eta = $job->eta?->format('d/m/Y') ?: '';
    
    $qty = $job->package_count ? $job->package_count . ' ' . ($job->package_unit ?: 'Package') : ($job->container_type ? '1x ' . strtoupper($job->container_type) : ($quotation?->cargo_qty ?? ''));
    $weight = $job->gross_weight ? \App\Support\Money::format($job->gross_weight) . ' KGS' : '';
    $commodity = $job->cargo_description ?: ($quotation?->commodity ?? '');
    
    $signDate = $job->job_date?->format('d-m-Y') ?? now()->format('d-m-Y');
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>SURAT KUASA DELIVERY ORDER IMPORT - {{ $job->number }}</title>
    <style>
        @page {
            margin: 45px 50px 35px 50px;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, DejaVu Sans, sans-serif;
            color: #000;
            font-size: 9.5px;
            line-height: 1.45;
        }
        .header-title {
            text-align: center;
            margin-top: 15px;
            margin-bottom: 30px;
        }
        .title-main {
            font-size: 13.5px;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .title-sub {
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        .paragraph {
            margin: 14px 0 6px 0;
            text-align: justify;
            line-height: 1.45;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2px 0 6px 0;
        }
        .info-table td {
            vertical-align: top;
            padding: 1.5px 0;
            font-size: 9.5px;
        }
        .col-label {
            width: 140px;
        }
        .col-label-ship {
            width: 180px;
        }
        .col-sep {
            width: 15px;
            text-align: center;
        }
        .col-val {
            text-align: left;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 50px;
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
            max-height: 52px;
            max-width: 140px;
            margin: 4px 0 2px 0;
            display: block;
        }
        .sign-name-bold {
            font-weight: bold;
            font-size: 9.5px;
        }
        .sign-role-title {
            font-weight: bold;
            font-size: 9.5px;
        }
        .sign-space-empty {
            height: 58px;
        }
    </style>
</head>
<body>

    <div class="header-title">
        <div class="title-main">SURAT KUASA</div>
        <div class="title-sub">DELIVERY ORDER IMPORT</div>
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
            <td class="col-label">Telp/Fax</td>
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
        Selanjutnya dalam Surat Kuasa ini disebut sebagai PEMBERI KUASA, dengan ini memberi kuasa kepada &nbsp;:
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
        Selanjutnya didalam surat kuasa ini disebut sebagai PENERIMA KUASA, guna bertindak untuk dan &nbsp;atas nama Pemberi Kuasa untuk pengambilan Original Delivery Order di yang bersangkutan atas import barang tersebut dibawah ini :
    </div>

    <table class="info-table">
        <tr>
            <td class="col-label-ship">No. HBL/AWB</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $hblNumber }}</td>
        </tr>
        <tr>
            <td class="col-label-ship">Flight atau Vessel No./Tgl Tiba</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $vesselVoyage }}{{ $vesselVoyage && $eta ? ' / ' : '' }}{{ $eta }}</td>
        </tr>
        <tr>
            <td class="col-label-ship">Jumlah Barang</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $qty }}{{ $qty && $weight ? ' / ' : '' }}{{ $weight }}</td>
        </tr>
        <tr>
            <td class="col-label-ship">Nama Barang</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $commodity }}</td>
        </tr>
    </table>

    <div class="paragraph" style="margin-top: 16px;">
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
