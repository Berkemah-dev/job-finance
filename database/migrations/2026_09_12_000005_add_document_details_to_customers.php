<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            if (! Schema::hasColumn('customers', 'authorizer_name')) {
                $t->string('authorizer_name', 255)->nullable()->after('address');
            }
            if (! Schema::hasColumn('customers', 'authorizer_title')) {
                $t->string('authorizer_title', 255)->nullable()->after('authorizer_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            if (Schema::hasColumn('customers', 'authorizer_title')) {
                $t->dropColumn('authorizer_title');
            }
            if (Schema::hasColumn('customers', 'authorizer_name')) {
                $t->dropColumn('authorizer_name');
            }
        });
    }
};
