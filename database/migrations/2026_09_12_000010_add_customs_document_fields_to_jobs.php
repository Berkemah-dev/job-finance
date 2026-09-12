<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->string('nopen', 60)->nullable()->after('booking_reference');
            $table->string('npe_number', 60)->nullable()->after('nopen');
            $table->string('peb_number', 60)->nullable()->after('npe_number');
            $table->date('peb_date')->nullable()->after('peb_number');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['nopen', 'npe_number', 'peb_number', 'peb_date']);
        });
    }
};
