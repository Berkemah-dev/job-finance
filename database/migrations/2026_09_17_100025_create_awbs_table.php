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
            $table->string('number', 60)->unique();       // No. AWB
            $table->date('awb_date');
            $table->foreignId('job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // Airline & Flight Info
            $table->string('airline', 160)->nullable();         // Nama maskapai
            $table->string('airline_code', 20)->nullable();     // Kode IATA maskapai
            $table->string('flight_number', 60)->nullable();    // No. penerbangan
            $table->date('etd')->nullable();                    // Tanggal berangkat
            $table->date('eta')->nullable();                    // Tanggal tiba

            // Routing
            $table->string('airport_of_departure', 120)->nullable();   // Bandara asal (JKTPO)
            $table->string('airport_of_destination', 120)->nullable(); // Bandara tujuan (SZXCT)
            $table->string('routing', 255)->nullable();                // Via / Rute transit

            // Cargo Parties
            $table->string('shipper_name', 160)->nullable();
            $table->string('consignee_name', 160)->nullable();
            $table->string('notify_party', 255)->nullable();

            // Cargo Details
            $table->string('commodity', 255)->nullable();       // Deskripsi barang
            $table->integer('pieces')->nullable();              // Jumlah koli
            $table->decimal('gross_weight', 12, 2)->nullable(); // Berat kotor (KGS)
            $table->decimal('chargeable_weight', 12, 2)->nullable(); // Berat tagihan
            $table->decimal('volume', 12, 3)->nullable();       // Kubikasi (CBM)

            // Freight
            $table->string('freight_term', 30)->default('PREPAID'); // PREPAID / COLLECT
            $table->string('shipper_ref', 100)->nullable();     // Ref shipper / PO

            // Remarks
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('draft');    // draft, issued, completed, cancelled

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
