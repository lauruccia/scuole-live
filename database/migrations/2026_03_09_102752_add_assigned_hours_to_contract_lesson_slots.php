<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_lesson_slots', function (Blueprint $table) {

            if (!Schema::hasColumn('contract_lesson_slots', 'assigned_hours')) {

                $table->decimal('assigned_hours', 8, 2)
                    ->nullable()
                    ->after('duration_minutes')
                    ->comment('Ore del contratto assegnate a questo studente per questo slot');

            }

        });
    }

    public function down(): void
    {
        Schema::table('contract_lesson_slots', function (Blueprint $table) {

            if (Schema::hasColumn('contract_lesson_slots', 'assigned_hours')) {
                $table->dropColumn('assigned_hours');
            }

        });
    }
};
