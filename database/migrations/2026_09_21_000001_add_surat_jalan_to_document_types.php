<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $types = [
            [
                'code' => 'IMPSEA-SJ',
                'name' => 'SURAT JALAN',
                'category' => 'delivery',
                'service_codes' => json_encode(['imp_sea']),
                'description' => 'Berkas Surat Jalan yang ditandatangani supir/penerima',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 80,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'IMPAIR-SJ',
                'name' => 'SURAT JALAN',
                'category' => 'delivery',
                'service_codes' => json_encode(['imp_air']),
                'description' => 'Berkas Surat Jalan yang ditandatangani supir/penerima',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 80,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'GEN-SJ',
                'name' => 'SURAT JALAN',
                'category' => 'delivery',
                'service_codes' => json_encode(['sea', 'air', 'trucking', 'domestic']),
                'description' => 'Berkas Surat Jalan pengantaran umum',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 85,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($types as $type) {
            DB::table('document_types')->updateOrInsert(
                ['code' => $type['code']],
                $type
            );
        }
    }

    public function down(): void
    {
        DB::table('document_types')->whereIn('code', ['IMPSEA-SJ', 'IMPAIR-SJ', 'GEN-SJ'])->delete();
    }
};
