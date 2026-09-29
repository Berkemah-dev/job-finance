<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $t) {
            if (! Schema::hasColumn('quotations', 'internal_notes')) {
                $t->text('internal_notes')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $t) {
            if (Schema::hasColumn('quotations', 'internal_notes')) {
                $t->dropColumn('internal_notes');
            }
        });
    }
};
