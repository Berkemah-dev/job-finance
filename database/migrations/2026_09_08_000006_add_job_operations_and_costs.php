<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $t) {
            $t->unsignedInteger('lock_version')->default(0);
            $t->string('service_type', 30)->nullable();
            $t->string('origin')->nullable();
            $t->string('destination')->nullable();
            $t->string('shipment_reference', 100)->nullable();
            $t->text('cargo_description')->nullable();
            $t->text('operational_notes')->nullable();
            $t->date('expected_completion_date')->nullable();
            $t->foreignId('opened_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('opened_at')->nullable();
            $t->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('cancelled_at')->nullable();
            $t->text('cancellation_reason')->nullable();
        });
        Schema::create('job_costs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('job_id')->constrained()->restrictOnDelete();
            $t->string('number', 40)->unique();
            $t->string('description');
            $t->string('type', 20);
            $t->string('status', 20)->default('draft');
            $t->date('cost_date');
            $t->string('unit', 30);
            foreach (['quantity', 'unit_cost', 'unit_price', 'total_cost', 'total_price'] as $field) {
                $t->decimal($field, 18, 2);
            }
            $t->string('payee')->nullable();
            $t->string('reference', 100)->nullable();
            $t->text('notes')->nullable();
            $t->unsignedInteger('lock_version')->default(0);
            foreach (['created_by', 'updated_by', 'finalized_by', 'deleted_by'] as $field) {
                $t->foreignId($field)->nullable()->constrained('users')->restrictOnDelete();
            }
            $t->timestamp('finalized_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['job_id', 'status', 'deleted_at']);
            $t->index(['type', 'cost_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_costs');
        Schema::table('jobs', function (Blueprint $t) {
            $t->dropConstrainedForeignId('opened_by');
            $t->dropConstrainedForeignId('cancelled_by');
            $t->dropColumn(['lock_version', 'service_type', 'origin', 'destination', 'shipment_reference', 'cargo_description', 'operational_notes', 'expected_completion_date', 'opened_at', 'cancelled_at', 'cancellation_reason']);
        });
    }
};
