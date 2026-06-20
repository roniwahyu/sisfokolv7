<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fields', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 100)->unique();                    // 'siswa.nis', 'tagihan.nominal_kurang'
            $table->string('model', 100);
            $table->string('kolom', 100);
            $table->string('label', 100);
            $table->enum('kategori', ['normal', 'sensitif', 'sangat_sensitif'])->default('normal');
            $table->enum('default_visibility', ['visible', 'hidden', 'readonly'])->default('visible');
            $table->timestamps();
        });

        Schema::create('field_role_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('field_id');
            $table->foreign('field_id')->references('id')->on('fields')->cascadeOnDelete();
            $table->unsignedBigInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->enum('visibility', ['visible', 'hidden', 'readonly'])->default('visible');
            $table->timestamps();
            $table->unique(['role_id', 'field_id', 'tenant_id'], 'uniq_field_role_tenant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_role_overrides');
        Schema::dropIfExists('fields');
    }
};
