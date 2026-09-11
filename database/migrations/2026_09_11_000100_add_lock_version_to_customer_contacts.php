<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customer_contacts', 'lock_version')) {
            Schema::table('customer_contacts', function (Blueprint $table) {
                $table->unsignedInteger('lock_version')->default(0)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customer_contacts', 'lock_version')) {
            Schema::table('customer_contacts', function (Blueprint $table) {
                $table->dropColumn('lock_version');
            });
        }
    }
};