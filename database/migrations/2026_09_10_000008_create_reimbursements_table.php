<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reimbursements', function (Blueprint $t) {
            $t->id();
            $t->string('number', 40)->unique();
            $t->foreignId('employee_id')->constrained('users')->restrictOnDelete();
            $t->string('category', 30)->index();
            $t->date('reimbursement_date')->index();
            $t->string('description', 255);
            $t->text('notes')->nullable();
            $t->decimal('amount', 18, 2);
            $t->string('status', 20)->default('pending')->index();
            $t->string('payment_reference', 100)->nullable();
            $t->date('paid_date')->nullable()->index();
            $t->foreignId('funding_account_id')->nullable()->constrained('chart_of_accounts')->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->unsignedInteger('lock_version')->default(0);
            $t->timestamps();
            $t->index(['status', 'reimbursement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reimbursements');
    }
};
