<?php

namespace App\Services;

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
            $balance = Money::decimal($invoice->balance);
            if ($amount->isGreaterThan($balance)) {
                throw ValidationException::withMessages(['amount' => 'Pembayaran tidak boleh melebihi sisa tagihan.']);
            }
            $maps = $this->journals->mapped([$data['deposit_account'], 'receivable']);
            $payment = Payment::create(['number' => $this->numbers->next('pay'), 'invoice_id' => $invoice->id, 'payment_date' => $data['payment_date'], 'amount' => (string) $amount, 'deposit_account_id' => $maps[$data['deposit_account']]->id, 'method' => $data['method'], 'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null, 'created_by' => $actor->id]);
            $newPaid = Money::decimal($invoice->paid_amount)->plus($amount);
            $newBalance = Money::decimal($invoice->total)->minus($newPaid);
            $invoice->paid_amount = Money::checked($newPaid);
            $invoice->balance = Money::checked($newBalance);
            $invoice->status = $newBalance->isZero() ? 'paid' : 'partially_paid';
            $invoice->lock_version++;
            $invoice->save();
            $this->journals->post('customer_payment', Payment::class, $payment->id, $data['payment_date'], 'Pembayaran '.$payment->number.' untuk '.$invoice->number, [
                ['account_id' => $maps[$data['deposit_account']]->id, 'description' => 'Penerimaan customer', 'debit' => (string) $amount, 'credit' => 0],
                ['account_id' => $maps['receivable']->id, 'description' => 'Pelunasan piutang', 'debit' => 0, 'credit' => (string) $amount]], $actor);
            $state = ['paid_amount' => $invoice->paid_amount, 'balance' => $invoice->balance, 'status' => $invoice->status];
            $this->master->log($actor, 'payment.created', $payment->number.' · '.$invoice->number, ['module' => 'payment', 'record_id' => $payment->id, 'after' => array_merge(['amount' => (string) $amount], $state)]);

            return $payment;
        }, 3);
    }
}
