<?php

namespace App\Services;

use App\Models\AccountMapping;
use App\Models\ActivityLog;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MasterDataService
{
    public function save(Model $model, array $data, User $actor): Model
    {
        return DB::transaction(function () use ($model, $data, $actor) {
            Gate::forUser($actor)->authorize($model instanceof Customer ? 'customers.manage' : 'coa.manage');
            $new = ! $model->exists;
            if (! $new) {
                $model = $model->newQuery()->lockForUpdate()->findOrFail($model->id);
                $this->checkVersion($model, $data);
                if ($model instanceof ChartOfAccount && $model->type !== $data['type'] && $model->mappings()->exists()) {
                    throw ValidationException::withMessages(['type' => 'Tipe akun yang digunakan mapping tidak dapat diubah. Ubah mapping terlebih dahulu.']);
                }
            }
            $model->fill($data);
            if ($new) {
                $model->created_by = $actor->id;
            }
            $model->updated_by = $actor->id;
            $model->lock_version = $new ? 0 : $model->lock_version + 1;
            $model->save();
            $this->log($actor, ($model instanceof Customer ? 'customer' : 'account').($new ? '.created' : '.updated'), ($new ? 'Membuat ' : 'Memperbarui ').$model->code.' · '.$model->name);

            return $model;
        }, 3);
    }

    public function archive(Model $model, array $data, User $actor): void
    {
        DB::transaction(function () use ($model, $data, $actor) {
            Gate::forUser($actor)->authorize($model instanceof Customer ? 'customers.manage' : 'coa.manage');
            $model = $model->newQuery()->lockForUpdate()->findOrFail($model->id);
            $this->checkVersion($model, $data);
            if ($model instanceof ChartOfAccount && $model->mappings()->exists()) {
                throw ValidationException::withMessages(['account' => 'Akun masih digunakan pada mapping. Pilih akun pengganti sebelum mengarsipkan.']);
            }
            $model->updated_by = $actor->id;
            $model->lock_version++;
            $model->save();
            $model->delete();
            $this->log($actor, ($model instanceof Customer ? 'customer' : 'account').'.archived', 'Mengarsipkan '.$model->code.' · '.$model->name);
        }, 3);
    }

    public function mappings(array $data, User $actor): void
    {
        Gate::forUser($actor)->authorize('coa.manage');
        DB::transaction(function () use ($data, $actor) {
            $accounts = ChartOfAccount::whereIn('id', array_values($data))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach (config('accounting.mappings') as $key => $settings) {
                $account = $accounts->get($data[$key] ?? null);
                if (! $account || $account->type !== $settings['type']) {
                    throw ValidationException::withMessages(['mappings.'.$key => 'Pilih akun aktif dengan tipe '.$settings['type'].' untuk '.$settings['label'].'.']);
                }
                AccountMapping::updateOrCreate(['key' => $key], ['chart_of_account_id' => $account->id, 'updated_by' => $actor->id]);
            }
            $this->log($actor, 'account_mapping.updated', 'Memperbarui mapping akun jurnal');
        }, 3);
    }

    public function checkVersion(Model $model, array $data): void
    {
        if ((int) ($data['lock_version'] ?? -1) !== (int) $model->lock_version) {
            throw ValidationException::withMessages(['lock_version' => 'Data sudah berubah. Muat ulang halaman sebelum melanjutkan.']);
        }
    }

    public function log(User $actor, string $action, string $description): void
    {
        ActivityLog::create(['user_id' => $actor->id, 'action' => $action, 'description' => mb_substr($description,0,255)]);
    }
}
