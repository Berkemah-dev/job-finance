<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $types = [
            ['code' => 'IMPSEA-DO', 'name' => 'DELIVERY ORDER (DO)', 'service_codes' => ['imp_sea'], 'sort_order' => 86],
            ['code' => 'IMPAIR-DO', 'name' => 'DELIVERY ORDER (DO)', 'service_codes' => ['imp_air'], 'sort_order' => 86],
            ['code' => 'EXPSEA-BC', 'name' => 'BILL OF CUSTOMS (BC)', 'service_codes' => ['exp_sea'], 'sort_order' => 86],
            ['code' => 'EXPAIR-BC', 'name' => 'BILL OF CUSTOMS (BC)', 'service_codes' => ['exp_air'], 'sort_order' => 86],
        ];

        foreach ($types as $type) {
            DB::table('document_types')->updateOrInsert(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'category' => 'shipment',
                    'service_codes' => json_encode($type['service_codes']),
                    'description' => $type['name'].' untuk Job Order',
                    'is_required' => false,
                    'is_active' => true,
                    'sort_order' => $type['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('document_types')->whereIn('code', ['IMPSEA-DO', 'IMPAIR-DO', 'EXPSEA-BC', 'EXPAIR-BC'])->delete();
    }
};
