<?php

namespace App\Services;

use App\Models\AccountMapping;
use App\Models\ActivityLog;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MasterDataService
{
    private function permissionFor(Model $model): string
    {
        return match (true) {
            $model instanceof Customer => 'customers.manage',
            $model instanceof Vendor => 'vendors.manage',
            default => 'coa.manage',
        };
    }

    private function keyFor(Model $model): string
    {
        return match (true) {
            $model instanceof Customer => 'customer',
            $model instanceof Vendor => 'vendor',
            default => 'account',
        };
    }

    public function save(Model $model, array $data, User $actor): Model
    {
        return DB::transaction(function () use ($model, $data, $actor) {
            Gate::forUser($actor)->authorize($this->permissionFor($model));
            $new = ! $model->exists;
            if (! $new) {
                $model = $model->newQuery()->lockForUpdate()->findOrFail($model->id);
                $this->checkVersion($model, $data);
                if ($model instanceof ChartOfAccount && $model->type !== $data['type'] && $model->mappings()->exists()) {
                    throw ValidationException::withMessages(['type' => 'Tipe akun yang digunakan mapping tidak dapat diubah. Ubah mapping terlebih dahulu.']);
                }
            }
            $model->fill($data);
            if ($model instanceof ChartOfAccount) {
                if (array_key_exists('parent_id', $data) && ! empty($data['parent_id'])) {
                    $parent = ChartOfAccount::find($data['parent_id']);
                    if ($parent) {
                        $model->level = $parent->level + 1;
                    }
                } elseif ($new && empty($data['parent_id'])) {
                    $model->level = 1;
                }
            }
            if ($new) {
                $model->created_by = $actor->id;
            }
            $model->updated_by = $actor->id;
            $model->lock_version = $new ? 0 : $model->lock_version + 1;
            $model->save();
            $this->log($actor, $this->keyFor($model).($new ? '.created' : '.updated'), ($new ? 'Membuat ' : 'Memperbarui ').$model->code.' · '.$model->name, ['module' => $this->keyFor($model), 'record_id' => $model->id]);

            return $model;
        }, 3);
    }

    public function archive(Model $model, array $data, User $actor): void
    {
        DB::transaction(function () use ($model, $data, $actor) {
            Gate::forUser($actor)->authorize($this->permissionFor($model));
            $model = $model->newQuery()->lockForUpdate()->findOrFail($model->id);
            $this->checkVersion($model, $data);
            if ($model instanceof ChartOfAccount) {
                if ($model->mappings()->exists()) {
                    throw ValidationException::withMessages(['account' => 'Akun masih digunakan pada mapping. Pilih akun pengganti sebelum mengarsipkan.']);
                }
                if ($model->children()->exists()) {
                    throw ValidationException::withMessages(['account' => 'Akun tidak dapat diarsipkan karena masih memiliki sub-akun. Hapus atau pindahkan sub-akun terlebih dahulu.']);
                }
            }
            $model->updated_by = $actor->id;
            $model->lock_version++;
            $model->save();
            $model->delete();
            $this->log($actor, $this->keyFor($model).'.archived', 'Mengarsipkan '.$model->code.' · '.$model->name, ['module' => $this->keyFor($model), 'record_id' => $model->id]);
        }, 3);
    }

    public function restore(Model $model, array $data, User $actor): void
    {
        DB::transaction(function () use ($model, $data, $actor) {
            Gate::forUser($actor)->authorize($this->permissionFor($model));
            $model = $model->newQueryWithoutScopes()->whereNotNull($model->getDeletedAtColumn())->whereKey($model->id)->lockForUpdate()->firstOrFail();
            $this->checkVersion($model, $data);
            $model->restore();
            $model->updated_by = $actor->id;
            $model->lock_version++;
            $model->save();
            $this->log($actor, $this->keyFor($model).'.restored', 'Mengaktifkan kembali '.$model->code.' · '.$model->name, ['module' => $this->keyFor($model), 'record_id' => $model->id]);
        }, 3);
    }

    public function toggleActive(Model $model, array $data, User $actor): void
    {
        DB::transaction(function () use ($model, $data, $actor) {
            Gate::forUser($actor)->authorize($this->permissionFor($model));
            $model = $model->newQuery()->lockForUpdate()->findOrFail($model->id);
            $this->checkVersion($model, $data);
            $model->is_active = ! $model->is_active;
            $model->updated_by = $actor->id;
            $model->lock_version++;
            $model->save();
            $this->log($actor, $this->keyFor($model).'.'.$this->stateFor($model), 'Mengubah status '.$model->code.' · '.$model->name, ['module' => $this->keyFor($model), 'record_id' => $model->id, 'after' => ['is_active' => $model->is_active]]);
        }, 3);
    }

    private function stateFor(Model $model): string
    {
        return $model->is_active ? 'activated' : 'deactivated';
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

    public function log(User $actor, string $action, string $description, array $context = []): void
    {
        ActivityLog::create([
            'user_id' => $actor->id,
            'role_id' => $actor->role_id,
            'action' => $action,
            'module' => $context['module'] ?? null,
            'record_id' => $context['record_id'] ?? null,
            'before' => $context['before'] ?? null,
            'after' => $context['after'] ?? null,
            'ip' => $context['ip'] ?? request()->ip(),
            'description' => mb_substr($description, 0, 255),
        ]);
    }
}
