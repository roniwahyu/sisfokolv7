<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran_rincian', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table, $withSoftDelete = false);
            $table->unsignedBigInteger('pembayaran_id');
            $table->foreign('pembayaran_id')->references('id')->on('pembayaran')->cascadeOnDelete();
            $table->unsignedBigInteger('tagihan_siswa_id');
            $table->foreign('tagihan_siswa_id')->references('id')->on('tagihan_siswa')->cascadeOnDelete();
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'pembayaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_rincian');
    }
};
