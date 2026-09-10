<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $t) {
            $t->string('shipper_name', 160)->nullable()->after('notes');
            $t->text('shipper_address')->nullable()->after('shipper_name');
            $t->string('consignee_name', 160)->nullable()->after('shipper_address');
            $t->text('consignee_address')->nullable()->after('consignee_name');
            $t->string('service_type', 30)->nullable()->after('consignee_address');
            $t->string('origin', 120)->nullable()->after('service_type');
            $t->string('destination', 120)->nullable()->after('origin');
            $t->string('currency', 10)->default('IDR')->after('destination');
            $t->decimal('exchange_rate', 18, 2)->default(1)->after('currency');
            $t->string('payment_terms', 30)->nullable()->after('exchange_rate');
            $t->decimal('discount', 18, 2)->default(0)->after('payment_terms');
            $t->decimal('tax_rate', 6, 2)->default(0)->after('discount');
            $t->decimal('tax_amount', 18, 2)->default(0)->after('tax_rate');
            $t->decimal('grand_total', 18, 2)->default(0)->after('tax_amount');
            $t->text('revision_reason')->nullable()->after('rejection_reason');
            $t->foreignId('revised_by')->nullable()->after('rejected_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('revised_at')->nullable()->after('revised_by');
        });

        Schema::create('quotation_status_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $t->string('from_status', 20)->nullable();
            $t->string('to_status', 20);
            $t->text('note')->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->index('quotation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_status_history');
        Schema::table('quotations', function (Blueprint $t) {
            foreach (['revised_at', 'revised_by', 'revision_reason', 'grand_total', 'tax_amount', 'tax_rate', 'discount', 'payment_terms', 'exchange_rate', 'currency', 'destination', 'origin', 'service_type', 'consignee_address', 'consignee_name', 'shipper_address', 'shipper_name'] as $column) {
                $t->dropColumn($column);
            }
        });
    }
};
