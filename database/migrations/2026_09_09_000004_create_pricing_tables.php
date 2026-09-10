<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_pricings', function (Blueprint $t) {
            $t->id();
            $t->string('week', 20)->index();
            $t->date('effective_date');
            $t->string('currency', 10)->default('IDR');
            $t->decimal('exchange_rate', 18, 2)->default(1);
            $t->string('service', 40)->nullable();
            $t->string('notes', 1000)->nullable();
            $t->boolean('is_active')->default(true)->index();
            $t->unsignedInteger('lock_version')->default(0);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('trucking_prices', function (Blueprint $t) {
            $t->id();
            $t->string('port_origin', 120)->index();
            $t->string('destination', 120)->index();
            $t->boolean('overweight')->default(false)->index();
            $t->string('container_type', 20)->index();
            $t->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $t->decimal('price', 18, 2);
            $t->string('currency', 10)->default('IDR');
            $t->date('effective_date');
            $t->boolean('is_active')->default(true)->index();
            $t->unsignedInteger('lock_version')->default(0);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['port_origin', 'destination', 'container_type'], 'trucking_price_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trucking_prices');
        Schema::dropIfExists('weekly_pricings');
    }
};
