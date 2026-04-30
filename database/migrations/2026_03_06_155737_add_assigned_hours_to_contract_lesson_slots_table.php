<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_lesson_slots', function (Blueprint $table) {
            $table->decimal('assigned_hours', 8, 2)
                ->nullable()
                ->after('duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('contract_lesson_slots', function (Blueprint $table) {
            $table->dropColumn('assigned_hours');
        });
    }
};
