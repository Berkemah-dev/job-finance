<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->date('issued_at');
            $table->foreignId('customer_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->string('address_label', 160)->nullable();
            $table->text('delivery_address');
            $table->string('container_number', 120)->nullable();
            $table->string('truck_plate_number', 30)->nullable();
            $table->string('driver_name', 160)->nullable();
            $table->string('driver_phone', 50)->nullable();
            $table->string('vehicle_type', 60)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
