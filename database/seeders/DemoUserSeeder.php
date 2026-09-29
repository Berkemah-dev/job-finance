<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        foreach (Role::all() as $role) {
            $email = ($role->name === 'super-admin' ? 'admin' : $role->name).'@jobfinance.test';
            $user = User::firstOrNew(['email' => $email]);
            if (! $user->exists) {
                $user->name = $role->label;
                $user->password = 'JobFinance!2026';
            }
            $user->role()->associate($role);
            $user->is_active = true;
            $user->save();
        }
    }
}
