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
    Schema::create('enrollments', function (Blueprint $table) {
        $table->id();

        $table->foreignId('student_id')->constrained()->cascadeOnDelete();

        $table->unsignedBigInteger('subject_id')->nullable(); // lingua/materia
        $table->string('course_name')->nullable();            // temporaneo

        $table->date('enrollment_date')->nullable();
        $table->string('status')->default('iscritto'); // iscritto|in_corso|completato|annullato

        $table->text('notes')->nullable();

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
