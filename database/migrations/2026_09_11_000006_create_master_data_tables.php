<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ports', function (Blueprint $t) {
            $t->id();
            $t->string('name', 150)->index();
            $t->string('code', 20)->index();
            $t->boolean('is_active')->default(true)->index();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('charge_types', function (Blueprint $t) {
            $t->id();
            $t->string('name', 150)->unique();
            $t->boolean('is_active')->default(true)->index();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('container_units', function (Blueprint $t) {
            $t->id();
            $t->string('name', 50)->unique();
            $t->boolean('is_active')->default(true)->index();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('container_units');
        Schema::dropIfExists('charge_types');
        Schema::dropIfExists('ports');
    }
};
