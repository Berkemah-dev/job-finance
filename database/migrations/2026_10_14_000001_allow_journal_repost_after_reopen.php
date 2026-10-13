<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sebuah job dapat ditutup, dibuka kembali, lalu ditutup lagi. Jurnal versi
     * sebelumnya sudah dibalik (reversed) tetapi barisnya tetap ada, sehingga
     * unique index (type, source_type, source_id) membuat penutupan kedua gagal.
     * Nomor revisi memungkinkan posting ulang, sedangkan perlindungan terhadap
     * pencatatan ganda dipindahkan ke JournalService::post().
     */
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->unsignedInteger('revision')->default(0)->after('source_id');
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->dropUnique(['type', 'source_type', 'source_id']);
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->unique(['type', 'source_type', 'source_id', 'revision'], 'journals_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropUnique('journals_source_unique');
        });

        // Jurnal dengan revisi > 1 tidak dapat dikembalikan ke index lama.
        DB::table('journals')
            ->where('revision', '>', 0)
            ->update(['revision' => 0]);

        Schema::table('journals', function (Blueprint $table) {
            $table->unique(['type', 'source_type', 'source_id']);
            $table->dropColumn('revision');
        });
    }
};
