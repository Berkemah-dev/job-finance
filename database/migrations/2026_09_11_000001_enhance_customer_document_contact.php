<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_documents', function (Blueprint $t) {
            $t->string('original_name', 255)->nullable()->after('filename');
            $t->string('mime', 120)->nullable()->after('original_name');
            $t->unsignedBigInteger('size')->nullable()->after('mime');
        });

        Schema::table('customer_contacts', function (Blueprint $t) {
            $t->string('country', 120)->nullable()->after('address');
            $t->text('notes')->nullable()->after('country');
            $t->boolean('is_active')->default(true)->after('notes');
            $t->index(['customer_id', 'type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_contacts', function (Blueprint $t) {
            $t->dropIndex(['customer_id', 'type', 'is_active']);
            $t->dropColumn(['country', 'notes', 'is_active']);
        });

        Schema::table('customer_documents', function (Blueprint $t) {
            $t->dropColumn(['size', 'mime', 'original_name']);
        });
    }
};
