<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('jobs', 'queue_jobs');
    }

    public function down(): void
    {
        Schema::rename('queue_jobs', 'jobs');
    }
};
