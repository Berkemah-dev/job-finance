<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Job;
use App\Models\JobClosingSnapshot;
use App\Models\User;
use App\Support\Money;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class JobClosingService
{
    public function __construct(private JobCostService $costs, private DocumentNumberService $numbers, private JournalService $journals, private MasterDataService $master) {}

    public function close(Job $job, array $data, User $actor): Invoice
    {
        return DB::transaction(function () use ($job, $data, $actor) {
            $job = Job::lockForUpdate()->findOrFail($job->id);
            Gate::forUser($actor)->authorize('jobs.close');
            $this->master->checkVersion($job, $data);
            if ($job->status !== 'open') {
                throw ValidationException::withMessages(['job' => 'Hanya job Open yang dapat ditutup.']);
            }
            if ($job->closingSnapshot()->exists() || $job->invoice()->exists()) {
                throw ValidationException::withMessages(['job' => 'Job sudah memiliki closing atau invoice.']);
            }
            if (! $job->customer()->withTrashed()->exists()) {
                throw ValidationException::withMessages(['customer' => 'Customer job tidak tersedia.']);
            }
            if ($job->costs()->count() === 0) {
                throw ValidationException::withMessages(['costs' => 'Job belum memiliki detail biaya.']);
            }
            if ($job->costs()->where('status', '!=', 'final')->exists()) {
                throw ValidationException::withMessages(['costs' => 'Semua biaya harus Final sebelum closing.']);
            }
            $rows = $job->costs()->orderBy('id')->lockForUpdate()->get();
            $summary = $this->costs->summary($job)['final'];
            $tax = Money::decimal($data['tax']);
            $total = Money::decimal($summary['subtotal'])->plus($tax);
            Money::checked($total);
            if ($total->isZero()) {
                throw ValidationException::withMessages(['total' => 'Total invoice harus lebih besar dari nol.']);
            }
            $maps = $this->journals->mapped(['temporary', 'provision_wip', 'receivable', 'revenue', 'cogs', 'tax_payable', $data['funding_account']]);
            $currency = (string) ($job->quotation_snapshot['currency'] ?? 'IDR');
            if (! in_array($currency, array_keys(config('operations.currencies')), true)) {
                throw ValidationException::withMessages(['job' => 'Mata uang quotation tidak valid untuk invoice.']);
            }
            $rate = Money::decimal((string) ($job->quotation_snapshot['exchange_rate'] ?? '1'));
            if ($rate->isZero()) {
                throw ValidationException::withMessages(['job' => 'Kurs quotation nol sehingga invoice tidak dapat dibuat.']);
            }
            $snapshot = JobClosingSnapshot::create(['job_id' => $job->id, 'closing_date' => $data['closing_date'], 'customer_snapshot' => $job->quotation_snapshot['customer'],
                'currency' => $currency, 'exchange_rate' => $rate->toScale(2, RoundingMode::HalfUp),
                'costs_snapshot' => $rows->map(fn ($c) => $c->only(['number', 'description', 'type', 'cost_date', 'quantity', 'unit', 'unit_cost', 'unit_price', 'total_cost', 'total_price']))->all(),
                'total_temporary' => $summary['temporary'], 'total_provision_cost' => $summary['provision_cost'], 'total_provision_sell' => $summary['provision_sell'],
                'subtotal' => $summary['subtotal'], 'tax' => (string) $tax, 'total' => Money::checked($total), 'profit' => $summary['profit'], 'margin' => $summary['margin'],
                'funding_account_id' => $maps[$data['funding_account']]->id, 'closed_by' => $actor->id, 'closed_at' => now()]);
            $invoice = Invoice::create(['number' => $this->numbers->next('inv'), 'job_id' => $job->id, 'job_closing_snapshot_id' => $snapshot->id, 'customer_id' => $job->customer_id,
                'customer_snapshot' => $job->quotation_snapshot['customer'], 'invoice_date' => $data['closing_date'], 'due_date' => $data['due_date'], 'status' => 'issued',
                'currency' => $currency, 'exchange_rate' => $rate->toScale(2, RoundingMode::HalfUp),
                'subtotal' => $summary['subtotal'], 'tax' => (string) $tax, 'total' => Money::checked($total), 'paid_amount' => '0.00', 'balance' => Money::checked($total), 'created_by' => $actor->id, 'issued_at' => now()]);
            foreach ($rows as $i => $cost) {
                $invoice->items()->create(['position' => $i + 1, 'description' => $cost->description, 'type' => $cost->type, 'quantity' => $cost->quantity, 'unit' => $cost->unit, 'unit_price' => $cost->type === 'temporary' ? $cost->unit_cost : $cost->unit_price, 'amount' => $cost->type === 'temporary' ? $cost->total_cost : $cost->total_price]);
            }
            $fund = Money::decimal($summary['temporary'])->plus($summary['provision_cost']);
            if (! $fund->isZero()) {
                $this->journals->post('job_cost_capitalization', Job::class, $job->id, $data['closing_date'], 'Kapitalisasi biaya '.$job->number, [
                    ['account_id' => $maps['temporary']->id, 'description' => 'Temporary '.$job->number, 'debit' => $summary['temporary'], 'credit' => 0],
                    ['account_id' => $maps['provision_wip']->id, 'description' => 'Provision WIP '.$job->number, 'debit' => $summary['provision_cost'], 'credit' => 0],
                    ['account_id' => $maps[$data['funding_account']]->id, 'description' => 'Sumber dana '.$job->number, 'debit' => 0, 'credit' => (string) $fund]], $actor);
            }
            $this->journals->post('job_closing', Job::class, $job->id, $data['closing_date'], 'Closing '.$job->number, [
                ['account_id' => $maps['receivable']->id, 'description' => 'Piutang '.$invoice->number, 'debit' => (string) $total, 'credit' => 0],
                ['account_id' => $maps['cogs']->id, 'description' => 'HPP '.$job->number, 'debit' => $summary['provision_cost'], 'credit' => 0],
                ['account_id' => $maps['temporary']->id, 'description' => 'Reklasifikasi temporary', 'debit' => 0, 'credit' => $summary['temporary']],
                ['account_id' => $maps['provision_wip']->id, 'description' => 'Reklasifikasi WIP', 'debit' => 0, 'credit' => $summary['provision_cost']],
                ['account_id' => $maps['revenue']->id, 'description' => 'Pendapatan provision', 'debit' => 0, 'credit' => $summary['provision_sell']],
                ['account_id' => $maps['tax_payable']->id, 'description' => 'Pajak keluaran', 'debit' => 0, 'credit' => (string) $tax]], $actor);
            $job->status = 'closed';
            $job->closed_by = $actor->id;
            $job->closed_at = now();
            $job->updated_by = $actor->id;
            $job->lock_version++;
            $job->save();
            $job->statusHistory()->create(['from_status' => 'open', 'to_status' => 'closed', 'note' => 'Closed '.$invoice->number, 'user_id' => $actor->id, 'created_at' => now()]);
            $this->master->log($actor, 'job.closed', $job->number.' → '.$invoice->number, ['module' => 'job_closing', 'record_id' => $snapshot->id, 'after' => ['invoice' => $invoice->number, 'currency' => $currency, 'exchange_rate' => $invoice->exchange_rate, 'subtotal' => $summary['subtotal'], 'tax' => (string) $tax, 'total' => $invoice->total, 'profit' => $summary['profit'], 'margin' => $summary['margin']]]);

            return $invoice;
        }, 3);
    }
}
