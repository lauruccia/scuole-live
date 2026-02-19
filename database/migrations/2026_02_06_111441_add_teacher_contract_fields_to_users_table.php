<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'teacher_contract_type')) {
                $table->string('teacher_contract_type')->nullable()->after('iban');
            }

            if (! Schema::hasColumn('users', 'teacher_hourly_rate_gross')) {
                $table->decimal('teacher_hourly_rate_gross', 10, 2)->nullable()->after('teacher_contract_type');
            }

            if (! Schema::hasColumn('users', 'teacher_billing_mode')) {
                $table->string('teacher_billing_mode')->nullable()->after('teacher_hourly_rate_gross');
            }

            if (! Schema::hasColumn('users', 'teacher_subjects')) {
                $table->json('teacher_subjects')->nullable()->after('teacher_billing_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'teacher_subjects')) {
                $table->dropColumn('teacher_subjects');
            }
            if (Schema::hasColumn('users', 'teacher_billing_mode')) {
                $table->dropColumn('teacher_billing_mode');
            }
            if (Schema::hasColumn('users', 'teacher_hourly_rate_gross')) {
                $table->dropColumn('teacher_hourly_rate_gross');
            }
            if (Schema::hasColumn('users', 'teacher_contract_type')) {
                $table->dropColumn('teacher_contract_type');
            }
        });
    }
};
