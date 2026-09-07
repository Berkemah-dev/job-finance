<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name');
            $t->string('contact_name')->nullable();
            $t->string('email')->nullable();
            $t->string('phone', 40)->nullable();
            $t->text('address')->nullable();
            $t->string('tax_number', 40)->nullable();
            $t->unsignedInteger('lock_version')->default(0);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->index('name');
        });
        Schema::create('chart_of_accounts', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('name');
            $t->string('type', 20)->index();
            $t->unsignedInteger('lock_version')->default(0);
            $t->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('account_mappings', function (Blueprint $t) {
            $t->id();
            $t->string('key', 40)->unique();
            $t->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_mappings');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('customers');
    }
};
