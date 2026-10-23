<?php

namespace App\Services;

use App\Models\Job;
use App\Support\Money;

class JobProfitReportService
{
    public function report(Job $job, JobCostService $payments): array
    {
        $job->loadMissing(['customer', 'quotation', 'invoice']);
        $costs = $job->costs()->with('vendor')->where('status', 'final')->orderBy('id')->get();
        $reimbursements = $costs->filter(fn ($cost) => $cost->type === 'temporary');
        $charges = $costs->reject(fn ($cost) => $cost->type === 'temporary');
        $sum = function ($rows, $field) {
            $total = Money::decimal(0);
            foreach ($rows as $row) {
                $total = $total->plus($row->{$field});
            }
            return (string) $total;
        };
        $unpaidCreditNotes = $costs->where('cost_category', 'credit_note')->map(function ($cost) use ($payments) {
            $paid = $payments->paidAmount($cost);
            return ['cost' => $cost, 'paid' => (string) $paid,
                'outstanding' => (string) Money::decimal($cost->total_cost)->minus($paid)];
        })->filter(fn ($row) => Money::decimal($row['outstanding'])->isPositive())->values();

        $sell = $sum($charges, 'total_price');
        $buy = $sum($charges, 'total_cost');
        return compact('job', 'charges', 'reimbursements', 'unpaidCreditNotes', 'sell', 'buy') + [
            'reimbursementSell' => $sum($reimbursements, 'total_price'),
            'reimbursementBuy' => $sum($reimbursements, 'total_cost'),
            'profit' => (string) Money::decimal($sell)->minus($buy),
            'draftCount' => $job->costs()->where('status', '!=', 'final')->count(),
        ];
    }
}
