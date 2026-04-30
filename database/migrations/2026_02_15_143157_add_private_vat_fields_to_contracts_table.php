<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'billing_vat_number')) {
                $table->string('billing_vat_number', 50)->nullable()->after('billing_tax_code');
            }
            if (! Schema::hasColumn('contracts', 'billing_sdi')) {
                $table->string('billing_sdi', 20)->nullable()->after('billing_vat_number');
            }
            if (! Schema::hasColumn('contracts', 'billing_pec')) {
                $table->string('billing_pec', 190)->nullable()->after('billing_sdi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'billing_pec')) $table->dropColumn('billing_pec');
            if (Schema::hasColumn('contracts', 'billing_sdi')) $table->dropColumn('billing_sdi');
            if (Schema::hasColumn('contracts', 'billing_vat_number')) $table->dropColumn('billing_vat_number');
        });
    }
};
