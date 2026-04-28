<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_student_id')->constrained('contract_students')->cascadeOnDelete();
            $table->date('start_date');

            $table->unsignedInteger('lessons_count')->default(0);

            // 1=lun ... 7=dom (o 0-6 se preferisci: basta essere coerenti nel generatore)
            $table->unsignedTinyInteger('weekly_day');
            $table->string('weekly_time', 5); // "HH:MM"
            $table->unsignedInteger('duration_minutes')->default(60);

            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['contract_student_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plans');
    }
};
