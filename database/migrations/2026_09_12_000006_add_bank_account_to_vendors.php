<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $t) {
            if (! Schema::hasColumn('vendors', 'bank_name')) {
                $t->string('bank_name', 100)->nullable()->after('tax_number');
            }
            if (! Schema::hasColumn('vendors', 'bank_account_number')) {
                $t->string('bank_account_number', 100)->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('vendors', 'bank_account_name')) {
                $t->string('bank_account_name', 255)->nullable()->after('bank_account_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $t) {
            if (Schema::hasColumn('vendors', 'bank_account_name')) {
                $t->dropColumn('bank_account_name');
            }
            if (Schema::hasColumn('vendors', 'bank_account_number')) {
                $t->dropColumn('bank_account_number');
            }
            if (Schema::hasColumn('vendors', 'bank_name')) {
                $t->dropColumn('bank_name');
            }
        });
    }
};
