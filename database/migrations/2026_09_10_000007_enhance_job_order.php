<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $t) {
            $t->string('shipper_name', 160)->nullable();
            $t->text('shipper_address')->nullable();
            $t->string('consignee_name', 160)->nullable();
            $t->text('consignee_address')->nullable();
            $t->string('pol', 120)->nullable();
            $t->string('pod', 120)->nullable();
            $t->date('etd')->nullable();
            $t->date('eta')->nullable();
            $t->string('vessel_voyage', 120)->nullable();
            $t->string('flight_number', 60)->nullable();
            $t->string('bl_number', 60)->nullable();
            $t->string('hbl_number', 60)->nullable();
            $t->string('awb_number', 60)->nullable();
            $t->string('hawb_number', 60)->nullable();
            $t->string('booking_reference', 60)->nullable();
            $t->unsignedInteger('package_count')->nullable();
            $t->decimal('gross_weight', 12, 2)->nullable();
            $t->decimal('volume', 12, 2)->nullable();
            $t->string('container_type', 10)->nullable();
            $t->foreignId('sales_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('cs_id')->nullable()->constrained('users')->restrictOnDelete();
        });

        Schema::create('job_status_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('job_id')->constrained()->cascadeOnDelete();
            $t->string('from_status', 20)->nullable();
            $t->string('to_status', 20);
            $t->text('note')->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->index('job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_status_history');
        Schema::table('jobs', function (Blueprint $t) {
            $t->dropConstrainedForeignId('sales_id');
            $t->dropConstrainedForeignId('cs_id');
            $t->dropColumn(['shipper_name', 'shipper_address', 'consignee_name', 'consignee_address', 'pol', 'pod', 'etd', 'eta', 'vessel_voyage', 'flight_number', 'bl_number', 'hbl_number', 'awb_number', 'hawb_number', 'booking_reference', 'package_count', 'gross_weight', 'volume', 'container_type']);
        });
    }
};
