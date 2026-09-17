<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_instructions', function (Blueprint $table) {
            // Transhipment toggle
            $table->boolean('is_transhipment')->default(false)->after('connecting_vessel');

            // Transit / transhipment route fields
            $table->string('transit_port', 160)->nullable()->after('is_transhipment');
            $table->date('transit_etd')->nullable()->after('transit_port');
            $table->date('transit_eta')->nullable()->after('transit_etd');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_instructions', function (Blueprint $table) {
            $table->dropColumn(['is_transhipment', 'transit_port', 'transit_etd', 'transit_eta']);
        });
    }
};
