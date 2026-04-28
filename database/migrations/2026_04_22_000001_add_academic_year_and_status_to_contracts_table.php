<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('academic_year', 9)->nullable()->after('notes')
                ->comment('Anno scolastico, es. 2025/2026');

            $table->string('status', 20)->nullable()->default('active')->after('academic_year')
                ->comment('active | completed | suspended | paused');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['academic_year', 'status']);
        });
    }
};
