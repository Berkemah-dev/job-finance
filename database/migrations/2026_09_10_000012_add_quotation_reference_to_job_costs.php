<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_costs', function (Blueprint $t) {
            $t->foreignId('quotation_id')->nullable()->after('job_id')->constrained('quotations')->nullOnDelete();
            $t->foreignId('quotation_item_id')->nullable()->after('quotation_id')->constrained('quotation_items')->nullOnDelete();
            $t->index(['quotation_id', 'quotation_item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('job_costs', function (Blueprint $t) {
            $t->dropIndex(['quotation_id', 'quotation_item_id']);
            $t->dropConstrainedForeignId('quotation_id');
            $t->dropConstrainedForeignId('quotation_item_id');
        });
    }
};
