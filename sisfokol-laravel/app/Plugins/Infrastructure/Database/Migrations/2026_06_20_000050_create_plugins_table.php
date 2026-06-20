<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama', 100);
            $table->text('deskripsi')->nullable();
            $table->string('versi', 20)->default('1.0.0');
            $table->boolean('is_core')->default(false);
            $table->string('provider_class', 200)->nullable();
            $table->boolean('aktif_global')->default(true);
            $table->timestamps();
        });

        Schema::create('tenant_plugins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('plugin_id');
            $table->foreign('plugin_id')->references('id')->on('plugins')->cascadeOnDelete();
            $table->boolean('aktif')->default(false);
            $table->json('pengaturan')->nullable();
            $table->unsignedBigInteger('diaktifkan_oleh')->nullable();
            $table->foreign('diaktifkan_oleh')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('diaktifkan_pada')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'plugin_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_plugins');
        Schema::dropIfExists('plugins');
    }
};
