<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_types')) {
            Schema::create('service_types', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name', 100);
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        foreach ([
            ['code' => 'exp_sea', 'name' => 'EXPORT SEA', 'sort_order' => 10],
            ['code' => 'exp_air', 'name' => 'EXPORT AIR', 'sort_order' => 20],
            ['code' => 'imp_sea', 'name' => 'IMPORT SEA', 'sort_order' => 30],
            ['code' => 'imp_air', 'name' => 'IMPORT AIR', 'sort_order' => 40],
            ['code' => 'domestic', 'name' => 'DOMESTIC / TRUCKING', 'sort_order' => 50],
        ] as $service) {
            DB::table('service_types')->updateOrInsert(
                ['code' => $service['code']],
                $service + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_types');
    }
};
