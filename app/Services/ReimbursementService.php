<?php

namespace App\Services;

use App\Models\Reimbursement;
use App\Models\User;
use App\Support\Money;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReimbursementService
{
    public function __construct(private DocumentNumberService $numbers, private JournalService $journals, private MasterDataService $master) {}

    public function create(array $data, User $actor): Reimbursement
    {
        return DB::transaction(function () use ($data, $actor) {
            Gate::forUser($actor)->authorize('reimbursements.manage');
            $amount = Money::decimal($data['amount']);
            Money::checked($amount);
            $reimbursement = Reimbursement::create([...$this->base($data, $amount), 'number' => $this->numbers->next('rem'), 'status' => 'pending', 'created_by' => $actor->id]);
            $this->master->log($actor, 'reimbursement.created', $reimbursement->number.' · '.$reimbursement->description, ['module' => 'reimbursement', 'record_id' => $reimbursement->id, 'after' => ['employee' => $reimbursement->employee_id, 'category' => $reimbursement->category, 'date' => $reimbursement->reimbursement_date->toDateString(), 'amount' => (string) $amount, 'status' => 'pending']]);

            return $reimbursement;
        }, 3);
    }

    public function approve(Reimbursement $reimbursement, array $data, User $actor): Reimbursement
    {
        return $this->transition($reimbursement, $data, $actor, 'approve');
    }

    public function reject(Reimbursement $reimbursement, array $data, User $actor): Reimbursement
    {
        return $this->transition($reimbursement, $data, $actor, 'reject');
    }

    public function pay(Reimbursement $reimbursement, array $data, User $actor): Reimbursement
    {
        return DB::transaction(function () use ($reimbursement, $data, $actor) {
            $reimbursement = Reimbursement::with('journals')->lockForUpdate()->findOrFail($reimbursement->id);
            Gate::forUser($actor)->authorize('reimbursements.manage');
            $this->master->checkVersion($reimbursement, $data);
            if ($reimbursement->status !== 'approved') {
                throw ValidationException::withMessages(['reimbursement' => 'Hanya reimbursement berstatus Disetujui yang dapat dibayar.']);
            }
            if ($reimbursement->paid_at || $reimbursement->journals()->exists()) {
                throw ValidationException::withMessages(['reimbursement' => 'Reimbursement sudah dibayar.']);
            }
            if ($data['paid_date'] < $reimbursement->reimbursement_date->format('Y-m-d')) {
                throw ValidationException::withMessages(['paid_date' => 'Tanggal bayar tidak boleh sebelum tanggal transaksi.']);
            }
            $amount = Money::decimal($reimbursement->amount);
            $maps = $this->journals->mapped([$data['funding_account'], 'expense']);
            $this->journals->post('reimbursement', Reimbursement::class, $reimbursement->id, $data['paid_date'], 'Reimbursement '.$reimbursement->number.' · '.$reimbursement->description, [
                ['account_id' => $maps['expense']->id, 'description' => 'Biaya reimbursement '.$reimbursement->description, 'debit' => (string) $amount, 'credit' => 0],
                ['account_id' => $maps[$data['funding_account']]->id, 'description' => 'Pembayaran reimbursement '.$reimbursement->number, 'debit' => 0, 'credit' => (string) $amount]], $actor);
            $reimbursement->status = 'paid';
            $reimbursement->payment_reference = $data['reference'] ?? null;
            $reimbursement->paid_date = $data['paid_date'];
            $reimbursement->funding_account_id = $maps[$data['funding_account']]->id;
            $reimbursement->paid_at = now();
            $reimbursement->notes = trim(($reimbursement->notes ?? '')."\n".($data['notes'] ?? '')) !== '' ? trim(($reimbursement->notes ?? '')."\n".($data['notes'] ?? '')) : null;
            $reimbursement->lock_version++;
            $reimbursement->save();
            $this->master->log($actor, 'reimbursement.paid', $reimbursement->number.' · '.$data['paid_date'], ['module' => 'reimbursement', 'record_id' => $reimbursement->id, 'after' => ['status' => 'paid', 'paid_date' => $data['paid_date'], 'funding_account' => $data['funding_account'], 'locked' => (string) $amount]]);

            return $reimbursement;
        }, 3);
    }

    private function transition(Reimbursement $reimbursement, array $data, User $actor, string $action): Reimbursement
    {
        return DB::transaction(function () use ($reimbursement, $data, $actor, $action) {
            $reimbursement = Reimbursement::lockForUpdate()->findOrFail($reimbursement->id);
            Gate::forUser($actor)->authorize('reimbursements.manage');
            $this->master->checkVersion($reimbursement, $data);
            if ($reimbursement->status !== 'pending') {
                throw ValidationException::withMessages(['reimbursement' => 'Hanya reimbursement berstatus Menunggu yang dapat diproses.']);
            }
            $reimbursement->status = $action === 'approve' ? 'approved' : 'rejected';
            $reimbursement->reviewed_by = $actor->id;
            $reimbursement->reviewed_at = now();
            $notes = trim((string) ($data['notes'] ?? ''));
            if ($notes !== '') {
                $reimbursement->notes = trim(($reimbursement->notes ?? '')."\n".$notes);
            }
            $reimbursement->lock_version++;
            $reimbursement->save();
            $this->master->log($actor, 'reimbursement.'.($action === 'approve' ? 'approved' : 'rejected'), $reimbursement->number, ['module' => 'reimbursement', 'record_id' => $reimbursement->id, 'after' => ['status' => $reimbursement->status, 'notes' => $notes]]);

            return $reimbursement;
        }, 3);
    }

    private function base(array $data, BigDecimal $amount): array
    {
        return [
            'employee_id' => $data['employee_id'],
            'category' => $data['category'],
            'reimbursement_date' => $data['reimbursement_date'],
            'description' => $data['description'],
            'notes' => $data['notes'] ?? null,
            'amount' => Money::checked($amount),
        ];
    }
}
