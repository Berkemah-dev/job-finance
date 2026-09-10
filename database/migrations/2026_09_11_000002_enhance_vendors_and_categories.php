<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $t) {
            $t->string('country', 120)->nullable()->after('address');
            $t->text('notes')->nullable()->after('country');
        });

        Schema::create('vendor_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $t->string('category', 40);
            $t->unique(['vendor_id', 'category']);
            $t->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_categories');

        Schema::table('vendors', function (Blueprint $t) {
            $t->dropColumn(['country', 'notes']);
        });
    }
};
