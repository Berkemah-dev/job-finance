<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('jobs', 'peb_number')) {
                $table->string('peb_number', 60)->nullable()->after('npe_number');
            }
            if (! Schema::hasColumn('jobs', 'peb_date')) {
                $table->date('peb_date')->nullable()->after('peb_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('jobs', 'peb_date')) {
                $drop[] = 'peb_date';
            }
            if (Schema::hasColumn('jobs', 'peb_number')) {
                $drop[] = 'peb_number';
            }
            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
