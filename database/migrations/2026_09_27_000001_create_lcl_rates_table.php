<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lcl_rates', function (Blueprint $table) {
            $table->id();
            $table->string('country')->nullable();
            $table->string('fob_port');
            $table->string('subject')->nullable();
            $table->string('customer')->nullable();
            $table->decimal('lead_time_days', 8, 2)->default(0);
            $table->decimal('ocean_freight_rate', 18, 2)->default(0);
            $table->decimal('gri_rate', 18, 2)->default(0);
            $table->decimal('cfs_rate', 18, 2)->default(0);
            $table->decimal('cfs_min_wm', 10, 2)->default(2);
            $table->decimal('others_per_set', 18, 2)->default(0);
            $table->decimal('mechanic_rate', 18, 2)->default(0);
            $table->decimal('mechanic_min_wm', 10, 2)->default(2);
            $table->decimal('administration', 18, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['fob_port', 'subject', 'customer'], 'lcl_rates_port_subject_customer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lcl_rates');
    }
};
