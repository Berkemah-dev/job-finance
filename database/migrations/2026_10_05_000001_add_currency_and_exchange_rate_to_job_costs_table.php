<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_costs', function (Blueprint $table) {
            if (! Schema::hasColumn('job_costs', 'currency')) {
                $table->string('currency', 3)->default('IDR')->after('unit');
            }
            if (! Schema::hasColumn('job_costs', 'exchange_rate')) {
                $table->decimal('exchange_rate', 18, 4)->default(1.0000)->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_costs', function (Blueprint $table) {
            $table->dropColumn(['currency', 'exchange_rate']);
        });
    }
};
