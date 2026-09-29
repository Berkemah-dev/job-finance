<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class InitialAccessSeeder extends Seeder
{
    /**
     * Akun akses awal untuk deployment baru. Aman dijalankan ulang karena password
     * hanya dibuat untuk akun yang belum ada.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Super Admin', 'email' => 'admin@jobfinance.test', 'role' => 'super-admin', 'department' => 'Management', 'position' => 'Super Admin'],
            ['name' => 'Felix', 'email' => 'felix@jobfinance.test', 'role' => 'sales', 'department' => 'Sales', 'position' => 'Sales'],
            ['name' => 'Elton', 'email' => 'elton@jobfinance.test', 'role' => 'sales', 'department' => 'Sales', 'position' => 'Sales'],
            ['name' => 'Steven', 'email' => 'steven@jobfinance.test', 'role' => 'sales-manager', 'department' => 'Sales', 'position' => 'Sales Manager'],
            ['name' => 'Alliyah', 'email' => 'alliyah@jobfinance.test', 'role' => 'customer-service', 'department' => 'Customer Service', 'position' => 'Customer Service'],
            ['name' => 'Cika', 'email' => 'cika@jobfinance.test', 'role' => 'operational', 'department' => 'Operational', 'position' => 'Operational'],
            ['name' => 'Amelsa', 'email' => 'amelsa@jobfinance.test', 'role' => 'finance', 'department' => 'Finance', 'position' => 'Finance'],
            ['name' => 'Syanne', 'email' => 'syanne@jobfinance.test', 'role' => 'finance-manager', 'department' => 'Finance', 'position' => 'Finance Manager'],
            ['name' => 'Management', 'email' => 'management@jobfinance.test', 'role' => 'management', 'department' => 'Management', 'position' => 'Management'],
        ];

        foreach ($users as $attributes) {
            $role = Role::where('name', $attributes['role'])->firstOrFail();
            $user = User::firstOrNew(['email' => $attributes['email']]);
            $isNew = ! $user->exists;

            $user->fill([
                'name' => $attributes['name'],
                'department' => $attributes['department'],
                'position' => $attributes['position'],
                'is_active' => true,
            ]);
            $user->role()->associate($role);

            if ($isNew) {
                $user->password = 'JobFinance!2026';
            }

            $user->save();
        }
    }
}
