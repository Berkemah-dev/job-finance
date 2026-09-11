<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $t) {
            if (! Schema::hasColumn('quotations', 'terms_of_delivery')) {
                $t->string('terms_of_delivery', 50)->nullable()->after('payment_terms');
            }
            if (! Schema::hasColumn('quotations', 'cargo_qty')) {
                $t->string('cargo_qty', 100)->nullable()->after('terms_of_delivery');
            }
            if (! Schema::hasColumn('quotations', 'weight_meas')) {
                $t->string('weight_meas', 100)->nullable()->after('cargo_qty');
            }
            if (! Schema::hasColumn('quotations', 'commodity')) {
                $t->string('commodity', 160)->nullable()->after('weight_meas');
            }
        });

        Schema::table('quotation_items', function (Blueprint $t) {
            if (! Schema::hasColumn('quotation_items', 'note')) {
                $t->string('note', 255)->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $t) {
            $t->dropColumn('note');
        });

        Schema::table('quotations', function (Blueprint $t) {
            $t->dropColumn(['terms_of_delivery', 'cargo_qty', 'weight_meas', 'commodity']);
        });
    }
};
