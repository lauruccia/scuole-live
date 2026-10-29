<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_purchases') && ! Schema::hasColumn('course_purchases', 'language')) {
            Schema::table('course_purchases', function (Blueprint $table) {
                $table->string('language', 100)->nullable()->after('course_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('course_purchases') && Schema::hasColumn('course_purchases', 'language')) {
            Schema::table('course_purchases', function (Blueprint $table) {
                $table->dropColumn('language');
            });
        }
    }
};
