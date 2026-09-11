<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_instructions', function (Blueprint $table) {
            $table->id();
            $table->string('number', 60)->unique();
            $table->date('si_date');
            $table->foreignId('job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            
            // Carrier / To section
            $table->string('to_carrier', 160)->nullable();
            $table->string('carrier_attn', 120)->nullable();
            $table->string('carrier_contact', 120)->nullable();
            
            // Cargo Parties
            $table->string('shipper_name', 160)->nullable();
            $table->text('shipper_address')->nullable();
            $table->string('consignee_name', 160)->nullable();
            $table->text('consignee_address')->nullable();
            $table->text('notify_party')->nullable();
            
            // Vessel & Schedule
            $table->string('vessel_voyage', 120)->nullable();
            $table->string('connecting_vessel', 120)->nullable();
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();
            $table->string('shipment_term', 30)->default('PREPAID');
            $table->string('pol', 120)->nullable();
            $table->string('pod', 120)->nullable();
            
            // B/L Cargo Grid
            $table->text('marks_numbers')->nullable();
            $table->text('cargo_description')->nullable();
            $table->decimal('gross_weight', 12, 2)->nullable();
            $table->decimal('net_weight', 12, 2)->nullable();
            $table->decimal('measurement', 12, 3)->nullable();
            
            // Bottom Remarks
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('submitted');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('number');
            $table->index('si_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_instructions');
    }
};
