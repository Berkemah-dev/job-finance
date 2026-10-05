<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_costs', function (Blueprint $table) {
            $table->string('currency', 10)->default('IDR')->after('unit');
            $table->decimal('exchange_rate', 18, 4)->default(1)->after('currency');
            $table->decimal('foreign_unit_cost', 18, 2)->nullable()->after('unit_price');
            $table->decimal('foreign_unit_price', 18, 2)->nullable()->after('foreign_unit_cost');
        });
    }

    public function down(): void
    {
        Schema::table('job_costs', function (Blueprint $table) {
            $table->dropColumn(['currency', 'exchange_rate', 'foreign_unit_cost', 'foreign_unit_price']);
        });
    }
};
