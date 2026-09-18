<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs', 'invoice_issuer')) {
                $table->string('invoice_issuer', 160)->nullable()->after('commercial_invoice_number');
            }
            if (!Schema::hasColumn('jobs', 'invoice_amount')) {
                $table->string('invoice_amount', 100)->nullable()->after('invoice_issuer');
            }
            if (!Schema::hasColumn('jobs', 'incoterm')) {
                $table->string('incoterm', 30)->nullable()->after('invoice_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('jobs', 'invoice_issuer')) {
                $columns[] = 'invoice_issuer';
            }
            if (Schema::hasColumn('jobs', 'invoice_amount')) {
                $columns[] = 'invoice_amount';
            }
            if (Schema::hasColumn('jobs', 'incoterm')) {
                $columns[] = 'incoterm';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
