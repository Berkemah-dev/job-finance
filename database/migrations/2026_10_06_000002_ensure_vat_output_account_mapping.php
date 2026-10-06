<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $id = DB::table('chart_of_accounts')->where('code', '28000')->value('id');
        if (! $id) {
            $id = DB::table('chart_of_accounts')->insertGetId([
                'code' => '28000',
                'name' => 'PPN Keluaran',
                'type' => 'liability',
                'level' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('account_mappings')->updateOrInsert(
            ['key' => 'tax_payable'],
            ['chart_of_account_id' => $id, 'updated_at' => now()]
        );
    }

    public function down(): void
    {
    }
};
