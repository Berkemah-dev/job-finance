<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Job;
use App\Models\JobClosingSnapshot;
use App\Models\Journal;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Money;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class JobClosingService
{
    public function __construct(private JobCostService $costs, private DocumentNumberService $numbers, private JournalService $journals, private MasterDataService $master, private PartyAccountService $partyAccounts) {}

    public function close(Job $job, array $data, User $actor): Invoice
    {
        return DB::transaction(function () use ($job, $data, $actor) {
            $job = Job::lockForUpdate()->findOrFail($job->id);
            Gate::forUser($actor)->authorize('jobs.close');
            $this->master->checkVersion($job, $data);
            if ($job->status !== 'open') {
                throw ValidationException::withMessages(['job' => 'Hanya job Open yang dapat ditutup.']);
            }
            if ($job->closingSnapshot()->exists()) {
                throw ValidationException::withMessages(['job' => 'Job sudah memiliki closing.']);
            }
            if (! $job->customer()->withTrashed()->exists()) {
                throw ValidationException::withMessages(['customer' => 'Customer job tidak tersedia.']);
            }
            $customerAccounts = $this->partyAccounts->customerAccounts($job->customer()->withTrashed()->firstOrFail());
            $isImport = in_array((string) ($job->service_type ?? ''), ['imp_sea', 'imp_air'], true);
            if ($isImport && ! $job->hasSuratJalanDocument()) {
                throw ValidationException::withMessages(['surat_jalan' => 'Job Import wajib memiliki berkas Surat Jalan yang sudah terupload sebelum job dapat diselesaikan/ditutup.']);
            }
            if ($job->costs()->count() === 0 && ! empty($job->quotation_snapshot['items'])) {
                app(JobService::class)->seedQuotationCharges($job, $actor);
            }
            if ($job->costs()->count() === 0) {
                throw ValidationException::withMessages(['costs' => 'Job belum memiliki detail biaya.']);
            }
            $workflowDrafts = $job->costs()->where('status', 'draft')->whereIn('cost_category', ['payment_request', 'reimbursement', 'debit_note', 'credit_note'])->lockForUpdate()->count();
            if ($workflowDrafts > 0) {
                throw ValidationException::withMessages(['costs' => 'Payment Request, Reimbursement, Debit Note, dan Credit Note harus disetujui sebelum Closing Job.']);
            }
            if ($job->costs()->whereIn('status', ['draft', 'approved'])->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['costs' => 'Seluruh biaya harus diperiksa dan difinalisasi Finance sebelum Closing Job.']);
            }
            $rows = $job->costs()->orderBy('id')->lockForUpdate()->get();
            $summary = $this->costs->summary($job)['final'];
            // Invoice yang sudah diterbitkan menjadi sumber PPN saat Closing Job.
            // Dengan demikian Finance tidak memasukkan ulang PPN pada tahap closing.
            $invoice = $job->invoice()->lockForUpdate()->first();
            $tax = Money::decimal($invoice?->tax ?? ($data['tax'] ?? 0));
            $stampDuty = Money::decimal($invoice?->stamp_duty ?? ($data['stamp_duty'] ?? 0));
            $total = Money::decimal($summary['subtotal'])->plus($tax)->plus($stampDuty);
            Money::checked($total);
            if ($total->isZero()) {
                throw ValidationException::withMessages(['total' => 'Total invoice harus lebih besar dari nol.']);
            }
            $flow = $this->flowAccountKeys($job);
            $maps = $this->journals->mapped(array_merge(['temporary', 'provision_wip', 'vendor_payable', 'temporary_receivable', 'agent_receivable', 'agent_payable', 'receivable', 'tax_payable'], array_values($flow)));
            [$currency, $rate] = $this->invoiceCurrencyAndRate($rows);
            $snapshot = JobClosingSnapshot::create(['job_id' => $job->id, 'closing_date' => $data['closing_date'], 'customer_snapshot' => $job->quotation_snapshot['customer'],
                'currency' => $currency, 'exchange_rate' => $rate->toScale(2, RoundingMode::HalfUp),
                'costs_snapshot' => $rows->map(fn ($c) => $c->only(['number', 'description', 'type', 'cost_category', 'cost_date', 'quantity', 'unit', 'unit_cost', 'unit_price', 'total_cost', 'total_price', 'pph23_amount']))->all(),
                'total_temporary' => $summary['temporary'], 'total_provision_cost' => $summary['provision_cost'], 'total_provision_sell' => $summary['provision_sell'],
                'subtotal' => $summary['subtotal'], 'tax' => (string) $tax, 'total' => Money::checked($total), 'profit' => $summary['profit'], 'margin' => $summary['margin'],
                'funding_account_id' => null, 'closed_by' => $actor->id, 'closed_at' => now()]);

            if ($invoice) {
                $invoice->job_closing_snapshot_id = $snapshot->id;
                $invoice->save();
            } else {
                $invoice = Invoice::create(['number' => $this->numbers->next('inv'), 'job_id' => $job->id, 'job_closing_snapshot_id' => $snapshot->id, 'customer_id' => $job->customer_id,
                    'customer_snapshot' => $job->quotation_snapshot['customer'], 'invoice_date' => $data['closing_date'], 'due_date' => $data['due_date'], 'status' => 'issued',
                    'currency' => $currency, 'exchange_rate' => $rate->toScale(2, RoundingMode::HalfUp),
                    'subtotal' => $summary['subtotal'], 'tax' => (string) $tax, 'stamp_duty' => (string) $stampDuty, 'total' => Money::checked($total), 'paid_amount' => '0.00', 'balance' => Money::checked($total), 'created_by' => $actor->id, 'issued_at' => now()]);
                foreach ($rows as $i => $cost) {
                    $invoice->items()->create($this->invoiceItemData($cost, $i + 1));
                }
            }

            $alreadyJournalizedCostIds = Journal::where('source_type', \App\Models\JobCost::class)
                ->whereIn('source_id', $rows->pluck('id'))
                ->whereNull('reversal_of_id')
                ->whereNull('reversed_at')
                ->pluck('source_id');
            $uncapitalizedRows = $rows->whereNotIn('id', $alreadyJournalizedCostIds);
            $capitalizedTemporary = Money::decimal((string) $uncapitalizedRows->where('type', 'temporary')->sum('total_cost'));
            $capitalizedProvision = Money::decimal((string) $uncapitalizedRows->where('type', '!=', 'temporary')->sum('total_cost'));
            $fund = $capitalizedTemporary->plus($capitalizedProvision);
            if (! $fund->isZero()) {
                $this->journals->post('job_cost_capitalization', Job::class, $job->id, $data['closing_date'], 'Kapitalisasi biaya '.$job->number, [
                    ['account_id' => $maps['temporary']->id, 'description' => 'Temporary '.$job->number, 'debit' => $capitalizedTemporary, 'credit' => 0],
                    ['account_id' => $maps['provision_wip']->id, 'description' => 'Provision WIP '.$job->number, 'debit' => $capitalizedProvision, 'credit' => 0],
                    ['account_id' => $maps['vendor_payable']->id, 'description' => 'Hutang biaya job '.$job->number, 'debit' => 0, 'credit' => (string) $fund]], $actor);
            }

            $temporary = Money::decimal($summary['temporary']);
            $provisionSell = Money::decimal($summary['provision_sell']);
            $provisionRows = $rows->where('type', 'provision');
            $invoiceRevenue = $provisionSell->plus($stampDuty);
            $customerReceivable = $invoiceRevenue->plus($tax);

            $closingEntries = [];
            // If invoice was already created earlier, receivable, revenue, tax and temporary WIP were already posted in job_invoice
            $hasInvoiceJournal = Journal::where('source_type', Job::class)->where('source_id', $job->id)->whereIn('type', ['job_invoice', 'job_invoice_amendment'])->whereNull('reversal_of_id')->whereNull('reversed_at')->exists();
            if (! $hasInvoiceJournal) {
                if ($customerReceivable->isPositive()) {
                    $closingEntries[] = ['account_id' => $customerAccounts['receivable']->id, 'description' => 'Piutang '.$invoice->number, 'debit' => (string) $customerReceivable, 'credit' => 0];
                }
                if ($temporary->isPositive()) {
                    $closingEntries[] = ['account_id' => $customerAccounts['temporary']->id, 'description' => 'Piutang Temporary '.$invoice->number, 'debit' => (string) $temporary, 'credit' => 0];
                }
                if ($temporary->isPositive()) {
                    $closingEntries[] = ['account_id' => $maps['temporary']->id, 'description' => 'Reklasifikasi temporary', 'debit' => 0, 'credit' => (string) $temporary];
                }
                foreach ($provisionRows as $cost) {
                    $sellingValue = Money::decimal($cost->total_price);
                    if ($sellingValue->isZero()) {
                        continue;
                    }
                    $closingEntries[] = ['account_id' => $maps[$flow['revenue_unclosed']]->id, 'description' => 'Pendapatan Unclose · '.strtoupper($cost->description).' · '.$job->number, 'debit' => 0, 'credit' => (string) $sellingValue];
                }
                if ($stampDuty->isPositive()) {
                    $closingEntries[] = ['account_id' => $maps[$flow['revenue_unclosed']]->id, 'description' => 'Tagihan Materai · '.$job->number, 'debit' => 0, 'credit' => (string) $stampDuty];
                }
                if ($tax->isPositive()) {
                    $closingEntries[] = ['account_id' => $maps['tax_payable']->id, 'description' => 'Pajak keluaran', 'debit' => 0, 'credit' => (string) $tax];
                }
            }
            $provisionCost = Money::decimal($summary['provision_cost']);
            if (! $hasInvoiceJournal && $provisionCost->isPositive()) {
                foreach ($provisionRows as $cost) {
                    $costValue = Money::decimal($cost->total_cost);
                    if ($costValue->isPositive()) {
                        $closingEntries[] = ['account_id' => $maps[$flow['cogs_unclosed']]->id, 'description' => 'HPP Unclose · '.strtoupper($cost->description).' · '.$job->number, 'debit' => (string) $costValue, 'credit' => 0];
                    }
                }
                $closingEntries[] = ['account_id' => $maps['provision_wip']->id, 'description' => 'Reklasifikasi Provisional Payment '.$invoice->number, 'debit' => 0, 'credit' => (string) $provisionCost];
            }
            if ($provisionCost->isPositive()) {
                $closingEntries[] = ['account_id' => $maps[$flow['cogs_closed']]->id, 'description' => 'HPP Close '.$job->number, 'debit' => (string) $provisionCost, 'credit' => 0];
                $closingEntries[] = ['account_id' => $maps[$flow['cogs_unclosed']]->id, 'description' => 'Reklasifikasi HPP Unclose '.$job->number, 'debit' => 0, 'credit' => (string) $provisionCost];
            }
            if ($invoiceRevenue->isPositive()) {
                $closingEntries[] = ['account_id' => $maps[$flow['revenue_unclosed']]->id, 'description' => 'Reklasifikasi Pendapatan Unclose '.$job->number, 'debit' => (string) $invoiceRevenue, 'credit' => 0];
                $closingEntries[] = ['account_id' => $maps[$flow['revenue_closed']]->id, 'description' => 'Pendapatan Close '.$job->number, 'debit' => 0, 'credit' => (string) $invoiceRevenue];
            }

            if (! empty($closingEntries)) {
                $this->journals->post('job_closing', Job::class, $job->id, $data['closing_date'], 'Closing '.$job->number, $closingEntries, $actor);
            }

            $categoryTotals = $rows->groupBy(fn ($cost) => $cost->cost_category ?: ($cost->type === 'temporary' ? 'reimbursement' : 'payment_request'));
            $debitNotes = $categoryTotals->get('debit_note', collect());
            $debitNote = Money::decimal((string) $debitNotes->sum('total_price'));
            if (! $debitNote->isZero()) {
                $entries = [];
                foreach ($debitNotes as $cost) {
                    $amount = Money::decimal($cost->total_price);
                    if ($amount->isZero()) continue;
                    $agent = $cost->vendor_id ? Vendor::find($cost->vendor_id) : null;
                    $account = $agent && in_array($agent->type, ['international_agent', 'national_agent'], true)
                        ? $this->partyAccounts->agentReceivable($agent)
                        : $maps['agent_receivable'];
                    $entries[] = ['account_id' => $account->id, 'description' => 'Piutang agent '.$cost->number.' · '.$job->number, 'debit' => (string) $amount, 'credit' => 0];
                }
                $entries[] = ['account_id' => $customerAccounts['receivable']->id, 'description' => 'Reklasifikasi debit note '.$job->number, 'debit' => 0, 'credit' => (string) $debitNote];
                $this->journals->post('agent_receivable_reclass', Job::class, $job->id, $data['closing_date'], 'Piutang Agent '.$job->number, $entries, $actor);
            }
            // Hutang Vendor/Agent untuk seluruh biaya, termasuk Credit Note,
            // sudah dibentuk ketika biaya di-Finance Confirm. Closing hanya
            // memindahkan HPP dan pendapatan, sehingga tidak boleh membuat
            // Agent Payable Recognition kedua.
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

    public function reopen(Job $job, array $data, User $actor): Job
    {
        return DB::transaction(function () use ($job, $data, $actor) {
            $job = Job::lockForUpdate()->findOrFail($job->id);
            Gate::forUser($actor)->authorize('jobs.close');
            $this->master->checkVersion($job, $data);

            if ($job->status !== 'closed') {
                throw ValidationException::withMessages(['job' => 'Hanya job dengan status Closed yang dapat dibuka kembali.']);
            }

            $invoice = $job->invoice()->lockForUpdate()->first();
            if ($invoice) {
                if ($invoice->payments()->exists() || (float) $invoice->paid_amount > 0) {
                    throw ValidationException::withMessages(['job' => 'Job tidak dapat dibuka kembali karena invoice telah memiliki riwayat pembayaran. Hapus atau batalkan pembayaran terlebih dahulu.']);
                }
            }

            $journals = Journal::where('source_type', Job::class)
                ->where('source_id', $job->id)
                ->whereIn('type', ['job_closing', 'job_cost_capitalization', 'job_invoice', 'job_invoice_amendment', 'agent_receivable_reclass', 'agent_payable_recognition'])
                ->whereNull('reversal_of_id')
                ->get();

            $reversalDate = now()->toDateString();
            $reason = ! empty($data['reason']) ? $data['reason'] : 'Undo / Buka Kembali Job '.$job->number;

            foreach ($journals as $journal) {
                if (! $journal->reversal()->exists()) {
                    $revDate = $reversalDate >= $journal->journal_date->format('Y-m-d')
                        ? $reversalDate
                        : $journal->journal_date->format('Y-m-d');

                    $this->journals->reverse($journal, [
                        'reversal_date' => $revDate,
                        'reason' => $reason,
                        'lock_version' => $journal->lock_version,
                    ], $actor);
                }
            }

            if ($invoice) {
                $invoice->items()->delete();
                $invoice->delete();
            }

            $snapshot = $job->closingSnapshot()->lockForUpdate()->first();
            if ($snapshot) {
                $snapshot->delete();
            }

            $job->status = 'open';
            $job->closed_by = null;
            $job->closed_at = null;
            $job->updated_by = $actor->id;
            $job->lock_version++;
            $job->save();

            $job->statusHistory()->create([
                'from_status' => 'closed',
                'to_status' => 'open',
                'note' => 'Buka Kembali Job: '.$reason,
                'user_id' => $actor->id,
                'created_at' => now(),
            ]);

            $this->master->log($actor, 'job.reopened', 'Membuka kembali job '.$job->number, [
                'module' => 'job_closing',
                'record_id' => $job->id,
                'reason' => $reason,
            ]);

            return $job;
        }, 3);
    }

    public function createInvoiceForJob(Job $job, array $data, User $actor): Invoice
    {
        return DB::transaction(function () use ($job, $data, $actor) {
            $job = Job::lockForUpdate()->findOrFail($job->id);
            Gate::forUser($actor)->authorize('invoices.manage');
            $this->master->checkVersion($job, $data);
            if ($job->status !== 'open') {
                throw ValidationException::withMessages(['job' => 'Hanya job Open yang dapat dibuatkan invoice pra-closing.']);
            }
            if ($job->invoice()->exists()) {
                throw ValidationException::withMessages(['job' => 'Job sudah memiliki invoice: '.$job->invoice->number]);
            }
            if (! $job->customer()->withTrashed()->exists()) {
                throw ValidationException::withMessages(['customer' => 'Customer job tidak tersedia.']);
            }
            $customerAccounts = $this->partyAccounts->customerAccounts($job->customer()->withTrashed()->firstOrFail());
            if ($job->costs()->count() === 0 && ! empty($job->quotation_snapshot['items'])) {
                app(JobService::class)->seedQuotationCharges($job, $actor);
            }
            if ($job->costs()->count() === 0) {
                throw ValidationException::withMessages(['costs' => 'Job belum memiliki detail biaya untuk invoice.']);
            }
            $rows = $job->costs()->orderBy('id')->get();
            $summary = $this->costs->summary($job)['all'];
            $tax = Money::decimal($data['tax'] ?? 0);
            $stampDuty = Money::decimal($data['stamp_duty'] ?? 0);
            $total = Money::decimal($summary['subtotal'])->plus($tax)->plus($stampDuty);
            Money::checked($total);
            if ($total->isZero()) {
                throw ValidationException::withMessages(['total' => 'Total invoice harus lebih besar dari nol.']);
            }
            $flow = $this->flowAccountKeys($job);
            $maps = $this->journals->mapped(array_merge(['temporary', 'provision_wip', 'vendor_payable', 'temporary_receivable', 'agent_receivable', 'agent_payable', 'receivable', 'tax_payable'], array_values($flow)));
            [$currency, $rate] = $this->invoiceCurrencyAndRate($rows);

            $invoiceDate = $data['invoice_date'] ?? today()->toDateString();
            $dueDate = $data['due_date'] ?? today()->addDays(30)->toDateString();

            $invoice = Invoice::create([
                'number' => $this->numbers->next('inv'),
                'job_id' => $job->id,
                'job_closing_snapshot_id' => null,
                'customer_id' => $job->customer_id,
                'customer_snapshot' => $job->quotation_snapshot['customer'],
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'status' => 'issued',
                'currency' => $currency,
                'exchange_rate' => $rate->toScale(2, RoundingMode::HalfUp),
                'subtotal' => $summary['subtotal'],
                'tax' => (string) $tax,
                'stamp_duty' => (string) $stampDuty,
                'total' => Money::checked($total),
                'paid_amount' => '0.00',
                'balance' => Money::checked($total),
                'created_by' => $actor->id,
                'issued_at' => now(),
            ]);

            foreach ($rows as $i => $cost) {
                $invoice->items()->create($this->invoiceItemData($cost, $i + 1));
            }

            $temporary = Money::decimal($summary['temporary']);
            $provisionSell = Money::decimal($summary['provision_sell']);
            $invoiceRevenue = $provisionSell->plus($stampDuty);
            $customerReceivable = $invoiceRevenue->plus($tax);

            $invoiceEntries = [];
            if ($customerReceivable->isPositive()) {
                $invoiceEntries[] = ['account_id' => $customerAccounts['receivable']->id, 'description' => 'Piutang '.$invoice->number, 'debit' => (string) $customerReceivable, 'credit' => 0];
            }
            if ($temporary->isPositive()) {
                $invoiceEntries[] = ['account_id' => $customerAccounts['temporary']->id, 'description' => 'Piutang Temporary '.$invoice->number, 'debit' => (string) $temporary, 'credit' => 0];
            }
            if ($temporary->isPositive()) {
                $invoiceEntries[] = ['account_id' => $maps['temporary']->id, 'description' => 'Reklasifikasi temporary '.$invoice->number, 'debit' => 0, 'credit' => (string) $temporary];
            }
            foreach ($rows->where('type', 'provision') as $cost) {
                $sellingValue = Money::decimal($cost->total_price);
                if ($sellingValue->isPositive()) {
                    $invoiceEntries[] = ['account_id' => $maps[$flow['revenue_unclosed']]->id, 'description' => 'Pendapatan Unclose · '.strtoupper($cost->description).' · '.$job->number, 'debit' => 0, 'credit' => (string) $sellingValue];
                }
                $costValue = Money::decimal($cost->total_cost);
                if ($costValue->isPositive()) {
                    $invoiceEntries[] = ['account_id' => $maps[$flow['cogs_unclosed']]->id, 'description' => 'HPP Unclose · '.strtoupper($cost->description).' · '.$job->number, 'debit' => (string) $costValue, 'credit' => 0];
                }
            }
            if ($stampDuty->isPositive()) {
                $invoiceEntries[] = ['account_id' => $maps[$flow['revenue_unclosed']]->id, 'description' => 'Tagihan Materai · '.$job->number, 'debit' => 0, 'credit' => (string) $stampDuty];
            }
            $provisionCost = Money::decimal($summary['provision_cost']);
            if ($provisionCost->isPositive()) {
                $invoiceEntries[] = ['account_id' => $maps['provision_wip']->id, 'description' => 'Reklasifikasi Provisional Payment '.$invoice->number, 'debit' => 0, 'credit' => (string) $provisionCost];
            }
            if ($tax->isPositive()) {
                $invoiceEntries[] = ['account_id' => $maps['tax_payable']->id, 'description' => 'Pajak keluaran '.$invoice->number, 'debit' => 0, 'credit' => (string) $tax];
            }

            $this->journals->post('job_invoice', Job::class, $job->id, $invoiceDate, 'Invoice '.$invoice->number.' untuk '.$job->number, $invoiceEntries, $actor);

            $this->master->log($actor, 'invoice.created', $job->number.' → '.$invoice->number, [
                'module' => 'invoice',
                'record_id' => $invoice->id,
                'after' => [
                    'invoice' => $invoice->number,
                    'subtotal' => $summary['subtotal'],
                    'tax' => (string) $tax,
                    'total' => $invoice->total,
                ],
            ]);

            return $invoice;
        }, 3);
    }

    /** Mapping akun Close/Unclose mengikuti jenis pekerjaan dalam referensi Neraca dan Laba Rugi. */
    private function flowAccountKeys(Job $job): array
    {
        $group = match (true) {
            in_array((string) $job->service_type, ['imp_sea', 'imp_air'], true) => 'import',
            in_array((string) $job->service_type, ['exp_sea', 'exp_air', 'sea', 'air'], true) => 'export',
            default => 'domestic',
        };
        return [
            'revenue_closed' => 'revenue_'.$group.'_closed',
            'revenue_unclosed' => 'revenue_'.$group.'_unclosed',
            'cogs_closed' => 'cogs_'.$group.'_closed',
            'cogs_unclosed' => 'cogs_'.$group.'_unclosed',
        ];
    }

    /** Simpan currency dan harga sumber per baris, sementara nilai buku tetap IDR. */
    private function invoiceItemData(\App\Models\JobCost $cost, int $position): array
    {
        $currency = strtoupper((string) ($cost->currency ?: 'IDR'));
        $isTemporary = $cost->type === 'temporary';
        $foreignUnitPrice = $isTemporary ? $cost->foreign_unit_cost : $cost->foreign_unit_price;

        // Biaya yang dibuat sebelum kolom foreign tersedia menyimpan harga
        // sumber pada unit_cost/unit_price. Pertahankan sebagai fallback.
        if ($foreignUnitPrice === null) {
            $foreignUnitPrice = $isTemporary ? $cost->unit_cost : $cost->unit_price;
        }

        return [
            'position' => $position,
            'description' => $cost->description,
            'type' => $cost->type,
            'quantity' => $cost->quantity,
            'unit' => $cost->unit,
            'currency' => $currency,
            'exchange_rate' => $cost->price_exchange_rate ?: $cost->exchange_rate ?: 1,
            'foreign_unit_price' => $foreignUnitPrice,
            'unit_price' => $isTemporary ? $cost->unit_cost : $cost->unit_price,
            'amount' => $isTemporary ? $cost->total_cost : $cost->total_price,
        ];
    }

    /**
     * Invoice tidak menerima input kurs ulang. Kurs header mengikuti Kurs Tagih
     * bila seluruh baris memakai currency dan kurs yang sama. Invoice campuran
     * dicatat dalam IDR, sementara kurs tagih tetap tersimpan pada tiap baris.
     */
    private function invoiceCurrencyAndRate(\Illuminate\Support\Collection $rows): array
    {
        $foreignRows = $rows->filter(fn ($cost) => strtoupper((string) ($cost->currency ?: 'IDR')) !== 'IDR');
        if ($foreignRows->isEmpty() || $foreignRows->count() !== $rows->count()) {
            return ['IDR', Money::decimal('1')];
        }

        $currencies = $foreignRows->map(fn ($cost) => strtoupper((string) $cost->currency))->unique();
        $rates = $foreignRows->map(fn ($cost) => (string) ($cost->price_exchange_rate ?: $cost->exchange_rate ?: 1))->unique();
        if ($currencies->count() !== 1 || $rates->count() !== 1) {
            return ['IDR', Money::decimal('1')];
        }

        return [$currencies->first(), Money::decimal($rates->first())];
    }

}
