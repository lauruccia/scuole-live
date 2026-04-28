<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'lessons_count')) {
                $table->unsignedInteger('lessons_count')->default(0)->after('name');
            }
            if (! Schema::hasColumn('courses', 'description')) {
                $table->text('description')->nullable()->after('lessons_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('courses', 'lessons_count')) {
                $table->dropColumn('lessons_count');
            }
        });
    }
};
