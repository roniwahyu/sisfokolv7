<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('npsn', 50)->nullable();
            $table->string('nss', 50)->nullable();
            $table->string('headmaster_name', 200)->nullable();
            $table->string('headmaster_nip', 100)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('google_map_url')->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->string('stamp_path', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_profiles');
    }
};
