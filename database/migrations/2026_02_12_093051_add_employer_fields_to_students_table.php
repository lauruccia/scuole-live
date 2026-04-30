<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('employer_name', 190)->nullable()->after('birth_country');
            $table->string('employer_vat_number', 50)->nullable()->after('employer_name');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['employer_name', 'employer_vat_number']);
        });
    }
};
