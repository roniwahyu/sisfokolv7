<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->morphs('documentable'); // relasi ke employee/student/subject/classroom
            $table->string('category', 100); // rpp, silabus, kartu, sertifikat, dll
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('file_path', 255);
            $table->string('file_type', 50)->nullable();
            $table->bigInteger('file_size')->default(0);
            $table->string('legacy_id', 50)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'documentable_type', 'documentable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
