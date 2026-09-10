<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $t) {
            $t->foreignId('sales_id')->nullable()->after('customer_id')->constrained('users')->nullOnDelete();
            $t->index('sales_id');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $t) {
            $t->dropIndex(['sales_id']);
            $t->dropConstrainedForeignId('sales_id');
        });
    }
};
