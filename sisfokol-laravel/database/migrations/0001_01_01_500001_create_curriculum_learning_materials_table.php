<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_learning_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_id')->constrained('curriculum_competencies')->cascadeOnDelete();
            $table->string('code', 50)->nullable();
            $table->text('description');
            $table->string('legacy_id', 50)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_learning_materials');
    }
};
