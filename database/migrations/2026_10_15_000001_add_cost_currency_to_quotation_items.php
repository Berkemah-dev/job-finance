<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->string('cost_currency', 3)->nullable()->after('currency');
            $table->decimal('cost_exchange_rate', 18, 2)->nullable()->after('exchange_rate');
        });

        DB::table('quotation_items')->whereNull('cost_currency')->update([
            'cost_currency' => DB::raw('currency'),
            'cost_exchange_rate' => DB::raw('exchange_rate'),
        ]);
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn(['cost_currency', 'cost_exchange_rate']);
        });
    }
};
