<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JobClosingSnapshot;
use App\Models\JournalEntry;
use App\Support\Money;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FinancialReportService
{
    public function trialBalance(string $to): Collection
    {
        return $this->accountBalances($to)->map(function ($account) {
            $normalDebit = in_array($account->type, ['asset', 'cogs', 'expense'], true);
            $net = $normalDebit ? Money::decimal($account->debit)->minus($account->credit) : Money::decimal($account->credit)->minus($account->debit);
            $account->closing_debit = $normalDebit ? ($net->isNegative() ? '0.00' : (string) $net) : ($net->isNegative() ? (string) $net->abs() : '0.00');
            $account->closing_credit = $normalDebit ? ($net->isNegative() ? (string) $net->abs() : '0.00') : ($net->isNegative() ? '0.00' : (string) $net);

            return $account;
        });
    }

    public function ledger(int $accountId, string $from, string $to): array
    {
        $account = ChartOfAccount::withTrashed()->findOrFail($accountId);
        $openingRows = JournalEntry::where('chart_of_account_id', $accountId)->whereHas('journal', fn (Builder $q) => $q->where('journal_date', '<', $from))->get();
        $opening = $this->net($account->type, $openingRows->sum('debit'), $openingRows->sum('credit'));
        $balance = Money::decimal($opening);
        $entries = JournalEntry::with('journal')->where('chart_of_account_id', $accountId)->whereHas('journal', fn (Builder $q) => $q->whereDate('journal_date', '>=', $from)->whereDate('journal_date', '<=', $to))->get()->sortBy(fn ($entry) => $entry->journal->journal_date->format('Y-m-d').str_pad($entry->id, 15, '0', STR_PAD_LEFT))->values();
        foreach ($entries as $entry) {
            $movement = $this->net($account->type, $entry->debit, $entry->credit);
            $balance = $balance->plus($movement);
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

        return ['revenue' => (string) $revenue, 'cogs' => (string) $cogs, 'gross' => (string) $gross, 'expense' => (string) $expense, 'net' => (string) $gross->minus($expense)];
    }

    public function balanceSheet(string $to): array
    {
        $rows = $this->accountBalances($to)->groupBy('type');
        $assets = $this->sumNet($rows->get('asset', collect()), true);
        $liabilities = $this->sumNet($rows->get('liability', collect()), false);
        $equity = $this->sumNet($rows->get('equity', collect()), false);
        $earnings = Money::decimal($this->sumNet($rows->get('revenue', collect()), false))->minus($this->sumNet($rows->get('cogs', collect()), true))->minus($this->sumNet($rows->get('expense', collect()), true));

        return ['assets' => $assets, 'liabilities' => $liabilities, 'equity' => $equity, 'earnings' => (string) $earnings, 'liabilities_equity' => (string) Money::decimal($liabilities)->plus($equity)->plus($earnings)];
    }

    public function cashFlow(string $from, string $to): array
    {
        $cashCodes = collect(config('accounting.mappings'))->only(['cash', 'bank'])->pluck('code');
        $entries = JournalEntry::with('journal')->whereHas('account', fn (Builder $q) => $q->whereIn('code', $cashCodes))->whereHas('journal', fn (Builder $q) => $q->whereDate('journal_date', '>=', $from)->whereDate('journal_date', '<=', $to))->get();
        $groups = ['customer_payment' => Money::decimal(0), 'job_cost_capitalization' => Money::decimal(0), 'adjustment' => Money::decimal(0), 'other' => Money::decimal(0)];
        foreach ($entries as $entry) {
            $key = array_key_exists($entry->journal->type, $groups) ? $entry->journal->type : 'other';
            $groups[$key] = $groups[$key]->plus($entry->debit)->minus($entry->credit);
        }

        return collect($groups)->map(fn ($amount) => (string) $amount)->all() + ['net' => (string) collect($groups)->reduce(fn ($sum, $amount) => $sum->plus($amount), Money::decimal(0))];
    }

    public function profitPerJob(string $from, string $to): Collection
    {
        return JobClosingSnapshot::with('job')->whereDate('closing_date', '>=', $from)->whereDate('closing_date', '<=', $to)->orderByDesc('closing_date')->get();
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
