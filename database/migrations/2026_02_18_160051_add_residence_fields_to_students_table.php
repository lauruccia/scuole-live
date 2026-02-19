<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Residenza
            $table->string('residence_address', 255)->nullable()->after('birth_country');
            $table->string('residence_zip', 20)->nullable()->after('residence_address');
            $table->string('residence_city', 100)->nullable()->after('residence_zip');
            $table->string('residence_province', 10)->nullable()->after('residence_city');
            $table->string('residence_country', 100)->nullable()->after('residence_province');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'residence_address',
                'residence_zip',
                'residence_city',
                'residence_province',
                'residence_country',
            ]);
        });
    }
};
