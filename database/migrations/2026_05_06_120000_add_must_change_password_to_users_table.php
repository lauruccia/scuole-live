<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add must_change_password flag to users.
 *
 * Motivo:
 *   StudentObserver e altre creazioni automatiche di User generano una password
 *   casuale (Str::password(16)) e la inviano via email. Per non lasciare account
 *   con password "creator-known" indefinitamente, segniamo questi utenti con
 *   must_change_password=1 al primo login un middleware li redirige alla
 *   ChangePasswordPage finche' il flag non torna 0.
 *
 * Default 0 sui record esistenti: chi ha gia' una password scelta non viene
 * forzato a cambiarla.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Idempotente: skip se la colonna esiste gia' (utile per re-run su deploy).
            if (! Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')
                    ->default(false)
                    ->after('password');
            }

            if (! Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')
                    ->nullable()
                    ->after('must_change_password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'password_changed_at')) {
                $table->dropColumn('password_changed_at');
            }
            if (Schema::hasColumn('users', 'must_change_password')) {
                $table->dropColumn('must_change_password');
            }
        });
    }
};
