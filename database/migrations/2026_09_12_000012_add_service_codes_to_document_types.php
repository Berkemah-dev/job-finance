<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->json('service_codes')->nullable()->after('category');
        });

        foreach ($this->defaultDocuments() as $index => $document) {
            DB::table('document_types')->updateOrInsert(
                ['code' => $document['code']],
                [
                    'name' => $document['name'],
                    'category' => 'shipment',
                    'service_codes' => json_encode($document['service_codes']),
                    'description' => $document['description'] ?? null,
                    'is_required' => true,
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('service_codes');
        });
    }

    private function defaultDocuments(): array
    {
        return [
            ['code' => 'HBL', 'name' => 'HOUSE BL', 'service_codes' => ['exp_sea', 'imp_sea']],
            ['code' => 'MBL', 'name' => 'MASTER BL', 'service_codes' => ['exp_sea', 'imp_sea']],
            ['code' => 'HAWB', 'name' => 'HOUSE AWB', 'service_codes' => ['exp_air', 'imp_air']],
            ['code' => 'MAWB', 'name' => 'MASTER AWB', 'service_codes' => ['exp_air', 'imp_air']],
            ['code' => 'PL', 'name' => 'PACKINGLIST', 'service_codes' => ['exp_sea', 'exp_air', 'imp_sea', 'imp_air']],
            ['code' => 'INV', 'name' => 'INVOICE', 'service_codes' => ['exp_sea', 'exp_air', 'imp_sea', 'imp_air']],
            ['code' => 'DG', 'name' => 'DG DECLARE', 'service_codes' => ['exp_sea', 'exp_air']],
            ['code' => 'PEB', 'name' => 'PEB', 'service_codes' => ['exp_sea', 'exp_air']],
            ['code' => 'NPE', 'name' => 'NPE', 'service_codes' => ['exp_sea', 'exp_air']],
            ['code' => 'LS', 'name' => 'LAPORAN SURVEYOR', 'service_codes' => ['imp_sea', 'imp_air']],
            ['code' => 'COO', 'name' => 'CERTIFICATE OF ORIGIN', 'service_codes' => ['imp_sea', 'imp_air']],
            ['code' => 'ECOO', 'name' => 'E-CERTIFICATE OF ORIGIN', 'service_codes' => ['imp_sea', 'imp_air']],
        ];
    }
};
