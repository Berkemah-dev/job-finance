<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Job;
use App\Models\JobClosingSnapshot;
use App\Models\JobCost;
use App\Support\Money;
use Illuminate\Support\Facades\Gate;

class DashboardService
{
    public function summary(): array
    {
        $counts = Job::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $temporary = Money::decimal(0);
        $provision = Money::decimal(0);
        $receivable = Money::decimal(0);
        $revenue = Money::decimal(0);
        $cogs = Money::decimal(0);
        $profit = Money::decimal(0);
        if (Gate::allows('reports.view')) {
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
        }

        return ['temporaryBalance' => (string) $temporary, 'provisionBalance' => (string) $provision, 'openJobs' => (int) ($counts['open'] ?? 0), 'closedJobs' => (int) ($counts['closed'] ?? 0),
            'receivableBalance' => (string) $receivable, 'revenueBalance' => (string) $revenue, 'cogsBalance' => (string) $cogs, 'profitBalance' => (string) $profit,
            'unpaidInvoices' => Gate::allows('reports.view') ? Invoice::where('balance', '>', 0)->orderBy('due_date')->limit(5)->get() : collect(),
            'draftJobs' => (int) ($counts['draft'] ?? 0), 'recentJobs' => Job::latest('id')->limit(5)->get()];
    }
}
