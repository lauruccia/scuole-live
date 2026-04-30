<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (! Schema::hasColumn('lessons', 'recovery_of_lesson_id')) {
                $table->unsignedBigInteger('recovery_of_lesson_id')->nullable()->after('cancellation_reason');
                $table->index('recovery_of_lesson_id');
            }

            if (! Schema::hasColumn('lessons', 'is_auto_recovery')) {
                $table->boolean('is_auto_recovery')->default(false)->after('recovery_of_lesson_id');
                $table->index('is_auto_recovery');
            }

            // FK (opzionale) – se vuoi, la aggiungiamo in una migration separata
            // perché se in DB hai dati sporchi o engine diverso può fallire.
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (Schema::hasColumn('lessons', 'is_auto_recovery')) {
                $table->dropIndex(['is_auto_recovery']);
                $table->dropColumn('is_auto_recovery');
            }

            if (Schema::hasColumn('lessons', 'recovery_of_lesson_id')) {
                $table->dropIndex(['recovery_of_lesson_id']);
                $table->dropColumn('recovery_of_lesson_id');
            }
        });
    }
};
