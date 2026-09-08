<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(private MasterDataService $master) {}

    public function save(?User $user, array $data, User $actor): User
    {
        return DB::transaction(function () use ($user, $data, $actor) {
            Gate::forUser($actor)->authorize('users.manage');
            $new = $user === null;
            if ($new) {
                $user = new User;
            } else {
                $user = User::lockForUpdate()->findOrFail($user->id);
                $this->master->checkVersion($user, $data);
            }
            $role = Role::lockForUpdate()->findOrFail($data['role_id']);
            if (! $new && $user->role?->name === 'super-admin' && $role->name !== 'super-admin') {
                $adminRole = Role::where('name', 'super-admin')->firstOrFail();
                if (User::where('role_id', $adminRole->id)->lockForUpdate()->get(['id'])->count() <= 1) {
                    throw ValidationException::withMessages(['role_id' => 'Minimal satu Super Admin harus tetap tersedia.']);
                }
            }
            $user->fill(Arr::only($data, ['name', 'email']));
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }
            $user->role()->associate($role);
            $user->lock_version = $new ? 0 : $user->lock_version + 1;
            $user->save();
            $this->master->log($actor, $new ? 'user.created' : 'user.updated', ($new ? 'Membuat ' : 'Memperbarui ').$user->email.' · '.$role->label);

            return $user;
        }, 3);
    }
}
