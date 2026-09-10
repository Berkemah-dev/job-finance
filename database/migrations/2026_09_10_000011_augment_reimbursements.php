<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reimbursements', function (Blueprint $t) {
            $t->foreignId('job_id')->nullable()->after('employee_id')->constrained('jobs')->nullOnDelete();
            $t->foreignId('vendor_id')->nullable()->after('job_id')->constrained('vendors')->nullOnDelete();
            $t->string('currency', 10)->default('IDR')->after('vendor_id');
            $t->decimal('exchange_rate', 18, 2)->default(1)->after('currency');
            $t->string('attachment_name', 255)->nullable()->after('amount');
            $t->string('attachment_path', 255)->nullable()->after('attachment_name');
        });
    }

    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $t) {
            $t->dropConstrainedForeignId('job_id');
            $t->dropConstrainedForeignId('vendor_id');
            $t->dropColumn(['currency', 'exchange_rate', 'attachment_name', 'attachment_path']);
        });
    }
};
