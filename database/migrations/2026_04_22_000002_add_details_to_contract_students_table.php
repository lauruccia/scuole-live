<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contract_students', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_students', 'beneficiary_birth_date')) {
                $table->date('beneficiary_birth_date')->nullable()->after('beneficiary_phone');
            }
            if (! Schema::hasColumn('contract_students', 'beneficiary_birth_place')) {
                $table->string('beneficiary_birth_place', 190)->nullable()->after('beneficiary_birth_date');
            }
            if (! Schema::hasColumn('contract_students', 'beneficiary_address')) {
                $table->string('beneficiary_address', 190)->nullable()->after('beneficiary_birth_place');
            }
            if (! Schema::hasColumn('contract_students', 'beneficiary_city')) {
                $table->string('beneficiary_city', 100)->nullable()->after('beneficiary_address');
            }
            if (! Schema::hasColumn('contract_students', 'beneficiary_zip')) {
                $table->string('beneficiary_zip', 20)->nullable()->after('beneficiary_city');
            }
            if (! Schema::hasColumn('contract_students', 'beneficiary_country')) {
                $table->string('beneficiary_country', 100)->nullable()->after('beneficiary_zip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contract_students', function (Blueprint $table) {
            $table->dropColumn([
                'beneficiary_birth_date',
                'beneficiary_birth_place',
                'beneficiary_address',
                'beneficiary_city',
                'beneficiary_zip',
                'beneficiary_country',
            ]);
        });
    }
};
