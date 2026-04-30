<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per la tabella activity_log di spatie/laravel-activitylog.
 * Equivale a quella generata da: php artisan activitylog:clean
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->create(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('log_name')->nullable()->index();
                $table->text('description');
                $table->nullableMorphs('subject', 'subject');
                $table->nullableMorphs('causer', 'causer');
                $table->json('properties')->nullable();
                $table->uuid('batch_uuid')->nullable();
                $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->dropIfExists(config('activitylog.table_name', 'activity_log'));
    }
};
