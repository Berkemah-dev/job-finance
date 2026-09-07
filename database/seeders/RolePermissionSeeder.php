<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (config('jobfinance.permissions') as $name) {
                Permission::firstOrCreate(['name' => $name]);
            }
            foreach (config('jobfinance.roles') as $name => $settings) {
                $role = Role::firstOrCreate(['name' => $name], ['label' => $settings['label']]);
                $names = $settings['permissions'] === ['*'] ? config('jobfinance.permissions') : $settings['permissions'];
                $role->permissions()->sync(Permission::whereIn('name', $names)->pluck('id'));
            }
        });
    }
}
