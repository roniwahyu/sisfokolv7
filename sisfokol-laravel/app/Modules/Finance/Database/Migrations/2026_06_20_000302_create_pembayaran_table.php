<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            tenant_and_audit_columns($table);
            $table->unsignedBigInteger('siswa_id');
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
            $table->string('no_nota', 50);
            $table->date('tanggal');
            $table->decimal('total', 15, 2)->default(0);
            $table->unsignedBigInteger('diterima_oleh');
            $table->foreign('diterima_oleh')->references('id')->on('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'no_nota']);
            $table->index(['tenant_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
