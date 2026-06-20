<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();      // NULL = SuperAdmin
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('username', 50);
            $table->string('nama', 100);
            $table->string('email')->nullable();
            $table->string('tipe', 20)->default('user');             // 'super_admin','admin_sekolah','guru','siswa',...
            $table->string('password');
            $table->string('foto')->nullable();
            $table->boolean('aktif')->default(true);
            $table->boolean('must_reset_password')->default(false);   // ETL: true untuk user migrasi
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'username']);                // NIS/NIP unique per tenant
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
