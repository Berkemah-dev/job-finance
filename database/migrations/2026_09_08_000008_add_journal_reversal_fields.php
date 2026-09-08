<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::table('journals', function (Blueprint $table) {
            $table->foreignId('reversal_of_id')->nullable()->unique()->after('source_id')->constrained('journals')->restrictOnDelete();
            $table->foreignId('reversed_by')->nullable()->after('posted_at')->constrained('users')->restrictOnDelete();
            $table->timestamp('reversed_at')->nullable()->after('reversed_by');
            $table->unsignedInteger('lock_version')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversal_of_id');
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn(['reversed_at', 'lock_version']);
        });
        Schema::dropIfExists('journal_adjustments');
    }
};
