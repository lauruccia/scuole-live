<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('contract_student_id')->nullable(); // se vuoi legare al beneficiario
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('duration_minutes')->default(60);

            // annullo
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable(); // user id segreteria/amministrazione
            $table->string('cancellation_reason')->nullable();

            // consumo ore
            $table->boolean('counts_as_consumed')->default(true); // se true scala ore
            $table->boolean('is_recoverable')->default(false); // true solo se annullata >=24h prima

            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('contracts')->cascadeOnDelete();
            // opzionali se hai users/students/teachers:
            // $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
