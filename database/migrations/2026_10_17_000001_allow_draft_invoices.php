<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('issued_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Draft invoices may have a null issued_at, so the column remains
        // nullable when this migration is rolled back.
    }
};
