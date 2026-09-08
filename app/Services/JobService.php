<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class JobService
{
    public function __construct(private MasterDataService $master) {}

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
            $job->fill(Arr::only($data, ['subject', 'job_date', 'expected_completion_date', 'service_type', 'origin', 'destination', 'shipment_reference', 'cargo_description', 'operational_notes']));
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
            } else {
                throw new \InvalidArgumentException('Unknown job transition.');
            }
            $job->updated_by = $actor->id;
            $job->lock_version++;
            $job->save();
            $this->master->log($actor, 'job.'.$action, $job->number.' → '.config('operations.job_statuses.'.$job->status));

            return $job;
        }, 3);
    }
}
