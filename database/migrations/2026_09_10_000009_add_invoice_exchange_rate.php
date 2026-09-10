<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $t) {
            $t->string('currency', 10)->default('IDR');
            $t->decimal('exchange_rate', 18, 2)->default(1);
        });
        Schema::table('job_closing_snapshots', function (Blueprint $t) {
            $t->string('currency', 10)->default('IDR');
            $t->decimal('exchange_rate', 18, 2)->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $t) {
            $t->dropColumn(['currency', 'exchange_rate']);
        });
        Schema::table('job_closing_snapshots', function (Blueprint $t) {
            $t->dropColumn(['currency', 'exchange_rate']);
        });
    }
};
