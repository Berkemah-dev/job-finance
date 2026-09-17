<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dnps', function (Blueprint $table) {
            $table->id();
            $table->string('number', 60)->unique();
            $table->date('dnp_date');
            $table->foreignId('job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('consignee_name', 160)->nullable();
            $table->string('shipper_name', 160)->nullable();
            $table->string('importer_name', 160)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->decimal('invoice_value', 15, 2)->nullable();
            $table->decimal('freight', 15, 2)->nullable();
            $table->decimal('insurance', 15, 2)->nullable();
            $table->decimal('total_value', 15, 2)->nullable();
            $table->boolean('is_repeated_transaction')->default(false);
            $table->json('supporting_documents')->nullable();
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('number');
            $table->index('dnp_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dnps');
    }
};
