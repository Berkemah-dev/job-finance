<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_pricings', function (Blueprint $t) {
            $t->date('effective_until')->nullable()->after('effective_date');
        });

        Schema::table('trucking_prices', function (Blueprint $t) {
            $t->date('effective_until')->nullable()->after('effective_date');
            $t->index(
                ['port_origin', 'destination', 'container_type', 'effective_date', 'effective_until'],
                'trucking_prices_lookup_effective_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('trucking_prices', function (Blueprint $t) {
            $t->dropIndex('trucking_prices_lookup_effective_idx');
            $t->dropColumn('effective_until');
        });

        Schema::table('weekly_pricings', function (Blueprint $t) {
            $t->dropColumn('effective_until');
        });
    }
};
