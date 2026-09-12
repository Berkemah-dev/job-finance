<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_confirmations', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_confirmations', 'consignee_name')) {
                $table->string('consignee_name', 160)->nullable()->after('shipper_name');
            }
            if (! Schema::hasColumn('booking_confirmations', 'consignee_address')) {
                $table->text('consignee_address')->nullable()->after('consignee_name');
            }
            if (! Schema::hasColumn('booking_confirmations', 'consignee_contact')) {
                $table->string('consignee_contact', 120)->nullable()->after('consignee_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_confirmations', function (Blueprint $table) {
            if (Schema::hasColumn('booking_confirmations', 'consignee_contact')) {
                $table->dropColumn('consignee_contact');
            }
            if (Schema::hasColumn('booking_confirmations', 'consignee_address')) {
                $table->dropColumn('consignee_address');
            }
            if (Schema::hasColumn('booking_confirmations', 'consignee_name')) {
                $table->dropColumn('consignee_name');
            }
        });
    }
};
