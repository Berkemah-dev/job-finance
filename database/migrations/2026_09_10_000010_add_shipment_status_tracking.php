<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->string('shipment_status', 30)->nullable()->index()->after('status');
            $table->timestamp('shipment_status_at')->nullable()->after('shipment_status');
            $table->unsignedBigInteger('shipment_status_by')->nullable()->after('shipment_status_at');
            $table->foreign('shipment_status_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('job_shipment_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('note', 1000)->nullable();
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('jobs')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index('to_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_shipment_statuses');

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['shipment_status_by']);
            $table->dropColumn(['shipment_status', 'shipment_status_at', 'shipment_status_by']);
        });
    }
};
