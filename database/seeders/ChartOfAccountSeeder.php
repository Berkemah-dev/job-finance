<?php

namespace Database\Seeders;

use App\Models\AccountMapping;
use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (config('accounting.mappings') as $key => $settings) {
                $account = ChartOfAccount::withTrashed()->firstOrCreate(['code' => $settings['code']], ['name' => $settings['name'], 'type' => $settings['type']]);
                if (! $account->trashed() && $account->type === $settings['type']) {
                    AccountMapping::firstOrCreate(['key' => $key], ['chart_of_account_id' => $account->id]);
                }
            }
            foreach (['2101' => ['Utang Usaha', 'liability'], '3101' => ['Modal Pemilik', 'equity']] as $code => [$name,$type]) {
                ChartOfAccount::withTrashed()->firstOrCreate(['code' => $code], compact('name', 'type'));
            }
        });
    }
}
