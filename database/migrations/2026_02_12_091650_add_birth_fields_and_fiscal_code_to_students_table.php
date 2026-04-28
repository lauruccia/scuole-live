<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('fiscal_code', 16)->nullable()->after('birth_date');

            $table->string('birth_place', 255)->nullable()->after('fiscal_code');
            $table->string('birth_province', 10)->nullable()->after('birth_place');
            $table->string('birth_country', 255)->nullable()->after('birth_province');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'fiscal_code',
                'birth_place',
                'birth_province',
                'birth_country',
            ]);
        });
    }
};
