<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('kode', 100)->unique();
            $table->string('label', 100);
            $table->string('icon', 50)->nullable();
            $table->string('route', 150)->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('menus')->nullOnDelete();
            $table->string('group', 50)->nullable();
            $table->string('permission_required', 100)->nullable();
            $table->string('plugin_kode', 50)->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'aktif', 'urutan']);
        });

        Schema::create('menu_role_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_id');
            $table->foreign('menu_id')->references('id')->on('menus')->cascadeOnDelete();
            $table->unsignedBigInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->enum('visible', ['show', 'hide', 'readonly'])->default('show');
            $table->timestamps();
            $table->unique(['role_id', 'menu_id', 'tenant_id'], 'uniq_menu_role_tenant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_role_overrides');
        Schema::dropIfExists('menus');
    }
};
