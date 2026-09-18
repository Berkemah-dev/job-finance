<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_trucks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('driver_name', 160);
            $table->string('driver_phone', 50)->nullable();
            $table->string('plate_number', 30);
            $table->string('vehicle_type', 60)->nullable(); // e.g. Trailer 20ft, Trailer 40ft, Tronton, Box
            $table->string('notes', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('plate_number');
            $table->index('driver_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_trucks');
    }
};
