<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'billing_is_beneficiary')) {
                $table->boolean('billing_is_beneficiary')->default(false)->after('billing_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'billing_is_beneficiary')) {
                $table->dropColumn('billing_is_beneficiary');
            }
        });
    }
};
