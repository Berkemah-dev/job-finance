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
            ['code' => 'EXPSEA-HBL', 'name' => 'HOUSE BL', 'service_codes' => ['exp_sea']],
            ['code' => 'EXPSEA-MBL', 'name' => 'MASTER BL', 'service_codes' => ['exp_sea']],
            ['code' => 'EXPSEA-PL', 'name' => 'PACKINGLIST', 'service_codes' => ['exp_sea']],
            ['code' => 'EXPSEA-INV', 'name' => 'INVOICE', 'service_codes' => ['exp_sea']],
            ['code' => 'EXPSEA-DG', 'name' => 'DG DECLARE', 'service_codes' => ['exp_sea']],
            ['code' => 'EXPSEA-PEB', 'name' => 'PEB', 'service_codes' => ['exp_sea']],
            ['code' => 'EXPSEA-NPE', 'name' => 'NPE', 'service_codes' => ['exp_sea']],
            ['code' => 'EXPAIR-HAWB', 'name' => 'HOUSE AWB', 'service_codes' => ['exp_air']],
            ['code' => 'EXPAIR-MAWB', 'name' => 'MASTER AWB', 'service_codes' => ['exp_air']],
            ['code' => 'EXPAIR-PL', 'name' => 'PACKINGLIST', 'service_codes' => ['exp_air']],
            ['code' => 'EXPAIR-INV', 'name' => 'INVOICE', 'service_codes' => ['exp_air']],
            ['code' => 'EXPAIR-DG', 'name' => 'DG DECLARE', 'service_codes' => ['exp_air']],
            ['code' => 'EXPAIR-PEBNPE', 'name' => 'PEB NPE', 'service_codes' => ['exp_air']],
            ['code' => 'IMPSEA-HBL', 'name' => 'HOUSE BL', 'service_codes' => ['imp_sea']],
            ['code' => 'IMPSEA-MBL', 'name' => 'MASTER BL', 'service_codes' => ['imp_sea']],
            ['code' => 'IMPSEA-PL', 'name' => 'PACKINGLIST', 'service_codes' => ['imp_sea']],
            ['code' => 'IMPSEA-INV', 'name' => 'INVOICE', 'service_codes' => ['imp_sea']],
            ['code' => 'IMPSEA-LS', 'name' => 'LAPORAN SURVEYOR', 'service_codes' => ['imp_sea']],
            ['code' => 'IMPSEA-COO', 'name' => 'CERTIFICATE OF ORIGIN', 'service_codes' => ['imp_sea']],
            ['code' => 'IMPSEA-ECOO', 'name' => 'E-CERTIFICATE OF ORIGIN', 'service_codes' => ['imp_sea']],
            ['code' => 'IMPAIR-HAWB', 'name' => 'HOUSE AWB', 'service_codes' => ['imp_air']],
            ['code' => 'IMPAIR-MAWB', 'name' => 'MASTER AWB', 'service_codes' => ['imp_air']],
            ['code' => 'IMPAIR-PL', 'name' => 'PACKINGLIST', 'service_codes' => ['imp_air']],
            ['code' => 'IMPAIR-INV', 'name' => 'INVOICE', 'service_codes' => ['imp_air']],
            ['code' => 'IMPAIR-LS', 'name' => 'LAPORAN SURVEYOR', 'service_codes' => ['imp_air']],
            ['code' => 'IMPAIR-COO', 'name' => 'CERTIFICATE OF ORIGIN', 'service_codes' => ['imp_air']],
            ['code' => 'IMPAIR-ECOO', 'name' => 'E-CERTIFICATE OF ORIGIN', 'service_codes' => ['imp_air']],
        ];
    }
};
