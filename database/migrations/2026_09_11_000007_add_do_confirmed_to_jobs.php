<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->timestamp('do_confirmed_at')->nullable()->after('shipment_status_at');
            $table->unsignedBigInteger('do_confirmed_by')->nullable()->after('do_confirmed_at');
            $table->foreign('do_confirmed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['do_confirmed_by']);
            $table->dropColumn(['do_confirmed_at', 'do_confirmed_by']);
        });
    }
};
