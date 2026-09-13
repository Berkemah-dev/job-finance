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
            $table->string('commercial_invoice_number', 60)->nullable()->after('npe_number');
            $table->date('commercial_invoice_date')->nullable()->after('commercial_invoice_number');
            $table->string('packing_list_number', 60)->nullable()->after('commercial_invoice_date');
            $table->date('packing_list_date')->nullable()->after('packing_list_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn([
                'commercial_invoice_number',
                'commercial_invoice_date',
                'packing_list_number',
                'packing_list_date',
            ]);
        });
    }
};
