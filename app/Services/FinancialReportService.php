<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Job;
use App\Models\JobClosingSnapshot;
use App\Models\JobCost;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Support\Money;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class FinancialReportService
{
    public function trialBalance(string $to): array
    {
        $all = $this->accountBalances($to)->map(function ($account) {
            $normalDebit = in_array($account->type, ['asset', 'cogs', 'expense'], true);
            $net = $normalDebit ? Money::decimal($account->debit)->minus($account->credit) : Money::decimal($account->credit)->minus($account->debit);
            $account->closing_debit = $normalDebit ? ($net->isNegative() ? '0.00' : (string) $net) : ($net->isNegative() ? (string) $net->abs() : '0.00');
            $account->closing_credit = $normalDebit ? ($net->isNegative() ? (string) $net->abs() : '0.00') : ($net->isNegative() ? '0.00' : (string) $net);

            return $account;
        });

        $totalDebit = $all->sum(fn ($r) => (float) $r->closing_debit);
        $totalCredit = $all->sum(fn ($r) => (float) $r->closing_credit);

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = min(100, max(5, (int) request('per_page', 10)));
        $offset = ($page - 1) * $perPage;
        $rows = new LengthAwarePaginator(
            $all->slice($offset, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        return compact('rows', 'totalDebit', 'totalCredit');
    }

    public function ledger(int $accountId, string $from, string $to): array
    {
        $account = ChartOfAccount::withTrashed()->findOrFail($accountId);
        $openingRow = JournalEntry::where('chart_of_account_id', $accountId)
            ->whereHas('journal', fn (Builder $q) => $q->where('journal_date', '<', $from))
            ->selectRaw('COALESCE(SUM(debit), 0) as debit, COALESCE(SUM(credit), 0) as credit')
            ->first();
        $opening = $this->net($account->type, $openingRow->debit ?? 0, $openingRow->credit ?? 0);
        $balance = Money::decimal($opening);
        $allEntries = JournalEntry::with('journal')
            ->where('chart_of_account_id', $accountId)
            ->whereHas('journal', fn (Builder $q) => $q->whereDate('journal_date', '>=', $from)->whereDate('journal_date', '<=', $to))
            ->orderBy(
                \App\Models\Journal::select('journal_date')
                    ->whereColumn('journals.id', 'journal_entries.journal_id')
                    ->limit(1)
            )
            ->orderBy('id')
            ->get();
        foreach ($allEntries as $entry) {
            $movement = $this->net($account->type, $entry->debit, $entry->credit);
            $balance = $balance->plus($movement);
            $entry->running_balance = (string) $balance;
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = min(100, max(5, (int) request('per_page', 10)));
        $offset = ($page - 1) * $perPage;
        $entries = new LengthAwarePaginator(
            $allEntries->slice($offset, $perPage)->values(),
            $allEntries->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        return compact('account', 'entries', 'opening') + ['closing' => (string) $balance];
    }

    public function ledgerExport(int $accountId, string $from, string $to): array
    {
        $account = ChartOfAccount::withTrashed()->findOrFail($accountId);
        $openingRow = JournalEntry::where('chart_of_account_id', $accountId)
            ->whereHas('journal', fn (Builder $q) => $q->where('journal_date', '<', $from))
            ->selectRaw('COALESCE(SUM(debit), 0) as debit, COALESCE(SUM(credit), 0) as credit')
            ->first();
        $opening = $this->net($account->type, $openingRow->debit ?? 0, $openingRow->credit ?? 0);
        $balance = Money::decimal($opening);
        $entries = JournalEntry::with('journal')
            ->where('chart_of_account_id', $accountId)
            ->whereHas('journal', fn (Builder $q) => $q->whereDate('journal_date', '>=', $from)->whereDate('journal_date', '<=', $to))
            ->orderBy(\App\Models\Journal::select('journal_date')->whereColumn('journals.id', 'journal_entries.journal_id')->limit(1))
            ->orderBy('id')
            ->get();
        foreach ($entries as $entry) {
            $balance = $balance->plus($this->net($account->type, $entry->debit, $entry->credit));
            $entry->running_balance = (string) $balance;
        }

        return compact('account', 'entries', 'opening') + ['closing' => (string) $balance];
    }

    public function incomeStatement(string $from, string $to): array
    {
        $rows = $this->periodBalances($from, $to)->keyBy('type');
        $revenue = Money::decimal($rows->get('revenue')->credit ?? 0)->minus($rows->get('revenue')->debit ?? 0);
        $cogs = Money::decimal($rows->get('cogs')->debit ?? 0)->minus($rows->get('cogs')->credit ?? 0);
        $expense = Money::decimal($rows->get('expense')->debit ?? 0)->minus($rows->get('expense')->credit ?? 0);
        $gross = $revenue->minus($cogs);

        $accounts = ChartOfAccount::query()
            ->whereIn('type', ['revenue', 'cogs', 'expense'])
            ->with(['entries' => function ($q) use ($from, $to) {
                $q->whereHas('journal', fn ($j) => $j->whereDate('journal_date', '>=', $from)->whereDate('journal_date', '<=', $to)->whereNull('reversal_of_id'));
            }])
            ->orderBy('code')
            ->get();

        $accountDetails = $accounts->map(function ($acc) {
            $debit = Money::decimal((string) $acc->entries->sum(fn ($e) => (float) $e->debit));
            $credit = Money::decimal((string) $acc->entries->sum(fn ($e) => (float) $e->credit));
            $balance = match ($acc->type) {
                'revenue' => $credit->minus($debit),
                default => $debit->minus($credit),
            };

            return (object) [
                'id' => $acc->id,
                'code' => $acc->code,
                'name' => $acc->name,
                'type' => $acc->type,
                'debit' => (string) $debit,
                'credit' => (string) $credit,
                'balance' => (string) $balance,
                'has_activity' => ! $debit->isZero() || ! $credit->isZero(),
            ];
        });

        $revenueAccounts = $accountDetails->where('type', 'revenue')->values();
        $cogsAccounts = $accountDetails->where('type', 'cogs')->values();
        $expenseAccounts = $accountDetails->where('type', 'expense')->values();

        return [
            'revenue' => (string) $revenue,
            'cogs' => (string) $cogs,
            'gross' => (string) $gross,
            'expense' => (string) $expense,
            'net' => (string) $gross->minus($expense),
            'revenueAccounts' => $revenueAccounts,
            'cogsAccounts' => $cogsAccounts,
            'expenseAccounts' => $expenseAccounts,
            // Dipakai sebagai submenu saat HPP Job dibuka pada Laba Rugi.
            'hppBreakdown' => $this->hppByCostType($from, $to)['rows'],
        ];
    }

    public function balanceSheet(string $to): array
    {
        $allAccounts = $this->accountBalances($to)->map(function ($account) {
            $account->report_balance = $this->net($account->type, $account->debit, $account->credit);
            return $account;
        });
        $rows = $allAccounts->groupBy('type');
        $assets = $this->sumNet($rows->get('asset', collect()), true);
        $liabilities = $this->sumNet($rows->get('liability', collect()), false);
        $equity = $this->sumNet($rows->get('equity', collect()), false);
        $earnings = Money::decimal($this->sumNet($rows->get('revenue', collect()), false))->minus($this->sumNet($rows->get('cogs', collect()), true))->minus($this->sumNet($rows->get('expense', collect()), true));

        $assetRows = $rows->get('asset', collect())->reject(fn ($account) => $account->code === '1');
        $currentAssets = $assetRows->filter(fn ($account) => str_starts_with((string) $account->code, '11'))->values();
        $nonCurrentAssets = $assetRows->filter(fn ($account) => str_starts_with((string) $account->code, '12'))->values();
        $otherAssets = $assetRows->reject(fn ($account) => str_starts_with((string) $account->code, '11') || str_starts_with((string) $account->code, '12'))->values();

        return [
            'assets' => $assets, 'liabilities' => $liabilities, 'equity' => $equity, 'earnings' => (string) $earnings,
            'liabilities_equity' => (string) Money::decimal($liabilities)->plus($equity)->plus($earnings),
            'currentAssets' => $currentAssets, 'nonCurrentAssets' => $nonCurrentAssets, 'otherAssets' => $otherAssets,
            'liabilityRows' => $rows->get('liability', collect())->reject(fn ($account) => $account->code === '2')->values(),
            'equityRows' => $rows->get('equity', collect())->reject(fn ($account) => $account->code === '3')->values(),
        ];
    }

    public function cashFlow(string $from, string $to): array
    {
        $cashAccounts = ChartOfAccount::where('type', 'asset')
            ->where(function ($q) {
                $q->whereIn('code', ['1101', '1102', '11100', '11101', '11120', '11121', '11122', '11123'])
                    ->orWhere('code', 'like', '1110%')
                    ->orWhere('code', 'like', '1112%')
                    ->orWhere('name', 'like', '%Bank%')
                    ->orWhere('name', 'like', '%Cash%')
                    ->orWhere('name', 'like', '%Kas%');
            })
            ->where('code', '!=', '1103')
            ->get();
        $cashIds = $cashAccounts->pluck('id');

        $openingEntries = JournalEntry::whereIn('chart_of_account_id', $cashIds)
            ->whereHas('journal', fn ($q) => $q->whereDate('journal_date', '<', $from)->whereNull('reversal_of_id'))
            ->get();
        $openingBalance = Money::decimal((string) $openingEntries->sum(fn ($e) => (float) $e->debit - (float) $e->credit));

        $entries = JournalEntry::with(['journal', 'account'])
            ->whereIn('chart_of_account_id', $cashIds)
            ->whereHas('journal', fn ($q) => $q->whereDate('journal_date', '>=', $from)->whereDate('journal_date', '<=', $to)->whereNull('reversal_of_id'))
            ->orderBy(\App\Models\Journal::select('journal_date')->whereColumn('journals.id', 'journal_entries.journal_id')->limit(1))
            ->get();

        $groups = [
            'customer_payment' => Money::decimal(0),
            'job_cost_capitalization' => Money::decimal(0),
            'job_cost_payment' => Money::decimal(0),
            'adjustment' => Money::decimal(0),
            'other' => Money::decimal(0),
        ];

        $inflow = Money::decimal(0);
        $outflow = Money::decimal(0);

        foreach ($entries as $entry) {
            $key = array_key_exists($entry->journal->type, $groups) ? $entry->journal->type : 'other';
            $netMovement = Money::decimal((string) $entry->debit)->minus(Money::decimal((string) $entry->credit));
            $groups[$key] = $groups[$key]->plus($netMovement);

            if ((float) $entry->debit > 0) {
                $inflow = $inflow->plus(Money::decimal((string) $entry->debit));
            }
            if ((float) $entry->credit > 0) {
                $outflow = $outflow->plus(Money::decimal((string) $entry->credit));
            }
        }

        $net = $inflow->minus($outflow);
        $closingBalance = $openingBalance->plus($net);

        $openingPerAccount = JournalEntry::whereIn('chart_of_account_id', $cashIds)
            ->whereHas('journal', fn ($q) => $q->whereDate('journal_date', '<', $from)->whereNull('reversal_of_id'))
            ->selectRaw('chart_of_account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');

        $periodPerAccount = JournalEntry::whereIn('chart_of_account_id', $cashIds)
            ->whereHas('journal', fn ($q) => $q->whereDate('journal_date', '>=', $from)->whereDate('journal_date', '<=', $to)->whereNull('reversal_of_id'))
            ->selectRaw('chart_of_account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');

        $accountBreakdown = $cashAccounts->map(function ($acc) use ($openingPerAccount, $periodPerAccount) {
            $opRow = $openingPerAccount->get($acc->id);
            $op = (float) ($opRow->total_debit ?? 0) - (float) ($opRow->total_credit ?? 0);

            $periodRow = $periodPerAccount->get($acc->id);
            $inflow = (float) ($periodRow->total_debit ?? 0);
            $outflow = (float) ($periodRow->total_credit ?? 0);
            $net = $inflow - $outflow;
            $closing = $op + $net;

            return (object) [
                'id' => $acc->id,
                'code' => $acc->code,
                'name' => $acc->name,
                'opening' => (string) Money::decimal((string) $op),
                'inflow' => (string) Money::decimal((string) $inflow),
                'outflow' => (string) Money::decimal((string) $outflow),
                'net' => (string) Money::decimal((string) $net),
                'closing' => (string) Money::decimal((string) $closing),
            ];
        });

        return [
            'customer_payment' => (string) $groups['customer_payment'],
            'job_cost_capitalization' => (string) $groups['job_cost_capitalization'],
            'job_cost_payment' => (string) $groups['job_cost_payment'],
            'adjustment' => (string) $groups['adjustment'],
            'other' => (string) $groups['other'],
            'inflow' => (string) $inflow,
            'outflow' => (string) $outflow,
            'net' => (string) $net,
            'opening' => (string) $openingBalance,
            'closing' => (string) $closingBalance,
            'accounts' => $accountBreakdown,
            'entries' => $entries,
        ];
    }

    public function profitPerJob(string $from, string $to): array
    {
        $snapshots = JobClosingSnapshot::with('job.customer')
            ->whereDate('closing_date', '>=', $from)
            ->whereDate('closing_date', '<=', $to)
            ->orderByDesc('closing_date')
            ->get();

        $rows = $snapshots->map(function ($s) {
            return (object) [
                'id' => 'closed_'.$s->id,
                'date' => $s->closing_date,
                'job_number' => $s->job?->number ?? '—',
                'job_id' => $s->job_id,
                'status' => 'closed',
                'customer_name' => $s->customer_snapshot['name'] ?? ($s->job?->customer?->name ?? '—'),
                'total_temporary' => (float) $s->total_temporary,
                'total_provision_cost' => (float) $s->total_provision_cost,
                'total_provision_sell' => (float) $s->total_provision_sell,
                'profit' => (float) $s->profit,
                'margin' => (float) $s->margin,
            ];
        });

        $closedJobIds = $snapshots->pluck('job_id')->filter();
        $openJobs = Job::where('status', 'open')
            ->whereNotIn('id', $closedJobIds)
            ->whereDate('job_date', '>=', $from)
            ->whereDate('job_date', '<=', $to)
            ->with(['costs', 'customer'])
            ->orderByDesc('job_date')
            ->get();

        $costService = app(JobCostService::class);
        $openRows = $openJobs->map(function ($job) use ($costService) {
            $summary = $costService->summary($job)['all'];
            return (object) [
                'id' => 'open_'.$job->id,
                'date' => $job->job_date,
                'job_number' => $job->number,
                'job_id' => $job->id,
                'status' => 'open',
                'customer_name' => $job->quotation_snapshot['customer']['name'] ?? ($job->customer?->name ?? '—'),
                'total_temporary' => (float) (string) $summary['temporary'],
                'total_provision_cost' => (float) (string) $summary['provision_cost'],
                'total_provision_sell' => (float) (string) $summary['provision_sell'],
                'profit' => (float) (string) $summary['profit'],
                'margin' => (float) (string) $summary['margin'],
            ];
        });

        $all = $rows->concat($openRows)->sortByDesc('date')->values();
        $totalJobs = $all->count();
        $totalTemporary = $all->sum(fn ($row) => $row->total_temporary);
        $totalCost = $all->sum(fn ($row) => $row->total_provision_cost);
        $totalRevenue = $all->sum(fn ($row) => $row->total_provision_sell);
        $totalProfit = $all->sum(fn ($row) => $row->profit);
        $totalMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

        $perPage = min(100, max(5, (int) request('per_page', 10)));
        $page = (int) request('page', 1);
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $all->forPage($page, $perPage),
            $all->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'rows' => $paginated,
            'totalJobs' => $totalJobs,
            'totalTemporary' => $totalTemporary,
            'totalCost' => $totalCost,
            'totalRevenue' => $totalRevenue,
            'totalProfit' => $totalProfit,
            'totalMargin' => $totalMargin,
        ];
    }

    public function monthlyProfit(int $year): array
    {
        $months = collect(range(1, 12))->mapWithKeys(fn (int $m) => [$m => ['jobs' => 0, 'temporary' => Money::decimal(0), 'cost' => Money::decimal(0), 'revenue' => Money::decimal(0), 'profit' => Money::decimal(0)]]);
        JobClosingSnapshot::whereYear('closing_date', $year)->get()->each(function ($snapshot) use ($months) {
            $row = $months[$snapshot->closing_date->month];
            $row['jobs']++;
            $row['temporary'] = $row['temporary']->plus($snapshot->total_temporary);
            $row['cost'] = $row['cost']->plus($snapshot->total_provision_cost);
            $row['revenue'] = $row['revenue']->plus($snapshot->total_provision_sell);
            $row['profit'] = $row['profit']->plus($snapshot->profit);
            $months[$snapshot->closing_date->month] = $row;
        });
        $rows = $months->values()->map(fn ($row, $i) => ['month' => $i + 1, 'jobs' => $row['jobs'],
            'temporary' => (string) $row['temporary'], 'cost' => (string) $row['cost'], 'revenue' => (string) $row['revenue'],
            'profit' => (string) $row['profit'], 'margin' => $row['revenue']->isZero() ? '0.00' : (string) $row['profit']->multipliedBy('100')->dividedBy($row['revenue'], 2, RoundingMode::HalfUp)]);
        $totals = [];
        foreach (['temporary', 'cost', 'revenue', 'profit'] as $key) {
            $totals[$key] = (string) collect($rows)->reduce(fn ($sum, $row) => $sum->plus(Money::decimal($row[$key])), Money::decimal(0));
        }
        $totals['jobs'] = collect($rows)->sum('jobs');
        $totals['margin'] = Money::decimal($totals['revenue'])->isZero() ? '0.00' : (string) Money::decimal($totals['profit'])->multipliedBy('100')->dividedBy(Money::decimal($totals['revenue']), 2, RoundingMode::HalfUp);

        return ['rows' => $rows, 'totals' => $totals];
    }

    /**
     * Rekap HPP yang benar-benar telah diakui saat Closing Job.
     * Data dibaca dari snapshot closing sehingga tidak berubah ketika biaya Job diedit setelahnya.
     */
    public function hppByCostType(string $from, string $to): array
    {
        $snapshots = JobClosingSnapshot::query()
            ->with('job:id,number')
            ->whereDate('closing_date', '>=', $from)
            ->whereDate('closing_date', '<=', $to)
            ->get(['id', 'job_id', 'costs_snapshot']);

        // PPh dilaporkan dari biaya yang benar-benar telah dibayar. Ini tetap
        // akurat ketika pembayaran vendor dilakukan setelah Closing Job.
        $pphByDescription = JobCost::query()
            ->whereIn('job_id', $snapshots->pluck('job_id')->filter())
            ->where('type', 'provision')
            ->whereNotNull('paid_at')
            ->where('pph23_amount', '>', 0)
            ->get(['description', 'pph23_amount'])
            ->groupBy(fn (JobCost $cost) => mb_strtoupper(trim($cost->description) ?: 'Tanpa uraian'))
            ->map(fn (Collection $costs) => $costs->reduce(fn ($sum, $cost) => $sum->plus(Money::decimal((string) $cost->pph23_amount)), Money::decimal(0)));

        $grouped = [];
        $jobIds = [];
        foreach ($snapshots as $snapshot) {
            foreach ($snapshot->costs_snapshot ?? [] as $cost) {
                // Reimbursement adalah talangan/piutang temporary, bukan HPP perusahaan.
                if (($cost['type'] ?? 'provision') === 'temporary') {
                    continue;
                }
                $description = trim((string) ($cost['description'] ?? '')) ?: 'Tanpa uraian';
                $key = mb_strtoupper($description);
                $grouped[$key] ??= ['description' => $description, 'transaction_count' => 0, 'job_ids' => [], 'total_cost' => Money::decimal(0), 'total_price' => Money::decimal(0), 'total_pph23' => Money::decimal(0), 'items' => []];
                $grouped[$key]['transaction_count']++;
                $grouped[$key]['job_ids'][$snapshot->job_id] = true;
                $grouped[$key]['total_cost'] = $grouped[$key]['total_cost']->plus(Money::decimal((string) ($cost['total_cost'] ?? 0)));
                $grouped[$key]['total_price'] = $grouped[$key]['total_price']->plus(Money::decimal((string) ($cost['total_price'] ?? 0)));
                $grouped[$key]['total_pph23'] = $grouped[$key]['total_pph23']->plus(Money::decimal((string) ($cost['pph23_amount'] ?? 0)));
                $grouped[$key]['items'][] = [
                    'job_id' => $snapshot->job_id,
                    'job_number' => $snapshot->job->number ?? ('#'.$snapshot->job_id),
                    'cost_number' => $cost['number'] ?? '—',
                    'cost_date' => $cost['cost_date'] ?? '—',
                    'payee' => $cost['payee'] ?? '—',
                    'quantity' => $cost['quantity'] ?? '1',
                    'unit' => $cost['unit'] ?? '',
                    'total_cost' => (string) ($cost['total_cost'] ?? 0),
                    'total_price' => (string) ($cost['total_price'] ?? 0),
                    'pph23_amount' => (string) ($cost['pph23_amount'] ?? 0),
                ];
                $jobIds[$snapshot->job_id] = true;
            }
        }

        $rows = collect($grouped)->map(fn (array $row, string $key) => (object) [
            'description' => $row['description'],
            'transaction_count' => $row['transaction_count'],
            'job_count' => count($row['job_ids']),
            'total_cost' => (string) $row['total_cost'],
            'total_price' => (string) $row['total_price'],
            'total_pph23' => (string) ($pphByDescription->get($key) ?? $row['total_pph23']),
            'items' => $row['items'],
        ])->sortByDesc(fn ($row) => (float) $row->total_cost)->values();

        $totalCost = $rows->reduce(fn ($sum, $row) => $sum->plus(Money::decimal($row->total_cost)), Money::decimal(0));
        $totalSales = $rows->reduce(fn ($sum, $row) => $sum->plus(Money::decimal($row->total_price)), Money::decimal(0));
        $totalPph23 = $rows->reduce(fn ($sum, $row) => $sum->plus(Money::decimal($row->total_pph23)), Money::decimal(0));

        return [
            'rows' => $rows,
            'totalCost' => (string) $totalCost,
            'totalSales' => (string) $totalSales,
            'totalPph23' => (string) $totalPph23,
            'totalTransactions' => (int) $rows->sum('transaction_count'),
            'totalJobs' => count($jobIds),
        ];
    }

    private function accountBalances(string $to): Collection
    {
        $totals = JournalEntry::whereHas('journal', fn (Builder $q) => $q->whereDate('journal_date', '<=', $to))->select('chart_of_account_id')->selectRaw('SUM(debit) debit, SUM(credit) credit')->groupBy('chart_of_account_id')->get()->keyBy('chart_of_account_id');

        return ChartOfAccount::withTrashed()->orderBy('code')->get()->each(function ($account) use ($totals) {
            $account->debit = (string) ($totals->get($account->id)->debit ?? '0.00');
            $account->credit = (string) ($totals->get($account->id)->credit ?? '0.00');
        });
    }

    private function periodBalances(string $from, string $to): Collection
    {
        return JournalEntry::join('journals', 'journals.id', '=', 'journal_entries.journal_id')->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')->whereDate('journals.journal_date', '>=', $from)->whereDate('journals.journal_date', '<=', $to)->select('chart_of_accounts.type')->selectRaw('SUM(journal_entries.debit) debit, SUM(journal_entries.credit) credit')->groupBy('chart_of_accounts.type')->get();
    }

    private function net(string $type, mixed $debit, mixed $credit): string
    {
        return (string) (in_array($type, ['asset', 'cogs', 'expense'], true) ? Money::decimal($debit)->minus($credit) : Money::decimal($credit)->minus($debit));
    }

    private function sumNet(Collection $rows, bool $debitNormal): string
    {
        return (string) $rows->reduce(fn ($sum, $row) => $sum->plus($debitNormal ? Money::decimal($row->debit)->minus($row->credit) : Money::decimal($row->credit)->minus($row->debit)), Money::decimal(0));
    }
}
