<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aggiungiamo colonne solo se NON esistono già
        Schema::table('contracts', function (Blueprint $table) {

            if (! Schema::hasColumn('contracts', 'billing_type')) {
                $table->string('billing_type', 20)->default('private')->after('id');
            }

            if (! Schema::hasColumn('contracts', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('billing_type')
                    ->constrained('companies')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('contracts', 'billing_profile_id')) {
                $table->foreignId('billing_profile_id')
                    ->nullable()
                    ->after('company_id')
                    ->constrained('billing_profiles')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('contracts', 'billing_is_student')) {
                $table->boolean('billing_is_student')
                    ->default(false)
                    ->after('billing_profile_id');
            }
        });
    }

    public function down(): void
    {
        // In down rimuoviamo solo ciò che questa migration aggiunge.
        Schema::table('contracts', function (Blueprint $table) {

            if (Schema::hasColumn('contracts', 'billing_profile_id')) {
                $table->dropConstrainedForeignId('billing_profile_id');
            }

            if (Schema::hasColumn('contracts', 'company_id')) {
                $table->dropConstrainedForeignId('company_id');
            }

            if (Schema::hasColumn('contracts', 'billing_is_student')) {
                $table->dropColumn('billing_is_student');
            }

            // NON rimuoviamo billing_type in down se esiste già da prima
            // perché potresti averlo creato con migrazioni precedenti.
        });
    }
};
