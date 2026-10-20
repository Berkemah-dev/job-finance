<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $parentId = DB::table('chart_of_accounts')->where('code', '65000')->value('id');
        $accountId = DB::table('chart_of_accounts')->where('code', '65600')->value('id');

        if (! $accountId) {
            $accountId = DB::table('chart_of_accounts')->insertGetId([
                'code' => '65600',
                'name' => 'Biaya Materai',
                'type' => 'expense',
                'parent_id' => $parentId,
                'level' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('chart_of_accounts')->where('id', $accountId)->update([
                'name' => 'Biaya Materai',
                'type' => 'expense',
                'parent_id' => $parentId,
                'level' => 3,
                'updated_at' => now(),
            ]);
        }

        DB::table('account_mappings')->updateOrInsert(
            ['key' => 'stamp_duty_expense'],
            ['chart_of_account_id' => $accountId, 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('account_mappings')->where('key', 'stamp_duty_expense')->delete();
    }
};
