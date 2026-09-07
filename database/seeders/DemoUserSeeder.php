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
            if (! User::where('email', $email)->exists()) {
                $user = new User(['name' => $role->label, 'email' => $email, 'password' => 'JobFinance!2026']);
                $user->role()->associate($role);
                $user->save();
            }
        }
    }
}
