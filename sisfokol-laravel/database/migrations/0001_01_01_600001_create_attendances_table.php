<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('attendable'); // bisa siswa/pegawai
            $table->date('date');
            $table->time('time');
            $table->string('type', 20); // in, out
            $table->string('source', 20)->default('qr'); // qr, manual
            $table->string('status', 20)->default('present'); // present, late, early
            $table->string('ip_address', 45)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('note')->nullable();
            $table->string('legacy_id', 50)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'date', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
