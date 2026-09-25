<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Support\Money;
use Carbon\Carbon;

use Illuminate\Pagination\LengthAwarePaginator;

class StatementOfAccountService
{
    private const AGING_BUCKETS = ['current', 'aging_1_30', 'aging_31_60', 'aging_61_90', 'aging_90_plus'];

    public function summary(?string $search = null, bool $unpaidOnly = false): array
    {
        $invoices = Invoice::orderBy('invoice_date')->get(['customer_id', 'number', 'invoice_date', 'due_date', 'status', 'total', 'paid_amount', 'balance']);
        $grand = $this->emptyTotals();
        $customerIds = $invoices->pluck('customer_id')->filter()->unique()->values();
        $customersById = Customer::withTrashed()
            ->whereIn('id', $customerIds)
            ->get(['id', 'code', 'name', 'contact_name'])
            ->keyBy('id');

        $customers = [];
        foreach ($invoices->groupBy('customer_id') as $customerId => $rows) {
            $customer = $customersById->get((int) $customerId);
            if (! $customer) {
                continue;
            }
            $needle = strtolower(trim((string) $search));
            if ($needle !== '' && ! str_contains(strtolower($customer->name), $needle) && ! str_contains(strtolower((string) $customer->code), $needle)) {
                continue;
            }
            $totals = $this->emptyTotals();
            $totals['invoices'] = $rows->count();
            foreach ($rows as $invoice) {
                $this->add($totals, $invoice);
                if (! Money::decimal($invoice->balance)->isZero()) {
                    $days = (int) today()->startOfDay()->diffInDays(Carbon::parse($invoice->due_date)->startOfDay(), false);
                    $totals[$this->bucket($days)] = $totals[$this->bucket($days)]->plus(Money::decimal($invoice->balance));
                }
            }
            if ($unpaidOnly && Money::decimal($totals['balance'])->isZero()) {
                continue;
            }
            foreach (array_merge(self::AGING_BUCKETS, ['invoices']) as $key) {
                $this->addTo($grand, $totals, $key);
            }
            $totals['customer'] = $customer;
            $customers[] = $totals;
        }
        usort($customers, fn ($a, $b) => $b['balance']->compareTo($a['balance']));

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = min(100, max(5, (int) request('per_page', 10)));
        $offset = ($page - 1) * $perPage;
        $paginatedCustomers = new LengthAwarePaginator(
            array_slice($customers, $offset, $perPage),
            count($customers),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        return ['customers' => $paginatedCustomers, 'grand' => $grand];
    }

    public function statement(Customer $customer, Carbon $from, Carbon $to, bool $paginate = true): array
    {
        $invoices = Invoice::with(['payments', 'job'])->where('customer_id', $customer->id)->orderBy('invoice_date')->orderBy('id')->get();

        $opening = Money::decimal(0);
        foreach ($invoices as $invoice) {
            if ($invoice->invoice_date->lt($from)) {
                $opening = $opening->plus(Money::decimal($invoice->total));
                foreach ($invoice->payments as $payment) {
                    if ($payment->payment_date->lt($from)) {
                        $opening = $opening->minus(Money::decimal($payment->amount));
                    }
                }
            }
        }

        $rows = [];
        $invoiced = Money::decimal(0);
        $paid = Money::decimal(0);
        foreach ($invoices as $invoice) {
            if ($invoice->invoice_date->between($from, $to)) {
                $invoiced = $invoiced->plus(Money::decimal($invoice->total));
                $rows[] = [
                    'date' => $invoice->invoice_date,
                    'number' => $invoice->number,
                    'description' => 'Invoice '.$invoice->number.($invoice->job?->number ? ' · Job '.$invoice->job->number : ''),
                    'type' => 'invoice',
                    'debit' => '0.00',
                    'credit' => (string) $invoice->total,
                    'invoice' => $invoice,
                    'is_overdue' => $invoice->due_date && Carbon::parse($invoice->due_date)->endOfDay()->isPast() && ! Money::decimal($invoice->balance)->isZero(),
                ];
            }
            foreach ($invoice->payments as $payment) {
                if ($payment->payment_date->between($from, $to)) {
                    $paid = $paid->plus(Money::decimal($payment->amount));
                    $rows[] = [
                        'date' => $payment->payment_date,
                        'number' => $payment->number,
                        'description' => 'Pembayaran '.$payment->number.' · '.ucfirst($payment->method),
                        'type' => 'payment',
                        'debit' => (string) $payment->amount,
                        'credit' => '0.00',
                        'invoice' => $invoice,
                        'is_overdue' => false,
                    ];
                }
            }
        }
        usort($rows, fn ($a, $b) => [$a['date']->toDateString(), $a['number']] <=> [$b['date']->toDateString(), $b['number']]);

        $running = $opening;
        foreach ($rows as &$row) {
            $running = $running->plus(Money::decimal($row['credit']))->minus(Money::decimal($row['debit']));
            $row['balance'] = (string) $running;
            $row['running'] = (string) $running;
        }
        unset($row);

        $closing = Money::decimal(0);
        foreach ($invoices as $invoice) {
            if ($invoice->invoice_date->lte($to)) {
                $closing = $closing->plus(Money::decimal($invoice->total));
            }
            foreach ($invoice->payments as $payment) {
                if ($payment->payment_date->lte($to)) {
                    $closing = $closing->minus(Money::decimal($payment->amount));
                }
            }
        }

        $aged = $this->emptyTotals();
        $aged['invoices'] = 0;
        foreach ($invoices as $invoice) {
            if (Money::decimal($invoice->balance)->isZero()) {
                continue;
            }
            $aged['invoices']++;
            $days = (int) today()->startOfDay()->diffInDays(Carbon::parse($invoice->due_date)->startOfDay(), false);
            $aged[$this->bucket($days)] = $aged[$this->bucket($days)]->plus(Money::decimal($invoice->balance));
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = min(100, max(5, (int) request('per_page', 10)));
        $offset = ($page - 1) * $perPage;
        $paginatedRows = new LengthAwarePaginator(
            array_slice($rows, $offset, $perPage),
            count($rows),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        return [
            'opening' => Money::checked($opening),
            'closing' => Money::checked($closing),
            'invoiced' => Money::checked($invoiced),
            'paid' => Money::checked($paid),
            'total_debit' => Money::checked($paid),
            'total_credit' => Money::checked($invoiced),
            'rows' => $paginate ? $paginatedRows : $rows,
            'all_rows' => $rows,
            'lines' => $rows,
            'aged' => $aged,
        ];
    }

    private function bucket(int $days): string
    {
        if ($days >= 0) {
            return 'current';
        }
        if ($days >= -30) {
            return 'aging_1_30';
        }
        if ($days >= -60) {
            return 'aging_31_60';
        }
        if ($days >= -90) {
            return 'aging_61_90';
        }

        return 'aging_90_plus';
    }

    private function emptyTotals(): array
    {
        return ['invoices' => 0, 'total' => Money::decimal(0), 'paid' => Money::decimal(0), 'balance' => Money::decimal(0), 'current' => Money::decimal(0), 'aging_1_30' => Money::decimal(0), 'aging_31_60' => Money::decimal(0), 'aging_61_90' => Money::decimal(0), 'aging_90_plus' => Money::decimal(0)];
    }

    private function add(array &$totals, Invoice $invoice): void
    {
        $totals['total'] = $totals['total']->plus(Money::decimal($invoice->total));
        $totals['paid'] = $totals['paid']->plus(Money::decimal($invoice->paid_amount));
        $totals['balance'] = $totals['balance']->plus(Money::decimal($invoice->balance));
    }

    private function addTo(array &$grand, array $totals, string $key): void
    {
        if ($key === 'invoices') {
            $grand['invoices'] += $totals[$key];
        } else {
            $grand[$key] = $grand[$key]->plus($totals[$key]);
        }
    }
}
