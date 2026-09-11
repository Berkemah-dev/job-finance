<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_confirmations', function (Blueprint $table) {
            $table->id();
            $table->string('number', 60)->unique();
            $table->date('booking_date');
            $table->foreignId('job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('contact_person', 120)->nullable();
            $table->string('customer_ref', 100)->nullable();
            $table->string('shipper_name', 160)->nullable();
            $table->string('carrier_name', 160)->nullable();
            $table->string('carrier_booking_no', 100)->nullable();
            $table->string('vessel_voyage', 120)->nullable();
            $table->string('service_term', 40)->default('CY/CY');
            $table->string('pol', 120)->nullable();
            $table->string('pod', 120)->nullable();
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();
            $table->string('quantity', 100)->nullable();
            $table->text('cargo_description')->nullable();
            $table->decimal('gross_weight', 12, 2)->nullable();
            $table->decimal('volume', 12, 3)->nullable();
            $table->text('delivery_cargo_to')->nullable();
            $table->dateTime('doc_cutoff_at')->nullable();
            $table->dateTime('cy_cutoff_at')->nullable();
            $table->dateTime('delivery_cutoff_at')->nullable();
            $table->string('status', 30)->default('confirmed');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('number');
            $table->index('booking_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_confirmations');
    }
};
