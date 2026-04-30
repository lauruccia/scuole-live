<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('teacher_contract_type')->nullable()->after('phone');
            $table->decimal('teacher_hourly_rate_gross', 10, 2)->nullable()->after('teacher_contract_type');

            // valori come screenshot
            $table->string('teacher_billing_mode')->nullable()->after('teacher_hourly_rate_gross');

            // materie (lista), semplice e veloce
            $table->json('teacher_subjects')->nullable()->after('teacher_billing_mode');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'teacher_contract_type',
                'teacher_hourly_rate_gross',
                'teacher_billing_mode',
                'teacher_subjects',
            ]);
        });
    }
};
