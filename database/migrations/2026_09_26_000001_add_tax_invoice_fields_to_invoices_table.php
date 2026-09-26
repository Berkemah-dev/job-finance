<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('tax_invoice_number', 100)->nullable()->after('number');
            $table->string('tax_invoice_file')->nullable()->after('tax_invoice_number');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['tax_invoice_number', 'tax_invoice_file']);
        });
    }
};
