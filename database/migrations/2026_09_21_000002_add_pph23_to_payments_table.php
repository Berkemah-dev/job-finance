<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $t) {
            $t->decimal('pph23_amount', 18, 2)->default(0)->after('amount');
            $t->string('currency', 3)->default('IDR')->after('pph23_amount');
            $t->decimal('exchange_rate', 12, 4)->default(1)->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $t) {
            $t->dropColumn(['pph23_amount', 'currency', 'exchange_rate']);
        });
    }
};
