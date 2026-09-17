<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills_of_lading', function (Blueprint $table) {
            $table->id();
            $table->string('number', 60)->unique();       // No. B/L internal
            $table->date('bl_date');
            $table->foreignId('job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // HBL & MBL
            $table->string('hbl_number', 100)->nullable();
            $table->string('mbl_number', 100)->nullable();

            // B/L Type & Parameters
            $table->string('bl_type', 30)->default('original'); // original, telex, seaway
            $table->integer('original_bl_count')->default(3);    // Default 3
            $table->string('place_of_issue', 60)->default('JAKARTA'); // JAKARTA / SURABAYA / SEMARANG
            $table->date('date_of_issue')->nullable();
            $table->date('shipped_on_board_date')->nullable();    // Shipping on Board
            $table->string('freight_term', 30)->default('PREPAID'); // PREPAID / COLLECT
            $table->string('freight_payable_at', 60)->default('JAKARTA'); // JAKARTA / SURABAYA / SEMARANG
            $table->string('customer_ref_number', 100)->nullable();

            // Shipping Line / Carrier
            $table->string('carrier', 160)->nullable();
            $table->string('carrier_bl_number', 100)->nullable();

            // Cargo Parties (Sesuai Anotasi Form Klien)
            $table->string('shipper_name', 160)->nullable();
            $table->string('consignee_name', 160)->nullable();
            $table->text('notify_party')->nullable();
            $table->string('agent_name', 160)->nullable();
            $table->string('shipper_switch', 160)->nullable();
            $table->string('consignee_switch', 160)->nullable();

            // Vessel & Routing
            $table->string('pre_carriage', 160)->nullable();
            $table->string('vessel_voyage', 120)->nullable();
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();
            $table->string('pol', 120)->nullable();               // Port of Loading
            $table->string('place_of_receipt', 120)->nullable();  // Receipt
            $table->string('pod', 120)->nullable();               // Port of Discharge
            $table->string('place_of_delivery', 120)->nullable(); // Delivery
            $table->string('final_destination', 120)->nullable(); // Destination
            $table->string('party', 100)->nullable();

            // Cargo Details & Quantities
            $table->integer('package_count')->nullable();         // Qty
            $table->string('package_unit', 50)->default('Package');
            $table->text('marks_numbers')->nullable();
            $table->text('cargo_description')->nullable();
            $table->decimal('gross_weight', 12, 2)->nullable();   // G.W KGS
            $table->decimal('net_weight', 12, 2)->nullable();     // N.W KGS
            $table->decimal('measurement', 12, 3)->nullable();    // MEAS M3

            // Remarks & Status
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('draft');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('number');
            $table->index('bl_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills_of_lading');
    }
};
