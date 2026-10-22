<?php

namespace App\Services;

use App\Models\JobCost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PaymentListService
{
    public const CATEGORIES = ['credit_note' => 'CN — Credit Note', 'payment_request' => 'PR — Payment Request', 'debit_note' => 'DN — Debit Note', 'reimbursement' => 'Reimbursement'];

    private const PAID_AMOUNT = 'CASE WHEN COALESCE(list_payment_amount, 0) > 0 THEN list_payment_amount WHEN paid_at IS NOT NULL THEN COALESCE(NULLIF(list_recognized_amount, 0), total_cost) ELSE 0 END';

    public function query(array $filters = []): Builder
    {
        $journals = fn () => DB::table('journals')
            ->join('journal_entries', 'journal_entries.journal_id', '=', 'journals.id')
            ->where('journals.source_type', JobCost::class)
            ->whereColumn('journals.source_id', 'job_costs.id')
            ->whereNull('journals.reversed_at')->whereNull('journals.reversal_of_id');
        $payments = $journals()->where('journals.type', 'job_cost_payment')
            ->where(fn ($q) => $q->whereNull('journal_entries.description')->orWhere('journal_entries.description', 'not like', 'Reklasifikasi Provisi biaya susulan%'))
            ->selectRaw('SUM(journal_entries.credit)');
        $recognition = $journals()->whereIn('journals.type', ['job_cost_draft', 'job_cost_direct', 'job_cost_finalization', 'job_cost_amendment'])->selectRaw('SUM(journal_entries.debit)');
        $base = JobCost::query()->select('job_costs.*')->selectSub($payments, 'list_payment_amount')->selectSub($recognition, 'list_recognized_amount');
        $query = JobCost::query()->fromSub($base, 'job_costs')->select('job_costs.*')
            ->selectRaw(self::PAID_AMOUNT.' AS list_paid_amount')->with(['job.customer', 'vendor']);

        if (array_key_exists($filters['category'] ?? '', self::CATEGORIES)) {
            $category = $filters['category'];
            $query->where(function ($q) use ($category) {
                $q->where('cost_category', $category);
                if ($category === 'payment_request') {
                    $q->orWhereNull('cost_category');
                }
            });
        }
        if (($filters['number'] ?? '') !== '') {
            $query->where('number', 'like', '%'.$filters['number'].'%');
        }
        if (($filters['job_number'] ?? '') !== '') {
            $query->whereHas('job', fn ($q) => $q->where('number', 'like', '%'.$filters['job_number'].'%'));
        }

        match ($filters['status'] ?? '') {
            'paid' => $query->whereNotNull('paid_at')->whereRaw('('.self::PAID_AMOUNT.') >= total_cost'),
            'unpaid' => $query->where(fn ($q) => $q->whereNull('paid_at')->orWhereRaw('('.self::PAID_AMOUNT.') < total_cost')),
            'partial' => $query->whereRaw('('.self::PAID_AMOUNT.') > 0')->whereRaw('('.self::PAID_AMOUNT.') < total_cost'),
            default => null,
        };

        return $query->orderByDesc('cost_date')->orderByDesc('id');
    }
}
