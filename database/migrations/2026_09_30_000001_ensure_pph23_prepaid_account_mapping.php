<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $id = DB::table('chart_of_accounts')->where('code', '11192')->value('id');
        if (! $id) {
            $id = DB::table('chart_of_accounts')->insertGetId([
                'code' => '11192',
                'name' => 'PPH 23 Dimuka',
                'type' => 'asset',
                'level' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('account_mappings')->updateOrInsert(
            ['key' => 'pph23_prepaid'],
            ['chart_of_account_id' => $id, 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('account_mappings')->where('key', 'pph23_prepaid')->delete();
    }
};
