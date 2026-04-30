<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge la colonna 'event' alla tabella activity_log.
 * Richiesta da spatie/laravel-activitylog ^4.x ma assente nella migration iniziale.
 */
return new class extends Migration {
    public function up(): void
    {
        $table = config('activitylog.table_name', 'activity_log');

        Schema::table($table, function (Blueprint $table) {
            if (! Schema::hasColumn(config('activitylog.table_name', 'activity_log'), 'event')) {
                $table->string('event')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        $table = config('activitylog.table_name', 'activity_log');

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn('event');
        });
    }
};
