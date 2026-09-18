<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('location_name', 160); // e.g. Gudang Cikarang Barat, Pabrik Karawang
            $table->text('address');
            $table->string('pic_name', 160)->nullable();
            $table->string('pic_phone', 50)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('customer_id');
            $table->index('location_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
