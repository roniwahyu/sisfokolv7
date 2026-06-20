<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('summative_assessment_score_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('score_id')->constrained('summative_assessment_scores')->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained('curriculum_competencies')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['score_id', 'competency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('summative_assessment_score_details');
    }
};
