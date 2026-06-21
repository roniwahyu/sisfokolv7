<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tabungan_siswa', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('siswa_id');
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
            $table->string('no_rekening', 30);
            $table->decimal('saldo', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['tenant_id', 'no_rekening']);
            $table->index(['tenant_id', 'siswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tabungan_siswa');
    }
};
