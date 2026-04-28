<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_students', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_students', 'assigned_hours')) {
                $table->unsignedInteger('assigned_hours')
                    ->nullable()
                    ->after('student_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contract_students', function (Blueprint $table) {
            if (Schema::hasColumn('contract_students', 'assigned_hours')) {
                $table->dropColumn('assigned_hours');
            }
        });
    }
};
