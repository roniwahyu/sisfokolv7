<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('npsn', 20)->unique();
            $table->string('domain')->nullable()->unique();
            $table->enum('jenjang', ['SD', 'SMP', 'SMA', 'SMK', 'MI', 'MTS', 'MA', 'SLTA', 'SLTP'])->default('SMP');
            $table->text('alamat')->nullable();
            $table->string('telepon', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('logo_url')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
        }
        Schema::dropIfExists('tenants');
    }
};
