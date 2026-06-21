<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_pembayaran', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->foreign('tahun_ajaran_id')->references('id')->on('tahun_ajaran')->cascadeOnDelete();
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->foreign('semester_id')->references('id')->on('semester')->nullOnDelete();
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->foreign('kelas_id')->references('id')->on('kelas')->nullOnDelete();
            $table->string('nama', 100);
            $table->enum('jenis', ['spp', 'infaq', 'kegiatan', 'lainnya'])->default('spp');
            $table->decimal('nominal', 15, 2)->default(0);
            $table->enum('periode', ['bulanan', 'semester', 'tahunan', 'sekali'])->default('bulanan');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'tahun_ajaran_id', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_pembayaran');
    }
};
