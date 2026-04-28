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
        Schema::table('enrollments', function (Blueprint $table) {
    if (!Schema::hasColumn('enrollments', 'student_id')) {
        $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
    }
    if (!Schema::hasColumn('enrollments', 'course')) {
        $table->string('course')->nullable();
    }
    if (!Schema::hasColumn('enrollments', 'subject_id')) {
        $table->string('subject_id')->nullable();
    }
    if (!Schema::hasColumn('enrollments', 'enrolled_at')) {
        $table->date('enrolled_at')->nullable();
    }
    if (!Schema::hasColumn('enrollments', 'status')) {
        $table->string('status')->default('iscritto');
    }
    if (!Schema::hasColumn('enrollments', 'notes')) {
        $table->text('notes')->nullable();
    }
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            //
        });
    }
};
