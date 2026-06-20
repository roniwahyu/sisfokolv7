<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formative_assessment_score_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('score_id')->constrained('formative_assessment_scores')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('curriculum_learning_materials')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['score_id', 'material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formative_assessment_score_details');
    }
};
