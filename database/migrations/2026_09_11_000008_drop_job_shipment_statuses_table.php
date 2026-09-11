<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel job_shipment_statuses adalah dead code — status shipment faktanya
        // selalu ditulis ke job_status_history oleh JobService::updateShipmentStatus().
        // Tabel ini selalu kosong sehingga aman di-drop tanpa kehilangan data bisnis.
        Schema::dropIfExists('job_shipment_statuses');
    }

    public function down(): void
    {
        // Tidak di-restore karena tabel ini adalah dead code.
        // Jika rollback diperlukan, jalankan ulang migrasi 2026_09_10_000010.
    }
};
