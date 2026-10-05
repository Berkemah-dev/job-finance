<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $id = DB::table('chart_of_accounts')->where('code', '27000')->value('id');
        if (! $id) {
            $id = DB::table('chart_of_accounts')->where('code', '2102')->value('id');
        }
        if (! $id) {
            $id = DB::table('chart_of_accounts')->insertGetId([
                'code' => '27000',
                'name' => 'PPH 23 Hutang Pajak',
                'type' => 'liability',
                'level' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('account_mappings')->updateOrInsert(
            ['key' => 'pph23_payable'],
            ['chart_of_account_id' => $id, 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('account_mappings')->where('key', 'pph23_payable')->delete();
    }
};
