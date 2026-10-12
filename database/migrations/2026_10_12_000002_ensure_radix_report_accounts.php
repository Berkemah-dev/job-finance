<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $parents = DB::table('chart_of_accounts')
            ->whereIn('code', ['2', '3', '4', '44000'])
            ->pluck('id', 'code');

        $accounts = [
            ['code' => '22500', 'name' => 'Hutang Komisi', 'type' => 'liability', 'parent_id' => $parents['2'] ?? null, 'level' => 2],
            ['code' => '34000', 'name' => 'Laba (rugi) Tahun Berjalan', 'type' => 'equity', 'parent_id' => $parents['3'] ?? null, 'level' => 2],
            ['code' => '71000', 'name' => 'Pendapatan Bunga Bank', 'type' => 'revenue', 'parent_id' => $parents['44000'] ?? ($parents['4'] ?? null), 'level' => 3],
            ['code' => '72000', 'name' => 'Pendapatan Jasa Giro', 'type' => 'revenue', 'parent_id' => $parents['44000'] ?? ($parents['4'] ?? null), 'level' => 3],
        ];

        foreach ($accounts as $acc) {
            $exists = DB::table('chart_of_accounts')->where('code', $acc['code'])->exists();
            if (! $exists) {
                DB::table('chart_of_accounts')->insert($acc + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void {}
};

