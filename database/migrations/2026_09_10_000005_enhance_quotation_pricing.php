<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $t) {
            $t->string('container_type', 20)->nullable()->after('unit');
            $t->boolean('overweight')->default(false)->after('container_type');
            $t->decimal('gross_weight', 12, 2)->nullable()->after('overweight');
            $t->decimal('volume', 12, 4)->nullable()->after('gross_weight');
            $t->string('currency', 10)->default('IDR')->after('volume');
            $t->decimal('exchange_rate', 18, 2)->default(1)->after('currency');
            $t->string('pricing_source', 20)->nullable()->after('exchange_rate');
            $t->unsignedBigInteger('pricing_id')->nullable()->after('pricing_source');
            $t->json('pricing_snapshot')->nullable()->after('pricing_id');
            $t->index(['pricing_source', 'pricing_id'], 'quotation_items_pricing_idx');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $t) {
            $t->dropIndex('quotation_items_pricing_idx');
            foreach (['pricing_snapshot', 'pricing_id', 'pricing_source', 'exchange_rate', 'currency', 'volume', 'gross_weight', 'overweight', 'container_type'] as $column) {
                $t->dropColumn($column);
            }
        });
    }
};