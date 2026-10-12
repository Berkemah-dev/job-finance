<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Job;
use App\Models\JobClosingSnapshot;
use App\Models\JobCost;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Support\Money;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class FinancialReportService
{
    /**
     * Definisi standar akun & pengelompokan laporan PT Radix International Logistics.
     */
    public function radixDefinitions(): array
    {
        return [
            'aktiva_lancar' => [
                ['acc_no' => '10', 'name' => 'Kas', 'type' => 'asset', 'codes' => ['1101', '11100', '11101']],
                ['acc_no' => '11', 'name' => 'Bank BCA IDR', 'type' => 'asset', 'codes' => ['11121', '1102', '11120']],
                ['acc_no' => '11', 'name' => 'Bank Mandiri IDR', 'type' => 'asset', 'codes' => ['11122']],
                ['acc_no' => '11', 'name' => 'Bank BCA USD', 'type' => 'asset', 'codes' => ['11123']],
                ['acc_no' => '12', 'name' => 'Piutang Customer', 'type' => 'asset', 'codes' => ['1103', '11130']],
                ['acc_no' => '12', 'name' => 'Piutang Temporary', 'type' => 'asset', 'codes' => ['1106']],
                ['acc_no' => '12', 'name' => 'Piutang Agent', 'type' => 'asset', 'codes' => ['1107', '11140']],
                ['acc_no' => '12', 'name' => 'Piutang Karyawan', 'type' => 'asset', 'codes' => ['11160']],
                ['acc_no' => '12', 'name' => 'Piutang Komisaris', 'type' => 'asset', 'codes' => ['11170']],
                ['acc_no' => '12', 'name' => 'Piutang Lain - Lain', 'type' => 'asset', 'codes' => ['11150']],
                ['acc_no' => '13', 'tb_acc_no' => '12', 'name' => 'Provisional Payment', 'type' => 'asset', 'codes' => ['1105', '11181']],
                ['acc_no' => '13', 'tb_acc_no' => '12', 'name' => 'Temporary Payment', 'type' => 'asset', 'codes' => ['1104', '11180']],
                ['acc_no' => '13', 'name' => 'Jaminan', 'type' => 'asset', 'codes' => ['11190']],
                ['acc_no' => '14', 'name' => 'PPN Masukan', 'type' => 'asset', 'codes' => ['11191']],
                ['acc_no' => '14', 'name' => 'PPH 23 Dimuka', 'type' => 'asset', 'codes' => ['11192']],
                ['acc_no' => '14', 'name' => 'PPH 21 Dimuka', 'type' => 'asset', 'codes' => ['11193']],
            ],
            'aktiva_tetap' => [
                ['acc_no' => '15', 'name' => 'Peralatan', 'type' => 'asset', 'codes' => ['12100']],
                ['acc_no' => '15', 'name' => 'Akumulasi Peralatan', 'type' => 'asset', 'codes' => ['12200']],
                ['acc_no' => '15', 'name' => 'Kendaraan', 'type' => 'asset', 'codes' => ['12300']],
                ['acc_no' => '15', 'name' => 'Akumulasi Penyusutan Kendaraan', 'type' => 'asset', 'codes' => ['12400']],
            ],
            'kewajiban_lancar' => [
                ['acc_no' => '20', 'name' => 'Hutang Vendor', 'type' => 'liability', 'codes' => ['2101', '21000', '2102']],
                ['acc_no' => '20', 'name' => 'Hutang Agent', 'type' => 'liability', 'codes' => ['2103', '22000']],
                ['acc_no' => '20', 'name' => 'Hutang Komisi', 'type' => 'liability', 'codes' => ['22500']],
                ['acc_no' => '20', 'name' => 'Hutang Lain - Lain', 'type' => 'liability', 'codes' => ['25000', '25100', '24000']],
                ['acc_no' => '21', 'name' => 'Deposit Customer', 'type' => 'liability', 'codes' => ['23100', '23000']],
                ['acc_no' => '21', 'name' => 'Deposit Vendor / Agent', 'type' => 'liability', 'codes' => ['23200']],
                ['acc_no' => '22', 'name' => 'PPH 21 Hutang Pajak', 'type' => 'liability', 'codes' => ['26000']],
                ['acc_no' => '22', 'name' => 'PPH 23 Hutang Pajak', 'type' => 'liability', 'codes' => ['27000']],
                ['acc_no' => '22', 'name' => 'PPN Keluaran', 'type' => 'liability', 'codes' => ['28000']],
                ['acc_no' => '22', 'name' => 'Hutang PPN', 'type' => 'liability', 'codes' => ['29000']],
                ['acc_no' => '22', 'name' => 'PPH 4(2) Hutang Pajak', 'type' => 'liability', 'codes' => ['29100']],
            ],
            'modal' => [
                ['acc_no' => '30', 'name' => 'Modal Disetor', 'type' => 'equity', 'codes' => ['31000', '3101']],
                ['acc_no' => '31', 'name' => 'Laba (rugi) Ditahan', 'type' => 'equity', 'codes' => ['32000', '33000']],
                ['acc_no' => '32', 'name' => 'Laba (rugi) Tahun Berjalan', 'type' => 'equity', 'codes' => ['34000'], 'exclude_from_tb' => true],
            ],
            'pendapatan_closed' => [
                ['acc_no' => '40', 'name' => 'Pendapatan Impor', 'type' => 'revenue', 'codes' => ['41000', '4101']],
                ['acc_no' => '40', 'name' => 'Pendapatan Ekspor', 'type' => 'revenue', 'codes' => ['42000']],
                ['acc_no' => '40', 'name' => 'Pendapatan Domestik', 'type' => 'revenue', 'codes' => ['43000']],
            ],
            'pendapatan_unclosed' => [
                ['acc_no' => '41', 'name' => 'Pendapatan Impor Unclosed', 'type' => 'revenue', 'codes' => ['41100']],
                ['acc_no' => '41', 'name' => 'Pendapatan Ekspor Unclosed', 'type' => 'revenue', 'codes' => ['42100']],
                ['acc_no' => '41', 'name' => 'Pendapatan Domestik Unclosed', 'type' => 'revenue', 'codes' => ['43100']],
            ],
            'hpp_closed' => [
                ['acc_no' => '50', 'name' => 'HPP Impor', 'type' => 'cogs', 'codes' => ['51000', '5101']],
                ['acc_no' => '50', 'name' => 'HPP Ekspor', 'type' => 'cogs', 'codes' => ['52000']],
                ['acc_no' => '50', 'name' => 'HPP Domestik', 'type' => 'cogs', 'codes' => ['53000']],
                ['acc_no' => '50', 'name' => 'HPP Sewa', 'type' => 'cogs', 'codes' => ['5400']],
            ],
            'hpp_unclosed' => [
                ['acc_no' => '51', 'name' => 'HPP Impor Unlosed', 'type' => 'cogs', 'codes' => ['51100']],
                ['acc_no' => '51', 'name' => 'HPP Ekspor Unlosed', 'type' => 'cogs', 'codes' => ['52100']],
                ['acc_no' => '51', 'name' => 'HPP Domestik Unlosed', 'type' => 'cogs', 'codes' => ['53100']],
            ],
            'biaya_operasional' => [
                ['acc_no' => '60', 'name' => 'Biaya Gaji Karyawan / Training', 'type' => 'expense', 'codes' => ['62100']],
                ['acc_no' => '60', 'name' => 'Biaya Tunjangan Hari Raya (THR)', 'type' => 'expense', 'codes' => ['62110']],
                ['acc_no' => '60', 'name' => 'Biaya Asuransi BPJS', 'type' => 'expense', 'codes' => ['62400']],
                ['acc_no' => '60', 'name' => 'Biaya Perjalanan Dinas', 'type' => 'expense', 'codes' => ['62240']],
                ['acc_no' => '60', 'name' => 'Biaya Kesejahteraan Karyawan', 'type' => 'expense', 'codes' => ['62310']],
                ['acc_no' => '61', 'name' => 'Biaya Entertainment', 'type' => 'expense', 'codes' => ['62300', '62900']],
                ['acc_no' => '61', 'name' => 'Biaya Parkir & Bensin', 'type' => 'expense', 'codes' => ['62200']],
                ['acc_no' => '61', 'name' => 'Biaya Alat Tulis Kantor (ATK)', 'type' => 'expense', 'codes' => ['61100']],
                ['acc_no' => '61', 'name' => 'Biaya Perlengkapan Kantor', 'type' => 'expense', 'codes' => ['61200', '61000']],
                ['acc_no' => '61', 'name' => 'Biaya Percetakan', 'type' => 'expense', 'codes' => ['62260']],
                ['acc_no' => '61', 'name' => 'Biaya Pengiriman/Pengurusan Dokumen', 'type' => 'expense', 'codes' => ['62220']],
                ['acc_no' => '61', 'name' => 'Biaya Keamanan', 'type' => 'expense', 'codes' => ['62210']],
                ['acc_no' => '61', 'name' => 'Biaya Utilities', 'type' => 'expense', 'codes' => ['62250']],
                ['acc_no' => '61', 'name' => 'Biaya Pemeliharaan Software', 'type' => 'expense', 'codes' => ['62800']],
                ['acc_no' => '61', 'name' => 'Biaya Pemeliharaan Kantor', 'type' => 'expense', 'codes' => ['62810', '6101', '62000']],
                ['acc_no' => '61', 'name' => 'Biaya Keperluan Dapur', 'type' => 'expense', 'codes' => ['62230']],
                ['acc_no' => '61', 'name' => 'Biaya Penyusutan Peralatan', 'type' => 'expense', 'codes' => ['62600']],
                ['acc_no' => '61', 'name' => 'Biaya Izin Usaha', 'type' => 'expense', 'codes' => ['62500']],
                ['acc_no' => '61', 'name' => 'Biaya Pengurusan Dokumen', 'type' => 'expense', 'codes' => ['62270']],
                ['acc_no' => '61', 'name' => 'Biaya Konsultan / Seminar / Pelatihan', 'type' => 'expense', 'codes' => ['62700']],
            ],
            'pendapatan_lain' => [
                ['acc_no' => '70', 'name' => 'Pendapatan Bunga Bank', 'type' => 'revenue', 'codes' => ['71000']],
                ['acc_no' => '70', 'name' => 'Pendapatan Jasa Giro', 'type' => 'revenue', 'codes' => ['72000', '44000', '64400']],
            ],
            'biaya_lain' => [
                ['acc_no' => '80', 'name' => 'Biaya Provisi dan Adminstrasi Bank', 'type' => 'expense', 'codes' => ['64100', '64000']],
                ['acc_no' => '80', 'name' => 'Pajak Bunga', 'type' => 'expense', 'codes' => ['64200']],
                ['acc_no' => '80', 'name' => 'Biaya Transfer Antar Bank', 'type' => 'expense', 'codes' => ['64300']],
                ['acc_no' => '81', 'name' => 'Rugi Selisih Kurs', 'type' => 'expense', 'codes' => ['63200']],
                ['acc_no' => '81', 'name' => 'Selisih Pembulatan', 'type' => 'expense', 'codes' => ['63300', '63100', '63000']],
            ],
            'pajak_penghasilan' => [
                ['acc_no' => '82', 'name' => 'Pajak Penghasilan', 'type' => 'expense', 'codes' => ['65000', '65100', '65200', '65300', '65400', '65500'], 'exclude_from_tb' => true],
            ],
        ];
    }

    public function trialBalance(string $to, ?string $from = null): array
    {
        $from = $from ?: Carbon::parse($to)->startOfYear()->toDateString();

        $all = $this->accountBalancesWithOpening($from, $to)->map(function ($account) {
            $normalDebit = in_array($account->type, ['asset', 'cogs', 'expense'], true);
            $net = $normalDebit ? Money::decimal($account->debit)->minus($account->credit) : Money::decimal($account->credit)->minus($account->debit);
            $account->closing_debit = $normalDebit ? ($net->isNegative() ? '0.00' : (string) $net) : ($net->isNegative() ? (string) $net->abs() : '0.00');
            $account->closing_credit = $normalDebit ? ($net->isNegative() ? (string) $net->abs() : '0.00') : ($net->isNegative() ? '0.00' : (string) $net);

            return $account;
        });

        $totalDebit = $all->sum(fn ($r) => (float) $r->closing_debit);
        $totalCredit = $all->sum(fn ($r) => (float) $r->closing_credit);

        $byCode = $all->keyBy('code');
        $usedCodes = ['1', '2', '3', '4', '5', '6', '11000', '12000'];
        $radixRows = collect();
        $no = 1;

        foreach ($this->radixDefinitions() as $sectionKey => $items) {
            foreach ($items as $def) {
                if (! empty($def['exclude_from_tb'])) {
                    continue;
                }
                foreach ($def['codes'] as $c) {
                    $usedCodes[] = $c;
                }
                $row = $this->buildRadixLine($def, $byCode, true);
                $row->no = $no++;
                $radixRows->push($row);
            }
        }

        // Tambahkan akun kustom yang memiliki mutasi namun di luar daftar standar agar Trial Balance selalu seimbang
        foreach ($all as $acc) {
            if (in_array((string) $acc->code, $usedCodes, true)) {
                continue;
            }
            $hasActivity = ! Money::decimal($acc->debit)->isZero() || ! Money::decimal($acc->credit)->isZero();
            if (! $hasActivity) {
                continue;
            }
            $def = [
                'acc_no' => $this->inferAccNo($acc),
                'name' => $acc->name,
                'type' => $acc->type,
                'codes' => [(string) $acc->code],
            ];
            $row = $this->buildRadixLine($def, $byCode, true);
            $row->no = $no++;
            $radixRows->push($row);
        }

        $totalOpening = (string) $radixRows->reduce(fn ($s, $r) => $s->plus(Money::decimal($r->opening_balance)), Money::decimal(0));
        $totalPeriodDebit = (string) $radixRows->reduce(fn ($s, $r) => $s->plus(Money::decimal($r->period_debit)), Money::decimal(0));
        $totalPeriodCredit = (string) $radixRows->reduce(fn ($s, $r) => $s->plus(Money::decimal($r->period_credit)), Money::decimal(0));
        $totalMutation = (string) Money::decimal($totalPeriodDebit)->minus($totalPeriodCredit);
        $totalEnding = (string) $radixRows->reduce(fn ($s, $r) => $s->plus(Money::decimal($r->ending_balance)), Money::decimal(0));

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = min(200, max(5, (int) request('per_page', 100)));
        $offset = ($page - 1) * $perPage;
        $rows = new LengthAwarePaginator(
            $all->slice($offset, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        return compact(
            'rows',
            'radixRows',
            'totalDebit',
            'totalCredit',
            'totalOpening',
            'totalPeriodDebit',
            'totalPeriodCredit',
            'totalMutation',
            'totalEnding'
        );
    }

    public function ledger(int $accountId, string $from, string $to, array $filters = []): array
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
                Journal::select('journal_date')
                    ->whereColumn('journals.id', 'journal_entries.journal_id')
                    ->limit(1)
            )
            ->orderBy('id')
            ->get();
        $this->attachLedgerReferences($allEntries);
        foreach ($allEntries as $entry) {
            $movement = $this->net($account->type, $entry->debit, $entry->credit);
            $balance = $balance->plus($movement);
            $entry->running_balance = (string) $balance;
        }
        $allEntries = $allEntries->filter(function ($entry) use ($filters) {
            if (! empty($filters['payment_status']) && ($entry->payment_status ?? '') !== $filters['payment_status']) {
                return false;
            }
            if (! empty($filters['customer']) && ! str_contains(mb_strtolower((string) ($entry->customer_name ?? '')), mb_strtolower($filters['customer']))) {
                return false;
            }
            if (! empty($filters['job_number']) && ! str_contains(mb_strtolower((string) ($entry->job_number ?? '')), mb_strtolower($filters['job_number']))) {
                return false;
            }
            if (! empty($filters['invoice_number']) && ! str_contains(mb_strtolower((string) ($entry->invoice_number ?? '')), mb_strtolower($filters['invoice_number']))) {
                return false;
            }
            if (! empty($filters['cost_name']) && ! str_contains(mb_strtolower((string) ($entry->cost_name ?? '')), mb_strtolower($filters['cost_name']))) {
                return false;
            }
            if (! empty($filters['pr_number']) && ! str_contains(mb_strtolower((string) ($entry->cost_number ?? '')), mb_strtolower($filters['pr_number']))) {
                return false;
            }
            if (! empty($filters['vendor']) && ! str_contains(mb_strtolower((string) ($entry->vendor_name ?? '')), mb_strtolower($filters['vendor']))) {
                return false;
            }

            return true;
        })->values();

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

    /** Tambahkan referensi Job, Invoice, Customer, Vendor, faktur pajak dan Bupot pada mutasi Buku Besar. */
    private function attachLedgerReferences(Collection $entries): void
    {
        $costs = JobCost::with(['job:id,number,customer_id', 'job.customer:id,name', 'vendor:id,name'])->whereIn('id', $entries->filter(fn ($e) => $e->journal->source_type === JobCost::class)->pluck('journal.source_id'))->get()->keyBy('id');
        $jobs = Job::with(['customer:id,name', 'invoice:id,job_id,number,status,tax_invoice_number'])->whereIn('id', $entries->filter(fn ($e) => $e->journal->source_type === Job::class)->pluck('journal.source_id'))->get()->keyBy('id');
        $payments = Payment::with('invoice.job.customer')->whereIn('id', $entries->filter(fn ($e) => $e->journal->source_type === Payment::class)->pluck('journal.source_id'))->get()->keyBy('id');
        foreach ($entries as $entry) {
            $entry->job_number = null;
            $entry->invoice_number = null;
            $entry->cost_number = null;
            $entry->customer_name = null;
            $entry->vendor_name = null;
            $entry->cost_name = null;
            $entry->tax_invoice_number = null;
            $entry->withholding_document_number = null;
            $entry->payment_status = null;
            if ($entry->journal->source_type === JobCost::class && ($cost = $costs->get($entry->journal->source_id))) {
                $entry->job_number = $cost->job?->number;
                $entry->cost_number = $cost->number;
                $entry->customer_name = $cost->job?->customer?->name;
                $entry->vendor_name = $cost->vendor?->name ?? $cost->payee;
                $entry->cost_name = $cost->description;
                $entry->withholding_document_number = $cost->withholding_document_number;
                $entry->payment_status = $cost->paid_at ? 'paid' : 'unpaid';
            } elseif ($entry->journal->source_type === Job::class && ($job = $jobs->get($entry->journal->source_id))) {
                $entry->job_number = $job->number;
                $entry->customer_name = $job->customer?->name;
                $entry->invoice_number = $job->invoice?->number;
                $entry->tax_invoice_number = $job->invoice?->tax_invoice_number;
                $entry->payment_status = $job->invoice?->status === 'paid' ? 'paid' : 'unpaid';
            } elseif ($entry->journal->source_type === Payment::class && ($payment = $payments->get($entry->journal->source_id))) {
                $entry->job_number = $payment->invoice?->job?->number;
                $entry->invoice_number = $payment->invoice?->number;
                $entry->customer_name = $payment->invoice?->job?->customer?->name ?? ($payment->invoice?->customer_snapshot['name'] ?? null);
                $entry->tax_invoice_number = $payment->invoice?->tax_invoice_number;
                $entry->withholding_document_number = $payment->withholding_document_number;
                $entry->payment_status = $payment->invoice?->status === 'paid' ? 'paid' : 'unpaid';
            }
        }
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
            ->orderBy(Journal::select('journal_date')->whereColumn('journals.id', 'journal_entries.journal_id')->limit(1))
            ->orderBy('id')
            ->get();
        $this->attachLedgerReferences($entries);
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

        $accounts = ChartOfAccount::withTrashed()
            ->whereIn('type', ['revenue', 'cogs', 'expense'])
            ->with(['entries' => function ($q) use ($from, $to) {
                $q->whereHas('journal', fn ($j) => $j->whereDate('journal_date', '>=', $from)->whereDate('journal_date', '<=', $to));
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
                'period_debit' => (string) $debit,
                'period_credit' => (string) $credit,
                'opening_debit' => '0.00',
                'opening_credit' => '0.00',
                'balance' => (string) $balance,
                'report_balance' => (string) $balance,
                'has_activity' => ! $debit->isZero() || ! $credit->isZero(),
            ];
        });

        $byCode = $accountDetails->keyBy('code');
        $defs = $this->radixDefinitions();

        $buildSection = function (string $key) use ($defs, $byCode): Collection {
            return collect($defs[$key] ?? [])->map(fn (array $def) => $this->buildRadixPeriodLine($def, $byCode));
        };

        $sumSection = fn (Collection $items) => $items->reduce(fn ($s, $r) => $s->plus(Money::decimal($r->balance)), Money::decimal(0));

        $pendapatanClosed = $buildSection('pendapatan_closed');
        $pendapatanUnclosed = $buildSection('pendapatan_unclosed');
        $hppClosed = $buildSection('hpp_closed');
        $hppUnclosed = $buildSection('hpp_unclosed');
        $biayaOperasional = $buildSection('biaya_operasional');
        $pendapatanLain = $buildSection('pendapatan_lain');
        $biayaLain = $buildSection('biaya_lain');
        $pajakLines = $buildSection('pajak_penghasilan');

        $totalPendapatanClosed = $sumSection($pendapatanClosed);
        $totalPendapatanUnclosed = $sumSection($pendapatanUnclosed);
        $totalPendapatan = $totalPendapatanClosed->plus($totalPendapatanUnclosed);

        $totalHppClosed = $sumSection($hppClosed);
        $totalHppUnclosed = $sumSection($hppUnclosed);
        $totalHpp = $totalHppClosed->plus($totalHppUnclosed);

        $labaKotor = $totalPendapatan->minus($totalHpp);
        $grossMarginPct = $totalPendapatan->isZero()
            ? '0.00'
            : (string) $labaKotor->multipliedBy('100')->dividedBy($totalPendapatan, 2, RoundingMode::HalfUp);

        $totalBiayaOperasional = $sumSection($biayaOperasional);
        $labaOperasional = $labaKotor->minus($totalBiayaOperasional);

        $totalPendapatanLain = $sumSection($pendapatanLain);
        $totalBiayaLain = $sumSection($biayaLain);
        $jumlahPendapatanBiayaLain = $totalPendapatanLain->minus($totalBiayaLain);

        $labaBersihSebelumPajak = $labaOperasional->plus($jumlahPendapatanBiayaLain);
        $netBeforeTaxPct = $totalPendapatan->isZero()
            ? '0.00'
            : (string) $labaBersihSebelumPajak->multipliedBy('100')->dividedBy($totalPendapatan, 2, RoundingMode::HalfUp);

        $pajakPenghasilan = $sumSection($pajakLines);
        $labaBersihSesudahPajak = $labaBersihSebelumPajak->minus($pajakPenghasilan);

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
            'hppBreakdown' => $this->hppByCostType($from, $to)['rows'],
            // Struktur Laporan Laba Rugi PT Radix International Logistics
            'pendapatanClosed' => $pendapatanClosed,
            'totalPendapatanClosed' => (string) $totalPendapatanClosed,
            'pendapatanUnclosed' => $pendapatanUnclosed,
            'totalPendapatanUnclosed' => (string) $totalPendapatanUnclosed,
            'totalPendapatan' => (string) $totalPendapatan,
            'hppClosed' => $hppClosed,
            'totalHppClosed' => (string) $totalHppClosed,
            'hppUnclosed' => $hppUnclosed,
            'totalHppUnclosed' => (string) $totalHppUnclosed,
            'totalHpp' => (string) $totalHpp,
            'labaKotor' => (string) $labaKotor,
            'grossMarginPct' => $grossMarginPct,
            'biayaOperasional' => $biayaOperasional,
            'totalBiayaOperasional' => (string) $totalBiayaOperasional,
            'labaOperasional' => (string) $labaOperasional,
            'pendapatanLain' => $pendapatanLain,
            'totalPendapatanLain' => (string) $totalPendapatanLain,
            'biayaLain' => $biayaLain,
            'totalBiayaLain' => (string) $totalBiayaLain,
            'jumlahPendapatanBiayaLain' => (string) $jumlahPendapatanBiayaLain,
            'labaBersihSebelumPajak' => (string) $labaBersihSebelumPajak,
            'netBeforeTaxPct' => $netBeforeTaxPct,
            'pajakPenghasilan' => (string) $pajakPenghasilan,
            'pajakAccount' => $pajakLines->first(),
            'labaBersihSesudahPajak' => (string) $labaBersihSesudahPajak,
        ];
    }

    public function balanceSheet(string $to, ?string $from = null): array
    {
        $from = $from ?: Carbon::parse($to)->startOfYear()->toDateString();

        $allAccounts = $this->accountBalancesWithOpening($from, $to)->map(function ($account) {
            $account->report_balance = $this->net($account->type, $account->debit, $account->credit);

            return $account;
        });
        $rows = $allAccounts->groupBy('type');
        $assets = $this->sumNet($rows->get('asset', collect()), true);
        $liabilities = $this->sumNet($rows->get('liability', collect()), false);
        $equity = $this->sumNet($rows->get('equity', collect()), false);
        $earnings = Money::decimal($this->sumNet($rows->get('revenue', collect()), false))
            ->minus($this->sumNet($rows->get('cogs', collect()), true))
            ->minus($this->sumNet($rows->get('expense', collect()), true));

        // Pisahkan Laba Ditahan periode sebelumnya vs Laba Tahun Berjalan (periode $from s/d $to)
        $currentPeriodPnl = $this->periodBalances($from, $to)->keyBy('type');
        $currentRev = Money::decimal($currentPeriodPnl->get('revenue')->credit ?? 0)->minus($currentPeriodPnl->get('revenue')->debit ?? 0);
        $currentCogs = Money::decimal($currentPeriodPnl->get('cogs')->debit ?? 0)->minus($currentPeriodPnl->get('cogs')->credit ?? 0);
        $currentExp = Money::decimal($currentPeriodPnl->get('expense')->debit ?? 0)->minus($currentPeriodPnl->get('expense')->credit ?? 0);
        $currentYearEarnings = $currentRev->minus($currentCogs)->minus($currentExp);
        $priorRetainedEarnings = $earnings->minus($currentYearEarnings);

        $byCode = $allAccounts->keyBy('code');
        $defs = $this->radixDefinitions();
        $usedAssetCodes = ['1', '11000', '12000'];
        $usedLiabilityCodes = ['2'];
        $usedEquityCodes = ['3'];

        $radixCurrentAssets = collect($defs['aktiva_lancar'])->map(function (array $def) use ($byCode, &$usedAssetCodes) {
            foreach ($def['codes'] as $c) {
                $usedAssetCodes[] = $c;
            }

            return $this->buildRadixLine($def, $byCode, false);
        });

        $radixFixedAssets = collect($defs['aktiva_tetap'])->map(function (array $def) use ($byCode, &$usedAssetCodes) {
            foreach ($def['codes'] as $c) {
                $usedAssetCodes[] = $c;
            }

            return $this->buildRadixLine($def, $byCode, false);
        });

        // Sertakan akun aset kustom yang memiliki saldo
        foreach ($rows->get('asset', collect()) as $acc) {
            if (in_array((string) $acc->code, $usedAssetCodes, true)) {
                continue;
            }
            if (Money::decimal($acc->report_balance)->isZero()) {
                continue;
            }
            $customLine = $this->buildRadixLine([
                'acc_no' => str_starts_with((string) $acc->code, '12') ? '15' : '12',
                'name' => $acc->name,
                'type' => 'asset',
                'codes' => [(string) $acc->code],
            ], $byCode, false);
            if (str_starts_with((string) $acc->code, '12')) {
                $radixFixedAssets->push($customLine);
            } else {
                $radixCurrentAssets->push($customLine);
            }
        }

        $radixCurrentLiabilities = collect($defs['kewajiban_lancar'])->map(function (array $def) use ($byCode, &$usedLiabilityCodes) {
            foreach ($def['codes'] as $c) {
                $usedLiabilityCodes[] = $c;
            }

            return $this->buildRadixLine($def, $byCode, false);
        });

        foreach ($rows->get('liability', collect()) as $acc) {
            if (in_array((string) $acc->code, $usedLiabilityCodes, true) || Money::decimal($acc->report_balance)->isZero()) {
                continue;
            }
            $radixCurrentLiabilities->push($this->buildRadixLine([
                'acc_no' => '20',
                'name' => $acc->name,
                'type' => 'liability',
                'codes' => [(string) $acc->code],
            ], $byCode, false));
        }

        $radixEquity = collect($defs['modal'])->map(function (array $def) use ($byCode, &$usedEquityCodes, $priorRetainedEarnings, $currentYearEarnings) {
            foreach ($def['codes'] as $c) {
                $usedEquityCodes[] = $c;
            }
            $line = $this->buildRadixLine($def, $byCode, false);
            if ($def['acc_no'] === '31') {
                $bal = Money::decimal($line->report_balance)->plus($priorRetainedEarnings);
                $line->report_balance = (string) $bal;
                $line->ending_balance = (string) $bal;
            } elseif ($def['acc_no'] === '32') {
                $bal = Money::decimal($line->report_balance)->plus($currentYearEarnings);
                $line->report_balance = (string) $bal;
                $line->ending_balance = (string) $bal;
            }

            return $line;
        });

        foreach ($rows->get('equity', collect()) as $acc) {
            if (in_array((string) $acc->code, $usedEquityCodes, true) || Money::decimal($acc->report_balance)->isZero()) {
                continue;
            }
            $radixEquity->push($this->buildRadixLine([
                'acc_no' => '30',
                'name' => $acc->name,
                'type' => 'equity',
                'codes' => [(string) $acc->code],
            ], $byCode, false));
        }

        $sumReportBalance = fn (Collection $items) => (string) $items->reduce(fn ($s, $r) => $s->plus(Money::decimal($r->report_balance)), Money::decimal(0));

        $totalCurrentAssets = $sumReportBalance($radixCurrentAssets);
        $totalFixedAssets = $sumReportBalance($radixFixedAssets);
        $totalCurrentLiabilities = $sumReportBalance($radixCurrentLiabilities);
        $totalModal = $sumReportBalance($radixEquity);

        $assetRows = $rows->get('asset', collect())->reject(fn ($account) => $account->code === '1');
        $currentAssets = $assetRows->filter(fn ($account) => str_starts_with((string) $account->code, '11'))->values();
        $nonCurrentAssets = $assetRows->filter(fn ($account) => str_starts_with((string) $account->code, '12'))->values();
        $otherAssets = $assetRows->reject(fn ($account) => str_starts_with((string) $account->code, '11') || str_starts_with((string) $account->code, '12'))->values();

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'earnings' => (string) $earnings,
            'liabilities_equity' => (string) Money::decimal($liabilities)->plus($equity)->plus($earnings),
            'currentAssets' => $currentAssets,
            'nonCurrentAssets' => $nonCurrentAssets,
            'otherAssets' => $otherAssets,
            'liabilityRows' => $rows->get('liability', collect())->reject(fn ($account) => $account->code === '2')->values(),
            'equityRows' => $rows->get('equity', collect())->reject(fn ($account) => $account->code === '3')->values(),
            // Struktur Neraca PT Radix International Logistics
            'radixCurrentAssets' => $radixCurrentAssets,
            'totalCurrentAssets' => $totalCurrentAssets,
            'radixFixedAssets' => $radixFixedAssets,
            'totalFixedAssets' => $totalFixedAssets,
            'radixCurrentLiabilities' => $radixCurrentLiabilities,
            'totalCurrentLiabilities' => $totalCurrentLiabilities,
            'radixEquity' => $radixEquity,
            'totalModal' => $totalModal,
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
            ->orderBy(Journal::select('journal_date')->whereColumn('journals.id', 'journal_entries.journal_id')->limit(1))
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
        $paginated = new LengthAwarePaginator(
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

    private function buildRadixLine(array $def, Collection $byCode, bool $useTbAccNo = false): object
    {
        $matched = collect($def['codes'])
            ->map(fn ($c) => $byCode->get((string) $c))
            ->filter();

        $activeAccount = $matched->first(fn ($acc) => ! Money::decimal($acc->debit ?? 0)->isZero() || ! Money::decimal($acc->credit ?? 0)->isZero())
            ?? $matched->first();

        $opDebit = $matched->reduce(fn ($s, $a) => $s->plus(Money::decimal($a->opening_debit ?? 0)), Money::decimal(0));
        $opCredit = $matched->reduce(fn ($s, $a) => $s->plus(Money::decimal($a->opening_credit ?? 0)), Money::decimal(0));
        $pDebit = $matched->reduce(fn ($s, $a) => $s->plus(Money::decimal($a->period_debit ?? 0)), Money::decimal(0));
        $pCredit = $matched->reduce(fn ($s, $a) => $s->plus(Money::decimal($a->period_credit ?? 0)), Money::decimal(0));
        $totDebit = $matched->reduce(fn ($s, $a) => $s->plus(Money::decimal($a->debit ?? 0)), Money::decimal(0));
        $totCredit = $matched->reduce(fn ($s, $a) => $s->plus(Money::decimal($a->credit ?? 0)), Money::decimal(0));

        $type = $def['type'];
        $openingBalance = $this->net($type, (string) $opDebit, (string) $opCredit);
        $mutation = $this->net($type, (string) $pDebit, (string) $pCredit);
        $endingBalance = $this->net($type, (string) $totDebit, (string) $totCredit);

        return (object) [
            'id' => $activeAccount?->id,
            'acc_no' => $useTbAccNo ? ($def['tb_acc_no'] ?? $def['acc_no']) : $def['acc_no'],
            'code' => $activeAccount?->code ?? ($def['codes'][0] ?? $def['acc_no']),
            'name' => $def['name'],
            'type' => $type,
            'opening_balance' => $openingBalance,
            'period_debit' => (string) $pDebit,
            'period_credit' => (string) $pCredit,
            'mutation' => $mutation,
            'ending_balance' => $endingBalance,
            'report_balance' => $endingBalance,
        ];
    }

    private function buildRadixPeriodLine(array $def, Collection $byCode): object
    {
        $matched = collect($def['codes'])
            ->map(fn ($c) => $byCode->get((string) $c))
            ->filter();

        $activeAccount = $matched->first(fn ($acc) => ! Money::decimal($acc->debit ?? 0)->isZero() || ! Money::decimal($acc->credit ?? 0)->isZero())
            ?? $matched->first();

        $debit = $matched->reduce(fn ($s, $a) => $s->plus(Money::decimal($a->debit ?? 0)), Money::decimal(0));
        $credit = $matched->reduce(fn ($s, $a) => $s->plus(Money::decimal($a->credit ?? 0)), Money::decimal(0));
        $type = $def['type'];
        $balance = $this->net($type, (string) $debit, (string) $credit);

        return (object) [
            'id' => $activeAccount?->id,
            'acc_no' => $def['acc_no'],
            'code' => $activeAccount?->code ?? ($def['codes'][0] ?? $def['acc_no']),
            'name' => $def['name'],
            'type' => $type,
            'debit' => (string) $debit,
            'credit' => (string) $credit,
            'balance' => $balance,
            'has_activity' => ! $debit->isZero() || ! $credit->isZero(),
        ];
    }

    private function inferAccNo(object $acc): string
    {
        return match ($acc->type) {
            'asset' => str_starts_with((string) $acc->code, '12') ? '15' : '11',
            'liability' => '20',
            'equity' => '30',
            'revenue' => '40',
            'cogs' => '50',
            default => '61',
        };
    }

    private function accountBalancesWithOpening(string $from, string $to): Collection
    {
        $openingTotals = JournalEntry::whereHas('journal', fn (Builder $q) => $q->whereDate('journal_date', '<', $from))
            ->select('chart_of_account_id')
            ->selectRaw('SUM(debit) debit, SUM(credit) credit')
            ->groupBy('chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');

        $periodTotals = JournalEntry::whereHas('journal', fn (Builder $q) => $q->whereDate('journal_date', '>=', $from)->whereDate('journal_date', '<=', $to))
            ->select('chart_of_account_id')
            ->selectRaw('SUM(debit) debit, SUM(credit) credit')
            ->groupBy('chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');

        $closingTotals = JournalEntry::whereHas('journal', fn (Builder $q) => $q->whereDate('journal_date', '<=', $to))
            ->select('chart_of_account_id')
            ->selectRaw('SUM(debit) debit, SUM(credit) credit')
            ->groupBy('chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');

        return ChartOfAccount::withTrashed()->orderBy('code')->get()->each(function ($account) use ($openingTotals, $periodTotals, $closingTotals) {
            $account->opening_debit = (string) ($openingTotals->get($account->id)->debit ?? '0.00');
            $account->opening_credit = (string) ($openingTotals->get($account->id)->credit ?? '0.00');
            $account->period_debit = (string) ($periodTotals->get($account->id)->debit ?? '0.00');
            $account->period_credit = (string) ($periodTotals->get($account->id)->credit ?? '0.00');
            $account->debit = (string) ($closingTotals->get($account->id)->debit ?? '0.00');
            $account->credit = (string) ($closingTotals->get($account->id)->credit ?? '0.00');
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
