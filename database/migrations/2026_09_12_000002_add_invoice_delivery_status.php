<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('delivery_status', 20)->default('not_sent')->after('status')
                ->comment('not_sent | sent | received');
            $table->timestamp('sent_at')->nullable()->after('delivery_status');
            $table->timestamp('received_at')->nullable()->after('sent_at');
            $table->string('tracking_number', 100)->nullable()->after('received_at');
            $table->text('delivery_notes')->nullable()->after('tracking_number');
            $table->unsignedBigInteger('sent_by')->nullable()->after('delivery_notes');
            $table->foreign('sent_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['sent_by']);
            $table->dropColumn(['delivery_status', 'sent_at', 'received_at', 'tracking_number', 'delivery_notes', 'sent_by']);
        });
    }
};
