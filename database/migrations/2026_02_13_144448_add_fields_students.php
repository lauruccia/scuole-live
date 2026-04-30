<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'birth_place')) {
                $table->string('birth_place', 120)->nullable()->after('birth_date');
            }

            if (! Schema::hasColumn('students', 'birth_province')) {
                $table->string('birth_province', 10)->nullable()->after('birth_place');
            }

            if (! Schema::hasColumn('students', 'birth_country')) {
                $table->string('birth_country', 100)->nullable()->after('birth_province');
            }

            if (! Schema::hasColumn('students', 'tax_code')) {
                $table->string('tax_code', 50)->nullable()->after('last_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            foreach (['birth_place', 'birth_province', 'birth_country', 'tax_code'] as $col) {
                if (Schema::hasColumn('students', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
