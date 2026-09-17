<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('awbs', function (Blueprint $table) {
            $table->id();
            $table->string('number', 60)->unique();       // No. AWB internal
            $table->date('awb_date');
            $table->foreignId('job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // HAWB & MAWB
            $table->string('hawb_number', 100)->nullable();
            $table->string('mawb_number', 100)->nullable();

            // Financial & Currency
            $table->string('freight_term', 30)->default('PREPAID'); // PREPAID / COLLECT
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 14, 4)->default(1.0000);

            // Parties (Sesuai Form Sistem Lama)
            $table->string('shipper_on_hawb', 160)->nullable();
            $table->string('shipper_on_mawb', 160)->nullable();
            $table->string('consignee_on_hawb', 160)->nullable();
            $table->string('consignee_on_mawb', 160)->nullable();
            $table->string('notify_party', 255)->nullable();
            $table->string('agent_name', 160)->nullable();

            // Flight & Schedule
            $table->string('flight_number', 60)->nullable();
            $table->date('flight_date')->nullable();
            $table->string('connecting_flight', 60)->nullable();
            $table->date('connecting_flight_date')->nullable();
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();

            // Routing
            $table->string('airport_of_departure', 120)->nullable(); // AOL
            $table->string('transit_airport', 120)->nullable();      // Transit
            $table->string('airport_of_destination', 120)->nullable(); // AOD

            // Airline / Carrier Info
            $table->string('airline', 160)->nullable();              // Airlines
            $table->string('airline_code', 20)->nullable();          // IATA Code
            $table->string('account_number', 100)->nullable();
            $table->string('value_of_carriage', 60)->nullable();
            $table->string('value_of_customs', 60)->nullable();

            // Cargo Details
            $table->string('commodity', 255)->nullable();
            $table->integer('pieces')->nullable();                  // No. of Pieces
            $table->decimal('gross_weight', 12, 2)->nullable();
            $table->string('gross_weight_unit', 20)->default('KGS');
            $table->decimal('chargeable_weight', 12, 2)->nullable();
            $table->decimal('volume', 12, 3)->nullable();

            // Status & Remarks
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('draft');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('number');
            $table->index('awb_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('awbs');
    }
};
