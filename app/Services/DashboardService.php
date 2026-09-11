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
    public function summary(?User $user = null): array
    {
        $user ??= request()->user();
        $counts = Job::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $temporary = Money::decimal(0);
        $provision = Money::decimal(0);
        $receivable = Money::decimal(0);
        $revenue = Money::decimal(0);
        $cogs = Money::decimal(0);
        $profit = Money::decimal(0);
        $monthly = collect(range(5, 0))->mapWithKeys(fn ($monthsAgo) => [today()->subMonths($monthsAgo)->format('Y-m') => ['label' => today()->subMonths($monthsAgo)->locale('id')->translatedFormat('M'), 'revenue' => Money::decimal(0), 'profit' => Money::decimal(0)]]);
        if (Gate::forUser($user)->allows('financial.view')) {
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

        $role = (string) ($user->role?->name ?? '');
        $weeklyPricing = WeeklyPricing::where('is_active', true)->orderByDesc('effective_date')->first();
        $invoiceAging = [
            'current' => Invoice::where('balance', '>', 0)->whereDate('due_date', '>=', today())->count(),
            'overdue_1_30' => Invoice::where('balance', '>', 0)->whereBetween('due_date', [today()->subDays(30), today()->subDay()])->count(),
            'overdue_30_plus' => Invoice::where('balance', '>', 0)->where('due_date', '<', today()->subDays(30))->count(),
        ];
        $openJobs = Job::where('status', 'open')->count();
        $jobsWithDraftCosts = Job::where('status', 'open')->whereHas('costs', fn ($q) => $q->where('status', '!=', 'final'))->count();
        return ['role' => $role, 'temporaryBalance' => (string) $temporary, 'provisionBalance' => (string) $provision, 'openJobs' => (int) ($counts['open'] ?? 0), 'closedJobs' => (int) ($counts['closed'] ?? 0),
            'receivableBalance' => (string) $receivable, 'revenueBalance' => (string) $revenue, 'cogsBalance' => (string) $cogs, 'profitBalance' => (string) $profit,
            'monthlyPerformance' => $monthly->map(fn ($row) => ['label' => $row['label'], 'revenue' => (string) $row['revenue'], 'profit' => (string) $row['profit']])->values(),
            'unpaidInvoices' => Gate::forUser($user)->allows('financial.view') ? Invoice::where('balance', '>', 0)->orderBy('due_date')->limit(5)->get() : collect(),
            'draftJobs' => (int) ($counts['draft'] ?? 0), 'recentJobs' => Job::latest('id')->limit(5)->get(),
            'weeklyPricing' => $weeklyPricing, 'invoiceAging' => $invoiceAging, 'costProgress' => ['open' => $openJobs, 'draft' => $jobsWithDraftCosts, 'final' => max(0, $openJobs - $jobsWithDraftCosts)], 'unfinishedJobs' => Job::where('status', 'open')->whereHas('costs', fn ($q) => $q->where('status', '!=', 'final'))->latest('id')->limit(8)->get(),
            'arrivalSoon' => Job::where('status', 'open')->whereNotNull('eta')->whereBetween('eta', [today(), today()->addDays(14)])->orderBy('eta')->limit(8)->get(), 'widgets' => $this->widgets($user)];
    }

    private function widgets(User $user): array
    {
        $out = [];
        $gate = Gate::forUser($user);
        if ($gate->allows('quotations.manage')) {
            $out['quotes'] = Quotation::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
            $out['quotes30d'] = Quotation::where('created_at', '>=', now()->subDays(30))->count();
            $out['myQuotes'] = Quotation::where('created_by', $user->id)->latest('id')->limit(5)->get();
        }
        if ($gate->allows('jobs.view')) {
            $out['shipment'] = Job::where('status', 'open')->whereNotNull('shipment_status')->selectRaw('shipment_status, count(*) as total')->groupBy('shipment_status')->pluck('total', 'shipment_status');
            $out['etdSoon'] = Job::where('status', 'open')->whereNotNull('etd')->whereBetween('etd', [today(), today()->addDays(14)])->orderBy('etd')->limit(5)->get(['id', 'number', 'etd', 'subject', 'quotation_snapshot']);
            $out['etaSoon'] = Job::where('status', 'open')->whereNotNull('eta')->whereBetween('eta', [today(), today()->addDays(7)])->orderBy('eta')->limit(5)->get(['id', 'number', 'eta', 'subject', 'quotation_snapshot']);
            $out['myOpenJobs'] = Job::where('cs_id', $user->id)->where('status', 'open')->count();
        }
        if ($gate->allows('financial.view')) {
            $overdue = Invoice::where('balance', '>', 0)->where('due_date', '<', today());
            $out['overdueReceivables'] = ['count' => (clone $overdue)->count(), 'amount' => (string) (clone $overdue)->sum('balance')];
            $out['pendingReimbursements'] = Reimbursement::where('status', 'pending')->count();
            $out['journalsThisMonth'] = Journal::where('status', 'posted')->where('journal_date', '>=', today()->startOfMonth())->count();
            $out['topJobs'] = Job::has('closingSnapshot')->with('closingSnapshot')->limit(100)->get()->sortByDesc(fn ($job) => (float) $job->closingSnapshot->profit)->take(5)->values();
        }
        if ($user->hasPermission('users.manage')) {
            $out['admin'] = ['users' => User::query()->count(), 'activity7d' => ActivityLog::where('created_at', '>=', now()->subDays(7))->count()];
        }

        return $out;
    }
}
