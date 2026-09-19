<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamUserSeeder extends Seeder
{
    /**
     * Create the named workspace accounts for local/demo use.
     *
     * These credentials are intentionally limited to non-production environments.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $users = [
            ['name' => 'Felix', 'email' => 'felix@jobfinance.test', 'role' => 'sales', 'department' => 'Sales', 'position' => 'Sales'],
            ['name' => 'Elton', 'email' => 'elton@jobfinance.test', 'role' => 'sales', 'department' => 'Sales', 'position' => 'Sales'],
            ['name' => 'Steven', 'email' => 'steven@jobfinance.test', 'role' => 'sales-manager', 'department' => 'Sales', 'position' => 'Sales Manager'],
            ['name' => 'Alliyah', 'email' => 'alliyah@jobfinance.test', 'role' => 'customer-service', 'department' => 'Customer Service', 'position' => 'Customer Service'],
            ['name' => 'Cika', 'email' => 'cika@jobfinance.test', 'role' => 'operational', 'department' => 'Operational', 'position' => 'Operational'],
            ['name' => 'Amelsa', 'email' => 'amelsa@jobfinance.test', 'role' => 'finance', 'department' => 'Finance', 'position' => 'Finance'],
            ['name' => 'Syanne', 'email' => 'syanne@jobfinance.test', 'role' => 'finance-manager', 'department' => 'Finance', 'position' => 'Finance Manager'],
        ];

        foreach ($users as $attributes) {
            $role = Role::where('name', $attributes['role'])->firstOrFail();
            $user = User::firstOrNew(['email' => $attributes['email']]);
            $isNew = ! $user->exists;

            $user->name = $attributes['name'];
            $user->department = $attributes['department'];
            $user->position = $attributes['position'];
            $user->role()->associate($role);
            $user->is_active = true;

            if ($isNew) {
                $user->password = 'JobFinance!2026';
            }

            $user->save();
        }
    }
}
