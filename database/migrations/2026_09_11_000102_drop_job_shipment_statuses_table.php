<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel job_shipment_statuses adalah dead code: riwayat status shipment
        // ditulis ke job_status_history (JobService::updateShipmentStatus) dan
        // tabel ini tidak pernah dibaca/ditulis oleh controller/service/view mana pun.
        // Tabel tersebut sudah dipastikan kosong serta tanpa FK/consumer eksternal.
        Schema::dropIfExists('job_shipment_statuses');
    }

    public function down(): void
    {
        // Tidak dibuat ulang: create dibuat oleh 2026_09_10_000010 (migrasi sumber).
    }
};