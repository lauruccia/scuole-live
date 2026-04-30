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
        Schema::create('contract_lesson_slots', function (Blueprint $table) {
    $table->id();

    $table->foreignId('contract_id')->constrained()->cascadeOnDelete();

    $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
    $table->unsignedBigInteger('teacher_id')->nullable(); // niente foreign key

    $table->tinyInteger('weekly_day'); // 1=lun ... 7=dom
    $table->time('weekly_time');

    $table->unsignedInteger('duration_minutes')->default(60);

    $table->boolean('is_active')->default(true);

    $table->date('starts_at')->nullable();
    $table->date('ends_at')->nullable();

    $table->string('meet_url', 500)->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_lesson_slots');
    }
};
