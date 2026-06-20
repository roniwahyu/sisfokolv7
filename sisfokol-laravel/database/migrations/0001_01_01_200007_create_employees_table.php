<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('code', 100)->unique(); // NIP/NUPTK
            $table->string('name', 200);
            $table->string('gender', 20)->nullable(); // L/P
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('position', 100)->nullable(); // guru, staff, dll
            $table->string('status', 50)->nullable(); // PNS, Honorer, dll
            $table->date('join_date')->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->string('qrcode_path', 255)->nullable();
            $table->string('legacy_id', 50)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
