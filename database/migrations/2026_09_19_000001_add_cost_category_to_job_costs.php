<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_costs', function (Blueprint $table) {
            $table->string('cost_category', 30)->nullable()->after('type');
            $table->foreignId('vendor_id')->nullable()->after('payee')->constrained('vendors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_costs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropColumn('cost_category');
        });
    }
};
