<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private DocumentNumberService $numbers, private JournalService $journals, private MasterDataService $master) {}

    public function create(Invoice $invoice, array $data, User $actor): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $actor) {
            Gate::forUser($actor)->authorize('payments.manage');
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $this->master->checkVersion($invoice, $data);
            if ($invoice->status === 'paid') {
                throw ValidationException::withMessages(['amount' => 'Invoice sudah lunas.']);
            }
            $amount = Money::decimal($data['amount']);
            $pph23Amount = ! empty($data['pph23_amount']) ? Money::decimal($data['pph23_amount']) : Money::decimal(0);
            $totalDeduction = $amount->plus($pph23Amount);
            $balance = Money::decimal($invoice->balance);

            if ($totalDeduction->isGreaterThan($balance)) {
                throw ValidationException::withMessages(['amount' => 'Total pembayaran dan PPh 23 tidak boleh melebihi sisa tagihan.']);
            }

            // Resolve deposit account
            $depositAccountInput = $data['deposit_account'] ?? 'bank';
            if (in_array($depositAccountInput, ['bank', 'cash'], true)) {
                $depositAccount = $this->journals->mapped([$depositAccountInput])[$depositAccountInput];
            } else {
                $depositAccount = is_numeric($depositAccountInput)
                    ? ChartOfAccount::where('type', 'asset')->findOrFail((int) $depositAccountInput)
                    : (ChartOfAccount::where('code', $depositAccountInput)->where('type', 'asset')->first()
                        ?: $this->journals->mapped(['bank'])['bank']);
            }

            $receivableAccount = $this->journals->mapped(['receivable'])['receivable'];
            $pph23Account = null;
            if ($pph23Amount->isPositive()) {
                $pph23Account = $this->journals->mapped(['pph23_prepaid'])['pph23_prepaid'];
            }

            $payment = Payment::create([
                'number' => $this->numbers->next('pay'),
                'invoice_id' => $invoice->id,
                'payment_date' => $data['payment_date'],
                'amount' => (string) $amount,
                'pph23_amount' => (string) $pph23Amount,
                'currency' => $data['currency'] ?? $invoice->currency ?? 'IDR',
                'exchange_rate' => $data['exchange_rate'] ?? $invoice->exchange_rate ?? 1,
                'deposit_account_id' => $depositAccount->id,
                'method' => $data['method'] ?? 'transfer',
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $newPaid = Money::decimal($invoice->paid_amount)->plus($totalDeduction);
            $newBalance = Money::decimal($invoice->total)->minus($newPaid);
            $invoice->paid_amount = Money::checked($newPaid);
            $invoice->balance = Money::checked($newBalance);
            $invoice->status = $newBalance->isZero() ? 'paid' : 'partially_paid';
            $invoice->lock_version++;
            $invoice->save();

            $journalEntries = [
                ['account_id' => $depositAccount->id, 'description' => 'Penerimaan kas/bank '.$invoice->number, 'debit' => (string) $amount, 'credit' => 0],
            ];
            if ($pph23Account && $pph23Amount->isPositive()) {
                $journalEntries[] = ['account_id' => $pph23Account->id, 'description' => 'PPh 23 dibayar dimuka '.$invoice->number, 'debit' => (string) $pph23Amount, 'credit' => 0];
            }
            $journalEntries[] = ['account_id' => $receivableAccount->id, 'description' => 'Pelunasan piutang '.$invoice->number, 'debit' => 0, 'credit' => (string) $totalDeduction];

            $journalNumber = $this->numbers->nextBankJournal($depositAccount->code, $depositAccount->name, true, \Carbon\Carbon::parse($data['payment_date']));
            $this->journals->post('customer_payment', Payment::class, $payment->id, $data['payment_date'], 'Pembayaran '.$payment->number.' untuk '.$invoice->number, $journalEntries, $actor, $journalNumber);

            $state = ['paid_amount' => $invoice->paid_amount, 'balance' => $invoice->balance, 'status' => $invoice->status];
            $this->master->log($actor, 'payment.created', $payment->number.' · '.$invoice->number, ['module' => 'payment', 'record_id' => $payment->id, 'after' => array_merge(['amount' => (string) $amount, 'pph23_amount' => (string) $pph23Amount], $state)]);

            return $payment;
        }, 3);
    }
}
