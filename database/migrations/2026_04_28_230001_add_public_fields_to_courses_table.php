<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
            $table->boolean('is_public')->default(true)->after('is_active');
            $table->string('short_description')->nullable()->after('is_public');
            $table->string('level')->nullable()->after('short_description'); // A1, B2, madrelingua…
            $table->string('image_path')->nullable()->after('level');
        });

        // Rende pubblici e attivi tutti i corsi già esistenti
        DB::table('courses')->update(['is_active' => true, 'is_public' => true]);
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'is_public', 'short_description', 'level', 'image_path']);
        });
    }
};
