<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f1f3d">
    <title>@yield('title', 'Dashboard') · JobFinance</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a class="skip-link" href="#main">Lewati ke konten</a>
<button class="sidebar-backdrop" data-menu-close aria-label="Tutup navigasi" tabindex="-1"></button>
<aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
    <a href="{{ route('dashboard') }}" class="brand brand-logo"><img src="{{ asset('images/logo.png') }}" alt="JobFinance Logo" class="brand-img"></a>
    <nav>
        <p class="nav-heading">WORKSPACE</p>
        <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><x-icon/>Dashboard</a>
        @php
        $groups = [
            'SALES & CUSTOMER' => [['quotations.manage','file','Quotation','quotations.index'],['customers.manage','users','Customer'],['customers.view','clock','Kontak & PIC','customer-contacts.index'],['vendors.manage','users','Vendor']],
            'PRICING' => [['pricing.view','chart','Weekly Pricing','pricing.weekly.index'],['pricing.view','briefcase','Trucking Price List','pricing.trucking.index']],
            'KALKULATOR' => [['dashboard.view','calculator','Kalkulator','calculators.index']],
<<<<<<< HEAD
            'OPERASIONAL' => [['jobs.view','briefcase','Job Order','jobs.index'],['jobs.view','file','Dokumen Job','documents.index'],['jobs.manage','file','Tipe Dokumen','document-types.index'],['tps.manage','briefcase','Master TPS','tps.index']],
            'KEUANGAN' => [['costs.manage','wallet','Biaya Job','costs.overview'],['jobs.close','check','Closing Job','closing.index'],['invoices.manage','file','Invoice','invoices.index'],['payments.manage','wallet','Pembayaran','payments.index'],['reimbursements.manage','wallet','Reimbursement','reimbursements.index']],
            'AKUNTANSI' => [['coa.manage','file','Chart of Accounts','accounts.index'],['journals.manage','file','Jurnal','journals.index'],['reports.view','chart','Buku Besar','reports.ledger'],['reports.view','chart','Neraca Saldo','reports.trial-balance']],
=======
            'OPERASIONAL' => [['jobs.view','briefcase','Job Order','jobs.index'],['jobs.manage','briefcase','Master TPS','tps.index'],['jobs.view','file','Dokumen Job','documents.index'],['jobs.manage','file','Tipe Dokumen','document-types.index']],
            'KEUANGAN' => [['costs.manage','wallet','Biaya Job','costs.overview'],['jobs.close','check','Closing Job','closing.index'],['invoices.manage','file','Invoice','invoices.index'],['invoices.manage','file','Coretax DJP','invoices.coretax.index'],['payments.manage','wallet','Pembayaran','payments.index'],['reimbursements.manage','wallet','Reimbursement','reimbursements.index']],
            'AKUNTANSI' => [['coa.manage','file','Chart of Accounts','accounts.index'],['coa.manage','file','Mapping Akun','accounts.mappings'],['journals.manage','file','Jurnal','journals.index'],['reports.view','chart','Buku Besar','reports.ledger'],['reports.view','chart','Neraca Saldo','reports.trial-balance']],
>>>>>>> 367a9ee93734e3a97628530a24dddb5e9c488978
            'Laporan Keuangan' => [['reports.view','chart','Neraca','reports.balance-sheet'],['reports.view','chart','Laba Rugi','reports.income-statement'],['reports.view','wallet','Arus Kas','reports.cash-flow'],['reports.view','briefcase','Profit per Job','reports.profit-per-job'],['reports.view','calendar','Profit Bulanan','reports.profit-monthly'],['reports.view','wallet','Statement of Account','reports.soa']],
        ];
        @endphp
        @foreach($groups as $heading => $items)
            @if(collect($items)->contains(fn ($item) => auth()->user()->can($item[0])))
            <p class="nav-heading">{{ $heading }}</p>
            @foreach($items as $item)
                @php [$permission, $icon, $label] = $item; @endphp
                @can($permission)
                @php
                $destination = $item[3] ?? ['vendors.manage'=>'vendors.index','customers.manage'=>'customers.index','coa.manage'=>'accounts.index','quotations.manage'=>'quotations.index','jobs.view'=>'jobs.index','costs.manage'=>'costs.overview','jobs.close'=>'closing.index','invoices.manage'=>'invoices.index','payments.manage'=>'payments.index','journals.manage'=>'journals.index','reimbursements.manage'=>'reimbursements.index'][$permission] ?? null;
                $isCostPage = request()->routeIs('jobs.costs.*','costs.*');
                $active = false;
                if ($destination) {
                    if ($destination === 'documents.index') {
                        $active = request()->routeIs('documents.*','quotations.*','jobs.*') && !$isCostPage;
                    } elseif (str_starts_with((string)$destination, 'reports.')) {
                        $active = request()->routeIs($destination, $destination.'.*');
                    } elseif ($permission === 'costs.manage') {
                        $active = $isCostPage;
                    } else {
                        $prefix = str_contains((string)$destination, '.') ? substr($destination, 0, strrpos($destination, '.')) : $destination;
                        $active = request()->routeIs($prefix . '.*') && !($permission === 'jobs.view' && $isCostPage);
                    }
                }
                @endphp
                @if($destination)
                <a class="nav-item {{ $active ? 'active' : '' }}" href="{{ route($destination) }}"><x-icon :name="$icon"/><span>{{ $label }}</span></a>
                @else
                <span class="nav-item upcoming" aria-disabled="true"><x-icon :name="$icon"/><span>{{ $label }}</span><small>Segera</small></span>
                @endif
                @endcan
            @endforeach
            @endif
        @endforeach
        @can('users.view')
        <p class="nav-heading">ADMINISTRASI</p>
        <a class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><x-icon name="users"/>Pengguna & Akses</a>
        @endcan
        @can('activity.view')
        <a class="nav-item {{ request()->routeIs('activity.*') ? 'active' : '' }}" href="{{ route('activity.index') }}"><x-icon name="clock"/>Log Aktivitas</a>
        @endcan
    </nav>
    <div class="sidebar-footer"><span class="status-dot"></span>JobFinance <span>Demo v0.1</span></div>
</aside>
<div class="workspace">
    <header class="topbar">
        <div class="topbar-left"><button class="icon-button menu-button" data-menu-toggle aria-label="Buka navigasi" aria-controls="sidebar" aria-expanded="false"><x-icon name="menu"/></button><span class="breadcrumb">Workspace <span>/</span> <strong>@yield('title', 'Dashboard')</strong></span></div>
        <div class="account"><span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span class="account-name">{{ auth()->user()->name }}<small>{{ auth()->user()->role?->label ?? 'Tanpa role' }}</small></span><form method="POST" action="{{ route('logout') }}">@csrf<button class="icon-button" title="Keluar" aria-label="Keluar dari akun"><x-icon name="logout"/></button></form></div>
    </header>
    <main id="main" tabindex="-1">
        @if(session('success'))<div class="flash-success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="flash-error" role="alert"><strong>Periksa kembali data Anda.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
    <footer class="page-footer"><span>© {{ date('Y') }} JobFinance</span><span>Setiap job, lebih terkontrol.</span></footer>
</div>
</body>
</html>
