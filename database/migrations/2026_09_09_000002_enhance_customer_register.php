<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            $t->string('default_payment_terms', 60)->nullable()->after('tax_number');
            $t->string('npwp_file', 255)->nullable()->after('default_payment_terms');
            $t->string('nib_file', 255)->nullable()->after('npwp_file');
        });

        Schema::create('customer_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->string('type', 30);
            $t->string('filename');
            $t->string('path');
            $t->string('disk', 20)->default('local');
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->index(['customer_id', 'type']);
        });

        Schema::create('customer_contacts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->string('type', 20);
            $t->string('name');
            $t->string('company')->nullable();
            $t->string('email')->nullable();
            $t->string('phone', 40)->nullable();
            $t->text('address')->nullable();
            $t->timestamps();
            $t->index(['customer_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_contacts');
        Schema::dropIfExists('customer_documents');
        Schema::table('customers', function (Blueprint $t) {
            $t->dropColumn(['nib_file', 'npwp_file', 'default_payment_terms']);
        });
    }
};