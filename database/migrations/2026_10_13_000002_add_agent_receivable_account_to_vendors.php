<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vendors', 'agent_receivable_account_id')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->foreignId('agent_receivable_account_id')
                    ->nullable()
                    ->after('payable_account_id')
                    ->constrained('chart_of_accounts')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vendors', 'agent_receivable_account_id')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropConstrainedForeignId('agent_receivable_account_id');
            });
        }
    }
};
