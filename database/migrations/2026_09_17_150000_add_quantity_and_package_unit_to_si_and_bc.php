<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_confirmations', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_confirmations', 'package_unit')) {
                $table->string('package_unit', 50)->nullable()->after('quantity');
            }
        });

        Schema::table('shipping_instructions', function (Blueprint $table) {
            if (!Schema::hasColumn('shipping_instructions', 'quantity')) {
                $table->string('quantity', 100)->nullable()->after('pod');
            }
            if (!Schema::hasColumn('shipping_instructions', 'package_unit')) {
                $table->string('package_unit', 50)->nullable()->after('quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_confirmations', function (Blueprint $table) {
            if (Schema::hasColumn('booking_confirmations', 'package_unit')) {
                $table->dropColumn('package_unit');
            }
        });

        Schema::table('shipping_instructions', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('shipping_instructions', 'quantity')) {
                $cols[] = 'quantity';
            }
            if (Schema::hasColumn('shipping_instructions', 'package_unit')) {
                $cols[] = 'package_unit';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
