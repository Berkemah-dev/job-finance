<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('name', 100);
            $t->string('category', 20)->default('general');
            $t->string('description', 500)->nullable();
            $t->boolean('is_required')->default(false);
            $t->boolean('is_active')->default(true);
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('job_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
            $t->foreignId('document_type_id')->constrained('document_types')->restrictOnDelete();
            $t->string('original_name', 255);
            $t->string('file_path', 500);
            $t->string('mime_type', 120)->nullable();
            $t->unsignedBigInteger('file_size')->nullable();
            $t->string('notes', 500)->nullable();
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['job_id', 'document_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_documents');
        Schema::dropIfExists('document_types');
    }
};
