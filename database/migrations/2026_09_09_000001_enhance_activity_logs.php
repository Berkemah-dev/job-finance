<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('user_id')->constrained('roles')->nullOnDelete();
            $table->string('module', 60)->nullable()->after('action');
            $table->unsignedBigInteger('record_id')->nullable()->after('module');
            $table->json('before')->nullable()->after('record_id');
            $table->json('after')->nullable()->after('before');
            $table->string('ip', 45)->nullable()->after('after');
            $table->index(['module', 'record_id'], 'activity_logs_module_record_idx');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_module_record_idx');
            $table->dropColumn(['ip', 'after', 'before', 'record_id', 'module', 'role_id']);
        });
    }
};