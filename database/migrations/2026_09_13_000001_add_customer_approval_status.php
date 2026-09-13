<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'approval_status')) {
                $table->string('approval_status', 30)->default('approved')->after('default_payment_terms');
            }
            if (! Schema::hasColumn('customers', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('customers', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        $roleId = DB::table('roles')->where('name', 'customer-service')->value('id');
        if ($roleId) {
            foreach (['quotations.manage', 'jobs.manage', 'jobs.view'] as $permissionName) {
                $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');
                if ($permissionId) {
                    DB::table('permission_role')->updateOrInsert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
            foreach (['approval_status', 'approved_at'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
