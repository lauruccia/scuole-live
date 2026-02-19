<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contract_students', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('student_id')->nullable(); // se esiste tabella students

            // dati beneficiario (anche senza student in DB)
            $table->string('beneficiary_first_name')->nullable();
            $table->string('beneficiary_last_name')->nullable();
            $table->string('beneficiary_email')->nullable();
            $table->string('beneficiary_phone')->nullable();

            // preferenze corso
            $table->unsignedTinyInteger('weekly_day')->nullable(); // 1..7
            $table->string('weekly_time')->nullable(); // HH:MM
            $table->unsignedBigInteger('teacher_id')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('contracts')->cascadeOnDelete();

            // opzionale: se hai students / teachers
            // $table->foreign('student_id')->references('id')->on('students')->nullOnDelete();
            // $table->foreign('teacher_id')->references('id')->on('teachers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_students');
    }
};
