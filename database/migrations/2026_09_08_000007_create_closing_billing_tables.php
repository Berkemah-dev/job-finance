<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_closing_snapshots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('job_id')->unique()->constrained()->restrictOnDelete();
            $t->date('closing_date');
            $t->json('customer_snapshot');
            $t->json('costs_snapshot');
            foreach (['total_temporary', 'total_provision_cost', 'total_provision_sell', 'subtotal', 'tax', 'total', 'profit', 'margin'] as $f) {
                $t->decimal($f, 18, 2);
            }$t->foreignId('funding_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $t->foreignId('closed_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('closed_at');
            $t->timestamps();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('number', 40)->unique();
            $t->foreignId('job_id')->unique()->constrained()->restrictOnDelete();
            $t->foreignId('job_closing_snapshot_id')->unique()->constrained()->restrictOnDelete();
            $t->foreignId('customer_id')->constrained()->restrictOnDelete();
            $t->json('customer_snapshot');
            $t->date('invoice_date')->index();
            $t->date('due_date')->index();
            $t->string('status', 30)->default('issued')->index();
            foreach (['subtotal', 'tax', 'total', 'paid_amount', 'balance'] as $f) {
                $t->decimal($f, 18, 2);
            }$t->unsignedInteger('lock_version')->default(0);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('issued_at');
            $t->timestamps();
        });
        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('position');
            $t->string('description');
            $t->string('type', 20);
            $t->decimal('quantity', 18, 2);
            $t->string('unit', 30);
            $t->decimal('unit_price', 18, 2);
            $t->decimal('amount', 18, 2);
            $t->timestamps();
            $t->unique(['invoice_id', 'position']);
        });
        Schema::create('journals', function (Blueprint $t) {
            $t->id();
            $t->string('number', 40)->unique();
            $t->date('journal_date')->index();
            $t->string('type', 30)->index();
            $t->string('source_type', 50);
            $t->unsignedBigInteger('source_id');
            $t->string('description');
            $t->string('status', 20)->default('posted')->index();
            $t->foreignId('posted_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('posted_at');
            $t->timestamps();
            $t->unique(['type', 'source_type', 'source_id']);
        });
        Schema::create('journal_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $t->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $t->string('description');
            $t->decimal('debit', 18, 2)->default(0);
            $t->decimal('credit', 18, 2)->default(0);
            $t->timestamps();
            $t->index(['chart_of_account_id', 'journal_id']);
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->string('number', 40)->unique();
            $t->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $t->date('payment_date')->index();
            $t->decimal('amount', 18, 2);
            $t->foreignId('deposit_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $t->string('method', 30);
            $t->string('reference', 100)->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->index(['invoice_id', 'payment_date']);
        });
        Schema::table('jobs', function (Blueprint $t) {
            $t->foreignId('closed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('closed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('jobs', fn (Blueprint $t) => $t->dropConstrainedForeignId('closed_by'));
        Schema::table('jobs', fn (Blueprint $t) => $t->dropColumn('closed_at'));
        Schema::dropIfExists('payments');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('job_closing_snapshots');
    }
};
