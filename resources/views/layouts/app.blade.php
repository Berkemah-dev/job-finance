<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f1f3d">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/favicon.png') }}">
    <title>@yield('title', 'Dashboard') · JobFinance</title>
    <script>try{if(window.innerWidth>900&&localStorage.getItem('jobfinance_sidebar_collapsed')==='1'){document.documentElement.classList.add('sidebar-collapsed');}if(localStorage.getItem('jobfinance_theme')==='dark'){document.documentElement.classList.add('theme-dark');}}catch(e){}</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .theme-toggle{width:40px;height:40px;border:1px solid var(--border-color,#dbe3ef);background:var(--surface,#fff);color:var(--text,#0f1f3d);border-radius:10px;padding:0;cursor:pointer;display:inline-grid;place-items:center;font-size:17px}.theme-toggle:hover{border-color:#1e3a8a}.port-autocomplete-field,.port-autocomplete-field:focus{border:1px solid #dbe3ef!important;box-shadow:none!important}
        .theme-toggle:hover{border-color:#1e3a8a}.theme-dark body{background:#0f172a;color:#e5e7eb}.theme-dark .workspace,.theme-dark main{background:#0f172a}.theme-dark .topbar,.theme-dark .panel,.theme-dark .card,.theme-dark .stat-card,.theme-dark .calculator-hub-card,.theme-dark .account-dropdown,.theme-dark .form-panel,.theme-dark .archive-panel{background:#172033!important;color:#e5e7eb;border-color:#334155!important}.theme-dark input,.theme-dark select,.theme-dark textarea{background:#111827!important;color:#f8fafc!important;border-color:#475569!important}.theme-dark h1,.theme-dark h2,.theme-dark h3,.theme-dark label,.theme-dark strong,.theme-dark .breadcrumb{color:#f8fafc}.theme-dark p,.theme-dark small,.theme-dark .subtle,.theme-dark .form-help{color:#aab7ca}.theme-dark table th{background:#1e293b;color:#cbd5e1}.theme-dark table td{border-color:#334155}.theme-dark .theme-toggle{background:#1e293b;color:#f8fafc;border-color:#475569}.theme-dark .page-footer{color:#94a3b8}
        .theme-dark .sidebar{background:#172033!important;border-color:#334155!important}.theme-dark .sidebar .nav-item,.theme-dark .sidebar .nav-item span,.theme-dark .sidebar .nav-heading,.theme-dark .sidebar .sidebar-footer{color:#cbd5e1!important}.theme-dark .sidebar .nav-item.active{background:#2a2230;color:#fff!important}.theme-dark .sidebar .nav-item:hover{background:#243047;color:#fff!important}.theme-dark .text-link{color:#cbd5e1!important}.nav-group{margin:0 0 8px}.nav-group>summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;padding-right:14px}.nav-group>summary::-webkit-details-marker{display:none}.nav-group-title{display:inline-flex;align-items:center;gap:8px}.nav-group-title .icon{width:14px;height:14px;color:#94a3b8}.nav-group-chevron{width:14px;height:14px;transition:transform .2s;color:#94a3b8}.nav-group[open] .nav-group-chevron{transform:rotate(180deg)}.nav-group .nav-item{margin-top:2px}.theme-dark .nav-group>summary{color:#cbd5e1!important}.port-autocomplete-wrap{position:relative}.port-suggestions{position:absolute;z-index:30;left:0;right:0;top:calc(100% + 4px);background:#fff;border:1px solid #cbd5e1;border-radius:8px;box-shadow:0 12px 24px rgba(15,31,61,.16);max-height:220px;overflow-y:auto;display:none}.port-suggestions.show{display:block}.port-suggestion{display:block;width:100%;padding:10px 12px;border:0;background:#fff;text-align:left;color:#0f1f3d;font-size:13px;cursor:pointer}.port-suggestion:hover,.port-suggestion.active{background:#e0f2fe;color:#0f1f3d}.theme-dark .port-suggestions,.theme-dark .port-suggestion{background:#172033;color:#f8fafc;border-color:#475569}.theme-dark .port-suggestion:hover,.theme-dark .port-suggestion.active{background:#243047}.port-autocomplete-field,.port-autocomplete-field:focus{border:1px solid #dbe3ef!important;box-shadow:none!important}
    </style>
</head>
<body>
<a class="skip-link" href="#main">Lewati ke konten</a>
<button class="sidebar-backdrop" data-menu-close aria-label="Tutup navigasi" tabindex="-1"></button>
<aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
    <a href="{{ route('dashboard.finance') }}" class="brand brand-logo"><img src="{{ asset('images/logo.png') }}" alt="JobFinance Logo" class="brand-img"></a>
    <nav id="sidebar-nav">
        <script>try{var s=sessionStorage.getItem('jobfinance_sidebar_scroll');if(s){var n=document.getElementById('sidebar-nav');if(n)n.scrollTop=parseInt(s,10);}}catch(e){}</script>
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
        <a class="nav-item {{ $isActive ? 'active' : '' }}" href="{{ route($dashboardRoute) }}" title="{{ $dashboardLabel }}"><x-icon name="grid"/><span>{{ $dashboardLabel }}</span></a>
        @endforeach
        @php
        $groups = [
            'SALES & CUSTOMER' => [['quotations.manage','file','Quotation','quotations.index'],['customers.manage','users','Customer'],['vendors.manage','users','Vendor']],
            'PRICING' => [['pricing.view','chart','Pricing Mingguan','pricing.weekly.index'],['pricing.view','briefcase','List Harga Trucking','pricing.trucking.index']],
            'KALKULATOR' => [['dashboard.view','calculator','Kalkulator','calculators.index']],
            'OPERASIONAL' => [['jobs.view','check','Booking Confirmation','booking-confirmations.index'],['jobs.view','briefcase','Job Order','jobs.index'],['jobs.view','file','Shipping Instruction','shipping-instructions.index'],['jobs.view','file','Dokumen Job','documents.index'],['jobs.manage','file','Tipe Dokumen','document-types.index'],['tps.manage','briefcase','Master TPS Air & Sea','tps.index']],
            'MASTER DATA' => [['jobs.manage','database','Data Port','ports.index'],['jobs.manage','briefcase','Data Service','service-types.index'],['jobs.manage','wallet','Data Cost','charge-types.index'],['jobs.manage','briefcase','Data Unit','container-units.index']],
            'KEUANGAN' => [['costs.manage','wallet','Biaya Job','costs.overview'],['jobs.close','check','Closing Job','closing.index'],['invoices.manage','file','Invoice','invoices.index'],['payments.manage','wallet','Pembayaran','payments.index'],['reimbursements.manage','wallet','Reimbursement','reimbursements.index']],
            'AKUNTANSI' => [['coa.manage','database','Data COA','accounts.index'],['coa.manage','file','Mapping Akun','accounts.mappings'],['journals.manage','file','Jurnal','journals.index'],['reports.view','chart','Buku Besar','reports.ledger'],['reports.view','chart','Neraca Saldo','reports.trial-balance']],
            'Laporan Keuangan' => [['reports.view','chart','Neraca','reports.balance-sheet'],['reports.view','chart','Laba Rugi','reports.income-statement'],['reports.view','wallet','Arus Kas','reports.cash-flow'],['reports.view','briefcase','Profit per Job','reports.profit-per-job'],['reports.view','calendar','Profit Bulanan','reports.profit-monthly'],['reports.view','wallet','Statement of Account','reports.soa']],
        ];
        $groupIcons = ['SALES & CUSTOMER' => 'users', 'PRICING' => 'chart', 'KALKULATOR' => 'calculator', 'OPERASIONAL' => 'briefcase', 'MASTER DATA' => 'database', 'KEUANGAN' => 'wallet', 'AKUNTANSI' => 'book', 'Laporan Keuangan' => 'chart'];
        @endphp
        @foreach($groups as $heading => $items)
            @if(collect($items)->contains(fn ($item) => auth()->user()->can($item[0])))
            @php $groupOpen = collect($items)->contains(fn ($item) => isset($item[3]) && request()->routeIs($item[3].'*')); @endphp
            <details class="nav-group" data-nav-group {{ $groupOpen ? 'open' : '' }}>
            <summary class="nav-heading"><span class="nav-group-title"><x-icon name="{{ $groupIcons[$heading] ?? 'folder' }}"/> <span>{{ $heading }}</span></span><x-icon name="chevron-down" class="nav-group-chevron"/></summary>
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
                <a class="nav-item {{ $active ? 'active' : '' }}" href="{{ route($destination) }}" title="{{ $label }}"><x-icon :name="$icon"/><span>{{ $label }}</span></a>
                @else
                <span class="nav-item upcoming" aria-disabled="true" title="{{ $label }} (Segera)"><x-icon :name="$icon"/><span>{{ $label }}</span><small>Segera</small></span>
                @endif
                @endcan
            @endforeach
            </details>
            @endif
        @endforeach
        @can('users.view')
        <p class="nav-heading">ADMINISTRASI</p>
        <a class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}" title="Pengguna & Akses"><x-icon name="users"/><span>Pengguna & Akses</span></a>
        @endcan
        @can('activity.view')
        <a class="nav-item {{ request()->routeIs('activity.*') ? 'active' : '' }}" href="{{ route('activity.index') }}" title="Log Aktivitas"><x-icon name="clock"/><span>Log Aktivitas</span></a>
        @endcan
    </nav>
</aside>
<div class="workspace">
    <header class="topbar">
        <div class="topbar-left"><button class="icon-button menu-button" data-menu-toggle aria-label="Buka / Tutup Navigasi" title="Buka / Tutup Navigasi" aria-controls="sidebar" aria-expanded="false"><span class="icon-nav-open" title="Buka Navigasi"><x-icon name="menu-unfold"/></span><span class="icon-nav-close" title="Tutup Navigasi"><x-icon name="menu-fold"/></span></button><span class="breadcrumb">Workspace <span>/</span> <strong>@yield('title', 'Dashboard')</strong></span></div>
        <div class="topbar-right">
            <button type="button" class="theme-toggle" data-theme-toggle aria-label="Ganti tema" title="Ganti tema"><span data-theme-icon>☾</span></button>
            <a class="notification-button" href="{{ route('notifications.index') }}" title="Notifikasi" aria-label="Notifikasi"><x-icon name="bell"/></a>
            <details class="account-menu"><summary class="account"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><span class="account-name">{{ auth()->user()->name }}<small>{{ auth()->user()->role?->label ?? 'Tanpa role' }}</small></span><x-icon name="chevron-down"/></summary><div class="account-dropdown"><div class="account-dropdown-head"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></div><a href="{{ route('profile.show') }}"><x-icon name="users"/>Profil Saya</a><a href="{{ route('profile.show') }}#security"><x-icon name="lock"/>Keamanan & Password</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><x-icon name="logout"/>Keluar</button></form></div></details>
        </div>
    </header>
    <main id="main" tabindex="-1">
        @if(session('success'))<div class="flash-success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="flash-error" role="alert"><strong>Periksa kembali data Anda.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){const source=document.getElementById('port-options');if(!source)return;let options=[];try{options=JSON.parse(source.textContent||'[]');}catch(e){return;}document.querySelectorAll('[data-port-autocomplete]').forEach(input=>{const wrap=input.parentElement;wrap.classList.add('port-autocomplete-wrap');const box=document.createElement('div');box.className='port-suggestions';wrap.appendChild(box);let active=-1;const render=()=>{const term=input.value.trim().toLowerCase();box.innerHTML='';active=-1;if(!term){box.classList.remove('show');return;}const matches=options.filter(o=>(o.value+' '+o.label).toLowerCase().includes(term)).slice(0,12);matches.forEach((o,i)=>{const b=document.createElement('button');b.type='button';b.className='port-suggestion';b.textContent=o.label;b.addEventListener('mousedown',e=>{e.preventDefault();input.value=o.value;box.classList.remove('show');});box.appendChild(b);});box.classList.toggle('show',matches.length>0);};input.addEventListener('input',render);input.addEventListener('keydown',e=>{const items=[...box.querySelectorAll('.port-suggestion')];if(!items.length)return;if(e.key==='ArrowDown'){e.preventDefault();active=Math.min(active+1,items.length-1);}else if(e.key==='ArrowUp'){e.preventDefault();active=Math.max(active-1,0);}else if(e.key==='Enter'&&active>=0){e.preventDefault();items[active].click();}else if(e.key==='Escape'){box.classList.remove('show');}items.forEach((item,i)=>item.classList.toggle('active',i===active));});input.addEventListener('blur',()=>setTimeout(()=>box.classList.remove('show'),150));});});
document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('input[list="port_list"]').forEach(input=>{const list=input.getAttribute('list');input.addEventListener('focus',()=>{if(!input.value.trim())input.removeAttribute('list');});input.addEventListener('input',()=>{if(input.value.trim())input.setAttribute('list',list);else input.removeAttribute('list');});input.addEventListener('blur',()=>{if(!input.value.trim())input.removeAttribute('list');});});});
document.addEventListener('DOMContentLoaded',function(){const root=document.documentElement,button=document.querySelector('[data-theme-toggle]'),icon=document.querySelector('[data-theme-icon]');if(!button)return;const sync=()=>{icon.textContent=root.classList.contains('theme-dark')?'☀':'☾';};button.addEventListener('click',()=>{root.classList.toggle('theme-dark');localStorage.setItem('jobfinance_theme',root.classList.contains('theme-dark')?'dark':'light');sync();});sync();});
</script>
</body>
</html>
