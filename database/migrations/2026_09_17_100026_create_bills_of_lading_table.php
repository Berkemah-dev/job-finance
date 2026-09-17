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
            $table->string('number', 60)->unique();       // No. B/L
            $table->date('bl_date');
            $table->foreignId('job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // B/L Type
            $table->string('bl_type', 30)->default('original'); // original, telex, seaway

            // Shipping Line
            $table->string('carrier', 160)->nullable();         // Nama pelayaran
            $table->string('carrier_bl_number', 100)->nullable(); // No. B/L dari pelayaran

            // Vessel & Schedule
            $table->string('vessel_voyage', 120)->nullable();
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();

            // Routing
            $table->string('pol', 120)->nullable();              // Port of Loading
            $table->string('pod', 120)->nullable();              // Port of Discharge
            $table->string('place_of_delivery', 120)->nullable(); // Tempat penyerahan akhir

            // Cargo Parties (tercetak di B/L)
            $table->string('shipper_name', 160)->nullable();
            $table->string('consignee_name', 160)->nullable();
            $table->text('notify_party')->nullable();

            // Cargo Details (3 kolom B/L)
            $table->text('marks_numbers')->nullable();           // Marks & Numbers
            $table->text('cargo_description')->nullable();       // Description of Goods
            $table->decimal('gross_weight', 12, 2)->nullable();  // G.W KGS
            $table->decimal('net_weight', 12, 2)->nullable();    // N.W KGS
            $table->decimal('measurement', 12, 3)->nullable();   // MEAS CBM

            // Freight
            $table->string('freight_term', 30)->default('PREPAID'); // PREPAID / COLLECT

            // Remarks
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('draft'); // draft, issued, released, completed, cancelled

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
