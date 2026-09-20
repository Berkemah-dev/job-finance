<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dnps', function (Blueprint $table) {
            $table->string('commodity', 500)->nullable()->after('importer_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dnps', function (Blueprint $table) {
            $table->dropColumn('commodity');
        });
    }
};
