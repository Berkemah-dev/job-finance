<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f1f3d">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">
    <title>@yield('title', 'Dashboard') · JobFinance</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a class="skip-link" href="#main">Lewati ke konten</a>
<button class="sidebar-backdrop" data-menu-close aria-label="Tutup navigasi" tabindex="-1"></button>
<aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
    <a href="{{ route('dashboard.finance') }}" class="brand brand-logo"><img src="{{ asset('images/logo.png') }}" alt="JobFinance Logo" class="brand-img"></a>
    <nav>
        <p class="nav-heading">WORKSPACE</p>
        @php
        $dashboardLinks = [
            'super-admin' => [
                ['dashboard.finance', 'Dashboard Finance'],
                ['dashboard.finance-manager', 'Dashboard Finance Manager'],
                ['dashboard.sales-manager', 'Dashboard Sales Manager'],
                ['dashboard.sales', 'Dashboard Sales'],
                ['dashboard.operational', 'Dashboard Operational'],
                ['dashboard.customer-service', 'Dashboard Customer Service']
            ],
            'finance' => [['dashboard.finance', 'Dashboard Finance']],
            'finance-manager' => [['dashboard.finance-manager', 'Dashboard Finance Manager']],
            'sales-manager' => [['dashboard.sales-manager', 'Dashboard Sales Manager']],
            'sales' => [['dashboard.sales', 'Dashboard Sales']],
            'operational' => [['dashboard.operational', 'Dashboard Operational']],
            'customer-service' => [['dashboard.customer-service', 'Dashboard Customer Service']],
        ];
        $currentRole = auth()->user()->role?->name ?? '';
        @endphp
        @foreach($dashboardLinks[$currentRole] ?? [['dashboard.finance', 'Dashboard Finance']] as [$dashboardRoute, $dashboardLabel])
        @php
            $isActive = request()->routeIs($dashboardRoute) || (request()->routeIs('dashboard') && ($currentRole === 'super-admin' ? $dashboardRoute === 'dashboard.finance' : str_ends_with($dashboardRoute, $currentRole)));
        @endphp
        <a class="nav-item {{ $isActive ? 'active' : '' }}" href="{{ route($dashboardRoute) }}"><x-icon name="grid"/><span>{{ $dashboardLabel }}</span></a>
        @endforeach
        @php
        $groups = [
            'SALES & CUSTOMER' => [['quotations.manage','file','Quotation','quotations.index'],['customers.manage','users','Customer'],['customers.view','clock','Kontak & PIC','customer-contacts.index'],['vendors.manage','users','Vendor']],
            'PRICING' => [['pricing.view','chart','Weekly Pricing','pricing.weekly.index'],['pricing.view','briefcase','Trucking Price List','pricing.trucking.index']],
            'KALKULATOR' => [['dashboard.view','calculator','Kalkulator','calculators.index']],
            'OPERASIONAL' => [['jobs.view','check','Booking Confirmation','booking-confirmations.index'],['jobs.view','briefcase','Job Order','jobs.index'],['jobs.view','file','Shipping Instruction','shipping-instructions.index'],['jobs.view','file','Dokumen Job','documents.index'],['jobs.manage','file','Tipe Dokumen','document-types.index'],['tps.manage','briefcase','Master TPS','tps.index']],
            'MASTER DATA' => [['jobs.manage','database','Data Port','ports.index'],['jobs.manage','wallet','Data Cost','charge-types.index'],['jobs.manage','briefcase','Data Unit','container-units.index']],
            'KEUANGAN' => [['costs.manage','wallet','Biaya Job','costs.overview'],['jobs.close','check','Closing Job','closing.index'],['invoices.manage','file','Invoice','invoices.index'],['payments.manage','wallet','Pembayaran','payments.index'],['reimbursements.manage','wallet','Reimbursement','reimbursements.index']],
            'AKUNTANSI' => [['coa.manage','database','Data COA','accounts.index'],['coa.manage','file','Mapping Akun','accounts.mappings'],['journals.manage','file','Jurnal','journals.index'],['reports.view','chart','Buku Besar','reports.ledger'],['reports.view','chart','Neraca Saldo','reports.trial-balance']],
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
                    if (str_starts_with((string)$destination, 'reports.')) {
                        $active = request()->routeIs($destination, $destination.'.*');
                    } elseif ($destination === 'accounts.mappings') {
                        $active = request()->routeIs('accounts.mappings');
                    } elseif ($destination === 'accounts.index') {
                        $active = request()->routeIs('accounts.*') && !request()->routeIs('accounts.mappings');
                    } elseif ($permission === 'costs.manage') {
                        $active = $isCostPage;
                    } else {
                        $prefix = str_contains((string)$destination, '.') ? substr($destination, 0, strrpos($destination, '.')) : $destination;
                        $active = request()->routeIs($prefix . '.*') && !($destination === 'jobs.index' && $isCostPage);
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
        <details class="account-menu"><summary class="account"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><span class="account-name">{{ auth()->user()->name }}<small>{{ auth()->user()->role?->label ?? 'Tanpa role' }}</small></span><x-icon name="chevron-down"/></summary><div class="account-dropdown"><div class="account-dropdown-head"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></div><a href="{{ route('profile.show') }}"><x-icon name="users"/>Profil Saya</a><a href="{{ route('profile.show') }}#security"><x-icon name="lock"/>Keamanan & Password</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><x-icon name="logout"/>Keluar</button></form></div></details>
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
