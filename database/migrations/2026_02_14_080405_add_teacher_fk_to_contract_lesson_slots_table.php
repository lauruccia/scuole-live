<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_lesson_slots', function (Blueprint $table) {
            // se vuoi essere super safe: assicurati che esista un indice
            $table->foreign('teacher_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contract_lesson_slots', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
        });
    }
};
