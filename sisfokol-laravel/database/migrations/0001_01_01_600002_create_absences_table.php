<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('absentable'); // siswa/pegawai
            $table->date('date');
            $table->string('type', 20); // sakit, ijin, alpha
            $table->text('reason')->nullable();
            $table->text('attachment_path')->nullable(); // surat dokter/surat izin
            $table->string('legacy_id', 50)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};
