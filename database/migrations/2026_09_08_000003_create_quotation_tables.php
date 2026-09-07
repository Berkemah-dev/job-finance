<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $t) {
            $t->id();
            $t->string('type', 20);
            $t->string('period', 6);
            $t->unsignedBigInteger('counter')->default(0);
            $t->unique(['type', 'period']);
        });
        Schema::create('quotations', function (Blueprint $t) {
            $t->id();
            $t->string('number', 40)->unique();
            $t->foreignId('customer_id')->constrained()->restrictOnDelete();
            $t->json('customer_snapshot');
            $t->string('subject');
            $t->date('quotation_date')->index();
            $t->date('valid_until');
            $t->string('status', 20)->default('draft')->index();
            $t->text('notes')->nullable();
            $t->unsignedInteger('lock_version')->default(0);
            foreach (['total_temporary', 'total_provision_cost', 'total_provision_sell', 'subtotal', 'profit', 'margin'] as $field) {
                $t->decimal($field, 18, 2)->default(0);
            }
            foreach (['created_by', 'updated_by', 'submitted_by', 'approved_by', 'rejected_by', 'converted_by'] as $field) {
                $t->foreignId($field)->nullable()->constrained('users')->restrictOnDelete();
            }
            foreach (['submitted_at', 'approved_at', 'rejected_at', 'converted_at'] as $field) {
                $t->timestamp($field)->nullable();
            }
            $t->text('rejection_reason')->nullable();
            $t->timestamps();
        });
        Schema::create('quotation_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('position');
            $t->string('description');
            $t->string('type', 20);
            $t->string('unit', 30);
            foreach (['quantity', 'unit_cost', 'unit_price', 'total_cost', 'total_price'] as $field) {
                $t->decimal($field, 18, 2);
            }
            $t->timestamps();
            $t->unique(['quotation_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('document_sequences');
    }
};
