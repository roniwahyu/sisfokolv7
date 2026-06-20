<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('permitable'); // siswa/pegawai
            $table->date('date');
            $table->string('type', 20); // in, out (izin masuk/pulang)
            $table->time('time')->nullable();
            $table->text('reason');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('attachment_path')->nullable();
            $table->string('legacy_id', 50)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permits');
    }
};
