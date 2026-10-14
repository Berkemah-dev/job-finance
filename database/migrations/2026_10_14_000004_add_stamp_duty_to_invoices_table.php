<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'stamp_duty')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->decimal('stamp_duty', 18, 2)->default(0)->after('tax');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'stamp_duty')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('stamp_duty');
            });
        }
    }
};
