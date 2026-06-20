<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('nama');
            $table->enum('jenjang', ['SD', 'SMP', 'SMA', 'SMK', 'MI', 'MTS', 'MA'])->default('SMP');
            $table->text('alamat')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'aktif']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
            });
        }
        Schema::dropIfExists('branches');
    }
};
