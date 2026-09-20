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
            $table->index('quotation_id', 'jobs_quotation_id_index');
            $table->dropUnique('jobs_quotation_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->unique('quotation_id', 'jobs_quotation_id_unique');
            $table->dropIndex('jobs_quotation_id_index');
        });
    }
};
