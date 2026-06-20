<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_payment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_payment_id')->constrained('student_payments')->cascadeOnDelete();
            $table->foreignId('student_bill_id')->constrained('student_bills')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('legacy_id', 50)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_payment_details');
    }
};
