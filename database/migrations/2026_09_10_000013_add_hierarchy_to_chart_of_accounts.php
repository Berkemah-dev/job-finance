<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('chart_of_accounts', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('type')->constrained('chart_of_accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('chart_of_accounts', 'level')) {
                $table->unsignedTinyInteger('level')->default(1)->after('parent_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'level']);
        });
    }
};
