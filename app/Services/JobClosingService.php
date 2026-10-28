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
    public const CLOSING_TRANSFER = 'job_closing_transfer';
    public const LEGACY_REOPEN_TRANSFER = 'job_reopen_legacy_transfer';
    public const CLOSING_PROVISION = 'job_closing_provision';
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
            if ($job->costs()->where('status', '!=', 'final')->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['costs' => 'Seluruh biaya harus berstatus Finance Confirmed sebelum Closing Job.']);
            }
            $rows = $job->costs()->orderBy('id')->lockForUpdate()->get();
            // Closing hanya boleh dilakukan setelah setiap biaya diperiksa
            // dan dikonfirmasi Finance.
            $summary = $this->costs->summary($job)['all'];
            $invoiceRows = $rows->filter(fn (\App\Models\JobCost $cost) => $cost->type === 'temporary' || Money::decimal($cost->total_price)->isPositive())->values();
            // Invoice yang sudah diterbitkan menjadi sumber PPN saat Closing Job.
            // Dengan demikian Finance tidak memasukkan ulang PPN pada tahap closing.
            $invoice = $job->invoice()->lockForUpdate()->first();
            if ($invoice && $invoice->status === 'draft') {
                throw ValidationException::withMessages(['invoice' => 'Finance Manager wajib melakukan Finalisasi Invoice sebelum Closing Job.']);
            }
            $tax = Money::decimal($invoice?->tax ?? ($data['tax'] ?? 0));
            $stampDuty = Money::decimal($invoice?->stamp_duty ?? ($data['stamp_duty'] ?? 0));
            $total = Money::decimal($summary['subtotal'])->plus($tax)->plus($stampDuty);
            Money::checked($total);
            if ($total->isZero()) {
                throw ValidationException::withMessages(['total' => 'Total invoice harus lebih besar dari nol.']);
            }
            $flow = $this->flowAccountKeys($job);
            $maps = $this->journals->mapped(array_merge(['temporary', 'provision_wip', 'vendor_payable', 'temporary_receivable', 'agent_receivable', 'agent_payable', 'receivable', 'tax_payable', 'stamp_duty_expense'], array_values($flow)));
            [$currency, $rate] = $this->invoiceCurrencyAndRate($invoiceRows);
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
                foreach ($invoiceRows as $i => $cost) {
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
            $existingCapitalization = Journal::with('entries')->where('source_type', Job::class)->where('source_id', $job->id)
                ->where('type', 'job_cost_capitalization')->whereNull('reversed_at')->whereNull('reversal_of_id')->lockForUpdate()->first();
            if ($existingCapitalization && ! $fund->isZero()) {
                $oldTemporary = Money::decimal(0);
                $oldProvision = Money::decimal(0);
                foreach ($existingCapitalization->entries as $entry) {
                    if ($entry->chart_of_account_id === $maps['temporary']->id) $oldTemporary = $oldTemporary->plus($entry->debit)->minus($entry->credit);
                    if ($entry->chart_of_account_id === $maps['provision_wip']->id) $oldProvision = $oldProvision->plus($entry->debit)->minus($entry->credit);
                }
                if (! $oldTemporary->isEqualTo($capitalizedTemporary) || ! $oldProvision->isEqualTo($capitalizedProvision)) {
                    throw ValidationException::withMessages(['journal' => 'Kapitalisasi biaya lama berbeda dari biaya Job. Periksa penyesuaian kapitalisasi sebelum closing ulang.']);
                }
                $fund = Money::decimal(0);
            }
            if (! $fund->isZero()) {
                $this->journals->post('job_cost_capitalization', Job::class, $job->id, $data['closing_date'], 'Kapitalisasi biaya '.$job->number, [
                    ['account_id' => $maps['temporary']->id, 'description' => 'Temporary '.$job->number, 'debit' => $capitalizedTemporary, 'credit' => 0],
                    ['account_id' => $maps['provision_wip']->id, 'description' => 'Provision WIP '.$job->number, 'debit' => $capitalizedProvision, 'credit' => 0],
                    ['account_id' => $maps['vendor_payable']->id, 'description' => 'Hutang biaya job '.$job->number, 'debit' => 0, 'credit' => (string) $fund]], $actor);
            }

            $temporary = Money::decimal($summary['temporary']);
            $provisionSell = Money::decimal($summary['provision_sell']);
            $provisionRows = $rows->where('type', 'provision');
            $invoiceRevenue = $provisionSell;
            $customerReceivable = $provisionSell->plus($stampDuty)->plus($tax);

            $closingEntries = [];
            // If invoice was already created earlier, receivable, revenue, tax and temporary WIP were already posted in job_invoice
            $hasInvoiceJournal = Journal::where('source_type', Job::class)->where('source_id', $job->id)
                ->where(fn ($q) => $q->whereIn('type', ['job_invoice', 'job_invoice_amendment'])
                    ->orWhere(fn ($legacy) => $legacy->where('type', 'job_closing')->whereHas('entries', fn ($e) => $e->where('description', 'like', 'Piutang%'))))
                ->whereNull('reversal_of_id')->whereNull('reversed_at')->exists();
            if (! $hasInvoiceJournal) {
                $this->postInvoiceJournal($job, $invoice, $rows, $summary, $tax, $stampDuty, $actor);
            }
            $provisionCost = Money::decimal($summary['provision_cost']);
            // Invoice dan pembayaran dapat sudah memindahkan sebagian modal ke
            // HPP Unclosed. Saat closing, pindahkan hanya sisa yang masih di
            // Provisi sebelum seluruh HPP direklasifikasi ke HPP Closed.
            $hppAccountId = $maps[$flow['cogs_unclosed']]->id;
            $recognizedHpp = Money::decimal(0);
            $hppJournals = Journal::with(['entries' => fn ($q) => $q->where('chart_of_account_id', $hppAccountId)])
                ->whereNull('reversed_at')->whereNull('reversal_of_id')
                ->where(function ($q) use ($job, $rows) {
                    $q->where(fn ($source) => $source->where('source_type', Job::class)->where('source_id', $job->id))
                        ->orWhere(fn ($source) => $source->where('source_type', \App\Models\JobCost::class)->whereIn('source_id', $rows->pluck('id')));
                })->get();
            foreach ($hppJournals as $hppJournal) {
                foreach ($hppJournal->entries as $entry) {
                    $recognizedHpp = $recognizedHpp->plus($entry->debit)->minus($entry->credit);
                }
            }
            $pendingProvision = $provisionCost->minus($recognizedHpp);
            if ($pendingProvision->isNegative()) {
                throw ValidationException::withMessages(['journal' => 'HPP Unclosed job melebihi total modal. Periksa jurnal HPP sebelum Closing Job.']);
            }
            // Pengakuan invoice/Provisi dipisahkan dari transfer Closed agar
            // membuka kembali Job tidak membatalkan piutang, pajak, atau WIP.
            if ($pendingProvision->isPositive()) {
                $remaining = $pendingProvision;
                foreach ($provisionRows->reverse() as $cost) {
                    $recognized = Money::decimal(0);
                    foreach ($hppJournals as $journal) {
                        foreach ($journal->entries as $entry) {
                            if (($journal->source_type === \App\Models\JobCost::class && $journal->source_id === $cost->id)
                                || str_contains((string) $entry->description, '· '.$cost->number.' ·')
                                || str_ends_with((string) $entry->description, '· '.$cost->number)) {
                                $recognized = $recognized->plus($entry->debit)->minus($entry->credit);
                            }
                        }
                    }
                    $amount = Money::decimal($cost->total_cost)->minus($recognized);
                    if (! $amount->isPositive()) continue;
                    if ($amount->isGreaterThan($remaining)) $amount = $remaining;
                    $this->journals->post(self::CLOSING_PROVISION, \App\Models\JobCost::class, $cost->id, $data['closing_date'], 'Kapitalisasi Provisi saat closing '.$cost->number, [
                        ['account_id' => $hppAccountId, 'description' => 'HPP Unclose · Sisa Provisi saat closing · '.$cost->number.' · '.$job->number, 'debit' => (string) $amount, 'credit' => 0],
                        ['account_id' => $maps['provision_wip']->id, 'description' => 'Reklasifikasi sisa Provisi saat closing '.$cost->number, 'debit' => 0, 'credit' => (string) $amount],
                    ], $actor);
                    $remaining = $remaining->minus($amount);
                    if ($remaining->isZero()) break;
                }
                if (! $remaining->isZero()) throw ValidationException::withMessages(['journal' => 'Sisa Provisi tidak dapat ditelusuri per biaya. Periksa rincian HPP Job.']);
            }
            $closingEntries = [];
            if ($provisionCost->isPositive()) {
                $closingEntries[] = ['account_id' => $maps[$flow['cogs_closed']]->id, 'description' => 'HPP Close '.$job->number, 'debit' => (string) $provisionCost, 'credit' => 0];
                $closingEntries[] = ['account_id' => $maps[$flow['cogs_unclosed']]->id, 'description' => 'Reklasifikasi HPP Unclose '.$job->number, 'debit' => 0, 'credit' => (string) $provisionCost];
            }
            if ($invoiceRevenue->isPositive()) {
                $closingEntries[] = ['account_id' => $maps[$flow['revenue_unclosed']]->id, 'description' => 'Reklasifikasi Pendapatan Unclose '.$job->number, 'debit' => (string) $invoiceRevenue, 'credit' => 0];
                $closingEntries[] = ['account_id' => $maps[$flow['revenue_closed']]->id, 'description' => 'Pendapatan Close '.$job->number, 'debit' => 0, 'credit' => (string) $invoiceRevenue];
            }

            if (! empty($closingEntries)) {
            if ($closingEntries) {
                $this->journals->post(self::CLOSING_TRANSFER, Job::class, $job->id, $data['closing_date'], 'Closing '.$job->number, $closingEntries, $actor);
            }
            }

            $categoryTotals = $rows->groupBy(fn ($cost) => $cost->cost_category ?: ($cost->type === 'temporary' ? 'reimbursement' : 'payment_request'));
            $debitNotes = $categoryTotals->get('debit_note', collect());
            $debitNote = Money::decimal((string) $debitNotes->sum('total_price'));
            if (! $debitNote->isZero() && ! Journal::where('source_type', Job::class)->where('source_id', $job->id)
                ->where('type', 'agent_receivable_reclass')->whereNull('reversed_at')->whereNull('reversal_of_id')->exists()) {
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
            if ($invoice && ! Journal::where('source_type', Job::class)->where('source_id', $job->id)
                ->whereNull('reversed_at')->whereNull('reversal_of_id')
                ->where(fn ($q) => $q->whereIn('type', ['job_invoice', 'job_invoice_amendment'])
                    ->orWhere(fn ($legacy) => $legacy->where('type', 'job_closing')->whereHas('entries', fn ($e) => $e->where('description', 'like', 'Piutang%'))))
                ->exists()) {
                throw ValidationException::withMessages(['journal' => 'Jurnal pengakuan invoice tidak tersedia. Periksa jurnal invoice sebelum membuka kembali Job; invoice dan pembayaran tidak diubah.']);
            }
            if ($invoice) {
                $payments = $invoice->payments()->lockForUpdate()->get();
                $journalizedPayments = Journal::where('source_type', \App\Models\Payment::class)
                    ->whereIn('source_id', $payments->pluck('id'))->where('type', 'customer_payment')
                    ->whereNull('reversed_at')->whereNull('reversal_of_id')->pluck('source_id');
                if ((Money::decimal($invoice->paid_amount)->isPositive() && $payments->isEmpty())
                    || $payments->pluck('id')->diff($journalizedPayments)->isNotEmpty()) {
                    throw ValidationException::withMessages(['journal' => 'Jurnal pembayaran invoice tidak lengkap. Periksa pembayaran sebelum membuka kembali Job; tidak ada jurnal atau pembayaran yang diubah.']);
                }
            }

            $journals = Journal::where('source_type', Job::class)
                ->where('source_id', $job->id)
                ->where('type', self::CLOSING_TRANSFER)
                ->whereNull('reversal_of_id')
                ->whereNull('reversed_at')->lockForUpdate()->get();

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

            if ($journals->isEmpty()) {
                $this->reopenLegacyClosedBalances($job, $actor, $reason);
            }

            if ($invoice) {
                $invoice->job_closing_snapshot_id = null;
                $invoice->save();
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

    /** Jurnal closing lama bisa mencampur invoice, WIP dan transfer Closed.
     * Jangan reverse seluruh jurnal tersebut: pindahkan hanya saldo Closed.
     */
    private function reopenLegacyClosedBalances(Job $job, User $actor, string $reason): void
    {
        $flow = $this->flowAccountKeys($job);
        $maps = $this->journals->mapped(array_values($flow));
        $closedIds = [$maps[$flow['revenue_closed']]->id, $maps[$flow['cogs_closed']]->id];
        $entries = \App\Models\JournalEntry::whereIn('chart_of_account_id', $closedIds)
            ->whereHas('journal', fn ($q) => $q->where('status', 'posted')->where(function ($source) use ($job) {
                $source->where(fn ($s) => $s->where('source_type', Job::class)->where('source_id', $job->id))
                    ->orWhere(fn ($s) => $s->where('source_type', \App\Models\JobCost::class)->whereIn('source_id', $job->costs()->withTrashed()->select('id')));
            }))->get();
        $revenue = Money::decimal(0);
        $hpp = Money::decimal(0);
        foreach ($entries as $entry) {
            if ($entry->chart_of_account_id === $closedIds[0]) {
                $revenue = $revenue->plus($entry->credit)->minus($entry->debit);
            } else {
                $hpp = $hpp->plus($entry->debit)->minus($entry->credit);
            }
        }
        $snapshot = $job->closingSnapshot;
        if ($revenue->isNegative() || $hpp->isNegative()
            || ($snapshot && (! $revenue->isEqualTo(Money::decimal($snapshot->total_provision_sell))
                || ! $hpp->isEqualTo(Money::decimal($snapshot->total_provision_cost))))) {
            throw ValidationException::withMessages(['journal' => 'Saldo Pendapatan/HPP Closed tidak sesuai closing Job. Periksa jurnal sebelum membuka kembali.']);
        }
        $lines = [];
        if ($revenue->isPositive()) {
            $lines[] = ['account_id' => $closedIds[0], 'description' => 'Pendapatan Close dibuka kembali '.$job->number, 'debit' => (string) $revenue, 'credit' => 0];
            $lines[] = ['account_id' => $maps[$flow['revenue_unclosed']]->id, 'description' => 'Pendapatan Unclose dibuka kembali '.$job->number, 'debit' => 0, 'credit' => (string) $revenue];
        }
        if ($hpp->isPositive()) {
            $lines[] = ['account_id' => $closedIds[1], 'description' => 'HPP Close dibuka kembali '.$job->number, 'debit' => 0, 'credit' => (string) $hpp];
            $lines[] = ['account_id' => $maps[$flow['cogs_unclosed']]->id, 'description' => 'HPP Unclose dibuka kembali '.$job->number, 'debit' => (string) $hpp, 'credit' => 0];
        }
        if ($lines) {
            $this->journals->post(self::LEGACY_REOPEN_TRANSFER, Job::class, $job->id, today()->toDateString(), $reason, $lines, $actor);
        }
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
            if ($job->costs()->count() === 0 && ! empty($job->quotation_snapshot['items'])) {
                app(JobService::class)->seedQuotationCharges($job, $actor);
            }
            if ($job->costs()->count() === 0) {
                throw ValidationException::withMessages(['costs' => 'Job belum memiliki detail biaya untuk invoice.']);
            }
            $rows = $job->costs()->orderBy('id')->get();
            $summary = $this->costs->summary($job)['all'];
            $invoiceRows = $rows->filter(fn (\App\Models\JobCost $cost) => $cost->type === 'temporary' || Money::decimal($cost->total_price)->isPositive())->values();
            $tax = Money::decimal($data['tax'] ?? 0);
            $stampDuty = Money::decimal($data['stamp_duty'] ?? 0);
            $total = Money::decimal($summary['subtotal'])->plus($tax)->plus($stampDuty);
            Money::checked($total);
            if ($total->isZero()) {
                throw ValidationException::withMessages(['total' => 'Total invoice harus lebih besar dari nol.']);
            }
            [$currency, $rate] = $this->invoiceCurrencyAndRate($invoiceRows);

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
                'status' => 'draft',
                'currency' => $currency,
                'exchange_rate' => $rate->toScale(2, RoundingMode::HalfUp),
                'subtotal' => $summary['subtotal'],
                'tax' => (string) $tax,
                'stamp_duty' => (string) $stampDuty,
                'total' => Money::checked($total),
                'paid_amount' => '0.00',
                'balance' => Money::checked($total),
                'created_by' => $actor->id,
                'issued_at' => null,
            ]);

            foreach ($invoiceRows as $i => $cost) {
                $invoice->items()->create($this->invoiceItemData($cost, $i + 1));
            }

            $this->master->log($actor, 'invoice.draft_created', $job->number.' → '.$invoice->number, [
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

    public function updateInvoiceStampDuty(Invoice $invoice, array $data, User $actor): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $actor) {
            Gate::forUser($actor)->authorize('invoices.manage');
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $job = Job::lockForUpdate()->findOrFail($invoice->job_id);
            $this->master->checkVersion($job, ['lock_version' => $data['job_version'] ?? -1]);
            $this->master->checkVersion($invoice, $data);
            if ($job->status !== 'open' || ! in_array($invoice->status, ['draft', 'issued'], true)) {
                throw ValidationException::withMessages(['stamp_duty' => 'Materai hanya dapat diubah pada invoice Draft/Issued dari Job Open.']);
            }
            if ($invoice->payments()->exists() || ! Money::decimal($invoice->paid_amount)->isZero()) {
                throw ValidationException::withMessages(['stamp_duty' => 'Materai tidak dapat diubah karena invoice sudah memiliki pembayaran customer.']);
            }
            $stampDuty = Money::decimal($data['stamp_duty']);
            if ($stampDuty->isNegative() || $stampDuty->isGreaterThan('999999999.99')) {
                throw ValidationException::withMessages(['stamp_duty' => 'Tagihan materai harus antara Rp 0 dan Rp 999.999.999,99.']);
            }
            if ($stampDuty->isEqualTo(Money::decimal($invoice->stamp_duty ?? 0))) {
                return $invoice;
            }
            $total = Money::decimal($invoice->subtotal)->plus($invoice->tax)->plus($stampDuty);
            Money::checked($total);
            if (! $total->isPositive()) {
                throw ValidationException::withMessages(['stamp_duty' => 'Total invoice harus lebih besar dari nol.']);
            }
            $before = $invoice->only(['stamp_duty', 'total', 'balance', 'status']);
            $activeJournals = Journal::where('source_type', Job::class)->where('source_id', $job->id)
                ->whereIn('type', ['job_invoice', 'job_invoice_amendment'])
                ->whereNull('reversed_at')->whereNull('reversal_of_id')->lockForUpdate()->get();
            foreach ($activeJournals as $journal) {
                $this->journals->reverse($journal, [
                    'lock_version' => $journal->lock_version, 'reversal_date' => today()->toDateString(),
                    'reason' => 'Perubahan tagihan materai '.$invoice->number.'; menunggu finalisasi ulang Finance Manager',
                ], $actor);
            }
            $invoice->update([
                'stamp_duty' => (string) $stampDuty, 'total' => (string) $total, 'balance' => (string) $total,
                'status' => 'draft', 'issued_at' => null, 'lock_version' => $invoice->lock_version + 1,
            ]);
            $job->update(['updated_by' => $actor->id, 'lock_version' => $job->lock_version + 1]);
            $this->master->log($actor, 'invoice.stamp_duty_updated', 'Perubahan materai '.$invoice->number.' memerlukan finalisasi Finance Manager.', [
                'module' => 'invoice', 'record_id' => $invoice->id,
                'before' => $before, 'after' => $invoice->only(['stamp_duty', 'total', 'balance', 'status']),
            ]);

            return $invoice->fresh();
        }, 3);
    }

    public function finalizeInvoice(Invoice $invoice, array $data, User $actor): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $actor) {
            Gate::forUser($actor)->authorize('invoices.manage');
            if (! $actor->hasRole(['finance-manager', 'super-admin', 'admin'])) {
                throw ValidationException::withMessages(['invoice' => 'Hanya Finance Manager yang dapat melakukan finalisasi invoice.']);
            }

            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $job = Job::lockForUpdate()->findOrFail($invoice->job_id);
            $this->master->checkVersion($job, ['lock_version' => $data['job_version'] ?? -1]);
            $this->master->checkVersion($invoice, $data);
            if ($job->status !== 'open') {
                throw ValidationException::withMessages(['job' => 'Hanya invoice dari Job Open yang dapat difinalisasi.']);
            }
            if ($invoice->status !== 'draft') {
                throw ValidationException::withMessages(['invoice' => 'Invoice ini sudah difinalisasi atau sudah memiliki pembayaran.']);
            }
            if ($invoice->payments()->exists() || Money::decimal($invoice->paid_amount)->isPositive()) {
                throw ValidationException::withMessages(['invoice' => 'Invoice draft tidak boleh memiliki pembayaran.']);
            }

            $rows = $job->costs()->orderBy('id')->lockForUpdate()->get();
            $this->costs->assertInvoiceTemporaryRecognized($rows);
            $summary = $this->costs->summary($job)['all'];
            $invoiceRows = $rows->filter(fn (\App\Models\JobCost $cost) => $cost->type === 'temporary' || Money::decimal($cost->total_price)->isPositive())->values();
            $tax = Money::decimal($invoice->tax);
            $stampDuty = Money::decimal($invoice->stamp_duty ?? 0);
            $total = Money::decimal($summary['subtotal'])->plus($tax)->plus($stampDuty);
            Money::checked($total);
            if ($total->isZero()) {
                throw ValidationException::withMessages(['total' => 'Total invoice harus lebih besar dari nol.']);
            }

            [$currency, $rate] = $this->invoiceCurrencyAndRate($invoiceRows);
            $invoice->update([
                'currency' => $currency,
                'exchange_rate' => $rate->toScale(2, RoundingMode::HalfUp),
                'subtotal' => $summary['subtotal'],
                'total' => Money::checked($total),
                'balance' => Money::checked($total),
                'status' => 'issued',
                'issued_at' => now(),
                'lock_version' => $invoice->lock_version + 1,
            ]);
            $invoice->items()->delete();
            foreach ($invoiceRows as $position => $cost) {
                $invoice->items()->create($this->invoiceItemData($cost, $position + 1));
            }

            $this->postInvoiceJournal($job, $invoice, $rows, $summary, $tax, $stampDuty, $actor);
            $this->master->log($actor, 'invoice.finalized', $job->number.' → '.$invoice->number, [
                'module' => 'invoice',
                'record_id' => $invoice->id,
                'after' => ['invoice' => $invoice->number, 'total' => $invoice->total, 'status' => $invoice->status],
            ]);

            return $invoice->fresh();
        }, 3);
    }

    private function postInvoiceJournal(Job $job, Invoice $invoice, \Illuminate\Support\Collection $rows, array $summary, \Brick\Math\BigDecimal $tax, \Brick\Math\BigDecimal $stampDuty, User $actor): void
    {
        $customerAccounts = $this->partyAccounts->customerAccounts($job->customer()->withTrashed()->firstOrFail());
        $flow = $this->flowAccountKeys($job);
        $maps = $this->journals->mapped(array_merge(['temporary', 'provision_wip', 'vendor_payable', 'temporary_receivable', 'agent_receivable', 'agent_payable', 'receivable', 'tax_payable', 'stamp_duty_expense'], array_values($flow)));
        $temporary = Money::decimal($summary['temporary']);
        $provisionSell = Money::decimal($summary['provision_sell']);
        $invoiceRevenue = $provisionSell;
        $customerReceivable = $provisionSell->plus($stampDuty)->plus($tax);

        $invoiceEntries = [];
        if ($customerReceivable->isPositive()) {
            $invoiceEntries[] = ['account_id' => $customerAccounts['receivable']->id, 'description' => 'Piutang '.$invoice->number, 'debit' => (string) $customerReceivable, 'credit' => 0];
        }
        if ($temporary->isPositive()) {
            $invoiceEntries[] = ['account_id' => $customerAccounts['temporary']->id, 'description' => 'Piutang Temporary '.$invoice->number, 'debit' => (string) $temporary, 'credit' => 0];
            $invoiceEntries[] = ['account_id' => $maps['temporary']->id, 'description' => 'Reklasifikasi temporary '.$invoice->number, 'debit' => 0, 'credit' => (string) $temporary];
        }
        $hppCosts = $this->costs->invoiceHppCosts($job, $rows);
        foreach ($rows->where('type', 'provision') as $cost) {
            $sellingValue = Money::decimal($cost->total_price);
            if ($sellingValue->isPositive()) {
                $invoiceEntries[] = ['account_id' => $maps[$flow['revenue_unclosed']]->id, 'description' => 'Pendapatan Unclose · '.strtoupper($cost->description).' · '.$job->number, 'debit' => 0, 'credit' => (string) $sellingValue];
            }
            if (! $hppCosts->contains('id', $cost->id)) {
                continue;
            }
            $costValue = Money::decimal($cost->total_cost);
            if ($costValue->isPositive()) {
                $invoiceEntries[] = ['account_id' => $maps[$flow['cogs_unclosed']]->id, 'description' => 'HPP Unclose · '.$cost->number.' · '.strtoupper($cost->description).' · '.$job->number, 'debit' => (string) $costValue, 'credit' => 0];
            }
        }
        if ($stampDuty->isPositive()) {
            $invoiceEntries[] = ['account_id' => $maps['stamp_duty_expense']->id, 'description' => 'Materai ditagihkan · '.$job->number, 'debit' => 0, 'credit' => (string) $stampDuty];
        }
        // HPP Unclosed hanya dibentuk dari biaya yang sudah Final.
        $provisionCost = $hppCosts->reduce(
            fn ($total, $cost) => $total->plus(Money::decimal($cost->total_cost)),
            Money::decimal(0)
        );
        if ($provisionCost->isPositive()) {
            $invoiceEntries[] = ['account_id' => $maps['provision_wip']->id, 'description' => 'Reklasifikasi Provisional Payment '.$invoice->number, 'debit' => 0, 'credit' => (string) $provisionCost];
        }
        if ($tax->isPositive()) {
            $invoiceEntries[] = ['account_id' => $maps['tax_payable']->id, 'description' => 'Pajak keluaran '.$invoice->number, 'debit' => 0, 'credit' => (string) $tax];
        }

        $wasPreviouslyPosted = Journal::where('source_type', Job::class)
            ->where('source_id', $job->id)
            ->whereIn('type', ['job_invoice', 'job_invoice_amendment'])
            ->whereNull('reversal_of_id')->exists();
        // Keep the document date, but book a reapproval in the revision period
        // rather than introducing another WIP credit into an earlier period.
        $postingDate = $wasPreviouslyPosted ? today()->toDateString() : $invoice->invoice_date->format('Y-m-d');
        $this->journals->post('job_invoice', Job::class, $job->id, $postingDate, 'Invoice '.$invoice->number.' untuk '.$job->number, $invoiceEntries, $actor);
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
