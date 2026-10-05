<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_costs', function (Blueprint $table) {
            $table->decimal('pph23_amount', 18, 2)->default(0)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('job_costs', function (Blueprint $table) {
            $table->decimal('pph23_amount', 18, 2)->default(0)->nullable(false)->change();
        });
    }
};
