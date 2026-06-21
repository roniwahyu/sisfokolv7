<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagihan_siswa', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('siswa_id');
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
            $table->unsignedBigInteger('item_pembayaran_id');
            $table->foreign('item_pembayaran_id')->references('id')->on('item_pembayaran')->cascadeOnDelete();
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->foreign('tahun_ajaran_id')->references('id')->on('tahun_ajaran')->cascadeOnDelete();
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->foreign('semester_id')->references('id')->on('semester')->nullOnDelete();
            $table->tinyInteger('bulan')->nullable();   // 1-12 untuk SPP
            $table->decimal('nominal_tagihan', 15, 2)->default(0);
            $table->decimal('nominal_bayar', 15, 2)->default(0);
            $table->decimal('nominal_kurang', 15, 2)->default(0);
            $table->boolean('lunas')->default(false);
            $table->date('tanggal_lunas')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'siswa_id', 'item_pembayaran_id', 'tahun_ajaran_id', 'bulan'], 'uniq_tagihan_siswa_bulan');
            $table->index(['tenant_id', 'siswa_id', 'lunas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan_siswa');
    }
};
