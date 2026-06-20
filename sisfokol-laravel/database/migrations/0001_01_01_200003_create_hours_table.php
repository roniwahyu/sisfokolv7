<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hours', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // Jam ke-1, Jam ke-2, etc
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hours');
    }
};
