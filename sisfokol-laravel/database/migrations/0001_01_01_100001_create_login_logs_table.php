<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('user_code', 100)->nullable();
            $table->string('user_name', 200)->nullable();
            $table->string('position', 100)->nullable();
            $table->string('job_title', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('logged_in_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'logged_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
