<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik', 32)->nullable()->after('name');
            $table->string('phone', 40)->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->date('birth_date')->nullable()->after('address');
            $table->string('gender', 20)->nullable()->after('birth_date');
            $table->string('position', 120)->nullable()->after('gender');
            $table->string('department', 120)->nullable()->after('position');
            $table->string('emergency_contact', 160)->nullable()->after('department');
            $table->string('emergency_phone', 40)->nullable()->after('emergency_contact');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nik', 'phone', 'address', 'birth_date', 'gender', 'position', 'department', 'emergency_contact', 'emergency_phone']);
        });
    }
};
