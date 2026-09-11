<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trucking_prices', function (Blueprint $t) {
            if (! Schema::hasColumn('trucking_prices', 'selling_price')) {
                $t->decimal('selling_price', 15, 2)->nullable()->default(0)->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trucking_prices', function (Blueprint $t) {
            if (Schema::hasColumn('trucking_prices', 'selling_price')) {
                $t->dropColumn('selling_price');
            }
        });
    }
};
