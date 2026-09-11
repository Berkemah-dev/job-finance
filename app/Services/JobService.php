<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Job;
use App\Models\JobCost;
use App\Models\QuotationItem;
use App\Models\User;
use App\Support\Money;
use Brick\Math\RoundingMode;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class JobService
{
    public function __construct(private MasterDataService $master, private DocumentNumberService $numbers) {}

    public function update(Job $job, array $data, User $actor): Job
    {
        return DB::transaction(function () use ($job, $data, $actor) {
            $job = Job::lockForUpdate()->findOrFail($job->id);
            Gate::forUser($actor)->authorize('update', $job);
            $this->master->checkVersion($job, $data);
            if ($job->status === 'open' && $job->job_date->format('Y-m-d') !== $data['job_date']) {
                throw ValidationException::withMessages(['job_date' => 'Tanggal job tidak dapat diubah setelah job dibuka.']);
            }
            // Customer, quotation, document number, snapshot and workflow fields are never editable here.
            $job->fill(Arr::only($data, ['subject', 'job_date', 'expected_completion_date', 'service_type', 'origin', 'destination', 'shipment_reference', 'shipper_name', 'shipper_address', 'consignee_name', 'consignee_address', 'pol', 'pod', 'etd', 'eta', 'vessel_voyage', 'flight_number', 'bl_number', 'hbl_number', 'awb_number', 'hawb_number', 'booking_reference', 'package_count', 'gross_weight', 'volume', 'container_type', 'sales_id', 'cs_id', 'cargo_description', 'operational_notes']));
            $job->updated_by = $actor->id;
            $job->lock_version++;
            $job->save();
            $this->master->log($actor, 'job.updated', 'Memperbarui operasional '.$job->number);

            return $job;
        }, 3);
    }

    public function transition(Job $job, string $action, array $data, User $actor): Job
    {
        return DB::transaction(function () use ($job, $action, $data, $actor) {
            $job = Job::lockForUpdate()->findOrFail($job->id);
            Gate::forUser($actor)->authorize($action, $job);
            $this->master->checkVersion($job, $data);
            $from = $job->status;
            $note = null;
            if ($action === 'open') {
                if (! Customer::whereKey($job->customer_id)->lockForUpdate()->first()) {
                    throw ValidationException::withMessages(['customer' => 'Customer sudah diarsipkan. Job tidak dapat dibuka.']);
                }
                if ($job->job_date->isAfter(today())) {
                    throw ValidationException::withMessages(['job_date' => 'Tanggal job tidak boleh melewati hari ini saat dibuka.']);
                }
                $job->status = 'open';
                $job->opened_by = $actor->id;
                $job->opened_at = now();
                $this->seedQuotationCharges($job, $actor);
            } elseif ($action === 'cancel') {
                if (trim($data['reason'] ?? '') === '') {
                    throw ValidationException::withMessages(['reason' => 'Alasan pembatalan wajib diisi.']);
                }
                if ($job->costs()->exists()) {
                    throw ValidationException::withMessages(['costs' => 'Job masih memiliki biaya aktif. Hapus biaya Draft terlebih dahulu. Biaya Final memerlukan proses koreksi sebelum pembatalan.']);
                }
                $job->status = 'cancelled';
                $job->cancelled_by = $actor->id;
                $job->cancelled_at = now();
                $job->cancellation_reason = $data['reason'];
                $note = $data['reason'];
            } else {
                throw new \InvalidArgumentException('Unknown job transition.');
            }
            $job->statusHistory()->create(['from_status' => $from, 'to_status' => $job->status, 'note' => $note, 'user_id' => $actor->id, 'created_at' => now()]);
            $job->updated_by = $actor->id;
            $job->lock_version++;
            $job->save();
            $this->master->log($actor, 'job.'.$action, $job->number.' → '.config('operations.job_statuses.'.$job->status));

            return $job;
        }, 3);
    }

    public function updateShipmentStatus(array $data, Job $job, User $actor): Job
    {
        return DB::transaction(function () use ($data, $job, $actor) {
            $job = Job::lockForUpdate()->findOrFail($job->id);
            Gate::forUser($actor)->authorize('update', $job);
            $this->master->checkVersion($job, $data);
            if ($job->status !== 'open') {
                throw ValidationException::withMessages(['shipment_status' => 'Status pengiriman hanya dapat diubah untuk job berstatus Open.']);
            }
            if (! is_string($data['shipment_status'] ?? null) || ! array_key_exists($data['shipment_status'], config('operations.shipment_statuses'))) {
                throw ValidationException::withMessages(['shipment_status' => 'Status pengiriman tidak valid.']);
            }
            $from = $job->shipment_status;
            $job->shipment_status = $data['shipment_status'];
            $job->shipment_status_by = $actor->id;
            $job->shipment_status_at = now();
            $job->shipmentStatusHistory()->create(['from_status' => $from, 'to_status' => $job->shipment_status, 'note' => $data['reason'] ?? null, 'user_id' => $actor->id, 'created_at' => now()]);
            $job->updated_by = $actor->id;
            $job->lock_version++;
            $job->save();
            $this->master->log($actor, 'job.shipment_status', $job->number.' → '.config('operations.shipment_statuses.'.$job->shipment_status), ['module' => 'job', 'record_id' => $job->id, 'before' => $from, 'after' => $job->shipment_status]);

            return $job;
        }, 3);
    }

<<<<<<< HEAD
    public function confirmDo(Job $job, User $actor): Job
    {
        return DB::transaction(function () use ($job, $actor) {
            $job = Job::lockForUpdate()->findOrFail($job->id);
            Gate::forUser($actor)->authorize('jobs.confirm-do');
            if ($job->status !== 'open') {
                throw ValidationException::withMessages(['do' => 'DO Selesai hanya dapat dikonfirmasi untuk job berstatus Open.']);
            }
            if ($job->do_confirmed_at) {
                throw ValidationException::withMessages(['do' => 'DO job ini sudah dikonfirmasi selesai sebelumnya.']);
=======
    public function confirmDo(Job $job, array $data, User $actor): Job
    {
        return DB::transaction(function () use ($job, $data, $actor) {
            $job = Job::lockForUpdate()->findOrFail($job->id);
            Gate::forUser($actor)->authorize('update', $job);
            $this->master->checkVersion($job, $data);
            if ($job->status !== 'open') {
                throw ValidationException::withMessages(['do' => 'DO hanya dapat dikonfirmasi untuk job berstatus Open.']);
            }
            if ($job->do_confirmed_at) {
                throw ValidationException::withMessages(['do' => 'DO sudah pernah dikonfirmasi sebelumnya.']);
>>>>>>> 367a9ee93734e3a97628530a24dddb5e9c488978
            }
            $job->do_confirmed_at = now();
            $job->do_confirmed_by = $actor->id;
            $job->updated_by = $actor->id;
            $job->lock_version++;
            $job->save();
<<<<<<< HEAD
            $this->master->log($actor, 'job.do_confirmed', 'Konfirmasi DO selesai untuk '.$job->number, ['module' => 'job', 'record_id' => $job->id, 'after' => $job->do_confirmed_at->toDateTimeString()]);
=======
            $this->master->log($actor, 'job.do_confirmed', 'DO Selesai dikonfirmasi untuk '.$job->number, ['module' => 'job', 'record_id' => $job->id]);
>>>>>>> 367a9ee93734e3a97628530a24dddb5e9c488978

            return $job;
        }, 3);
    }

    private function seedQuotationCharges(Job $job, User $actor): void
    {
        $snapshot = $job->quotation_snapshot;
        $items = $snapshot['items'] ?? [];
        if ($items === [] || $job->costs()->exists()) {
            return;
        }
        $created = [];
        $sources = $job->quotation_id
            ? QuotationItem::where('quotation_id', $job->quotation_id)->orderBy('position')->orderBy('id')->get()
            : collect();
        foreach ($items as $index => $item) {
            $source = $sources[$index] ?? null;
            $currency = (string) ($item['currency'] ?? 'IDR');
            $rate = Money::decimal($currency === 'IDR' ? '1' : (string) ($item['exchange_rate'] ?? $snapshot['exchange_rate'] ?? '1'));
            if ($rate->isZero()) {
                throw ValidationException::withMessages(['job_date' => 'Quotation memiliki kurs nol sehingga biaya tidak dapat disalin saat membuka job.']);
            }
            $unitCost = Money::decimal((string) $item['unit_cost'])->multipliedBy($rate);
            $unitPrice = Money::decimal((string) $item['unit_price'])->multipliedBy($rate);
            $quantity = Money::decimal((string) $item['quantity']);
            $cost = new JobCost;
            $cost->job_id = $job->id;
            $cost->quotation_id = $job->quotation_id ?? ($source->quotation_id ?? null);
            $cost->quotation_item_id = $source->id ?? null;
            $cost->number = $this->numbers->next('cst');
            $cost->description = (string) $item['description'];
            $cost->type = (string) $item['type'];
            $cost->status = 'draft';
            $cost->cost_date = $job->job_date->toDateString();
            $cost->unit = (string) $item['unit'];
            $cost->quantity = $quantity->toScale(2, RoundingMode::HalfUp);
            $cost->unit_cost = Money::checked($unitCost->toScale(2, RoundingMode::HalfUp));
            $cost->unit_price = Money::checked($unitPrice->toScale(2, RoundingMode::HalfUp));
            $cost->total_cost = Money::checked($quantity->multipliedBy($unitCost)->toScale(2, RoundingMode::HalfUp));
            $cost->total_price = Money::checked($quantity->multipliedBy($unitPrice)->toScale(2, RoundingMode::HalfUp));
            $cost->notes = 'Otomatis dari quotation '.$snapshot['number'];
            $cost->created_by = $actor->id;
            $cost->updated_by = $actor->id;
            $cost->lock_version = 0;
            $cost->save();
            $created[] = $cost->number;
            $this->master->log($actor, 'job_cost.created', $cost->number.' · '.$job->number, ['module' => 'job_cost', 'record_id' => $cost->id, 'before' => null, 'after' => array_merge($cost->only(['description', 'type', 'quantity', 'unit', 'unit_cost', 'unit_price', 'total_cost', 'total_price']), ['quotation_id' => $cost->quotation_id, 'quotation_item_id' => $cost->quotation_item_id])]);
        }
        if (count($created) > 0) {
            $this->master->log($actor, 'job.costs.seeded', 'Salinan biaya quotation '.$snapshot['number'].' ● '.implode(', ', $created), ['module' => 'job', 'record_id' => $job->id]);
        }
    }
}
