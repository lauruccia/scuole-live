<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'billing_birth_date')) {
                $table->date('billing_birth_date')->nullable()->after('billing_last_name');
            }

            if (! Schema::hasColumn('contracts', 'billing_birth_place')) {
                $table->string('billing_birth_place', 120)->nullable()->after('billing_birth_date');
            }

            if (! Schema::hasColumn('contracts', 'billing_province')) {
                $table->string('billing_province', 10)->nullable()->after('billing_birth_place');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'billing_birth_date')) {
                $table->dropColumn('billing_birth_date');
            }
            if (Schema::hasColumn('contracts', 'billing_birth_place')) {
                $table->dropColumn('billing_birth_place');
            }
            if (Schema::hasColumn('contracts', 'billing_province')) {
                $table->dropColumn('billing_province');
            }
        });
    }
};
