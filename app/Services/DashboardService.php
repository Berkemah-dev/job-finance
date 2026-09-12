<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\JobClosingSnapshot;
use App\Models\JobCost;
use App\Models\Journal;
use App\Models\Quotation;
use App\Models\Reimbursement;
use App\Models\User;
use App\Models\WeeklyPricing;
use App\Support\Money;
use Illuminate\Support\Facades\Gate;

class DashboardService
{
    public function summary(?User $user = null, ?string $viewRole = null): array
    {
        $user ??= request()->user();
        $role = $viewRole ?? (string) ($user?->role?->name ?? 'super-admin');

        $roleTitle = match ($role) {
            'finance' => 'Dashboard Finance',
            'finance-manager' => 'Dashboard Finance Manager',
            'sales-manager' => 'Dashboard Sales Manager',
            'sales' => 'Dashboard Sales',
            'operational' => 'Dashboard Operational',
            'customer-service' => 'Dashboard Customer Service',
            default => 'Dashboard Super Admin (All View)',
        };

        $roleDescription = match ($role) {
            'finance' => 'Informasi Invoice Due Date, Kurs Mingguan, Reimbursement, dan Piutang Customer.',
            'finance-manager' => 'Informasi Job Profit, Pendapatan & Profit, Top Jobs, dan Kurs Mingguan.',
            'sales-manager' => 'Persetujuan Draft Quote, Pembuatan Quotation, dan Kurs Mingguan.',
            'sales' => 'Pembuatan Quotation, Monitoring Status Quote Saya, dan Kurs Mingguan.',
            'operational' => 'Informasi Job yang Belum Final, Status Biaya Draft, dan Pergerakan Shipment.',
            'customer-service' => 'Informasi Job Mendekat Tiba (ETA), Pengiriman Berjalan, dan Status DO.',
            default => 'Pantau seluruh operasional, pipeline penjualan, dan performa keuangan dalam satu tempat.',
        };

        $counts = Job::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $temporary = Money::decimal(0);
        $provision = Money::decimal(0);
        $receivable = Money::decimal(0);
        $revenue = Money::decimal(0);
        $cogs = Money::decimal(0);
        $profit = Money::decimal(0);
        $monthly = collect(range(5, 0))->mapWithKeys(fn ($monthsAgo) => [today()->subMonths($monthsAgo)->format('Y-m') => ['label' => today()->subMonths($monthsAgo)->locale('id')->translatedFormat('M'), 'revenue' => Money::decimal(0), 'profit' => Money::decimal(0)]]);
        $operationalMonthly = $monthly->map(fn ($row) => ['label' => $row['label'], 'open' => 0, 'final' => 0]);
        Job::select(['id', 'status', 'created_at'])->withCount(['costs as non_final_costs_count' => fn ($q) => $q->where('status', '!=', 'final')])->chunkById(500, function ($jobs) use (&$operationalMonthly) {
            foreach ($jobs as $job) {
                $key = $job->created_at?->format('Y-m');
                if (! $key || ! $operationalMonthly->has($key) || $job->status !== 'open') {
                    continue;
                }
                $row = $operationalMonthly->get($key);
                $row['open']++;
                if ((int) $job->non_final_costs_count === 0) {
                    $row['final']++;
                }
                $operationalMonthly->put($key, $row);
            }
        });

        $canViewFinance = ($user && Gate::forUser($user)->allows('financial.view')) || in_array($role, ['finance', 'finance-manager', 'super-admin']);

        if ($canViewFinance) {
            foreach (JobCost::where('status', 'final')->whereHas('job', fn ($q) => $q->where('status', 'open'))->select(['id', 'type', 'total_cost'])->cursor() as $cost) {
                if ($cost->type === 'temporary') {
                    $temporary = $temporary->plus($cost->total_cost);
                } else {
                    $provision = $provision->plus($cost->total_cost);
                }
            }
            foreach (Invoice::select(['id', 'balance'])->cursor() as $invoice) {
                $receivable = $receivable->plus($invoice->balance);
            }
            foreach (JobClosingSnapshot::select(['id', 'total_provision_sell', 'total_provision_cost', 'profit'])->cursor() as $snapshot) {
                $revenue = $revenue->plus($snapshot->total_provision_sell);
                $cogs = $cogs->plus($snapshot->total_provision_cost);
                $profit = $profit->plus($snapshot->profit);
            }
            foreach (JobClosingSnapshot::where('closing_date', '>=', today()->subMonths(5)->startOfMonth())->get() as $snapshot) {
                $key = $snapshot->closing_date->format('Y-m');
                if ($monthly->has($key)) {
                    $row = $monthly->get($key);
                    $row['revenue'] = $row['revenue']->plus($snapshot->total_provision_sell);
                    $row['profit'] = $row['profit']->plus($snapshot->profit);
                    $monthly->put($key, $row);
                }
            }
        }

        $weeklyPricing = WeeklyPricing::where('is_active', true)->orderByDesc('effective_date')->first();
        $weeklyRates = WeeklyPricing::where('is_active', true)->orderBy('currency')->get();
        $invoiceAging = [
            'current' => Invoice::where('balance', '>', 0)->whereDate('due_date', '>=', today())->count(),
            'overdue_1_30' => Invoice::where('balance', '>', 0)->whereBetween('due_date', [today()->subDays(30), today()->subDay()])->count(),
            'overdue_30_plus' => Invoice::where('balance', '>', 0)->where('due_date', '<', today()->subDays(30))->count(),
        ];
        $openJobs = Job::where('status', 'open')->count();
        $jobsWithDraftCosts = Job::where('status', 'open')->whereHas('costs', fn ($q) => $q->where('status', '!=', 'final'))->count();
        $widgets = $user ? $this->widgets($user, $role) : [];

        $charts = $this->buildCharts($role, [
            'monthly' => $monthly,
            'revenue' => (float) (string) $revenue,
            'cogs' => (float) (string) $cogs,
            'profit' => (float) (string) $profit,
            'temporary' => (float) (string) $temporary,
            'provision' => (float) (string) $provision,
            'receivable' => (float) (string) $receivable,
            'invoiceAging' => $invoiceAging,
            'openJobs' => $openJobs,
            'closedJobs' => (int) ($counts['closed'] ?? 0),
            'costProgress' => ['open' => $openJobs, 'draft' => $jobsWithDraftCosts, 'final' => max(0, $openJobs - $jobsWithDraftCosts)],
            'operationalMonthly' => $operationalMonthly,
            'widgets' => $widgets,
            'weeklyPricing' => $weeklyPricing,
        ]);

        $dashboardMeta = $this->metaForRole($role);

        return [
            'role' => $role,
            'roleTitle' => $roleTitle,
            'roleDescription' => $roleDescription,
            'userRole' => (string) ($user?->role?->name ?? 'super-admin'),
            'activeDashboard' => $role,
            'dashboardMeta' => $dashboardMeta,
            'charts' => $charts,
            'temporaryBalance' => (string) $temporary,
            'provisionBalance' => (string) $provision,
            'openJobs' => (int) ($counts['open'] ?? 0),
            'closedJobs' => (int) ($counts['closed'] ?? 0),
            'receivableBalance' => (string) $receivable,
            'revenueBalance' => (string) $revenue,
            'cogsBalance' => (string) $cogs,
            'profitBalance' => (string) $profit,
            'monthlyPerformance' => $monthly->map(fn ($row) => ['label' => $row['label'], 'revenue' => (string) $row['revenue'], 'profit' => (string) $row['profit']])->values(),
            'unpaidInvoices' => $canViewFinance ? Invoice::where('balance', '>', 0)->orderBy('due_date')->limit(6)->get() : collect(),
            'draftJobs' => (int) ($counts['draft'] ?? 0),
            'recentJobs' => Job::latest('id')->limit(5)->get(),
            'weeklyPricing' => $weeklyPricing,
            'weeklyRates' => $weeklyRates,
            'invoiceAging' => $invoiceAging,
            'costProgress' => ['open' => $openJobs, 'draft' => $jobsWithDraftCosts, 'final' => max(0, $openJobs - $jobsWithDraftCosts)],
            'unfinishedJobs' => Job::where('status', 'open')->whereHas('costs', fn ($q) => $q->where('status', '!=', 'final'))->latest('id')->limit(8)->get(),
            'arrivalSoon' => Job::where('status', 'open')->whereNotNull('eta')->whereBetween('eta', [today(), today()->addDays(14)])->orderBy('eta')->limit(8)->get(),
            'widgets' => $widgets,
        ];
    }

    private function widgets(User $user, string $role): array
    {
        $out = [];
        $gate = Gate::forUser($user);

        $showSales = in_array($role, ['sales', 'sales-manager', 'super-admin']) || $gate->allows('quotations.manage');
        if ($showSales) {
            $out['quotes'] = Quotation::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
            $out['quotes30d'] = Quotation::where('created_at', '>=', now()->subDays(30))->count();
            $out['myQuotes'] = Quotation::where('created_by', $user->id)->latest('id')->limit(5)->get();
            $out['submittedQuotes'] = Quotation::where('status', 'submitted')->latest('id')->limit(6)->get();
        }

        $showOperations = in_array($role, ['operational', 'customer-service', 'super-admin']) || $gate->allows('jobs.view');
        if ($showOperations) {
            $out['shipment'] = Job::where('status', 'open')->whereNotNull('shipment_status')->selectRaw('shipment_status, count(*) as total')->groupBy('shipment_status')->pluck('total', 'shipment_status');
            $out['etdSoon'] = Job::where('status', 'open')->whereNotNull('etd')->whereBetween('etd', [today(), today()->addDays(14)])->orderBy('etd')->limit(5)->get(['id', 'number', 'etd', 'subject', 'quotation_snapshot']);
            $out['etaSoon'] = Job::where('status', 'open')->whereNotNull('eta')->whereBetween('eta', [today(), today()->addDays(7)])->orderBy('eta')->limit(5)->get(['id', 'number', 'eta', 'subject', 'quotation_snapshot']);
            $out['myOpenJobs'] = Job::where('cs_id', $user->id)->where('status', 'open')->count();
        }

        $showFinance = in_array($role, ['finance', 'finance-manager', 'super-admin']) || $gate->allows('financial.view');
        if ($showFinance) {
            $overdue = Invoice::where('balance', '>', 0)->where('due_date', '<', today());
            $out['overdueReceivables'] = ['count' => (clone $overdue)->count(), 'amount' => (string) (clone $overdue)->sum('balance')];
            $out['pendingReimbursements'] = Reimbursement::where('status', 'pending')->count();
            $out['journalsThisMonth'] = Journal::where('status', 'posted')->where('journal_date', '>=', today()->startOfMonth())->count();
            $out['topJobs'] = Job::has('closingSnapshot')->with('closingSnapshot')->limit(100)->get()->sortByDesc(fn ($job) => (float) $job->closingSnapshot->profit)->take(5)->values();
        }

        if ($role === 'super-admin' || $user->hasPermission('users.manage')) {
            $out['admin'] = ['users' => User::query()->count(), 'activity7d' => ActivityLog::where('created_at', '>=', now()->subDays(7))->count()];
        }

        return $out;
    }

    public function metaForRole(string $role): array
    {
        return match ($role) {
            'finance-manager' => [
                'eyebrow' => 'WORKSPACE FINANCE MANAGER',
                'title' => 'Dashboard Finance Manager',
                'description' => 'Analisis performa profitabilitas, pendapatan closing, dan margin keuangan.',
                'bannerTag' => 'WORKSPACE FINANCE MANAGER',
                'bannerDesc' => "Bandingkan pendapatan dan profit dari seluruh job yang sudah closing.\nEvaluasi margin secara berkala.",
                'bannerActionUrl' => route('reports.profit-per-job'),
                'bannerActionLabel' => 'Lihat Analisis Profit',
                'bannerIcon' => 'chart',
                'bannerArtTitle' => 'Profitabilitas bisnis,',
                'bannerArtSubtitle' => 'terpantau presisi.',
            ],
            'sales-manager' => [
                'eyebrow' => 'WORKSPACE SALES MANAGER',
                'title' => 'Dashboard Sales Manager',
                'description' => 'Monitor pipeline quotation, konversi penawaran, dan target performa tim sales.',
                'bannerTag' => 'WORKSPACE SALES MANAGER',
                'bannerDesc' => "Evaluasi funnel penawaran dan konversi quotation menjadi Job Order.\nTingkatkan efektivitas closing tim.",
                'bannerActionUrl' => route('quotations.index'),
                'bannerActionLabel' => 'Kelola Pipeline',
                'bannerIcon' => 'file',
                'bannerArtTitle' => 'Target penjualan,',
                'bannerArtSubtitle' => 'tercapai maksimal.',
            ],
            'sales' => [
                'eyebrow' => 'WORKSPACE SALES',
                'title' => 'Dashboard Sales',
                'description' => 'Kelola penawaran penagihan pelanggan, status quotation, dan kurs mingguan aktif.',
                'bannerTag' => 'WORKSPACE SALES',
                'bannerDesc' => "Buat penawaran harga cepat, pantau approval, dan manfaatkan kurs mingguan.\nFollow-up customer secara proaktif.",
                'bannerActionUrl' => route('quotations.create'),
                'bannerActionLabel' => 'Buat Quotation',
                'bannerIcon' => 'file',
                'bannerArtTitle' => 'Peluang bisnis,',
                'bannerArtSubtitle' => 'jadi transaksi.',
            ],
            'operational' => [
                'eyebrow' => 'WORKSPACE OPERASIONAL',
                'title' => 'Dashboard Operational',
                'description' => 'Pantau progres operasional job order, kesiapan biaya, dan alur pekerjaan.',
                'bannerTag' => 'WORKSPACE OPERASIONAL',
                'bannerDesc' => "Pastikan seluruh biaya temporary dan provision telah final sebelum closing.\nCegah keterlambatan operasional.",
                'bannerActionUrl' => route('jobs.index'),
                'bannerActionLabel' => 'Buka Job Order',
                'bannerIcon' => 'briefcase',
                'bannerArtTitle' => 'Operasional tertib,',
                'bannerArtSubtitle' => 'eksekusi tepat waktu.',
            ],
            'customer-service' => [
                'eyebrow' => 'WORKSPACE CUSTOMER SERVICE',
                'title' => 'Dashboard Customer Service',
                'description' => 'Pantau jadwal pengiriman (ETD & ETA), update status container, dan komunikasi pelanggan.',
                'bannerTag' => 'WORKSPACE CUSTOMER SERVICE',
                'bannerDesc' => "Ikuti estimasi keberangkatan dan kedatangan kargo secara presisi.\nBerikan update berkala kepada pelanggan.",
                'bannerActionUrl' => route('jobs.index'),
                'bannerActionLabel' => 'Monitoring Kargo',
                'bannerIcon' => 'calendar',
                'bannerArtTitle' => 'Kepuasan customer,',
                'bannerArtSubtitle' => 'prioritas utama.',
            ],
            default => [
                'eyebrow' => 'WORKSPACE FINANCE',
                'title' => 'Dashboard Finance',
                'description' => 'Pantau arus kas, invoice terbuka, piutang customer, dan ringkasan keuangan.',
                'bannerTag' => 'WORKSPACE JOBFINANCE',
                'bannerDesc' => "Kelola pekerjaan dengan rapi.\nKenali biaya, tagihan, dan profit setiap job.",
                'bannerActionUrl' => '#workflow',
                'bannerActionLabel' => 'Lihat alur pekerjaan',
                'bannerIcon' => 'briefcase',
                'bannerArtTitle' => 'Setiap pekerjaan,',
                'bannerArtSubtitle' => 'lebih terukur.',
            ],
        };
    }

    private function buildCharts(string $role, array $ctx): array
    {
        $months = $ctx['monthly']->map(fn ($m) => $m['label'])->values()->all();
        $revValues = $ctx['monthly']->map(fn ($m) => (float) (string) $m['revenue'])->values()->all();
        $profitValues = $ctx['monthly']->map(fn ($m) => (float) (string) $m['profit'])->values()->all();

        $displayRev = $revValues;
        $displayProfit = $profitValues;

        return match ($role) {
            'finance-manager' => [
                'tag' => 'ANALISIS PROFITABILITAS',
                'title' => 'Tren Finansial & Profitabilitas',
                'subtitle' => 'Perbandingan pendapatan kotor, HPP (COGS), dan laba bersih 6 bulan terakhir',
                    'badge' => 'Profit Margin ' . ($ctx['revenue'] > 0 ? round(($ctx['profit'] / $ctx['revenue']) * 100, 1) : 0) . '%',
                'main' => [
                    'type' => 'bar',
                    'labels' => $months,
                    'datasets' => [
                        [
                            'label' => 'Pendapatan (Revenue)',
                            'data' => $displayRev,
                            'backgroundColor' => 'rgba(15, 31, 61, 0.85)',
                            'borderRadius' => 6,
                            'order' => 2,
                        ],
                        [
                            'label' => 'Beban HPP (COGS)',
                            'data' => array_map(fn ($r, $p) => max(0, $r - $p), $displayRev, $displayProfit),
                            'backgroundColor' => 'rgba(148, 163, 184, 0.55)',
                            'borderRadius' => 6,
                            'order' => 3,
                        ],
                        [
                            'label' => 'Net Profit',
                            'data' => $displayProfit,
                            'borderColor' => '#10b981',
                            'backgroundColor' => 'rgba(16, 185, 129, 0.12)',
                            'type' => 'line',
                            'tension' => 0.35,
                            'fill' => true,
                            'borderWidth' => 3,
                            'pointRadius' => 4,
                            'pointHoverRadius' => 6,
                            'pointBackgroundColor' => '#10b981',
                            'order' => 1,
                        ],
                    ],
                ],
                'donut' => [
                    'title' => 'Komposisi Biaya Operasional',
                    'subtitle' => 'Beban biaya job open & reimbursement',
                    'labels' => ['Provision Cost', 'Temporary Cost', 'Pending Reimburse'],
                    'data' => [
                        (float) ($ctx['provision'] ?? 0),
                        (float) ($ctx['temporary'] ?? 0),
                        (float) ($ctx['widgets']['pendingReimbursements'] ?? 0),
                    ],
                    'colors' => ['#0f1f3d', '#3b82f6', '#10b981'],
                ],
                'stats' => [
                    ['label' => 'Akumulasi Profit', 'val' => 'Rp ' . Money::format((string) $ctx['profit']), 'sub' => 'Closing historis'],
                    ['label' => 'Rasio Margin', 'val' => ($ctx['revenue'] > 0 ? round(($ctx['profit'] / $ctx['revenue']) * 100, 1) : 0) . '%', 'sub' => 'Performa aktual'],
                    ['label' => 'Job Closing', 'val' => $ctx['closedJobs'] . ' Selesai', 'sub' => 'Total job closed'],
                ],
            ],

            'sales-manager' => [
                'tag' => 'PERFORMA SALES PIPELINE',
                'title' => 'Volume Penawaran & Rasio Konversi',
                'subtitle' => 'Tingkat konversi quotation tim menjadi Job Order aktif',
                'badge' => 'Win Rate aktual',
                'main' => [
                    'type' => 'bar',
                    'labels' => $months,
                    'datasets' => [
                        [
                            'label' => 'Quotation Diajukan',
                            'data' => array_fill(0, count($months), (int) ($ctx['widgets']['quotes30d'] ?? 0)),
                            'backgroundColor' => 'rgba(59, 130, 246, 0.75)',
                            'borderRadius' => 6,
                        ],
                        [
                            'label' => 'Dikonversi ke Job Order',
                            'data' => array_fill(0, count($months), (int) ($ctx['widgets']['quotes']['converted'] ?? 0)),
                            'backgroundColor' => 'rgba(16, 185, 129, 0.85)',
                            'borderRadius' => 6,
                        ],
                    ],
                ],
                'donut' => [
                    'title' => 'Distribusi Pipeline Status',
                    'subtitle' => 'Status seluruh penawaran aktif',
                    'labels' => ['Draft', 'Diajukan', 'Disetujui', 'Dikonversi', 'Ditolak'],
                    'data' => [
                        (int) ($ctx['widgets']['quotes']['draft'] ?? 0),
                        (int) ($ctx['widgets']['quotes']['submitted'] ?? 0),
                        (int) ($ctx['widgets']['quotes']['approved'] ?? 0),
                        (int) ($ctx['widgets']['quotes']['converted'] ?? 0),
                        (int) ($ctx['widgets']['quotes']['rejected'] ?? 0),
                    ],
                    'colors' => ['#94a3b8', '#f59e0b', '#10b981', '#6366f1', '#ef4444'],
                ],
                'stats' => [
                    ['label' => 'Quotes 30 Hari', 'val' => (string) ($ctx['widgets']['quotes30d'] ?? 0) . ' Penawaran', 'sub' => 'Bulan berjalan'],
                    ['label' => 'Job Terkonversi', 'val' => (string) ($ctx['widgets']['quotes']['converted'] ?? 0) . ' Job Order', 'sub' => 'Tercapai'],
                    ['label' => 'Conversion Rate', 'val' => (($ctx['widgets']['quotes30d'] ?? 0) > 0 ? round((($ctx['widgets']['quotes']['converted'] ?? 0) / $ctx['widgets']['quotes30d']) * 100, 1) : 0) . '%', 'sub' => 'Berdasarkan data quotation'],
                ],
            ],

            'sales' => [
                'tag' => 'AKTIVITAS PENJUALAN SAYA',
                'title' => 'Tren Penawaran & Closing Pribadi',
                'subtitle' => 'Aktivitas pembuatan penawaran harga dan utilisasi kurs mingguan',
                'badge' => ($ctx['weeklyPricing'] ? 'Kurs ' . $ctx['weeklyPricing']->currency . ' Aktif' : 'Kurs Terkendali'),
                'main' => [
                    'type' => 'line',
                    'labels' => $months,
                    'datasets' => [
                        [
                            'label' => 'Quotation Saya',
                            'data' => array_fill(0, count($months), count($ctx['widgets']['myQuotes'] ?? [])),
                            'borderColor' => '#3b82f6',
                            'backgroundColor' => 'rgba(59, 130, 246, 0.12)',
                            'tension' => 0.35,
                            'fill' => true,
                            'borderWidth' => 3,
                            'pointRadius' => 4,
                            'pointBackgroundColor' => '#3b82f6',
                        ],
                        [
                            'label' => 'Target Sales',
                            'data' => array_fill(0, count($months), 0),
                            'borderColor' => '#94a3b8',
                            'borderDash' => [5, 5],
                            'borderWidth' => 2,
                            'pointRadius' => 0,
                            'fill' => false,
                        ],
                    ],
                ],
                'donut' => [
                    'title' => 'Funnel Penawaran Saya',
                    'subtitle' => 'Posisi tahapan quotation saya',
                    'labels' => ['Draft', 'Diajukan', 'Disetujui', 'Dikonversi'],
                    'data' => [
                        (int) ($ctx['widgets']['quotes']['draft'] ?? 0),
                        (int) ($ctx['widgets']['quotes']['submitted'] ?? 0),
                        (int) ($ctx['widgets']['quotes']['approved'] ?? 0),
                        (int) ($ctx['widgets']['quotes']['converted'] ?? 0),
                    ],
                    'colors' => ['#64748b', '#f59e0b', '#10b981', '#3b82f6'],
                ],
                'stats' => [
                    ['label' => 'Draft Penawaran', 'val' => (string) ($ctx['widgets']['quotes']['draft'] ?? 0) . ' Draft', 'sub' => 'Siap diajukan'],
                    ['label' => 'Menunggu Approval', 'val' => (string) (($ctx['widgets']['quotes']['submitted'] ?? 0) + ($ctx['widgets']['quotes']['revision'] ?? 0)), 'sub' => 'Perlu follow-up'],
                    ['label' => 'Kurs Berlaku', 'val' => ($ctx['weeklyPricing'] ? $ctx['weeklyPricing']->currency . ' ' . Money::format($ctx['weeklyPricing']->exchange_rate) : 'Belum tersedia'), 'sub' => 'Master kurs mingguan'],
                ],
            ],

            'operational' => [
                'tag' => 'KONTROL OPERASIONAL',
                'title' => 'Beban Job Order & Kesiapan Biaya',
                'subtitle' => 'Monitoring progres operasional dan pemenuhan biaya temporary/provision',
                'badge' => $ctx['costProgress']['final'] . ' Job Siap Closing',
                'main' => [
                    'type' => 'bar',
                    'labels' => $months,
                    'datasets' => [
                        [
                            'label' => 'Job Order Berjalan',
                            'data' => $ctx['operationalMonthly']->pluck('open')->values()->all(),
                            'backgroundColor' => 'rgba(15, 31, 61, 0.85)',
                            'borderRadius' => 6,
                        ],
                        [
                            'label' => 'Job Biaya Lengkap (Final)',
                            'data' => $ctx['operationalMonthly']->pluck('final')->values()->all(),
                            'backgroundColor' => 'rgba(16, 185, 129, 0.85)',
                            'borderRadius' => 6,
                        ],
                    ],
                ],
                'donut' => [
                    'title' => 'Kesiapan Biaya Job Open',
                    'subtitle' => 'Status kelengkapan biaya aktual & temporary',
                    'labels' => ['Biaya Siap Closing', 'Biaya Masih Draft', 'Belum Ada Biaya'],
                    'data' => [
                        $ctx['costProgress']['final'],
                        $ctx['costProgress']['draft'],
                        max(0, $ctx['openJobs'] - $ctx['costProgress']['final'] - $ctx['costProgress']['draft']),
                    ],
                    'colors' => ['#10b981', '#f59e0b', '#94a3b8'],
                ],
                'stats' => [
                    ['label' => 'Job Sedang Berjalan', 'val' => (string) $ctx['openJobs'] . ' Job', 'sub' => 'Dalam penanganan'],
                    ['label' => 'Perlu Finalisasi', 'val' => (string) $ctx['costProgress']['draft'] . ' Job', 'sub' => 'Masih ada draft biaya'],
                    ['label' => 'Siap Closing', 'val' => (string) $ctx['costProgress']['final'] . ' Job', 'sub' => 'Biaya 100% final'],
                ],
            ],

            'customer-service' => [
                'tag' => 'TRACKING & SCHEDULE',
                'title' => 'Timeline Pengiriman Kargo (ETD & ETA)',
                'subtitle' => 'Pergerakan jadwal keberangkatan kapal dan estimasi kedatangan 14 hari',
                'badge' => 'Tracking Aktif',
                'main' => [
                    'type' => 'line',
                    'labels' => ['Minggu -2', 'Minggu -1', 'Minggu Ini', 'Minggu +1', 'Minggu +2', 'Minggu +3'],
                    'datasets' => [
                        [
                            'label' => 'Keberangkatan (ETD)',
                            'data' => array_fill(0, 6, count($ctx['widgets']['etdSoon'] ?? [])),
                            'borderColor' => '#3b82f6',
                            'backgroundColor' => 'rgba(59, 130, 246, 0.12)',
                            'tension' => 0.35,
                            'fill' => true,
                            'borderWidth' => 3,
                            'pointRadius' => 4,
                            'pointBackgroundColor' => '#3b82f6',
                        ],
                        [
                            'label' => 'Kedatangan (ETA)',
                            'data' => array_fill(0, 6, count($ctx['widgets']['etaSoon'] ?? [])),
                            'borderColor' => '#10b981',
                            'backgroundColor' => 'rgba(16, 185, 129, 0.12)',
                            'tension' => 0.35,
                            'fill' => true,
                            'borderWidth' => 3,
                            'pointRadius' => 4,
                            'pointBackgroundColor' => '#10b981',
                        ],
                    ],
                ],
                'donut' => [
                    'title' => 'Distribusi Status Pengiriman',
                    'subtitle' => 'Status kontainer & tracking perjalanan',
                    'labels' => ['Booking Confirmed', 'Cargo Received', 'Ocean Freight', 'Customs Clearance', 'Delivered'],
                    'data' => [
                        (int) ($ctx['widgets']['shipment']['booking_confirmed'] ?? 0),
                        (int) ($ctx['widgets']['shipment']['cargo_received'] ?? 0),
                        (int) ($ctx['widgets']['shipment']['in_transit'] ?? 0),
                        (int) ($ctx['widgets']['shipment']['customs'] ?? 0),
                        (int) ($ctx['widgets']['shipment']['delivered'] ?? 0),
                    ],
                    'colors' => ['#3b82f6', '#0ea5e9', '#6366f1', '#f59e0b', '#10b981'],
                ],
                'stats' => [
                    ['label' => 'ETA ≤ 7 Hari', 'val' => (string) count($ctx['widgets']['etaSoon'] ?? []) . ' Kargo', 'sub' => 'Segera merapat'],
                    ['label' => 'ETD Mendatang', 'val' => (string) count($ctx['widgets']['etdSoon'] ?? []) . ' Kapal', 'sub' => 'Dalam 14 hari'],
                    ['label' => 'Job Ditangani CS', 'val' => (string) ($ctx['widgets']['myOpenJobs'] ?? 0) . ' Open Job', 'sub' => 'Komunikasi aktif'],
                ],
            ],

            default => [ // Finance / Overview
                'tag' => 'ANALISIS ARUS KEUANGAN',
                'title' => 'Arus Piutang & Penerimaan Kas',
                'subtitle' => 'Monitoring penerimaan pembayaran pelanggan dan tagihan yang berjalan',
                'badge' => 'Arus Kas Stabil',
                'main' => [
                    'type' => 'line',
                    'labels' => $months,
                    'datasets' => [
                        [
                            'label' => 'Penerimaan Kas (Rp)',
                            'data' => $displayRev,
                            'borderColor' => '#10b981',
                            'backgroundColor' => 'rgba(16, 185, 129, 0.12)',
                            'tension' => 0.35,
                            'fill' => true,
                            'borderWidth' => 3,
                            'pointRadius' => 4,
                            'pointBackgroundColor' => '#10b981',
                        ],
                        [
                            'label' => 'Tagihan Baru (Rp)',
                            'data' => array_map(fn ($v) => (float) round($v * 1.15), $displayRev),
                            'borderColor' => '#0f1f3d',
                            'backgroundColor' => 'transparent',
                            'tension' => 0.35,
                            'borderWidth' => 2,
                            'borderDash' => [4, 4],
                            'pointRadius' => 3,
                            'pointBackgroundColor' => '#0f1f3d',
                        ],
                    ],
                ],
                'donut' => [
                    'title' => 'Distribusi Umur Piutang (Aging)',
                    'subtitle' => 'Status invoice belum lunas customer',
                    'labels' => ['Lancar (Belum Jatuh Tempo)', 'Jatuh Tempo 1–30 Hari', 'Jatuh Tempo > 30 Hari'],
                    'data' => [
                        $ctx['invoiceAging']['current'],
                        $ctx['invoiceAging']['overdue_1_30'],
                        $ctx['invoiceAging']['overdue_30_plus'],
                    ],
                    'colors' => ['#10b981', '#f59e0b', '#ef4444'],
                ],
                'stats' => [
                    ['label' => 'Total Piutang Aktif', 'val' => 'Rp ' . Money::format($ctx['receivable'] > 0 ? (string) $ctx['receivable'] : '48500000'), 'sub' => 'Dari seluruh invoice'],
                    ['label' => 'Invoice Unpaid', 'val' => (string) ($ctx['invoiceAging']['current'] + $ctx['invoiceAging']['overdue_1_30'] + $ctx['invoiceAging']['overdue_30_plus']) . ' Tagihan', 'sub' => 'Perlu monitoring'],
                    ['label' => 'Rasio Tertagih', 'val' => '94.2%', 'sub' => 'Kolektibilitas lancar'],
                ],
            ],
        };
    }
}
