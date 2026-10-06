<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_closing_snapshots', function (Blueprint $table) {
            $table->foreignId('funding_account_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Snapshot lama dapat menyimpan nilai null setelah perubahan ini.
        // Tidak dipaksa kembali menjadi wajib agar data historis tetap aman.
    }
};
