<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_process_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_score_id')->constrained('project_scores')->cascadeOnDelete();
            $table->string('dimension', 50); // e.g. kerja sama, kreativitas, komunikasi
            $table->decimal('score', 5, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_process_scores');
    }
};
