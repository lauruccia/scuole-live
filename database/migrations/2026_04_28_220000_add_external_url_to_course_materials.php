<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_materials', function (Blueprint $table) {
            // Link esterno (YouTube, Vimeo, ecc.) — alternativo al file
            $table->string('external_url')->nullable()->after('description');

            // file_path ora è opzionale (può esserci solo il link)
            $table->string('file_path')->nullable()->change();
            $table->string('file_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('course_materials', function (Blueprint $table) {
            $table->dropColumn('external_url');
            $table->string('file_path')->nullable(false)->change();
            $table->string('file_name')->nullable(false)->change();
        });
    }
};
