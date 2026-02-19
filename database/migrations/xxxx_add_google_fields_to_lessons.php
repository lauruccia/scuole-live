<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('meet_url', 500)->nullable()->after('ends_at');
            $table->string('google_event_id', 255)->nullable()->after('meet_url');
            $table->string('google_calendar_id', 255)->nullable()->after('google_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['meet_url','google_event_id','google_calendar_id']);
        });
    }
};
